<?php

namespace App\Http\Controllers\User\Payment;

use Illuminate\Http\Request;
use App\Models\User\UserPackage;
use App\Models\User\BasicSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use Cartalyst\Stripe\Laravel\Facades\Stripe;
use App\Http\Controllers\Front\UserCheckoutController;

class StripeController extends Controller
{
    public function __construct()
    {
        //Set Spripe Keys
        $stripe = UserPaymentGateway::whereKeyword('stripe')->where('user_id', getUser()->id)->first();
        $stripeConf = json_decode($stripe->information, true);
        Config::set('services.stripe.key', $stripeConf["key"]);
        Config::set('services.stripe.secret', $stripeConf["secret"]);
    }

    public function paymentProcess($request, $_amount, $_title, $_success_url, $_cancel_url)
    {
        $title = $_title;
        $price = $_amount;
        $price = round($price, 2);
        $cancel_url = $_cancel_url;


        $stripe = Stripe::make(Config::get('services.stripe.secret'));

        $token = $request['stripeToken'];


        if (!isset($token)) {
            return back()->with('error', __('Token Problem With Your Token') . '.');
        }

            $charge = $stripe->charges()->create([
                'card' => $token,
                'currency' =>  "USD",
                'amount' => $price,
                'description' => $title,
            ]);
        
        $requestData = Session::get('user_request');
        if ($charge['status'] == 'succeeded') {
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = json_encode($charge);
            $user = getUser();
            $be = BasicSetting::where('user_id', $user->id)->firstorFail();
            $amount = $_amount;

            $checkout = new UsercheckoutController();
            $requestData['templateType'] = 'appointment_booking_notification';
            $appointment = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $be);
            $checkout->mailToTanentUser($requestData, $appointment, $amount, "Stripe", $be, $transaction_id);

            session()->flash('success', toastrMsg('successful_payment'));
            Session::forget('user_paymentFor');
            $onlinesuccess  = route('customer.success.page', [getParam(), $appointment->id]);
            return redirect($onlinesuccess);
        }
        return redirect($cancel_url)->with('error', 'Please Enter Valid Credit Card Informations.');
    }
    public function cancelPayment()
    {
        session()->flash('warning', toastrMsg('cancel_payment'));
        return redirect()->route('front.user.appointment', getParam());
    }
}
