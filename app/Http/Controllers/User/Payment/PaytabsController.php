<?php

namespace App\Http\Controllers\User\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\UserCheckoutController;

class PaytabsController extends Controller
{
    public function paymentProcess($request, $_amount, $_title, $bs)
    {
        $paytabInfo = userPaytabInfo(getUser()->id);
        /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~ Init Payment Gateway ~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
        $description = 'Equipemnt Booking via paytabs';
        try {
            $response = Http::withHeaders([
                'Authorization' => $paytabInfo['server_key'], // Server Key
                'Content-Type' => 'application/json',
            ])->post($paytabInfo['url'], [
                'profile_id' => $paytabInfo['profile_id'], // Profile ID
                'tran_type' => 'sale',
                'tran_class' => 'ecom',
                'cart_id' => uniqid(),
                'cart_description' => $description,
                'cart_currency' => $paytabInfo['currency'], // set currency by region
                'cart_amount' => round($_amount, 2),
                'return' => route('customer.appointment.paytabs.notify', getParam()),
            ]);

            $responseData = $response->json();
            // put some data in session before redirect to paytm url
            Session::put('user_request', $request);
            Session::put('bs', $bs);
            Session::put('user_amount', $_amount);
            return redirect()->to($responseData['redirect_url']);
        } catch (\Exception $e) {
            Session::flash('error', 'Something went wrong!');
            return redirect()->route('customer.appointment.paytabs.cancel', getParam());
        }
    }


    public function successPayment(Request $request)
    {
        $requestData = Session::get('user_request');
        $user_amount = Session::get('user_amount');
        $bs = Session::get('bs');
        $resp = $request->all();

        if ($resp['respStatus'] == "A" && $resp['respMessage'] == 'Authorised') {
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
        } else {
            return redirect()->route('customer.appointment.paytabs.cancel', getParam());
        }
    }

    public function cancelPayment()
    {
        session()->flash('warning', toastrMsg('cancel_payment'));
        return redirect()->route('front.user.appointment', getParam());
    }
}
