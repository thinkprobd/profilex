<?php

namespace App\Http\Controllers\User\Payment;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\UserCheckoutController;

class XenditController extends Controller
{
    protected $secret_key;
    public function __construct()
    {
        $user_id = getUser()->id;
        $info = UserPaymentGateway::where('user_id', $user_id)->where('keyword', 'xendit')->first();
        $information = json_decode($info->information, true);

        $this->secret_key = base64_encode($information['secret_key'] . ':');
    }


    public function paymentProcess($request, $_amount, $_title, $bs)
    {
        $notify_url = route('customer.appointment.xendit.notify', getParam());
        $cancel_url = route('customer.appointment.xendit.cancel', getParam());

        Session::put('user_request', $request);
        Session::put('bs', $bs);
        Session::put('user_amount', $_amount);
        Session::put('cancel_url', $cancel_url);

        $external_id = Str::random(10);
        $data_request = Http::withHeaders([
            'Authorization' => 'Basic ' . $this->secret_key,
        ])->post('https://api.xendit.co/v2/invoices', [
            'external_id' => $external_id,
            'amount' => (int) round($_amount),
            'currency' => $bs->base_currency_text,
            'success_redirect_url' => $notify_url,
        ]);

        $response = $data_request->object();

        // if currency not supported by xendit
        if (isset($response->error_code) && $response->error_code == 'UNSUPPORTED_CURRENCY') {
            return redirect($cancel_url)->with('error', __('Invalid Currency') . '.');
        }

        $response = json_decode(json_encode($response), true);
        if (!empty($response['success_redirect_url'])) {
            Session::put('xendit_id', $response['id']);
            return redirect($response['invoice_url']);
        } else {
            return redirect($cancel_url)->with('error', __('Payment Canceled') . '.');
        }
    }

    public function successPayment(Request $request)
    {
        $requestData = Session::get('user_request');
        $user_amount = Session::get('user_amount');
        $bs = Session::get('bs');
        $cancel_url = Session::get('cancel_url');
        $xendit_id = Session::get('xendit_id');

        //check payment status through xendit api
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $this->secret_key,
        ])->get("https://api.xendit.co/v2/invoices/{$xendit_id}");

        if ($response->failed()) {
            return redirect($cancel_url)->with('error', __('Failed to verify payment.'));
        }

        $payment = $response->object();
        if (isset($payment->status) && in_array($payment->status, ['PAID', 'SETTLED'])) {  //check if payment is actually paid or settled
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
