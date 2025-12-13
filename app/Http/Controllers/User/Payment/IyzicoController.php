<?php

namespace App\Http\Controllers\User\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\UserCheckoutController;

class IyzicoController extends Controller
{
    public function paymentProcess($requestData, $_amount, $_title, $bs)
    {
        /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Payment Gateway Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
        $user_id = getUser()->id;
        $name = $requestData['iname'];
        $phone = $requestData['iphone'];
        $email = $requestData['iemail'];
        $address = $requestData['iaddress'];
        $city = $requestData['icity'];
        $country = $requestData['icountry'];
        $price = $_amount;
        //payment gateway code start
        $notifyURL = route('customer.appointment.lyzico.notify', getParam());
        $options = self::options($user_id);
        $conversion_id = uniqid(9999, 999999);
        $basket_id = 'B' . uniqid(999, 99999);
        $id_number = $requestData['identity_number'];
        $zip_code = $requestData['zip_code'];

        # create request class
        $request = new \Iyzipay\Request\CreatePayWithIyzicoInitializeRequest();
        $request->setLocale(\Iyzipay\Model\Locale::EN);
        $request->setConversationId($conversion_id);
        $request->setPrice($price);
        $request->setPaidPrice($price);
        $request->setCurrency(\Iyzipay\Model\Currency::TL);
        $request->setBasketId($basket_id);
        $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
        $request->setCallbackUrl($notifyURL);
        $request->setEnabledInstallments(array(2, 3, 6, 9));

        $buyer = new \Iyzipay\Model\Buyer();
        $buyer->setId(uniqid());
        $buyer->setName("$name");
        $buyer->setSurname("$name");
        $buyer->setGsmNumber("$phone");
        $buyer->setEmail("$email");
        $buyer->setIdentityNumber($id_number);
        $buyer->setLastLoginDate("");
        $buyer->setRegistrationDate("");
        $buyer->setRegistrationAddress("$address");
        $buyer->setIp("");
        $buyer->setCity("$city");
        $buyer->setCountry("$country");
        $buyer->setZipCode($zip_code);
        $request->setBuyer($buyer);

        $shippingAddress = new \Iyzipay\Model\Address();
        $shippingAddress->setContactName("$name");
        $shippingAddress->setCity("$city");
        $shippingAddress->setCountry("$country");
        $shippingAddress->setAddress("$address");
        $shippingAddress->setZipCode("$zip_code");
        $request->setShippingAddress($shippingAddress);

        $billingAddress = new \Iyzipay\Model\Address();
        $billingAddress->setContactName("$name");
        $billingAddress->setCity("$city");
        $billingAddress->setCountry("$country");
        $billingAddress->setAddress("$address");
        $billingAddress->setZipCode("$zip_code");
        $request->setBillingAddress($billingAddress);

        $q_id = uniqid(999, 99999);
        $basketItems = array();
        $firstBasketItem = new \Iyzipay\Model\BasketItem();
        $firstBasketItem->setId($q_id);
        $firstBasketItem->setName("Purchase Id " . $q_id);
        $firstBasketItem->setCategory1("Purchase or Booking");
        $firstBasketItem->setCategory2("");
        $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
        $firstBasketItem->setPrice($price);
        $basketItems[0] = $firstBasketItem;
        $request->setBasketItems($basketItems);

        # make request
        $payWithIyzicoInitialize = \Iyzipay\Model\PayWithIyzicoInitialize::create($request, $options);

        $paymentResponse = (array)$payWithIyzicoInitialize;
        foreach ($paymentResponse as $key => $data) {
            $paymentInfo = json_decode($data, true);

            if ($paymentInfo['status'] == 'success') {
                if (!empty($paymentInfo['payWithIyzicoPageUrl'])) {
                    Cache::forget('conversation_id');
                    Session::put('iyzico_token', $paymentInfo['token']);
                    Session::put('conversation_id', $conversion_id);
                    Cache::put('conversation_id', $conversion_id, 60000);
                    Session::put('user_request_data', $requestData);
                    Session::put('bs', $bs);
                    Session::put('user_amount', $_amount);
                    //return for payment
                    return redirect($paymentInfo['payWithIyzicoPageUrl']);
                }
            }
            return redirect()->route('customer.appointment.lyzico.cancel', getParam());
        }
    }


    public function successPayment(Request $request)
    {
        $requestData = Session::get('user_request_data');
        $user_amount = Session::get('user_amount');
        $bs = Session::get('bs');


        $transaction_id = UserPermissionHelper::uniqidReal(8);
        $transaction_details = json_encode($request->payment_request_id);

        $amount = $user_amount;
        $checkout = new UserCheckoutController();
        $requestData['templateType'] = 'appointment_booking_notification';
        $requestData['conversation_id'] = Session::get('conversation_id');
        $appointment = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $bs);
        $checkout->mailToTanentUser($requestData, $appointment, $amount, "Paypal", $bs, $transaction_id);
        session()->flash('success', toastrMsg('successful_payment'));

        Session::forget('user_amount');
        Session::forget('user_request');
        Session::forget('bs');
        Session::forget('conversation_id');
        $onlinesuccess  = route('customer.success.page', [getParam(), $appointment->id]);
        return redirect($onlinesuccess);

        return redirect($cancel_url);
    }

    public function cancelPayment()
    {
        session()->flash('warning', toastrMsg('cancel_payment'));
        return redirect()->route('front.user.appointment', getParam());
    }


    public static function options($user_id)
    {
        $data = UserPaymentGateway::where('keyword', 'iyzico')->where('user_id', $user_id)->first();
        $information = json_decode($data->information, true);
        $options = new \Iyzipay\Options();
        $options->setApiKey($information['api_key']);
        $options->setSecretKey($information['secrect_key']);

        if ($information['iyzico_mode'] == 1) {
            $options->setBaseUrl("https://sandbox-api.iyzipay.com");
        } else {
            $options->setBaseUrl("https://api.iyzipay.com"); // production mode
        }
        return $options;
    }
}
