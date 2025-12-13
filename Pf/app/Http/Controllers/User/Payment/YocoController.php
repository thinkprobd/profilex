<?php

namespace App\Http\Controllers\User\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\UserCheckoutController;

class YocoController extends Controller
{
    public function paymentProcess($request, $_amount, $_title, $bs)
    {
        $user_id = getUser()->id;
        $info = UserPaymentGateway::where('keyword', 'yoco')->where('user_id', $user_id)->first();
        $information = json_decode($info->information, true);

        $cancel_url = route('customer.appointment.yoco.cancel', getParam());
        $notify_url = route('customer.appointment.yoco.notify', getParam());

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $information['secret_key'],
        ])->post('https://payments.yoco.com/api/checkouts', [
            'amount' => $_amount * 100,
            'currency' => 'ZAR',
            'successUrl' => $notify_url,
        ]);

        $responseData = $response->json();
        if (array_key_exists('redirectUrl', $responseData)) {
            // put some data in session before redirect
            Session::put('user_request', $request);
            Session::put('bs', $bs);
            Session::put('user_amount', $_amount);
            Session::put('user_id', $user_id);
            Session::put('cancel_url', $cancel_url);
            Session::put('yoco_id', $responseData['id']);
            Session::put('s_key', $information['secret_key']);
            return redirect($responseData['redirectUrl']);
        } else {
            return redirect($cancel_url);
        }
    }

    public function successPayment(Request $request)
    {
        $requestData = Session::get('user_request');
        $user_amount = Session::get('user_amount');
        $user_id = Session::get('user_id');
        $bs = Session::get('bs');

        $id = Session::get('yoco_id');
        $s_key = Session::get('s_key');
        $info = UserPaymentGateway::where('keyword', 'yoco')->where('user_id', $user_id)->first();
        $information = json_decode($info->information, true);

        if ($id && $information['secret_key'] == $s_key) {
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
        return redirect()->route('customer.appointment.yoco.cancel', getParam());
    }

    public function cancelPayment()
    {
        session()->flash('warning', toastrMsg('cancel_payment'));
        return redirect()->route('front.user.appointment', getParam());
    }
}
