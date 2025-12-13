<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Models\User\Category;
use App\Models\User\Language;
use App\Http\Helpers\MegaMailer;
use App\Models\User\BasicSetting;
use App\Models\User\UserTimeSlot;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User\AppointmentBooking;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\User\UserOfflinePaymentGateway;
use App\Http\Controllers\User\Payment\YocoController;
use App\Http\Controllers\User\Payment\PaytmController;
use App\Http\Controllers\User\Payment\IyzicoController;
use App\Http\Controllers\User\Payment\MollieController;
use App\Http\Controllers\User\Payment\PaypalController;
use App\Http\Controllers\User\Payment\StripeController;
use App\Http\Controllers\User\Payment\XenditController;
use App\Http\Controllers\User\Payment\PaytabsController;
use App\Http\Controllers\User\Payment\PhonePeController;
use App\Http\Controllers\User\Payment\MidtransController;
use App\Http\Controllers\User\Payment\PaystackController;
use App\Http\Controllers\User\Payment\RazorpayController;
use App\Http\Controllers\User\Payment\InstamojoController;
use App\Http\Controllers\User\Payment\ToyyibpayController;
use App\Http\Controllers\User\Payment\MyFatoorahController;
use App\Http\Controllers\User\Payment\FlutterWaveController;
use App\Http\Controllers\User\Payment\MercadopagoController;
use App\Http\Controllers\User\Payment\AuthorizenetController;
use App\Http\Controllers\User\Payment\PerfectMoneyController;

