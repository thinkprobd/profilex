<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Session;
use App\Models\Language;
use Config;
use App\Models\BasicSetting as BS;
use App\Models\BasicExtended as BE;
use App\Models\BasicSetting;
use App\Models\Seo;
use App\Models\User;
use Carbon\Carbon;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest', ['except' => ['logout', 'userLogout']]);
        $this->middleware('setlang');
        $bs = BS::first();

        Config::set('captcha.sitekey', $bs->google_recaptcha_site_key);
        Config::set('captcha.secret', $bs->google_recaptcha_secret_key);
    }

    public function showLoginForm()
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();
        return view('front.auth.login', $data);
    }

    public function login(Request $request)
    {

        if (Session::has('link')) {
            $redirectUrl = Session::get('link');
            Session::forget('link');
        } else {
            $redirectUrl = route('user-dashboard');
        }


        //--- Validation Section
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }

        $bs = $currentLang->basic_setting;
        $be = $currentLang->basic_extended;



        $rules = [
            'email'   => 'required|email',
            'password' => 'required'
        ];

        if ($bs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }
        $messages = [
            'g-recaptcha-response.required' => __('Please verify that you are not a robot') . '.',
            'g-recaptcha-response.captcha' => __('Captcha error! try again later or contact site admin') . '.',
        ];
        $request->validate($rules, $messages);
        //--- Validation Section Ends

        $user = User::where('email', $request->email)->first();
        // dd($user);
        if (empty($user)) {
            return back()->with('err', __("Credentials Does not Match") . '!')->withInput();
        }
        if ($user->login_attempt_time !== null) {
            $userTime = Carbon::parse($user->login_attempt_time);
            $minutesPassed = $userTime->diffInMinutes(Carbon::now());

            if ($minutesPassed >= 15) {
                $user->update([
                    'status' => 1,
                ]);
            }
        }
        // Attempt to log the user in
        if (Auth::guard('web')->attempt(['email' => $request->email, 'password' => $request->password])) {


            // Check If Email is verified or not
            if (Auth::guard('web')->user()->email_verified == 0 || Auth::guard('web')->user()->email_verified == 0) {
                Auth::guard('web')->logout();

                Session::flash('error', __('Please verify your account'));
                return redirect()->route('user.login');
            }
            if (Auth::guard('web')->user()->status == '0') {
                Auth::guard('web')->logout();

                Session::flash('error', __('Your account has been banned') . '!');
                return redirect()->route('user.login');
            }
            $user->login_attempts = 0;
            $user->login_attempt_time = null;
            $user->save();
            return redirect($redirectUrl);
        }
        $abs = BasicSetting::select('user_login_attempts')->first();

        $user->update([
            'login_attempts' => $user->login_attempts + 1,
            'login_attempt_time' => Carbon::now()
        ]);



        // checking how many time user attempt to login, for deactive this account
        if ($user->login_attempts >= $abs->user_login_attempts) {
            $user->update([
                'status' => 0,
            ]);

            Session::flash('error', __('Too many attempt, your account has been banned for 15 minutes'));
            return redirect()->route('user.login');
        }

        // if unsuccessful, then redirect back to the login with the form data
        return back()->with('err', __("Credentials Does not Match") . '!')->withInput();
    }

    public function logout()
    {
        Auth::guard('web')->logout();
        return redirect('/');
    }
}
