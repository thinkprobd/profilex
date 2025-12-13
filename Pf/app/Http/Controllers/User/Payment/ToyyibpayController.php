<?php

namespace App\Http\Controllers\User\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Controllers\Front\UserCheckoutController;

class ToyyibpayController extends Controller
{
    public function paymentProcess($request, $_amount, $_title, $bs)
    {
        // Payment Gateway Info
        $user_id = getUser()->id;
        $info = UserPaymentGateway::where('user_id', $user_id)->where('keyword', 'toyyibpay')->first();
        $information = json_decode($info->information, true);

        $ref = uniqid();
        session()->put('toyyibpay_ref_id', $ref);
        $bill_title = 'Appointment Booking';
        $bill_description = 'Appointment Booking via toyyibpay';

        $some_data = [
            'userSecretKey' => $information['secret_key'],
            'categoryCode' => $information['category_code'],
            'billName' => $bill_title,
            'billDescription' => $bill_description,
            'billPriceSetting' => 1,
            'billPayorInfo' => 1,
            'billAmount' => $_amount * 100,
            'billReturnUrl' => route('customer.appointment.toyyibpay.notify', getParam()),
            'billExternalReferenceNo' => $ref,
            'billTo' => $request['name'],
            'billEmail' => $request['email'],
            'billPhone' => $request['phone_number'],
        ];

        $host = $information['sandbox_status'] == 1 ? 'https://dev.toyyibpay.com/' : 'https://toyyibpay.com/';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, $host . 'index.php/api/createBill'); // sandbox will be dev.
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $some_data);

        $result = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        $response = json_decode($result, true);
        if (!empty($response[0])) {
            // Put some data in session before redirecting to Toyyibpay
            Session::put('user_request', $request);
            Session::put('bs', $bs);
            Session::put('user_amount', $_amount);
            return redirect($host . $response[0]["BillCode"]);
        } else {

            // Check if the error message indicates an invalid phone format
            if (strpos($result, 'billPhone format is invalid') !== false) {
                return back()->with(['error' => 'Please enter a valid phone number.']);
            } else {
                session()->forget('equipmentItem');
                // Redirect with a generic error message
                return redirect()->route('customer.appointment.toyyibpay.cancel', getParam());
            }
        }
    }


    public function successPayment(Request $request)
    {
        $requestData = Session::get('user_request');
        $user_amount = Session::get('user_amount');
        $bs = Session::get('bs');
        $ref = session()->get('toyyibpay_ref_id');

        if ($request['status_id'] == 1 && $request['order_id'] == $ref) {
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
            return redirect()->route('customer.appointment.toyyibpay.cancel', getParam());
        }
    }

    public function cancelPayment()
    {
        session()->flash('warning', toastrMsg('cancel_payment'));
        return redirect()->route('front.user.appointment', getParam());
    }
}
