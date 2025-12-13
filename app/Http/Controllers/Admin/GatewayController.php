<?php

namespace App\Http\Controllers\Admin;

use Validator;
use App\Models\User;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\OfflineGateway;
use App\Models\PaymentGateway;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;

class GatewayController extends Controller
{
    public function index()
    {
        $data['flutterwave'] = PaymentGateway::find(6);
        $data['razorpay'] = PaymentGateway::find(9);
        $data['paytm'] = PaymentGateway::find(11);
        $data['paystack'] = PaymentGateway::find(12);
        $data['instamojo'] = PaymentGateway::find(13);
        $data['stripe'] = PaymentGateway::find(14);
        $data['paypal'] = PaymentGateway::find(15);
        $data['mollie'] = PaymentGateway::find(17);
        $data['mercadopago'] = PaymentGateway::find(19);
        $data['anet'] = PaymentGateway::find(20);

        $data['midtrans'] = PaymentGateway::where('keyword', 'midtrans')->first();
        $data['paytabs'] = PaymentGateway::where('keyword', 'paytabs')->first();
        $data['iyzico'] = PaymentGateway::where('keyword', 'iyzico')->first();
        $data['toyyibpay'] = PaymentGateway::where('keyword', 'toyyibpay')->first();
        $data['phonepe'] = PaymentGateway::where('keyword', 'phonepe')->first();
        $data['myfatoorah'] = PaymentGateway::where('keyword', 'myfatoorah')->first();
        $data['xendit'] = PaymentGateway::where('keyword', 'xendit')->first();
        $data['yoco'] = PaymentGateway::where('keyword', 'yoco')->first();
        $data['perfect_money'] = PaymentGateway::where('keyword', 'perfect_money')->first();

        return view('admin.gateways.index', $data);
    }

    public function paypalUpdate(Request $request)
    {
        $paypal = PaymentGateway::find(15);
        $paypal->status = $request->status;

        $information = [];
        $information['client_id'] = $request->client_id;
        $information['client_secret'] = $request->client_secret;
        $information['sandbox_check'] = $request->sandbox_check;
        $information['text'] = 'Pay via your PayPal account.';

        $paypal->information = json_encode($information);

        $paypal->save();

        $request->session()->flash('success', __('Updated successfully!'));

        return back();
    }

    public function stripeUpdate(Request $request)
    {
        $stripe = PaymentGateway::find(14);
        $stripe->status = $request->status;

        $information = [];
        $information['key'] = $request->key;
        $information['secret'] = $request->secret;
        $information['text'] = 'Pay via your Credit account.';

        $stripe->information = json_encode($information);

        $stripe->save();

        $request->session()->flash('success', __('Updated successfully!'));

        return back();
    }

    public function paystackUpdate(Request $request)
    {
        $paystack = PaymentGateway::find(12);
        $paystack->status = $request->status;

        $information = [];
        $information['key'] = $request->key;
        $information['email'] = $request->email;
        $information['text'] = 'Pay via your Paystack account.';

        $paystack->information = json_encode($information);

        $paystack->save();

        $request->session()->flash('success', __('Updated successfully!'));

        return back();
    }

    public function paytmUpdate(Request $request)
    {
        $paytm = PaymentGateway::find(11);
        $paytm->status = $request->status;

        $information = [];
        $information['environment'] = $request->environment;
        $information['merchant'] = $request->merchant;
        $information['secret'] = $request->secret;
        $information['website'] = $request->website;
        $information['industry'] = $request->industry;
        $information['text'] = 'Pay via your paytm account.';

        $paytm->information = json_encode($information);

        $paytm->save();

        $request->session()->flash('success', __('Updated successfully!'));

        return back();
    }

    public function flutterwaveUpdate(Request $request)
    {
        $flutterwave = PaymentGateway::find(6);
        $flutterwave->status = $request->status;

        $information = [];
        $information['public_key'] = $request->public_key;
        $information['secret_key'] = $request->secret_key;
        $information['text'] = 'Pay via your Flutterwave account.';

        $flutterwave->information = json_encode($information);

        $flutterwave->save();

        $request->session()->flash('success', __('Updated successfully!'));

        return back();
    }

