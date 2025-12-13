<?php

namespace App\Http\Controllers\User;

use Validator;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Session;
use App\Models\User\UserOfflinePaymentGateway;

class GatewayController extends Controller
{
    public function index()
    {
        $userId = Auth::guard('web')->user()->id;
        $data['paypal'] = UserPaymentGateway::where([['user_id', $userId], ['keyword', 'paypal']])->first();
        $data['stripe'] = UserPaymentGateway::where([['user_id', $userId], ['keyword', 'stripe']])->first();
        $data['paystack'] = UserPaymentGateway::where([['user_id', $userId], ['keyword', 'paystack']])->first();
        $data['paytm'] = UserPaymentGateway::where([['user_id', $userId], ['keyword', 'paytm']])->first();
        $data['flutterwave'] = UserPaymentGateway::where([['user_id', $userId], ['keyword', 'flutterwave']])->first();
        $data['instamojo'] = UserPaymentGateway::where([['user_id', $userId], ['keyword', 'instamojo']])->first();
        $data['mollie'] = UserPaymentGateway::where([['user_id', $userId], ['keyword', 'mollie']])->first();
        $data['razorpay'] = UserPaymentGateway::where([['user_id', $userId], ['keyword', 'razorpay']])->first();
        $data['mercadopago'] = UserPaymentGateway::where([['user_id', $userId], ['keyword', 'mercadopago']])->first();
        $data['anet'] = UserPaymentGateway::where([['user_id', $userId], ['keyword', 'authorize.net']])->first();

        $data['midtrans'] = UserPaymentGateway::where('user_id', $userId)->where('keyword', 'midtrans')->first();
        $data['iyzico'] = UserPaymentGateway::where('user_id', $userId)->where('keyword', 'iyzico')->first();
        $data['perfect_money'] = UserPaymentGateway::where('user_id', $userId)->where('keyword', 'perfect_money')->first();

        $data['paytabs'] = UserPaymentGateway::where('user_id', $userId)->where('keyword', 'paytabs')->first();
        $data['toyyibpay'] = UserPaymentGateway::where('user_id', $userId)->where('keyword', 'toyyibpay')->first();
        $data['phonepe'] = UserPaymentGateway::where('user_id', $userId)->where('keyword', 'phonepe')->first();
        $data['yoco'] = UserPaymentGateway::where('user_id', $userId)->where('keyword', 'yoco')->first();
        $data['myfatoorah'] = UserPaymentGateway::where('user_id', $userId)->where('keyword', 'myfatoorah')->first();
        $data['xendit'] = UserPaymentGateway::where('user_id', $userId)->where('keyword', 'xendit')->first();

        return view('user.gateways.index', $data);
    }

    public function updateIyzicoInfo(Request $request)
    {
        UserPaymentGateway::query()->updateOrCreate(
            [
                'user_id' => Auth::guard('web')->user()->id,
                'keyword' => 'iyzico'
            ],
            $request->except(['_token', 'information', 'keyword']) + [
                'user_id' => Auth::guard('web')->user()->id,
                'status' => (int)$request->status,
                'keyword' => 'iyzico',
                'name' => 'Iyzico',
                'type' => 'automatic',
                'information' => json_encode([
                    'api_key' => $request->api_key,
                    'secrect_key' => $request->secrect_key,
                    'iyzico_mode' => $request->iyzico_mode,
                    'text' => "Pay via your Iyzico account."
                ])
            ]
        );

        session()->flash('success', __('Updated successfully'));

        return back();
    }

