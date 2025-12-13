<?php

namespace App\Http\Controllers\User\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\UserCheckoutController;

class PhonePeController extends Controller
{

    private $sandboxCheck;
    public function paymentProcess($request, $_amount, $_title, $bs)
    {
        $user_id = getUser()->id;
        $info = UserPaymentGateway::where('keyword', 'phonepe')->where('user_id', $user_id)->first();
        $paydata = json_decode($info->information, true);

        $notify_url = route('customer.appointment.phonepe.notify', getParam());
        $cancel_url = route('customer.appointment.phonepe.cancel', getParam());

        $this->sandboxCheck = $paydata['sandbox_status'];

        $clientId = $paydata['merchant_id'];
        $clientSecret = $paydata['salt_key'];

        //* Here i completed 1 step which is generating access token in each request
        $accessToken = $this->getPhonePeAccessToken($clientId, $clientSecret);


        if (!$accessToken) {
            return back()->withError(__('Failed to get PhonePe access token') . '.');
        }

        Session::put('user_request', $request);
        Session::put('bs', $bs);
        Session::put('user_amount', $_amount);
        Session::put('cancel_url', $cancel_url);
        Session::put('user_id', $user_id);

        return $this->initiatePayment($accessToken, $notify_url, $cancel_url, $_amount);
    }

    private function getPhonePeAccessToken($clientId, $clientSecret)
    {
        return Cache::remember('phonepe_access_token', 3500, function () use ($clientId, $clientSecret) {
            $tokenUrl = $this->sandboxCheck ? 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token' : 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token';

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
            'amount' => intval($_amount * 100), //you have to multiply the amount by 100 to convert it to paise
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
        $bs = Session::get('bs');
        $requestData = Session::get('user_request');
        $cancel_url = Session::get('cancel_url');

        $merchantOrderId = $request->input('merchantOrderId') ??
            Session::get('merchantOrderId') ??
            uniqid();

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
            $amount = Session::get('user_amount');
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

    private function verifyOrderStatus($merchantOrderId)
    {
        $user_id = Session::get('user_id');
        $info = UserPaymentGateway::where('keyword', 'phonepe')->where('user_id', $user_id)->first();
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
        session()->flash('warning', toastrMsg('cancel_payment'));
        return redirect()->route('front.user.appointment', getParam());
    }
}
