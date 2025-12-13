<?php

namespace App\Http\Controllers\Payment;

use Carbon\Carbon;
use Config\Iyzipay;
use App\Models\Package;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use App\Http\Helpers\MegaMailer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\User\UserCheckoutController;
use Auth;

class IyzicoController extends Controller
{
    public function paymentProcess(Request $request, $_amount, $_success_url, $_cancel_url, $_title, $bex)
    {
        $paymentMethod = PaymentGateway::where('keyword', 'iyzico')->first();
        $paydata = json_decode($paymentMethod->information, true);

        if (Session::get('paymentFor') == 'extend') {
            $fname = Auth::guard('web')->user()->first_name;
            $lname = Auth::guard('web')->user()->last_name;
            $email = Auth::guard('web')->user()->email;
            $phone = Auth::guard('web')->user()->phone;
            $city = Auth::guard('web')->user()->city;
            $country = Auth::guard('web')->user()->country;
            $address = Auth::guard('web')->user()->address;
            $zip_code = Auth::guard('web')->user()->zip_code;
        } else {
            $fname = $request->first_name;
            $lname = $request->last_name;
            $email = $request->email;
            $phone = $request->phone;
            $city = $request->city;
            $country = $request->country;
            $address = $request->address;
            $zip_code = $request->post_code;
        }
        $id_number = $phone;
        $basket_id = 'B' . uniqid(999, 99999);

        $cancel_url = $_cancel_url;
        $notify_url = $_success_url;

        Session::put('request', $request->all());
        $conversion_id = uniqid(9999, 999999);
        Session::put('conversation_id', $conversion_id);

        $options = Iyzipay::options();
        $options->setApiKey($paydata['api_key']);
        $options->setSecretKey($paydata['secret_key']);
        if ($paydata['iyzico_mode'] == 1) {
            $options->setBaseUrl('https://sandbox-api.iyzipay.com');
        } else {
            $options->setBaseUrl('https://api.iyzipay.com'); // production mode
        }

        # create request class
        $request = new \Iyzipay\Request\CreatePayWithIyzicoInitializeRequest();
        $request->setLocale(\Iyzipay\Model\Locale::EN);
        $request->setConversationId($conversion_id);
        $request->setPrice($_amount);
        $request->setPaidPrice($_amount);
        $request->setCurrency(\Iyzipay\Model\Currency::TL);
        $request->setBasketId($basket_id);
        $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
        $request->setCallbackUrl($notify_url);
        $request->setEnabledInstallments([2, 3, 6, 9]);

        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId(uniqid());
        $buyer->setName($fname);
        $buyer->setSurname($lname);
        $buyer->setGsmNumber($phone);
        $buyer->setEmail($email);
        $buyer->setIdentityNumber($id_number);
        $buyer->setLastLoginDate('');
        $buyer->setRegistrationDate('');
        $buyer->setRegistrationAddress($address);
        $buyer->setIp('');
        $buyer->setCity($city);
        $buyer->setCountry($country);
        $buyer->setZipCode($zip_code);
        $request->setBuyer($buyer);

        $shippingAddress = new \Iyzipay\Model\Address();
        $shippingAddress->setContactName($fname);
        $shippingAddress->setCity($city);
        $shippingAddress->setCountry($country);
        $shippingAddress->setAddress($address);
        $shippingAddress->setZipCode($zip_code);
        $request->setShippingAddress($shippingAddress);

        $billingAddress = new \Iyzipay\Model\Address();
        $billingAddress->setContactName($fname);
        $billingAddress->setCity($city);
        $billingAddress->setCountry($country);
        $billingAddress->setAddress($address);
        $billingAddress->setZipCode($zip_code);
        $request->setBillingAddress($billingAddress);

        $q_id = uniqid(999, 99999);
        $basketItems = [];
        $firstBasketItem = new \Iyzipay\Model\BasketItem();
        $firstBasketItem->setId($q_id);
        $firstBasketItem->setName('Purchase Id ' . $q_id);
        $firstBasketItem->setCategory1('Purchase or Extend');
        $firstBasketItem->setCategory2('');
        $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
        $firstBasketItem->setPrice($_amount);
        $basketItems[0] = $firstBasketItem;
        $request->setBasketItems($basketItems);

        # make request
        $payWithIyzicoInitialize = \Iyzipay\Model\PayWithIyzicoInitialize::create($request, $options);

        $paymentResponse = (array) $payWithIyzicoInitialize;

        foreach ($paymentResponse as $key => $data) {
            $paymentInfo = json_decode($data, true);

            if ($paymentInfo['status'] == 'success') {
                if (!empty($paymentInfo['payWithIyzicoPageUrl'])) {
                    Session::put('cancel_url', $cancel_url);
                    return redirect($paymentInfo['payWithIyzicoPageUrl']);
                } else {
                    return redirect($cancel_url)->with('error', __('Payment Canceled') . '.');
                }
            } else {
                return redirect($cancel_url)->with('error', __('Payment Canceled') . '.');
            }
        }
    }

    public function successPayment(Request $request)
    {
        $requestData = Session::get('request');

        $currentLang = session()->has('lang') ? Language::where('code', session()->get('lang'))->first() : Language::where('is_default', 1)->first();
        $be = $currentLang->basic_extended;
        $bs = $currentLang->basic_setting;
        $cancel_url = Session::get('cancel_url');

        $package = Package::find($requestData['package_id']);
        $paymentFor = Session::get('paymentFor');
        $transaction_id = UserPermissionHelper::uniqidReal(8);

        $requestData['conversation_id'] = Session::get('conversation_id');
        $requestData['status'] = 0;

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