class UsercheckoutController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:customer')->except('userCheckout', 'checkoutFinal', 'checkout', 'customerSuccess');
    }

    public function userCheckout(Request $request, $domain)
    {
        $user = getUser();
        $user_request = Session::get('user_request');
        $user_request['slot'] = $request->slot;
        $user_request['date'] = $request->date;
        if ($user_request['slot'] == null) {
            return redirect()->back()->with('error', __('Please select a time slot'));
        }
        $timeSlots = UserTimeSlot::where('user_id', $user->id)->where('id', $request->slotId)->first();
        $max_booking_limit  = $timeSlots->max_booking;
        $slt = ($timeSlots->start . ' - ' . $timeSlots->end);
        $countAppointment = AppointmentBooking::where('user_id', $user->id)->where('date', $request->date)->where('status', '!=', 4)->where('time', $slt)->get();
        $countAppointment = count($countAppointment);

        $slotBookedText = $keywords['this_time_slot_is_booked'] ?? __('This time slot is booked');
        $tryAnotherText = $keywords['please_try_another_slot'] ?? __('Please try another slot');

        if (!empty($max_booking_limit)) {
            if ($max_booking_limit == $countAppointment) {
                return redirect()->back()->with('error', $slotBookedText . '! ' . $tryAnotherText . '.');
            }
        }
        $user_request = Session::put('user_request', $user_request);
        return redirect()->route('customer.payment', getParam());
    }

    /**
     * appointment checkout page
     */
    public function checkoutFinal(Request $request, $domain)
    {
        $user_request = Session::get('user_request');
        $user = getUser();
        $ubs = BasicSetting::where('user_id', $user->id)->firstOrFail();
        if (empty($user_request['category_id'])) {
            $data['total_fee'] = $ubs->appointment_price;
        } else {
            $data['total_fee'] = Category::find($user_request['category_id'])->appointment_price;
        }
        if ($ubs->full_payment == 1) {
            $data['price']  = $data['total_fee'];
        } else {
            $data['price']  = ($data['total_fee'] * $ubs->advance_percentage) / 100;
        }
        $id = $user->id;
        $data['user'] = $user;
        if (session()->has('user_lang')) {
            $userCurrentLang = Language::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = Language::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = Language::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['userBs'] = $ubs;
        if ($ubs->theme == 1 || $ubs->theme == 2) {
            $data['folder'] = "profile1";
        } elseif ($ubs->theme == 3) {
            $data['folder'] = "profile1.theme3";
        } elseif ($ubs->theme == 4) {
            $data['folder'] = "profile1.theme4";
        } elseif ($ubs->theme == 5) {
            $data['folder'] = "profile1.theme5";
        } elseif ($ubs->theme == 6 || $ubs->theme == 7 || $ubs->theme == 8) {
            $data['folder'] = 'profile1.theme' . $ubs->theme;
        } elseif ($ubs->theme == 9) {
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            $data['folder'] = 'profile1.theme9';
        } elseif ($ubs->theme == 10) {
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            $data['folder'] = 'profile1.theme10';
        } elseif ($ubs->theme == 11) {
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            $data['folder'] = 'profile1.theme11';
        } elseif ($ubs->theme == 12) {
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            $data['folder'] = 'profile1.theme12';
        } else {
            $data['folder'] = "profile";
        }
        $data['payment_methods'] = UserPaymentGateway::query()->where('status', 1)->where('user_id', $user->id)->get();

        $data['offline']  = UserOfflinePaymentGateway::where([
            ['status', 1],
            ['user_id', $user->id],
        ])->get();

        $data['authuser'] = Auth::guard('customer')->user();
        $data['userCurrentLang'] = $userCurrentLang;
        return view('user-front.common.checkout', $data);
    }

    public function checkout($domain, Request $request)
    {

        $allowedExts = array('jpg', 'png', 'jpeg');
        $img = $request->file('receipt');
        $rules =  [
            'price' => 'required',
            'payment_method' => $request->price != 0 ? 'required' : '',
            'cardNumber' => 'sometimes|required',
            'month' => 'sometimes|required',
            'year' => 'sometimes|required',
            'cardCVC' => 'sometimes|required',
            'receipt' => $request->is_receipt == 1 ? [
                'required', // Ensure receipt is required
                function ($attribute, $value, $fail) use ($request, $allowedExts) {
                    if ($request->hasFile('receipt')) {
                        $img = $request->file('receipt');
                        $ext = $img->getClientOriginalExtension();
                        if (!in_array($ext, $allowedExts)) {
                            Session::flash('receipt_error', __('Only') . ' ' . implode(', ', $allowedExts) . ' ' . __('images are allowed'));
                            return $fail(__('Only') . ' ' . implode(', ', $allowedExts) . ' ' . __('images are allowed'));
                        }
                    } else {
                        Session::flash('receipt_error', __('receipt'));
                        return $fail(__('receipt'));
                    }
                },
            ] : '',
        ];

        // Create the validator with custom messages
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            // // Add a custom error message if needed
            // $validator->getMessageBag()->add('error', 'true');

            // Redirect back with validation errors
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user_request = Session::get('user_request');
        $user  = getUser();
        $offline_payment_gateways = UserOfflinePaymentGateway::where('user_id', $user->id)->pluck('name')->toArray();
        $bs = BasicSetting::where('user_id', $user->id)->firstorFail();

        $user_request['stripeToken'] = $request->stripeToken;
        $user_request['mode'] = 'online';
        $user_request['receipt_name'] = null;
        $user_request['payment_method'] = $request->payment_method;
        Session::put('user_paymentFor', 'appointment_booking');
        Session::put('user_request', $user_request);
        $title = "You are paying for appointment";
        $description = "Congratulation you are going to book an appointment.
        Please make a payment for confirming your time slot now!";
        // dd($request->payment_method);
        if ($request->payment_method == "Paypal") {
            $amount = round($request->price == 0 ? 0 : ($request->price / $bs->base_currency_rate), 2);
            $paypal = new PaypalController();
            $cancel_url = route('customer.appointment.paypal.cancel', getParam());
            $success_url = route('customer.appointment.paypal.success', getParam());
            return $paypal->paymentProcess($user_request, $amount, $title, $success_url, $cancel_url);
        } elseif ($request->payment_method == "Stripe") {
            $amount = round(($request->price / $bs->base_currency_rate), 2);
            $stripe = new StripeController();

            $cancel_url = route('customer.appointment.stripe.cancel', getParam());

            return $stripe->paymentProcess($user_request, $amount, $title, NULL, $cancel_url);
        } elseif ($request->payment_method == "Paytm") {
            if ($bs->base_currency_text != "INR") {
                return redirect()->back()->with('error', __('only_paytm_INR'))->withInput($request->all());
            }
            $amount = $request->price;
            $item_number = uniqid('paytm-') . time();
            $callback_url = route('customer.appointment.paytm.status', getParam());
            $paytm = new PaytmController();
            return $paytm->paymentProcess($user_request, $amount, $item_number, $callback_url);
        } elseif ($request->payment_method == "Paystack") {
            if ($bs->base_currency_text != "NGN") {
                return redirect()->back()->with('error', __('only_paystack_NGN'))->withInput($request->all());
            }
            $amount = $request->price * 100;
            $email = $user_request['email'];
            $success_url = route('customer.appointment.paystack.success', getParam());
            $payStack = new PaystackController();
            return $payStack->paymentProcess($user_request, $amount, $email, $success_url, $bs);
        } elseif ($request->payment_method == "Razorpay") {
            if ($bs->base_currency_text != "INR") {
                return redirect()->back()->with('error', __('only_razorpay_INR'))->withInput($request->all());
            }
            $amount = $request->price;
            $item_number = uniqid('razorpay-') . time();
            $cancel_url = route('customer.appointment.razorpay.cancel', getParam());
            $success_url = route('customer.appointment.razorpay.success', getParam());
            $razorpay = new RazorpayController();
            return $razorpay->paymentProcess($user_request, $amount, $item_number, $cancel_url, $success_url, $title, $description, $bs);
        } elseif ($request->payment_method == "Instamojo") {
            if ($bs->base_currency_text != "INR") {
                return redirect()->back()->with('error', __('only_instamojo_INR'))->withInput($request->all());
            }
            if ($request->price < 9) {
                return redirect()->back()->with('error', __('Minimum 10 INR required for this payment gateway'));
            }
            $amount = $request->price;
            $success_url = route('customer.appointment.instamojo.success', getParam());
            $cancel_url = route('customer.appointment.instamojo.cancel', getParam());
            $instaMojo = new InstamojoController();
            return $instaMojo->paymentProcess($user_request, $amount, $success_url, $cancel_url, $title, $bs);
        } elseif ($request->payment_method == "Mercadopago") {
            if ($bs->base_currency_text != "BRL") {
                return redirect()->back()->with('error', __('only_mercadopago_BRL'))->withInput($request->all());
            }
            $amount = $request->price;
            $email = $user_request['email'];
            $success_url = route('customer.appointment.mercadopago.success', getParam());
            $cancel_url = route('customer.appointment.mercadopago.cancel', getParam());
            $mercadopagoPayment = new MercadopagoController();
            return $mercadopagoPayment->paymentProcess($user_request, $amount, $success_url, $cancel_url, $email, $title, $description, $bs);
        } elseif ($request->payment_method == "Flutterwave") {
            $available_currency = array(
                'BIF',
                'CAD',
                'CDF',
                'CVE',
                'EUR',
                'GBP',
                'GHS',
                'GMD',
                'GNF',
                'KES',
                'LRD',
                'MWK',
                'NGN',
                'RWF',
                'SLL',
                'STD',
                'TZS',
                'UGX',
                'USD',
                'XAF',
                'XOF',
                'ZMK',
                'ZMW',
                'ZWD'
            );
            if (!in_array($bs->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $email = $user_request['email'];
            $item_number = uniqid('flutterwave-') . time();
            $cancel_url = route('customer.appointment.flutterwave.cancel', getParam());
            $success_url = route('customer.appointment.flutterwave.success', getParam());
            $flutterWave = new FlutterWaveController();
            return $flutterWave->paymentProcess($user_request, $amount, $email, $item_number, $success_url, $cancel_url, $bs);
        } elseif ($request->payment_method == "Authorize.net") {
            $available_currency = array('USD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'NOK', 'PLN', 'SEK', 'AUD', 'NZD');
            if (!in_array($bs->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }

            $amount = $request->price;
            $user_request['opaqueDataValue'] = $request->opaqueDataValue;
            $user_request['opaqueDataDescriptor'] = $request->opaqueDataDescriptor;
            $user_request['templateType'] = 'appointment_booking_notification';

            $cancel_url = route('customer.appointment.anet.cancel', getParam());
            $anetPayment = new AuthorizenetController();
            return $anetPayment->paymentProcess($user_request, $amount, $cancel_url, $title, $bs);
        } elseif ($request->payment_method == "Mollie") {
            $available_currency = array('AED', 'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HRK', 'HUF', 'ILS', 'ISK', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB', 'SEK', 'SGD', 'THB', 'TWD', 'USD', 'ZAR');
            if (!in_array($bs->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $success_url = route('customer.appointment.mollie.success', getParam());
            $cancel_url = route('customer.appointment.mollie.cancel', getParam());
            $molliePayment = new MollieController();
            return $molliePayment->paymentProcess($user_request, $amount, $success_url, $cancel_url, $title, $bs);
        } elseif ($request->payment_method == "Midtrans") {

            $available_currency = array('IDR');
            if (!in_array($bs->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $midtransPayment = new MidtransController();
            return $midtransPayment->paymentProcess($user_request, $amount, $title, $bs);
        } elseif ($request->payment_method == "Iyzico") {

            $available_currency = array('TRY');
            if (!in_array($bs->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))
                    ->withInput($request->all());
            }
            $request->validate([
                'identity_number' => 'required',
                'iname'           => 'required',
                'iemail'          => 'required|email',
                'iphone'          => 'required',
                'iaddress'         => 'required|max:255',
                'zip_code'        => 'required|numeric',
                'icountry'        => 'required',
                'icity'        => 'required',
            ], [
                'identity_number.required' => 'The identity number field is required.',
                'iname.required'           => 'The name field is required.',
                'iemail.required'          => 'The email field is required.',
                'iemail.email'             => 'The email field must be a valid email address.',
                'iphone.required'          => 'The phone number field is required.',
                'iaddress.required'         => 'The address field is required.',
                'iaddress.max'              => 'The address field must not exceed 255 characters.',
                'zip_code.required'        => 'The zip code field is required.',
                'zip_code.numeric'         => 'The zip code field must be numeric.',
                'icountry.required'        => 'The country field is required.',
                'icity.required'        => 'The city field is required.',
            ]);

            $user_request += [
                'identity_number' => $request->identity_number,
                'iname'           => $request->iname,
                'iemail'          => $request->iemail,
                'iphone'          => $request->iphone,
                'iaddress'         => $request->iaddress,
                'zip_code'        => $request->zip_code,
                'icountry'        => $request->icountry,
                'icity'        => $request->icity,
            ];

            $amount = $request->price;
            $iyzicoPayment = new IyzicoController();
            return $iyzicoPayment->paymentProcess($user_request, $amount, $title, $bs);
        } elseif ($request->payment_method == "Paytabs") {

            $paytabInfo = paytabInfo($user->id);
            if ($bs->base_currency_text != $paytabInfo['currency']) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $amount = $request->price;
            $paytabsPayment = new PaytabsController();
            return $paytabsPayment->paymentProcess($user_request, $amount, $title, $bs);
        } elseif ($request->payment_method == "Toyyibpay") {

            $available_currency = array('RM');
            if (!in_array($bs->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }
            $formatted_phone = preg_replace('/[^0-9]/', '', $request->phone_number);
            if (!preg_match('/^(\d{10,15})$/', $formatted_phone)) {
                return back()->with(['error' => __('Invalid phone number format Please enter a valid phone number')]);
            }
            $user_request += [
                'phone_number' => $request->phone_number,
            ];
            $amount = $request->price;
            $toyyibpayPayment = new ToyyibpayController();
            return $toyyibpayPayment->paymentProcess($user_request, $amount, $title, $bs);
        } elseif ($request->payment_method == "Phonepe") {

            $available_currency = array('INR');
            if (!in_array($bs->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }

            $amount = $request->price;
            $toyyibpayPayment = new PhonePeController();
            return $toyyibpayPayment->paymentProcess($user_request, $amount, $title, $bs);
        } elseif ($request->payment_method == "Yoco") {

            $available_currency = array('ZAR');
            if (!in_array($bs->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }

            $amount = $request->price;
            $yocoPayment = new YocoController();
            return $yocoPayment->paymentProcess($user_request, $amount, $title, $bs);
        } elseif ($request->payment_method == "My Fatoorah") {
            $available_currency = array('KWD', 'SAR', 'BHD', 'AED', 'QAR', 'OMR', 'JOD');
            if (!in_array($bs->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }

            $user_request += [
                'phone_number' => $request->phone_number,
            ];
            $amount = $request->price;
            $myfatoorayhPayment = new MyFatoorahController();
            return $myfatoorayhPayment->paymentProcess($user_request, $amount, $title, $bs);
        } elseif ($request->payment_method == "Xendit") {

            $available_currency = array('IDR', 'PHP', 'USD', 'SGD', 'MYR');
            if (!in_array($bs->base_currency_text, $available_currency)) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }

            $amount = $request->price;
            $xenditPayment = new XenditController();
            return $xenditPayment->paymentProcess($user_request, $amount, $title, $bs);
        } elseif ($request->payment_method == "Perfect Money") {
            $perfect_money = UserPaymentGateway::where('user_id', $user->id)->where('keyword', 'perfect_money')->first();
            $info = json_decode($perfect_money->information, true);

            $wallateValidationKey = substr($info['perfect_money_wallet_id'], 0, 1);
            $validCurrencies = [
                'U' => 'USD',
                'E' => 'EUR',
                'G' => 'TROY'
            ];

            if (!isset($validCurrencies[$wallateValidationKey]) || $bs->base_currency_text != $validCurrencies[$wallateValidationKey]) {
                return redirect()->back()->with('error', __('invalid_currency'))->withInput($request->all());
            }

            $amount = $request->price;
            $perfectMoneyPayment = new PerfectMoneyController();
            return $perfectMoneyPayment->paymentProcess($user_request, $amount, $title, $bs);
        } elseif (in_array($request->payment_method, $offline_payment_gateways)) {

            $user_request['mode'] = 'offline';
            $user_request['status'] = 0;
            $user_request['receipt_name'] = null;
            Session::put('user_request', $user_request);
            if ($request->has('receipt')) {
                $filename = time() . '.' . $request->file('receipt')->getClientOriginalExtension();
                $directory = public_path("assets/front/img/membership/receipt");
                if (!file_exists($directory)) mkdir($directory, 0775, true);
                $request->file('receipt')->move($directory, $filename);
                $request['receipt_name'] = $filename;
            }
            $amount = $request->price;
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = "offline";
            $appointment = $this->store($user_request, $transaction_id, $transaction_details, $amount, $bs);
            $user_request['templateType'] = 'appointment_booking_notification';
            $this->mailToTanentUser($user_request, $appointment, $amount, $request->payment_method, $bs, $transaction_id);
            $success_message  = route('customer.success.page', [getParam(), $appointment->id]);
            return redirect($success_message);
        } elseif ($request->payment_method == null) {

            $user_request['mode'] = 'Free Appointment';
            $user_request['status'] = 0;
            $user_request['receipt_name'] = null;
            Session::put('user_request', $user_request);
            if ($request->has('receipt')) {
                $filename = time() . '.' . $request->file('receipt')->getClientOriginalExtension();
                $directory = public_path("assets/front/img/membership/receipt");
                if (!file_exists($directory)) mkdir($directory, 0775, true);
                $request->file('receipt')->move($directory, $filename);
                $request['receipt_name'] = $filename;
            }
            $amount = $request->price;
            $transaction_id = UserPermissionHelper::uniqidReal(8);
            $transaction_details = "Free";
            $appointment = $this->store($user_request, $transaction_id, $transaction_details, $amount, $bs);
            $user_request['templateType'] = 'appointment_booking_notification';
            $this->mailToTanentUser($user_request, $appointment, $amount, $request->payment_method, $bs, $transaction_id);
            $success_message  = route('customer.success.page', [getParam(), $appointment->id]);
            return redirect($success_message);
        }
    }

    public function store($request, $transaction_id, $transaction_details, $amount, $be)
    {
        $user = getUser();
        $customer = Auth::guard('customer')->user();
        $ubs = BasicSetting::where('user_id', $user->id)->firstOrFail();
        // Serial code
        $sl = BasicSetting::select(DB::raw("serial_reset , CONCAT( '', LPAD(serial_reset + 1,5,'0') ) as slId"))->where('user_id', $user->id)
            ->first();
        if ($sl) {
            $sl_no = $sl->slId;
        } else {
            $sl_no = '00001';
        }
        // Serial code

        if (empty($request['category_id'])) {
            $total_fee = $ubs->appointment_price;
        } else {
            $total_fee = Category::find($request['category_id'])->appointment_price;
        }

        if ($ubs->full_payment == 1) {
            $payment_status = 2;
            $due  = null;
        } else {
            $due  = $total_fee - (($total_fee * $ubs->advance_percentage) / 100);
            $payment_status = 3;
        }

        $appointment  =  AppointmentBooking::create([
            'customer_id' => $customer ? $customer->id : null,
            'user_id' => $user->id,
            'name' => $request['name'],
            'email' => $request['email'],
            'date' => $request['date'],
            'category_id' => $request['category_id'] ?? null,
            'amount' => $amount,
            'total_amount' => $total_fee,
            'due_amount' => $due,
            'time' => $request['slot'],
            'serial_number' => $sl_no,
            'transaction_id' => $transaction_id,
            'transaction_details' => $transaction_details,
            'status' => 1,
            'payment_status' => $payment_status,
            'receipt' => $request['receipt_name'],
            'payment_method' => $request['payment_method'],
            'currency' => $be->base_currency_text,
            'details' => json_encode($request['customer_form']),
        ]);
        Session::forget('user_request');
        $ubs->serial_reset = $sl->serial_reset + 1;
        $ubs->save();
        return $appointment;
    }
    public function mailToTanentUser($requestData, $appointment, $amount, $method, $be, $transaction_id)
    {
        $user = Auth::guard('customer')->user();
        $category = Category::find($appointment->category_id)->name ?? '';
        $file_name = $this->userMakeInvoice($requestData, $user, $appointment, $category, $amount, $method, $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $transaction_id);
        $mailer = new MegaMailer();
        $data = [
            'toMail' => $requestData['email'],
            'toName' => $requestData['name'],
            'serial_number' => $appointment->serial_number,
            'date' => $appointment->date,
            'slot' => $appointment->time,
            'amount' => $amount,
            'due_amount' => $appointment->due_amount,
            'total_amount' => $appointment->total_amount,
            'customer_name' => $requestData['name'],
            'category' => $category ?? '-',
            'user_appointment' => $file_name,
            'website_title' => $be->website_title,
            'templateType' => $requestData['templateType'],
            'user' => Auth::guard('web')->check() ? Auth::guard('web')->user() : getUser()
        ];
        $mailer->mailFromTanent($data);
    }
    public function customerSuccess($domain, AppointmentBooking $appointment)
    {
        $data['appointment'] = $appointment;
        return view('user-front.online-success', $data);
    }
    public function offlineSuccess($domain, AppointmentBooking $appointment)
    {
        $data['appointment'] = $appointment;
        return view('user-front.offline-success', $data);
    }
}