    public function updateXenditInfo(Request $request)
    {
        $rules = [
            'status' => 'required',
            'secret_key' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        UserPaymentGateway::query()->updateOrCreate(
            [
                'user_id' => Auth::guard('web')->user()->id,
                'keyword' => 'xendit'
            ],
            $request->except(['_token', 'information', 'keyword']) + [
                'user_id' => Auth::guard('web')->user()->id,
                'status' => (int)$request->status,
                'keyword' => 'xendit',
                'name' => 'Xendit',
                'type' => 'automatic',
                'information' => json_encode([
                    'secret_key' => $request->secret_key,
                    'text' => "Pay via your Xendit account."
                ])
            ]
        );

        session()->flash('success', __('Updated successfully'));

        return back();
    }

        public function updateYocoInfo(Request $request)
    {
        $rules = [
            'status' => 'required',
            'secret_key' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        UserPaymentGateway::query()->updateOrCreate(
            [
                'user_id' => Auth::guard('web')->user()->id,
                'keyword' => 'yoco'
            ],
            $request->except(['_token', 'information', 'keyword']) + [
                'user_id' => Auth::guard('web')->user()->id,
                'status' => (int)$request->status,
                'keyword' => 'yoco',
                'name' => 'Yoco',
                'type' => 'automatic',
                'information' => json_encode([
                    'secret_key' => $request->secret_key,
                    'text' => "Pay via your Yoco account."
                ])
            ]
        );

        session()->flash('success', __('Updated successfully'));

        return back();
    }

    public function updateMidtransInfo(Request $request)
    {
        UserPaymentGateway::query()->updateOrCreate(
            [
                'user_id' => Auth::guard('web')->user()->id,
                'keyword' => 'midtrans'
            ],
            $request->except(['_token', 'information', 'keyword']) + [
                'user_id' => Auth::guard('web')->user()->id,
                'status' => (int)$request->status,
                'keyword' => 'midtrans',
                'name' => 'Midtrans',
                'type' => 'automatic',
                'information' => json_encode([
                    'server_key' => $request->server_key,
                    'midtrans_mode' => $request->midtrans_mode,
                    'text' => "Pay via your Midtrans account."
                ])
            ]
        );

        session()->flash('success', __('Updated successfully'));
        return back();
    }
    public function updateMyFatoorahInfo(Request $request)
    {
        $rules = [
            'status' => 'required',
            'sandbox_status' => 'required',
            'token' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        UserPaymentGateway::query()->updateOrCreate(
            [
                'user_id' => Auth::guard('web')->user()->id,
                'keyword' => 'myfatoorah'
            ],
            $request->except(['_token', 'information', 'keyword']) + [
                'user_id' => Auth::guard('web')->user()->id,
                'status' => (int)$request->status,
                'keyword' => 'myfatoorah',
                'name' => 'My Fatoorah',
                'type' => 'automatic',
                'information' => json_encode([
                    'sandbox_status' => $request->sandbox_status,
                    'token' => $request->token,
                    'text' => "Pay via your My Fatoorah account."
                ])
            ]
        );

        session()->flash('success', __('Updated successfully'));
        return back();
    }
    public function updatePerfectMoneyInfo(Request $request)
    {
        $rules = [
            'status' => 'required',
            'perfect_money_wallet_id' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        UserPaymentGateway::query()->updateOrCreate(
            [
                'user_id' => Auth::guard('web')->user()->id,
                'keyword' => 'perfect_money'
            ],
            $request->except(['_token', 'information', 'keyword']) + [
                'user_id' => Auth::guard('web')->user()->id,
                'status' => (int)$request->status,
                'keyword' => 'perfect_money',
                'name' => 'Perfect Money',
                'type' => 'automatic',
                'information' => json_encode([
                    'perfect_money_wallet_id' => $request->perfect_money_wallet_id,
                    'text' => "Pay via your Perfect Money account."
                ])
            ]
        );

        session()->flash('success', __('Updated successfully'));
        return back();
    }

    public function updateToyyibpayInfo(Request $request)
    {
        $rules = [
            'status' => 'required',
            'sandbox_status' => 'required',
            'secret_key' => 'required',
            'category_code' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        UserPaymentGateway::query()->updateOrCreate(
            [
                'user_id' => Auth::guard('web')->user()->id,
                'keyword' => 'toyyibpay'
            ],
            $request->except(['_token', 'information', 'keyword']) + [
                'user_id' => Auth::guard('web')->user()->id,
                'status' => (int)$request->status,
                'keyword' => 'toyyibpay',
                'name' => 'Toyyibpay',
                'type' => 'automatic',
                'information' => json_encode([
                    'sandbox_status' => $request->sandbox_status,
                    'secret_key' => $request->secret_key,
                    'category_code' => $request->category_code,
                    'text' => "Pay via your Toyyibpay account."
                ])
            ]
        );

        session()->flash('success', __('Updated successfully'));
        return back();
    }
    public function updatePhonepeInfo(Request $request)
    {
        $rules = [
            'status' => 'required',
            'sandbox_status' => 'required',
            'merchant_id' => 'required',
            'salt_key' => 'required',
            'salt_index' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        UserPaymentGateway::query()->updateOrCreate(
            [
                'user_id' => Auth::guard('web')->user()->id,
                'keyword' => 'phonepe'
            ],
            $request->except(['_token', 'information', 'keyword']) + [
                'user_id' => Auth::guard('web')->user()->id,
                'status' => (int)$request->status,
                'keyword' => 'phonepe',
                'name' => 'Phonepe',
                'type' => 'automatic',
                'information' => json_encode([
                    'merchant_id' => $request->merchant_id,
                    'sandbox_status' => $request->sandbox_status,
                    'salt_key' => $request->salt_key,
                    'salt_index' => $request->salt_index,
                    'text' => "Pay via your Phonepe account."
                ])
            ]
        );

        session()->flash('success', __('Updated successfully'));
        return back();
    }
    public function updatePaytabsInfo(Request $request)
    {
        $rules = [
            'status' => 'required',
            'country' => 'required',
            'server_key' => 'required',
            'profile_id' => 'required',
            'api_endpoint' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        UserPaymentGateway::query()->updateOrCreate(
            [
                'user_id' => Auth::guard('web')->user()->id,
                'keyword' => 'paytabs'
            ],
            $request->except(['_token', 'information', 'keyword']) + [
                'user_id' => Auth::guard('web')->user()->id,
                'status' => (int)$request->status,
                'keyword' => 'paytabs',
                'name' => 'Paytabs',
                'type' => 'automatic',
                'information' => json_encode([
                    'server_key' => $request->server_key,
                    'profile_id' => $request->profile_id,
                    'api_endpoint' => $request->api_endpoint,
                    'country' => $request->country,
                    'text' => "Pay via your Paytabs account."
                ])
            ]
        );

        session()->flash('success', __('Updated successfully'));
        return back();
    }

    public function paypalUpdate(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Get existing record or create new instance
        $paypal = UserPaymentGateway::firstOrNew(
            [
                'user_id' => $user->id,
                'keyword' => 'paypal'
            ],
            [
                'information' => json_encode([])
            ]
        );

        // Set properties
        $paypal->status = $request->status ?? 0;

        $information = [
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'sandbox_check' => $request->sandbox_check ?? 0,
            'text' => "Pay via your PayPal account."
        ];

        $paypal->information = json_encode($information);
        $paypal->save();

        return back()->with('success', __("Updated successfully") . '!');
    }


    public function stripeUpdate(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Get existing record or create new instance
        $stripe = UserPaymentGateway::firstOrNew(
            [
                'user_id' => $user->id,
                'keyword' => 'stripe'
            ],
            [
                'information' => json_encode([])
            ]
        );

        $stripe->status = $request->status;

        $information = [];
        $information['key'] = $request->key;
        $information['secret'] = $request->secret;
        $information['text'] = "Pay via your Credit account.";

        $stripe->information = json_encode($information);

        $stripe->save();

        $request->session()->flash('success', __("Updated successfully") . '!');

        return back();
    }

    public function paystackUpdate(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Get existing record or create new instance
        $paystack = UserPaymentGateway::firstOrNew(
            [
                'user_id' => $user->id,
                'keyword' => 'paystack'
            ],
            [
                'information' => json_encode([])
            ]
        );
        $paystack->status = $request->status;

        $information = [];
        $information['key'] = $request->key;
        $information['email'] = $request->email;
        $information['text'] = "Pay via your Paystack account.";

        $paystack->information = json_encode($information);

        $paystack->save();

        $request->session()->flash('success', __("Updated successfully") . '!');

        return back();
    }

    public function paytmUpdate(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Get existing record or create new instance
        $paytm = UserPaymentGateway::firstOrNew(
            [
                'user_id' => $user->id,
                'keyword' => 'paytm'
            ],
            [
                'information' => json_encode([])
            ]
        );
        $paytm->status = $request->status;

        $information = [];
        $information['environment'] = $request->environment;
        $information['merchant'] = $request->merchant;
        $information['secret'] = $request->secret;
        $information['website'] = $request->website;
        $information['industry'] = $request->industry;
        $information['text'] = "Pay via your paytm account.";

        $paytm->information = json_encode($information);

        $paytm->save();


        $request->session()->flash('success', __("Updated successfully") . '!');

        return back();
    }

    public function flutterwaveUpdate(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Get existing record or create new instance
        $flutterwave = UserPaymentGateway::firstOrNew(
            [
                'user_id' => $user->id,
                'keyword' => 'flutterwave'
            ],
            [
                'information' => json_encode([])
            ]
        );
        $flutterwave->status = $request->status;

        $information = [];
        $information['public_key'] = $request->public_key;
        $information['secret_key'] = $request->secret_key;
        $information['text'] = "Pay via your Flutterwave account.";

        $flutterwave->information = json_encode($information);

        $flutterwave->save();

        $request->session()->flash('success', __("Updated successfully") . '!');;

        return back();
    }

    public function instamojoUpdate(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Get existing record or create new instance
        $instamojo = UserPaymentGateway::firstOrNew(
            [
                'user_id' => $user->id,
                'keyword' => 'instamojo'
            ],
            [
                'information' => json_encode([])
            ]
        );
        $instamojo->status = $request->status;

        $information = [];
        $information['key'] = $request->key;
        $information['token'] = $request->token;
        $information['sandbox_check'] = $request->sandbox_check;
        $information['text'] = "Pay via your Instamojo account.";

        $instamojo->information = json_encode($information);

        $instamojo->save();

        $request->session()->flash('success', __("Updated successfully") . '!');

        return back();
    }

    public function mollieUpdate(Request $request)
    {

        $user = Auth::guard('web')->user();

        // Get existing record or create new instance
        $mollie = UserPaymentGateway::firstOrNew(
            [
                'user_id' => $user->id,
                'keyword' => 'mollie'
            ],
            [
                'information' => json_encode([])
            ]
        );
        $mollie->status = $request->status;

        $information = [];
        $information['key'] = $request->key;
        $information['text'] = "Pay via your Mollie Payment account.";

        $mollie->information = json_encode($information);

        $mollie->save();

        $arr = ['MOLLIE_KEY' => $request->key];
        setEnvironmentValue($arr);
        \Artisan::call('config:clear');

        $request->session()->flash('success', __("Updated successfully") . '!');

        return back();
    }

    public function razorpayUpdate(Request $request)
    {

        $user = Auth::guard('web')->user();

        // Get existing record or create new instance
        $razorpay = UserPaymentGateway::firstOrNew(
            [
                'user_id' => $user->id,
                'keyword' => 'razorpay'
            ],
            [
                'information' => json_encode([])
            ]
        );
        $razorpay->status = $request->status;

        $information = [];
        $information['key'] = $request->key;
        $information['secret'] = $request->secret;
        $information['text'] = "Pay via your Razorpay account.";

        $razorpay->information = json_encode($information);

        $razorpay->save();

        $request->session()->flash('success', __("Updated successfully") . '!');

        return back();
    }

    public function anetUpdate(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Get existing record or create new instance
        $anet = UserPaymentGateway::firstOrNew(
            [
                'user_id' => $user->id,
                'keyword' => 'authorize.net'
            ],
            [
                'information' => json_encode([])
            ]
        );
        $anet->status = $request->status;

        $information = [];
        $information['login_id'] = $request->login_id;
        $information['transaction_key'] = $request->transaction_key;
        $information['public_key'] = $request->public_key;
        $information['sandbox_check'] = $request->sandbox_check;
        $information['text'] = "Pay via your Authorize.net account.";

        $anet->information = json_encode($information);

        $anet->save();

        $request->session()->flash('success', __("Updated successfully") . '!');

        return back();
    }

    public function mercadopagoUpdate(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Get existing record or create new instance
        $mercadopago = UserPaymentGateway::firstOrNew(
            [
                'user_id' => $user->id,
                'keyword' => 'mercadopago'
            ],
            [
                'information' => json_encode([])
            ]
        );
        $mercadopago->status = $request->status;

        $information = [];
        $information['token'] = $request->token;
        $information['sandbox_check'] = $request->sandbox_check;
        $information['text'] = "Pay via your Mercado Pago account.";

        $mercadopago->information = json_encode($information);

        $mercadopago->save();

        $request->session()->flash('success', __("Updated successfully") . '!');

        return back();
    }

    public function offline(Request $request)
    {
        $data['ogateways'] = UserOfflinePaymentGateway::where('user_id', Auth::guard('web')->user()->id)->orderBy('id', 'DESC')->get();
        return view('user.gateways.offline.index', $data);
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|max:100',
            'short_description' => 'nullable',
            'serial_number' => 'required|integer',
            'is_receipt' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $in = $request->all();
        $in['user_id'] = Auth::guard('web')->user()->id;
        UserOfflinePaymentGateway::create($in);
        Session::flash('success', __('Store successfully') . '!');
        return "success";
    }

    public function update(Request $request)
    {

        $rules = [
            'name' => 'required|max:100',
            'short_description' => 'nullable',
            'serial_number' => 'required|integer',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $in = $request->except('_token', 'ogateway_id');
        UserOfflinePaymentGateway::where('id', $request->ogateway_id)->update($in);
        Session::flash('success', __('Updated successfully') . '!');
        return "success";
    }

    public function status(Request $request)
    {
        $og = UserOfflinePaymentGateway::find($request->ogateway_id);
        $og->status = $request->status;
        $og->save();
        Session::flash('success', __('Gateway status changed successfully') . '!');
        return back();
    }

    public function delete(Request $request)
    {
        $ogateway = UserOfflinePaymentGateway::findOrFail($request->ogateway_id);
        $ogateway->delete();
        Session::flash('success', __('Deleted successfully') . '!');
        return back();
    }
}
