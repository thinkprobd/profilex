<?php

namespace App\Http\Controllers\Payment;

use Carbon\Carbon;
use App\Models\Package;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Http\Helpers\MegaMailer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\User\UserCheckoutController;

class PaytabsController extends Controller
{
    public function paymentProcess(Request $request, $_amount, $_success_url, $_cancel_url)
    {
        $cancel_url = $_cancel_url;
        $notify_url = $_success_url;

        $paytabInfo = paytabInfo();
        $description = 'Package Purchase via paytabs';
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
                'cart_amount' => $_amount,
                'return' => $notify_url,
            ]);

            $responseData = $response->json();
            // put some data in session before redirect
            Session::put('request', $request->all());
            Session::put('cancel_url', $cancel_url);
            return redirect()->to($responseData['redirect_url']);
        } catch (\Exception $e) {
            return redirect($cancel_url);
        }
    }

    public function successPayment(Request $request)
    {
 
        $currentLang = session()->has('lang') ? Language::where('code', session()->get('lang'))->first() : Language::where('is_default', 1)->first();
        $be = $currentLang->basic_extended;
        $bs = $currentLang->basic_setting;
        $requestData = Session::get('request');
        $cancel_url = Session::get('cancel_url');
        /** Get the payment ID before session clear **/
        $package = Package::find($requestData['package_id']);
        $paymentFor = Session::get('paymentFor');
        $resp = $request->all();
        if ($resp['respStatus'] == 'A' && $resp['respMessage'] == 'Authorised') {
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = json_encode($request['payment_request_id']);

            if ($paymentFor == 'membership') {
                $amount = $requestData['price'];
                $password = $requestData['password'];
                $checkout = new CheckoutController();
                $user = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $be, $password);

                $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();
                $activation = Carbon::parse($lastMemb->start_date);
                $expire = Carbon::parse($lastMemb->expire_date);
                $file_name = $this->makeInvoice($requestData, 'membership', $user, $password, $amount, $requestData['payment_method'], $requestData['phone'], $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $transaction_id, $package->title);

                $mailer = new MegaMailer();
                $data = [
                    'toMail' => $user->email,
                    'toName' => $user->fname,
                    'username' => $user->username,
                    'package_title' => $package->title,
                    'package_price' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $package->price . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                    'activation_date' => $activation->toFormattedDateString(),
                    'expire_date' => Carbon::parse($expire->toFormattedDateString())->format('Y') == '9999' ? 'Lifetime' : $expire->toFormattedDateString(),
                    'membership_invoice' => $file_name,
                    'website_title' => $bs->website_title,
                    'templateType' => 'registration_with_premium_package',
                    'type' => 'registrationWithPremiumPackage',
                ];
                $mailer->mailFromAdmin($data);
                session()->flash('success', __('successful payment'));
                Session::forget('request');
                Session::forget('paymentFor');
                return redirect()->route('success.page');
            } elseif ($paymentFor == 'extend') {
                $amount = $requestData['price'];
                $password = uniqid('qrcode');
                $checkout = new UserCheckoutController();
                $user = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $be, $password);
                $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();
                $activation = Carbon::parse($lastMemb->start_date);
                $expire = Carbon::parse($lastMemb->expire_date);
                $file_name = $this->makeInvoice($requestData, 'extend', $user, $password, $amount, $requestData['payment_method'], $user->phone_number, $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $transaction_id, $package->title);
                $mailer = new MegaMailer();
                $data = [
                    'toMail' => $user->email,
                    'toName' => $user->fname,
                    'username' => $user->username,
                    'package_title' => $package->title,
                    'package_price' => ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $package->price . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : ''),
                    'activation_date' => $activation->toFormattedDateString(),
                    'expire_date' => Carbon::parse($expire->toFormattedDateString())->format('Y') == '9999' ? 'Lifetime' : $expire->toFormattedDateString(),
                    'membership_invoice' => $file_name,
                    'website_title' => $bs->website_title,
                    'templateType' => 'membership_extend',
                    'type' => 'membershipExtend',
                ];
                $mailer->mailFromAdmin($data);
                session()->flash('success', __('successful payment'));
                Session::forget('request');
                Session::forget('paymentFor');
                return redirect()->route('success.page');
            }
        } else {
            return redirect($cancel_url);
        }
    }

    public function cancelPayment()
    {
        $requestData = Session::get('request');
        $paymentFor = Session::get('paymentFor');
        session()->flash('warning', __('cancel_payment'));
        if ($paymentFor == 'membership') {
            return redirect()
                ->route('front.register.view', ['status' => $requestData['package_type'], 'id' => $requestData['package_id']])
                ->withInput($requestData);
        } else {
            return redirect()
                ->route('user.plan.extend.checkout', ['package_id' => $requestData['package_id']])
                ->withInput($requestData);
        }
    }
}
