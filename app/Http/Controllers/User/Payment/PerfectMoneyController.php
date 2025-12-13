<?php

namespace App\Http\Controllers\User\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\UserCheckoutController;

class PerfectMoneyController extends Controller
{
    public function paymentProcess($request, $_amount, $_title, $bs)
    {
        $user_id = getUser()->id;
        $info = UserPaymentGateway::where('keyword', 'perfect_money')->where('user_id', $user_id)->first();
        $information = json_decode($info->information, true);

        $notify_url = route('customer.appointment.perfect_money.notify', getParam());
        $cancel_url = route('customer.appointment.perfect_money.cancel', getParam());

        $randomNo = substr(uniqid(), 0, 8);
        $email = $request->email;

        $info = $information;
        $val['PAYEE_ACCOUNT'] = $info['perfect_money_wallet_id'];
        $val['PAYEE_NAME'] = $bs->website_title;
        $val['PAYMENT_ID'] = "$randomNo"; //random id
        $val['PAYMENT_AMOUNT'] = $_amount;
        $val['PAYMENT_UNITS'] = "$bs->base_currency_text";

        $val['STATUS_URL'] = $notify_url;
        $val['PAYMENT_URL'] = $notify_url;
        $val['PAYMENT_URL_METHOD'] = 'GET';
        $val['NOPAYMENT_URL'] = $cancel_url;
        $val['NOPAYMENT_URL_METHOD'] = 'GET';
        $val['SUGGESTED_MEMO'] = $email;
        $val['BAGGAGE_FIELDS'] = 'IDENT';

        $data['val'] = $val;
        $data['method'] = 'get';
        $data['url'] = 'https://perfectmoney.com/api/step1.asp';

        Session::put('payment_id', $randomNo);
        Session::put('user_request', $request);
        Session::put('bs', $bs);
        Session::put('user_amount', $_amount);
        Session::put('user_id', $user_id);

        return view('front.payment.perfect-money', compact('data'));
    }

    public function successPayment(Request $request)
    {
        $requestData = Session::get('user_request');
        $user_amount = Session::get('user_amount');
        $bs = Session::get('bs');
        $user_id = Session::get('user_id');
        $cancel_url = Session::get('cancel_url');

        /** Get the payment ID before session clear **/
        $perfect_money = UserPaymentGateway::where('keyword', 'perfect_money')->where('user_id', $user_id)->first();
        $perfectMoneyInfo = json_decode($perfect_money->information, true);

        $amo = $request['PAYMENT_AMOUNT'];
        $unit = $request['PAYMENT_UNITS'];
        $track = $request['PAYMENT_ID'];
        $id = Session::get('payment_id');
        $final_amount = $requestData['price']; //live amount

        if ($request->PAYEE_ACCOUNT == $perfectMoneyInfo['perfect_money_wallet_id'] && $unit == $bs->base_currency_text && $track == $id && $amo == round($final_amount, 2)) {
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = json_encode($request['payment_request_id']);
            $amount = $user_amount;
            $checkout = new UserCheckoutController();
            $requestData['templateType'] = 'appointment_booking_notification';
            $appointment = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $bs);
            $checkout->mailToTanentUser($requestData, $appointment, $amount, "Paypal", $bs, $transaction_id);
            session()->flash('success', toastrMsg('successful_payment'));

            Session::forget('user_amount');
            Session::forget('user_request');
            Session::forget('bs');
            $onlinesuccess  = route('customer.success.page', [getParam(), $appointment->id]);
            return redirect($onlinesuccess);
        }
        return redirect($cancel_url);
    }

    public function cancelPayment()
    {
        session()->flash('warning', toastrMsg('cancel_payment'));
        return redirect()->route('front.user.appointment', getParam());
    }
}
