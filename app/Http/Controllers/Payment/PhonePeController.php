<?php

namespace App\Http\Controllers\Payment;

use Carbon\Carbon;
use App\Models\Package;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use App\Http\Helpers\MegaMailer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\User\UserCheckoutController;

class PhonePeController extends Controller
{

    private $sandboxCheck;
    public function paymentProcess(Request $request, $_amount, $_success_url, $_cancel_url)
    {
        $info = PaymentGateway::where('keyword', 'phonepe')->first();
        $paydata = json_decode($info->information, true);

        $cancel_url = $_cancel_url;
        $notify_url = $_success_url;

        $this->sandboxCheck = $paydata['sandbox_status'];

        $clientId = $paydata['merchant_id'];
        $clientSecret = $paydata['salt_key'];

        //* Here i completed 1 step which is generating access token in each request

        $accessToken = $this->getPhonePeAccessToken($clientId, $clientSecret);

        if (!$accessToken) {
            return back()->withError(__('Failed to get PhonePe access token') . '.');
        }
        Session::put('request', $request->all());
        Session::put('cancel_url', $cancel_url);

        return $this->initiatePayment($accessToken, $notify_url, $cancel_url, $_amount);
    }

    private function getPhonePeAccessToken($clientId, $clientSecret)
    {
        return Cache::remember('phonepe_access_token', 3500, function () use ($clientId, $clientSecret) {
            $tokenUrl = $this->sandboxCheck
                ? 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token'
                : 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token';


            $response = Http::asForm()->post($tokenUrl, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'client_version' => 1,
                'grant_type' => 'client_credentials',
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }
            return null;
        });
    }

    public function initiatePayment($accessToken, $successUrl, $cancelUrl, $_amount)
    {
        $baseUrl = $this->sandboxCheck
            ? 'https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay'
            : 'https://api.phonepe.com/apis/pg/checkout/v2/pay';

        // Generate a unique merchantOrderId and store it in the session
        $merchantOrderId = uniqid();
        Session::put('merchantOrderId', $merchantOrderId);
        Session::put('cancel_url', $cancelUrl);

        //here we preapare the parameter of the request
        $payload = [
            'merchantOrderId' => $merchantOrderId,
            'amount' => intval($_amount * 100),
            'paymentFlow' => [
                'type' => 'PG_CHECKOUT',
                'merchantUrls' => [
                    'redirectUrl' => $successUrl,
                    'cancelUrl' => $cancelUrl,
                ],
            ],
        ];

        try {
            //after preparing the parameter we send a request to create a payment link
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'O-Bearer ' . $accessToken,
            ])->post($baseUrl, $payload);
            $responseData = $response->json();

            //after successfully created the payment link of we redirect the user to api responsed redirectUrl
            if ($response->successful() && isset($responseData['redirectUrl'])) {
                return redirect()->away($responseData['redirectUrl']);
            } else {
                // Handle API errors
                Session::forget(['merchantOrderId', 'cancel_url']);
                return back()->with('error', 'Failed to initiate payment: ' . ($responseData['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Session::forget(['merchantOrderId', 'cancel_url']);
            return response()->json([
                'success' => false,
                'code' => 'NETWORK_ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function successPayment(Request $request)
    {
        $currentLang = session()->has('lang') ? Language::where('code', session()->get('lang'))->first() : Language::where('is_default', 1)->first();
        $be = $currentLang->basic_extended;
        $bs = $currentLang->basic_setting;
        $requestData = Session::get('request');
        $cancel_url = Session::get('cancel_url');
        $package = Package::find($requestData['package_id']);

        $merchantOrderId = $request->input('merchantOrderId') ?? (Session::get('merchantOrderId') ?? uniqid());
        $paymentFor = Session::get('paymentFor');

        $verificationResponse = $this->verifyOrderStatus($merchantOrderId);

        // Prepare transaction details with all relevant data
        $transactionDetails = [
            'payment_gateway' => 'PhonePe',
            'merchant_order_id' => $merchantOrderId,
            'gateway_response' => $verificationResponse,
            'request_data' => $requestData,
        ];
        if ($verificationResponse['success']) {
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = json_encode($transactionDetails);
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
        return redirect($cancel_url);
    }

    private function verifyOrderStatus($merchantOrderId)
    {
        $info = PaymentGateway::where('keyword', 'phonepe')->first();
        $paymentInfo = json_decode($info->information, true);

        $this->sandboxCheck = $paymentInfo['sandbox_status'];

        try {
            $accessToken = $this->getPhonePeAccessToken($paymentInfo['merchant_id'], $paymentInfo['salt_key']);

            if (!$accessToken) {
                throw new \Exception('Failed to get access token');
            }

            $baseUrl = $this->sandboxCheck
                ? "https://api-preprod.phonepe.com/apis/pg-sandbox/payments/v2/order/{$merchantOrderId}/status"
                : "https://api.phonepe.com/apis/pg/payments/v2/order/{$merchantOrderId}/status";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'O-Bearer ' . $accessToken,
            ])->get($baseUrl);

            $responseData = $response->json();
            if ($response->successful() && isset($responseData['state'])) {
                return [
                    'state' => $responseData['state'] ?? null,
                    'amount' => $responseData['amount'] ?? null,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->json() ?? 'Unknown error'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
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