    public function instamojoUpdate(Request $request)
    {
        $instamojo = PaymentGateway::find(13);
        $instamojo->status = $request->status;
        $information = [];
        $information['key'] = $request->key;
        $information['token'] = $request->token;
        $information['sandbox_check'] = $request->sandbox_check;
        $information['text'] = 'Pay via your Instamojo account.';
        $instamojo->information = json_encode($information);
        $instamojo->save();
        $request->session()->flash('success', __('Updated successfully!'));
        return back();
    }

    public function mollieUpdate(Request $request)
    {
        $mollie = PaymentGateway::find(17);
        $mollie->status = $request->status;
        $information = [];
        $information['key'] = $request->key;
        $information['text'] = 'Pay via your Mollie Payment account.';
        $mollie->information = json_encode($information);
        $mollie->save();
        $arr = ['MOLLIE_KEY' => $request->key];
        setEnvironmentValue($arr);
        \Artisan::call('config:clear');
        $request->session()->flash('success', __('Updated successfully!'));
        return back();
    }

    public function razorpayUpdate(Request $request)
    {
        $razorpay = PaymentGateway::find(9);
        $razorpay->status = $request->status;
        $information = [];
        $information['key'] = $request->key;
        $information['secret'] = $request->secret;
        $information['text'] = 'Pay via your Razorpay account.';
        $razorpay->information = json_encode($information);
        $razorpay->save();
        $request->session()->flash('success', __('Updated successfully!'));
        return back();
    }

    public function anetUpdate(Request $request)
    {
        $anet = PaymentGateway::find(20);
        $anet->status = $request->status;
        $information = [];
        $information['login_id'] = $request->login_id;
        $information['transaction_key'] = $request->transaction_key;
        $information['public_key'] = $request->public_key;
        $information['sandbox_check'] = $request->sandbox_check;
        $information['text'] = 'Pay via your Authorize.net account.';
        $anet->information = json_encode($information);
        $anet->save();
        $request->session()->flash('success', __('Updated successfully!'));
        return back();
    }

    public function mercadopagoUpdate(Request $request)
    {
        $mercadopago = PaymentGateway::find(19);
        $mercadopago->status = $request->status;
        $information = [];
        $information['token'] = $request->token;
        $information['sandbox_check'] = $request->sandbox_check;
        $information['text'] = 'Pay via your Mercado Pago account.';
        $mercadopago->information = json_encode($information);
        $mercadopago->save();
        $request->session()->flash('success', __('Updated successfully!'));
        return back();
    }

    // for shohag-4.0

    public function updateMidtransInfo(Request $request)
    {
        $midtrans = PaymentGateway::where('keyword', 'midtrans')->first();
        $information = [];
        $information['server_key'] = $request->server_key;
        $information['midtrans_mode'] = $request->midtrans_mode;
        $midtrans->information = json_encode($information);
        $midtrans->status = $request->status;
        $midtrans->save();
        $request->session()->flash('success', __('Midtran\'s informations updated successfully') . '.');
        return back();
    }

    public function updateIyzicoInfo(Request $request)
    {
        $iyzico = PaymentGateway::where('keyword', 'iyzico')->first();
        $information = [];
        $information['api_key'] = $request->api_key;
        $information['secret_key'] = $request->secret_key;
        $information['sandbox_status'] = $request->sandbox_status;
        $information['iyzico_mode'] = $request->iyzico_mode;
        $iyzico->information = json_encode($information);
        $iyzico->status = $request->status;
        $iyzico->save();

        $request->session()->flash('success', __('Iyzico\'s informations updated successfully') . '!');

        return back();
    }

    public function updatePaytabsInfo(Request $request)
    {
        $data = PaymentGateway::where('keyword', 'paytabs')->first();
        $information = [];

        $information['server_key'] = $request->server_key;
        $information['profile_id'] = $request->profile_id;
        $information['country'] = $request->country;
        $information['api_endpoint'] = $request->api_endpoint;

        $data->information = json_encode($information);
        $data->status = $request->status;
        $data->save();

        Session::flash('success', __('Updated Paytabs\'s Information Successfully') . '.');

        return redirect()->back();
    }

