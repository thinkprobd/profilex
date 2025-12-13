<?php

namespace App\Http\Controllers\User\Payment;


use Illuminate\Http\Request;
use Basel\MyFatoorah\MyFatoorah;
use App\Http\Controllers\Controller;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\UserCheckoutController;

class MyFatoorahController extends Controller
{
    private $myfatoorah;
    private $user_id;

    public function __construct()
    {
        $user_id = getUser()->id;
        $this->user_id = $user_id;
        $info = UserPaymentGateway::where('keyword', 'myfatoorah')->where('user_id', $user_id)->first();
        $information = json_decode($info->information, true);
        $this->myfatoorah = MyFatoorah::getInstance($information['sandbox_status'] == 1 ? true : false);

        config([
            'myfatoorah.CallBackUrl' => route('customer.appointment.myfatoorah.notify', getParam()),
            'myfatoorah.ErrorUrl' => route('customer.appointment.myfatoorah.cancel', getParam()),
        ]);
    }

    public function paymentProcess($request, $_amount, $_title, $bs)
    {

        $cancel_url = route('customer.appointment.myfatoorah.cancel', getParam());
        /********************************************************
         * send payment request to yoco for create a payment url
         ********************************************************/
        $info = UserPaymentGateway::where('keyword', 'myfatoorah')->where('user_id', $this->user_id)->first();
        $information = json_decode($info->information, true);
        $paymentFor = Session::get('paymentFor');

        $random_1 = rand(999, 9999);
        $random_2 = rand(9999, 99999);

        $name = $request['name'];
        $phone = $request['phone_number'];

        $result = $this->myfatoorah->sendPayment($name, intval($_amount), [
            'CustomerMobile' => $information['sandbox_status'] == 1 ? '56562123544' : $phone,
            'CustomerReference' => "$random_1", //orderID
            'UserDefinedField' => "$random_2", //clientID
            'InvoiceItems' => [
                [
                    'ItemName' => 'Appointment Booking',
                    'Quantity' => 1,
                    'UnitPrice' => intval($_amount),
                ],
            ],
        ]);

        if ($result && $result['IsSuccess'] == true) {
            Session::put('myfatoorah_payment_type', $paymentFor);
            Session::put('user_request', $request);
            Session::put('bs', $bs);
            Session::put('user_amount', $_amount);
            return redirect($result['Data']['InvoiceURL']);
        } else {
            return redirect($cancel_url);
        }
    }

    public function successPayment(Request $request)
    {
        $requestData = Session::get('user_request');
        $user_amount = Session::get('user_amount');
        $bs = Session::get('bs');

        /** Get the payment ID before session clear **/
        if (!empty($request->paymentId)) {
            $result = $this->myfatoorah->getPaymentStatus('paymentId', $request->paymentId);
            if ($result && $result['IsSuccess'] == true && $result['Data']['InvoiceStatus'] == 'Paid') {
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
        }
    }

    public function cancelPayment()
    {
        session()->flash('warning', toastrMsg('cancel_payment'));
        return redirect()->route('front.user.appointment', getParam());
    }
}
