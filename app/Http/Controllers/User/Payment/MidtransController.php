<?php

namespace App\Http\Controllers\User\Payment;

use Midtrans\Snap;
use Illuminate\Http\Request;
use App\Models\User\BasicSetting;
use Midtrans\Config as MidtransConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\UserCheckoutController;

class MidtransController extends Controller
{
    public function paymentProcess($request, $_amount, $_title, $bex)
    {
        $data = UserPaymentGateway::whereKeyword('midtrans')->where('user_id', getUser()->id)->first();
        $data = json_decode($data->information, true);

        MidtransConfig::$serverKey = $data['server_key'];
        if ($data['midtrans_mode'] == 1) {
            MidtransConfig::$isProduction = false;
        } elseif ($data['midtrans_mode'] == 0) {
            MidtransConfig::$isProduction = true;
        }
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;

        $is_production = $data['midtrans_mode'];
        $client_key = $data['server_key'];

        $params = [
            'transaction_details' => [
                'order_id' => uniqid(),
                'gross_amount' => intval($_amount) * 1000, // will be multiplied by 1000
            ],
        ];

        $snapToken = Snap::getSnapToken($params);
        $title = 'Appointment Booking';
        Session::put('user_amount', $_amount);
        Session::put('user_request', $request);
        return view('user-front.midtrans', compact('snapToken', 'is_production', 'client_key'));
    }

    public function creditCardNotify(Request $request)
    {
        $order_id = $request->id;
        $requestData = Session::get('user_request');
        $user  = getUser();
        $bs = BasicSetting::where('user_id', $user->id)->firstorFail();
        $requestData['user_id'] = Auth::guard('customer')->user()->id ?? getUser()->id;

        if ($order_id) {
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = json_encode($order_id);
            $amount = Session::get('user_amount');
            $checkout = new UserCheckoutController();
            $requestData['templateType'] = 'appointment_booking_notification';
            $appointment = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $bs);
            $checkout->mailToTanentUser($requestData, $appointment, $amount, "Paypal", $bs, $transaction_id);
            session()->flash('success', toastrMsg('successful_payment'));

            Session::forget('user_amount');
            Session::forget('user_request');
            $onlinesuccess  = route('customer.success.page', [getParam(), $appointment->id]);
            return redirect($onlinesuccess);
        } else {
            return redirect()->route('customer.appointment.midtrans.cancel', getParam());
        }
    }


    public function cancelPayment()
    {
        session()->flash('warning', toastrMsg('cancel_payment'));
        return redirect()->route('front.user.appointment', getParam());
    }
}