    //Toyyibpay
    public function updateToyyibpayInfo(Request $request)
    {
        $data = PaymentGateway::where('keyword', 'toyyibpay')->first();
        $information = [];
        $information['sandbox_status'] = $request->sandbox_status;
        $information['secret_key'] = $request->secret_key;
        $information['category_code'] = $request->category_code;

        $data->information = json_encode($information);
        $data->status = $request->status;
        $data->save();

        Session::flash('success', __('Updated Toyyibpay\'s Information Successfully') . '.');

        return redirect()->back();
    }

    public function updatePhonepeInfo(Request $request)
    {
        $data = PaymentGateway::where('keyword', 'phonepe')->first();
        $information = [];
        $information['merchant_id'] = $request->merchant_id;
        $information['sandbox_status'] = $request->sandbox_status;
        $information['salt_key'] = $request->salt_key;
        $information['salt_index'] = $request->salt_index;

        $data->information = json_encode($information);
        $data->status = $request->status;
        $data->save();

        Session::flash('success', __('Updated Phonepe\'s Information Successfully') . '.');
        return redirect()->back();
    }

    //Yoco
    public function updateYocoInfo(Request $request)
    {
        $data = PaymentGateway::where('keyword', 'yoco')->first();
        $information = [];
        $information['secret_key'] = $request->secret_key;

        $data->information = json_encode($information);
        $data->status = $request->status;
        $data->save();
        $request->session()->flash('success', __('Updated Yoco\'s Information Successfully') . '.');

        return redirect()->back();
    }

    public function updateMyFatoorahInfo(Request $request)
    {
        $data = PaymentGateway::where('keyword', 'myfatoorah')->first();
        $information = [];
        $information['token'] = $request->token;
        $information['sandbox_status'] = $request->sandbox_status;

        $data->information = json_encode($information);
        $data->status = $request->status;
        $data->save();

        Session::flash('success', __('Updated Myfatoorah\'s Information Successfully') . '.');

        return redirect()->back();
    }

    //xendit
    public function updateXenditInfo(Request $request)
    {
        $information = [];
        $data = PaymentGateway::where('keyword', 'xendit')->first();
        $information['secret_key'] = $request->secret_key;

        $data->information = json_encode($information);
        $data->status = $request->status;
        $data->save();

        $array = [
            'XENDIT_SECRET_KEY' => $request->secret_key,
        ];

        setEnvironmentValue($array);
        Artisan::call('config:clear');

        Session::flash('success', __('Updated Xendit\'s Information Successfully') . '.');

        return redirect()->back();
    }

    public function updatePerfectMoneyInfo(Request $request)
    {
        
        $data = PaymentGateway::where('keyword', 'perfect_money')->first();
        $information = [];
        $information['perfect_money_wallet_id'] = $request->perfect_money_wallet_id;

        $data->information = json_encode($information);
        $data->status = $request->status;
        $data->save();

        Session::flash('success', __('Updated Perfect Money\'s Information Successfully') . '.');

        return redirect()->back();
    }

    public function offline(Request $request)
    {
        $data['ogateways'] = OfflineGateway::orderBy('id', 'DESC')->get();
        return view('admin.gateways.offline.index', $data);
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
        OfflineGateway::create($in);
        Session::flash('success', __('Store successfully!'));
        return 'success';
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
        OfflineGateway::where('id', $request->ogateway_id)->update($in);
        Session::flash('success', __('Updated successfully!'));
        return 'success';
    }

    public function status(Request $request)
    {
        $og = OfflineGateway::find($request->ogateway_id);
        $og->status = $request->status;
        $og->save();
        Session::flash('success', __('Gateway status changed successfully!'));
        return back();
    }

    public function delete(Request $request)
    {
        $ogateway = OfflineGateway::findOrFail($request->ogateway_id);
        $ogateway->delete();
        Session::flash('success', __('Gateway deleted successfully!'));
        return back();
    }
}
