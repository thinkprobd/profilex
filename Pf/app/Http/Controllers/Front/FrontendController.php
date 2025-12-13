<?php

namespace App\Http\Controllers\Front;

require_once __DIR__ . '/../../../../vendor/Transliterator/Transliterator.php';
require_once __DIR__ . '/../../../../vendor/vcard/VCard.php';

use Config;
use Validator;
use Carbon\Carbon;
use App\Models\Faq;
use App\Models\Seo;
use App\Models\Blog;
use App\Models\Page;
use App\Models\User;
use App\Models\Feature;
use App\Models\Package;
use App\Models\Partner;
use App\Models\Process;
use App\Models\Language;
use App\Models\Bcategory;
use App\Models\Subscriber;
use App\Models\Testimonial;
use App\Models\User\UserCv;
use App\Models\User\Gallery;
use App\Models\User\UserDay;
use Illuminate\Http\Request;
use App\Models\BasicExtended;
use App\Models\User\Category;
use App\Models\BasicSetting as BS;
use App\Models\OfflineGateway;
use App\Models\PaymentGateway;
use App\Models\User\FormInput;
use App\Models\User\UserVcard;
use App\Models\BasicExtended as BE;
use App\Http\Helpers\MegaMailer;
use App\Models\User\UserHoliday;
use App\Models\User\WorkProcess;
use App\Models\User\HomePageText;
use App\Models\User\UserTimeSlot;
use JeroenDesloovere\VCard\VCard;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User\UserCustomDomain;
use App\Models\User\AppointmentBooking;
use App\Models\User\Feature as UserFeature;
use Illuminate\Support\Facades\Session;
use App\Models\User\Language as UserLanguage;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\User\UserOfflinePaymentGateway;

class FrontendController extends Controller
{
    public function __construct()
    {
        $bs = BS::first();
        $be = BE::first();

        Config::set('captcha.sitekey', $bs->google_recaptcha_site_key);
        Config::set('captcha.secret', $bs->google_recaptcha_secret_key);
        Config::set('mail.host', $be->smtp_host);
        Config::set('mail.port', $be->smtp_port);
        Config::set('mail.username', $be->smtp_username);
        Config::set('mail.password', $be->smtp_password);
        Config::set('mail.encryption', $be->encryption);
    }

    public function index()
    {
        $currentLang = null;

        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        }

        // If $lang is still null, fallback to the default language
        if (is_null($currentLang)) {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $lang_id = $currentLang->id;

        $data['processes'] = Process::where('language_id', $lang_id)->orderBy('serial_number', 'ASC')->get();
        $data['features'] = Feature::where('language_id', $lang_id)->orderBy('serial_number', 'ASC')->get();
        $data['featured_users'] = User::where([
            ['featured', 1],
            ['status', 1]
        ])
            ->whereHas('memberships', function ($q) {
                $q->where('status', '=', 1)
                    ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
            })->orderBy('feature_time', 'DESC')->get();


        $data['templates'] = User::where([
            ['preview_template', 1],
            ['status', 1],
            ['online_status', 1]
        ])
            ->whereHas('memberships', function ($q) {
                $q->where('status', '=', 1)
                    ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
            })->orderBy('template_serial_number', 'ASC')->get();


        $data['testimonials'] = Testimonial::where('language_id', $lang_id)
            ->orderBy('serial_number', 'ASC')
            ->get();
        $data['blogs'] = Blog::where('language_id', $lang_id)->orderBy('id', 'DESC')->take(2)->get();

        $data['partners'] = Partner::where('language_id', $lang_id)
            ->orderBy('serial_number', 'ASC')
            ->get();

        $data['seo'] = Seo::where('language_id', $lang_id)->first();

        $terms = [];
        if (Package::query()->where('status', '1')->where('featured', '1')->where('term', 'monthly')->count() > 0) {
            $terms[] = 'Monthly';
        }
        if (Package::query()->where('status', '1')->where('featured', '1')->where('term', 'yearly')->count() > 0) {
            $terms[] = 'Yearly';
        }
        if (Package::query()->where('status', '1')->where('featured', '1')->where('term', 'lifetime')->count() > 0) {
            $terms[] = 'Lifetime';
        }
        $data['terms'] = $terms;

        $be = BasicExtended::select('package_features')->firstOrFail();
        $allPfeatures = $be->package_features ? $be->package_features : "[]";
        $data['allPfeatures'] = json_decode($allPfeatures, true);
        return view('front.index', $data);
    }

    public function userGallery($domain)
    {
        $user = getUser();
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();

            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {

            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['galleries'] = Gallery::where([['language_id', $userCurrentLang->id], ['user_id', $user->id]])
            ->orderBy('serial_number', 'ASC')
            ->get();

        $user = getUser();
        $ubs = User\BasicSetting::where('user_id', $user->id)->firstOrFail();

        if ($ubs->theme == 9) {
            $data['folder'] = "profile1.theme9";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 10) {
            $data['folder'] = "profile1.theme10";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 11) {
             $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            $data['folder'] = "profile1.theme11";
        } elseif ($ubs->theme == 12) {
            $data['folder'] = "profile1.theme12";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } else {
            $data['folder'] = "profile1.theme9";
        }
        return view('user.profile.gallery', $data);
    }

    public function templates()
    {
        $currentLang = null;

        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        }

        // If $lang is still null, fallback to the default language
        if (is_null($currentLang)) {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $lang_id = $currentLang->id;

        $data['seo'] = Seo::where('language_id', $lang_id)->first();


        $data['templates'] = User::where([
            ['preview_template', 1],
            ['status', 1],
            ['online_status', 1]
        ])
            ->whereHas('memberships', function ($q) {
                $q->where('status', '=', 1)
                    ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
            })->orderBy('template_serial_number', 'ASC')->get();


        return view('front.templates', $data);
    }

    public function cvtemplates()
    {
        $currentLang = null;

        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        }

        // If $lang is still null, fallback to the default language
        if (is_null($currentLang)) {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $lang_id = $currentLang->id;

        $data['seo'] = Seo::where('language_id', $lang_id)->first();

        $data['cvs'] = UserCv::where('status', '1')
            ->where('preview_template_status', '1')
            ->orderBy('preview_template_serial_number', 'ASC')
            ->get();

        return view('front.cv', $data);
    }

    public function vcards()
    {
        $currentLang = null;

        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        }

        // If $lang is still null, fallback to the default language
        if (is_null($currentLang)) {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $lang_id = $currentLang->id;

        $data['seo'] = Seo::where('language_id', $lang_id)->first();

        $data['vcards'] = UserVcard::where('status', '1')->where('preview_template_status', '1')
            ->orderBy('preview_template_serial_number', 'ASC')
            ->get();

        return view('front.vcards', $data);
    }

    public function subscribe(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:subscribers'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        $subsc = new Subscriber;
        $subsc->email = $request->email;
        $subsc->save();
        return response()->json(['message' => __('You are subscribed successfully')], 200);
    }

    public function loginView()
    {

        return view('front.login');
    }

    public function checkUsername($username)
    {
        $count = User::where('username', $username)->count();
        $status = $count > 0 ? true : false;
        return response()->json($status);
    }

    public function step1($status, $id)
    {
        Session::forget('coupon');
        Session::forget('coupon_amount');
        if (Auth::check()) {
            return redirect()->route('user.plan.extend.index');
        }
        $data['status'] = $status;
        $data['id'] = $id;
        $package = Package::findOrFail($id);
        $data['package'] = $package;

        $hasSubdomain = false;
        $features = [];
        if (!empty($package->features)) {
            $features = json_decode($package->features, true);
        }
        if (is_array($features) && in_array('Subdomain', $features)) {
            $hasSubdomain = true;
        }
        $data['hasSubdomain'] = $hasSubdomain;
        return view('front.step', $data);
    }

    public function step2(Request $request)
    {

        $data = $request->session()->get('data');

        if (session()->has('coupon_amount')) {
            $data['cAmount'] = session()->get('coupon_amount');
        } else {
            $data['cAmount'] = 0;
        }
        return view('front.checkout', $data);
    }

    public function checkout(Request $request)
    {

        $this->validate($request, [
            'username' => 'required|alpha_num|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed'
        ]);
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $seo = Seo::where('language_id', $currentLang->id)->first();
        $be = $currentLang->basic_extended;
        $data['bex'] = $be;
        $data['username'] = $request->username;
        $data['email'] = $request->email;
        $data['password'] = $request->password;
        $data['status'] = $request->status;
        $data['id'] = $request->id;
        $online = PaymentGateway::query()->where('status', 1)->get();
        $offline = OfflineGateway::where('status', 1)->get();
        $data['offline'] = $offline;
        $data['payment_methods'] = $online->merge($offline);
        $data['package'] = Package::query()->findOrFail($request->id);
        $data['seo'] = $seo;
        $request->session()->put('data', $data);
        return redirect()->route('front.registration.step2');
    }


    // packages start
    public function pricing(Request $request)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $data['bex'] = BE::first();
        $data['abs'] = BS::first();

        $terms = [];
        if (Package::query()->where('status', '1')->where('term', 'monthly')->count() > 0) {
            $terms[] = 'Monthly';
        }
        if (Package::query()->where('status', '1')->where('term', 'yearly')->count() > 0) {
            $terms[] = 'Yearly';
        }
        if (Package::query()->where('status', '1')->where('term', 'lifetime')->count() > 0) {
            $terms[] = 'Lifetime';
        }
        $data['terms'] = $terms;

        $be = BasicExtended::select('package_features')->firstOrFail();
        $allPfeatures = $be->package_features ? $be->package_features : "[]";
        $data['allPfeatures'] = json_decode($allPfeatures, true);

        return view('front.pricing', $data);
    }

    // blog section start
    public function blogs(Request $request)
    {

        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $data['currentLang'] = $currentLang;

        $lang_id = $currentLang->id;

        $category = $request->category;
        if (!empty($category)) {
            $data['category'] = Bcategory::findOrFail($category);
        }
        $term = $request->term;

        $data['bcats'] = Bcategory::where('language_id', $lang_id)->where('status', 1)->orderBy('serial_number', 'ASC')->get();


        $data['blogs'] = Blog::when($category, function ($query, $category) {
            return $query->where('bcategory_id', $category);
        })
            ->when($term, function ($query, $term) {
                return $query->where('title', 'like', '%' . $term . '%');
            })
            ->when($currentLang, function ($query, $currentLang) {
                return $query->where('language_id', $currentLang->id);
            })->orderBy('serial_number', 'ASC')->paginate(3);

        return view('front.blogs', $data);
    }

    public function blogdetails($slug, $id)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }

        $lang_id = $currentLang->id;


        $data['blog'] = Blog::findOrFail($id);
        $data['bcats'] = Bcategory::where('status', 1)->where('language_id', $lang_id)->orderBy('serial_number', 'ASC')->get();


        return view('front.blog-details', $data);
    }

    public function contactView()
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        return view('front.contact', $data);
    }

    public function faqs()
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $lang_id = $currentLang->id;
        $data['faqs'] = Faq::where('language_id', $lang_id)
            ->orderBy('serial_number', 'DESC')
            ->get();
        return view('front.faq', $data);
    }

    public function dynamicPage($slug)
    {
        $data['page'] = Page::where('slug', $slug)->firstOrFail();

        return view('front.dynamic', $data);
    }

    public function users(Request $request)
    {

        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $homeTexts = [];
        if (!empty($request->designation)) {
            $homeTexts = HomePageText::when($request->designation, function ($q) use ($request) {
                return $q->where('designation', 'like', '%' . $request->designation . '%');
            })->select('user_id')->get();
        }

        $userIds = [];

        foreach ($homeTexts as $key => $homeText) {
            if (!in_array($homeText->user_id, $userIds)) {
                $userIds[] = $homeText->user_id;
            }
        }

        $data['users'] = null;
        $users = User::where('online_status', 1)
            ->whereHas('memberships', function ($q) {
                $q->where('status', '=', 1)
                    ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
            })
            ->whereHas('permissions', function ($q) {
                $q->where('permissions', 'LIKE', '%"Profile Listing"%');
            })
            ->when($request->search, function ($q) use ($request) {
                return $q->where(function ($query) use ($request) {
                    $query->where('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('username', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->location, function ($q) use ($request) {
                return $q->where(function ($query) use ($request) {
                    $query->where('city', 'like', '%' . $request->location . '%')
                        ->orWhere('country', 'like', '%' . $request->location . '%');
                });
            })
            ->when($request->designation, function ($q) use ($userIds) {
                return $q->where(function ($query) use ($userIds) {
                    $query->whereIn('id', $userIds);
                });
            })

            ->orderBy('id', 'DESC')
            ->paginate(9);

        $data['users'] = $users;
        return view('front.users', $data);
    }

    public function userDetailView($domain)
    {

        $user = getUser();
        if (Auth::check() && Auth::user()->id != $user->id && $user->online_status != 1) {
            return redirect()->route('front.index');
        } elseif (!Auth::check() && $user->online_status != 1) {
            return redirect()->route('front.index');
        }

        $package = UserPermissionHelper::userPackage($user->id);
        if (is_null($package)) {
            Session::flash('warning', 'User membership is expired');
            if (Auth::check()) {
                return redirect()->route('user-dashboard')->with('error', 'User membership is expired');
            } else {
                return redirect()->route('front.index');
            }
        }

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();

            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {

            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }


        $data['home_text'] = User\HomePageText::query()->where([
            ['user_id', $user->id],
            ['language_id', $userCurrentLang->id]
        ])->first();
        $data['portfolios'] = $user->portfolios()
            ->where('language_id', $userCurrentLang->id)
            ->where('featured', 1)
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);

        $data['portfolio_categories'] = $user->portfolioCategories()
            ->whereHas('portfolios', function ($q) {
                $q->where('featured', 1);
            })->where('language_id', $userCurrentLang->id)->where('status', 1)->orderBy('serial_number', 'ASC')->get() ?? collect([]);
        $data['skills'] = $user->skills()->where('language_id', $userCurrentLang->id)->orderBy('serial_number', 'ASC')->get() ?? collect([]);
        $data['achievements'] = $user->achievements()->where('language_id', $userCurrentLang->id)->orderBy('serial_number', 'ASC')->get() ?? collect([]);
        $data['services'] = $user->services()->where([
            ['lang_id', $userCurrentLang->id],
            ['featured', 1]
        ])
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);
        $data['testimonials'] = $user->testimonials()->where('lang_id', $userCurrentLang->id)->orderBy('serial_number', 'ASC')->get() ?? collect([]);


        $data['job_experiences'] = $user->job_experiences()
            ->where('lang_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);
        $data['educations'] = $user->educations()
            ->where('lang_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);


        $data['user'] = $user;

        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();

        $data['workprocess'] = WorkProcess::where('user_id', $user->id)
            ->where('language_id', $userCurrentLang->id)
            ->get();
        $data['categories'] = Category::where([
            ['user_id', $user->id],
            ['language_id', $userCurrentLang->id]
        ])
            ->where('is_featured', 1)
            ->latest()
            ->get();

        if ($ubs->theme == 10 || $ubs->theme == 11 || $ubs->theme == 12) {
            $data['features'] = UserFeature::where([['language_id', $userCurrentLang->id], ['user_id', $user->id]])
                ->orderBy('serial_number', 'ASC')
                ->get();
            $data['inputs'] = FormInput::where('user_id', $user->id)->where('category_id', null)->get();
        }

        if ($ubs->theme != 12) {
            $data['blogs'] = $user->blogs()
                ->where('language_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'asc')
                ->take(3)
                ->get() ?? collect([]);
        } else {
            $data['blogs'] = $user->blogs()
                ->where('language_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'asc')
                ->take(4)
                ->get() ?? collect([]);
        }

        if ($ubs->theme == 12) {
            $data['time_slots'] = UserTimeSlot::where('user_id', $user->id)
                ->get();
            $data['user_days'] = UserDay::where('user_id', $user->id)
                ->get();
        }
        if ($ubs->theme == 11) {
            $data['galleries'] = Gallery::where([['language_id', $userCurrentLang->id], ['user_id', $user->id]])
                ->orderBy('serial_number', 'ASC')
                ->get();
        }

        $themes = [
            1 => 'user.profile1.index',
            2 => 'user.profile1.index2',
            3 => 'user.profile1.theme3.index',
            4 => 'user.profile1.theme4.index',
            5 => 'user.profile1.theme5.index',
            6 => 'user.profile1.theme6.index',
            7 => 'user.profile1.theme7.index',
            8 => 'user.profile1.theme8.index',
            9 => 'user.profile1.theme9.index',
            10 => 'user.profile1.theme10.index',
            11 => 'user.profile1.theme11.index',
            12 => 'user.profile1.theme12.index',
        ];

        $view = $themes[$ubs->theme] ?? 'user.profile.profile';

        return view($view, $data);
    }

    public function userAbout($domain)
    {
        $user = getUser();
        $id = $user->id;

        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();
        if ($ubs->theme != 3) {
            return view('errors.404');
        }

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }
        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();
        $data['achievements'] = $user->achievements()->where('language_id', $userCurrentLang->id)->orderBy('serial_number', 'ASC')->get() ?? collect([]);
        return view('user.profile1.theme3.about', $data);
    }

    public function paymentInstruction(Request $request)
    {

        $offline = OfflineGateway::where('name', $request->name)
            ->select('short_description', 'instructions', 'is_receipt')
            ->first();
        // dd($offline);
        return response()->json([
            'description' => $offline->short_description,
            'instructions' => $offline->instructions,
            'is_receipt' => $offline->is_receipt
        ]);
    }

    public function contactMessage($domain, Request $request)
    {
        $user = getUser();
        $keywords = getUserLanguageKeywords($user);

        $rules = [
            'fullname' => 'required',
            'email' => 'required|email:rfc,dns',
            'subject' => 'required',
            'message' => 'required'
        ];

        $messages = [
            'fullname.required' => $keywords['fullname_required'] ?? __('Full Name is required') . '.',
            'email.required' => $keywords['email_required'] ?? __('Email is required') . '.',
            'email.email' => $keywords['email_invalid'] ?? __('Please enter a valid email address') . '.',
            'subject.required' => $keywords['subject_required'] ?? __('Subject is required') . '.',
            'message.required' => $keywords['message_required'] ?? __('Message is required') . '.',
        ];


        $request->validate($rules, $messages);

        if (!empty($request->type) && $request->type == 'vcard') {

            $data['toMail'] = $request->to_mail;
            $data['toName'] = $request->to_name;
        } else {

            $toUser = User::query()->findOrFail($request->id);

            $data['toMail'] = $toUser->email;
            $data['toName'] = $toUser->username;
        }


        $data['subject'] = $request->subject;
        $data['body'] = "<div>$request->message</div><br>
                         <strong>For further contact with the enquirer please use the below information:</strong><br>
                         <strong>Enquirer Name:</strong> $request->fullname <br>
                         <strong>Enquirer Mail:</strong> $request->email <br>
                         ";

        $mailer = new MegaMailer();
        $mailer->mailContactMessage($data);

        $message = isset($keywords['Mail_sent_successfully']) ? $keywords['Mail_sent_successfully'] . '!' : __('Mail sent successfully!');
        Session::flash('success',  $message);

        return back();
    }

    public function adminContactMessage(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email:rfc,dns',
            'subject' => 'required',
            'message' => 'required'
        ];

        $bs = BS::select('is_recaptcha')->first();
        if ($bs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }
        $messages = [
            'g-recaptcha-response.required' => __('Please verify that you are not a robot') . '.',
            'g-recaptcha-response.captcha' => __('Captcha error') . '! ' . __('try again later or contact site admin') . '.',
        ];

        $request->validate($rules, $messages);
        $be =  BE::firstOrFail();
        $from = $request->email;
        $to = $be->to_mail;
        $subject = $request->subject;
        $message = $request->message;
        try {
            $mail = new PHPMailer(true);

            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $be->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $be->smtp_username;
            $mail->Password = $be->smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Set From and To addresses
            $mail->setFrom($from, $request->name);
            $mail->addAddress($to);
            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->CharSet = "UTF-8";

            // Send the email
            $mail->send();
            Session::flash('success', __('Message sent successfully'));
        } catch (\Exception $e) {

            Session::flash('success', $e->getMessage());
        }

        return back();
    }

    public function userServices($domain)
    {
        $user = getUser();
        $id = $user->id;

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();

        $data['services'] = User\UserService::query()
            ->where('user_id', $id)
            ->where('lang_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->get();

        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();
        $data['layout'] = 'theme' . $ubs->theme;


        $themes = [
            1 => 'user.profile1.services',
            2 => 'user.profile1.services2',
            3 => 'user.profile1.theme3.services',
            4 => 'user.profile1.theme4.services',
            5 => 'user.profile1.theme5.services',
            6 => 'user.profile1.theme6-8.services',
            7 => 'user.profile1.theme6-8.services',
            8 => 'user.profile1.theme6-8.services',
            9 => 'user.profile1.theme9.services',
            10 => 'user.profile1.theme10.services',
            11 => 'user.profile1.theme11.services',
            12 => 'user.profile1.theme12.services',
        ];

        $view = $themes[$ubs->theme] ?? 'user.profile.services';
        return view($view, $data);
    }

    public function userServiceDetail($domain, $slug, $id)
    {
        $data['service'] = User\UserService::query()->findOrFail($id);
        $userId = $data['service']->user_id;

        $ubs = User\BasicSetting::select('theme')->where('user_id', $userId)->firstOrFail();
        if ($ubs->theme == 1 || $ubs->theme == 2) {
            $data['folder'] = "profile1";
        } elseif ($ubs->theme == 3) {
            $data['folder'] = "profile1.theme3";
        } elseif ($ubs->theme == 4) {
            $data['folder'] = "profile1.theme4";
        } elseif ($ubs->theme == 5) {
            $data['folder'] = "profile1.theme5";
        } elseif ($ubs->theme == 6 || $ubs->theme == 7 || $ubs->theme == 8) {
            $data['folder'] = "profile1.theme9";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 9) {
            $data['folder'] = "profile1.theme9";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 10) {
            $data['folder'] = "profile1.theme10";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 11) {
            $data['folder'] = "profile1.theme11";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 12) {
            $data['folder'] = "profile1.theme12";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } else {
            $data['folder'] = "profile";
        }

        return view('user.profile-common.service-details', $data);
    }

    public function userExperience($domain)
    {
        $user = getUser();
        $id = $user->id;

        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();
        if ($ubs->theme != 3) {
            return view('errors.404');
        }

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();

        $data['job_experiences'] = $user->job_experiences()
            ->where('lang_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);
        $data['educations'] = $user->educations()
            ->where('lang_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);

        return view('user.profile1.theme3.experience', $data);
    }




    public function userTestimonial($domain)
    {
        $user = getUser();
        $id = $user->id;

        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();
        if ($ubs->theme != 3) {
            return view('errors.404');
        }

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();

        $data['testimonials'] = $user->testimonials()->where('lang_id', $userCurrentLang->id)->orderBy('serial_number', 'ASC')->get() ?? collect([]);

        return view('user.profile1.theme3.testimonial', $data);
    }

    public function userBlogs(Request $request, $domain)
    {
        $user = getUser();
        $id = $user->id;
        $data['user'] = $user;
        $catid = $request->category;
        $term = $request->term;

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();


        $data['blogs'] = User\Blog::query()
            ->when($catid, function ($query, $catid) {
                return $query->where('category_id', $catid);
            })
            ->when($term, function ($query, $term) {
                return $query->where('title', 'LIKE', '%' . $term . '%');
            })
            ->where('user_id', $id)
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->paginate(6);


        $data['latestBlogs'] = User\Blog::query()
            ->where('user_id', $id)
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('id', 'DESC')
            ->limit(3)->get();

        $data['blog_categories'] = User\BlogCategory::query()
            ->where('status', 1)
            ->orderBy('serial_number', 'ASC')
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $id)
            ->get();

        $data['allCount'] = User\Blog::query()
            ->where('user_id', $id)
            ->where('language_id', $userCurrentLang->id)
            ->count();

        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();


        // Blogs views
        $blogThemes = [
            1 => 'user.profile1.blogs',
            2 => 'user.profile1.blogs2',
            3 => 'user.profile1.theme3.blogs',
            4 => 'user.profile1.theme4.blogs',
            5 => 'user.profile1.theme5.blogs',
            6 => 'user.profile1.theme6-8.blogs',
            7 => 'user.profile1.theme6-8.blogs',
            8 => 'user.profile1.theme6-8.blogs',
            9 => 'user.profile1.theme9.blogs',
            10 => 'user.profile1.theme10.blogs',
            11 => 'user.profile1.theme11.blogs',
            12 => 'user.profile1.theme12.blogs',
        ];

        $data['layout'] = in_array($ubs->theme, [6, 7, 8]) ? 'theme' . $ubs->theme : null;
        $blogView = $blogThemes[$ubs->theme] ?? 'user.profile.blogs';

        // Return blogs view
        return view($blogView, $data);
    }


    public function appointment(Request $request, $domain)
    {

        $user = getUser();
        $ubs = User\BasicSetting::where('user_id', $user->id)->firstOrFail();
        if (!Auth::guard('customer')->check()) {
            if ($ubs->guest_checkout == 1) {
                if ($request->type != 'guest') {
                    Session::put('redirect_link', route('front.user.appointment', getParam()));
                    return redirect(route('customer.login', [getParam(), 'redirected' => 'appointment']));
                }
            } elseif ($ubs->guest_checkout == 0) {
                Session::put('redirect_link', route('front.user.appointment', getParam()));
                return redirect(route('customer.login', [getParam(), 'redirected' => 'appointment']));
            }
        }

        $id = $user->id;
        $data['user'] = $user;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }
        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();


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
            $data['folder'] = 'profile1.theme9';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 10) {
            $data['folder'] = 'profile1.theme10';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 11) {
            $data['folder'] = 'profile1.theme11';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            if ($ubs->appointment_category == 1) {
                $data['categories'] = Category::where([
                    ['user_id', $id],
                    ['language_id', $userCurrentLang->id]
                ])->get();
                return view('user.profile1.theme11.appointment', $data);
            }
        } elseif ($ubs->theme == 12) {
            $data['folder'] = 'profile1.theme12';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } else {
            $data['folder'] = "profile";
        }

        if ($ubs->appointment_category == 1) {
            $data['categories'] = Category::where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->get();
            return view('user.profile-common.appointment-category', $data);
        } else {
            $data['inputs'] = FormInput::where('user_id', $id)->where('category_id', null)->get();
            return view('user.profile-common.appointment-form', $data);
        }
    }

    public function appointmentForm($domain, $cat)
    {
        $user = getUser();
        $id = $user->id;
        $data['user'] = $user;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }
        $ubs = User\BasicSetting::where('user_id', $user->id)->firstOrFail();
        if ($ubs->theme == 1 || $ubs->theme == 2) {
            $data['folder'] = "profile1";
        } elseif ($ubs->theme == 3) {
            $data['folder'] = "profile1.theme3";
        } elseif ($ubs->theme == 4) {
            $data['folder'] = "profile1.theme4";
        } elseif ($ubs->theme == 6 || $ubs->theme == 7 || $ubs->theme == 8) {
            $data['folder'] = 'profile1.theme' . $ubs->theme;
        } elseif ($ubs->theme == 5) {
            $data['folder'] = "profile1.theme5";
        } elseif ($ubs->theme == 9) {
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            $data['folder'] = 'profile1.theme9';
        } elseif ($ubs->theme == 10) {
            $data['folder'] = 'profile1.theme10';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 11) {
            $data['folder'] = 'profile1.theme11';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 12) {
            $data['folder'] = 'profile1.theme12';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } else {
            $data['folder'] = "profile";
        }

        $data['cat'] = $cat;
        $data['inputs'] = FormInput::where('user_id', $id)->where('category_id', $cat)->get();

        return view('user.profile-common.appointment-form', $data);
    }

    public function appointmentBookingStep1($domain, Request $request)
    {

        $user = getUser();
        $keywords = getUserLanguageKeywords($user);

        $id = $user->id;
        if (empty($request->category_id)) {
            $data['inputs'] = FormInput::where('user_id', $id)->where('category_id', null)->get();
        } else {
            $data['inputs'] = FormInput::where('user_id', $id)->where('category_id', $request->category_id)->get();
        }

        $fields = [];
        $messages = [];
        $rules = [];
        $rules['name'] = 'required';
        $rules['email'] = 'required';

        // Custom messages for name and email required fields
        $messages['name.required'] = $keywords['name_required'] ?? __('The name field is required') . '.';
        $messages['email.required'] = $keywords['email_required'] ?? __('The email field is required') . '.';

        foreach ($data['inputs'] as $input) {
            if ($input->required == 1) {
                $rules["$input->name"] = 'required';


                $label = str_replace('_', ' ', $input->name);
                $formInputMessage = ($keywords['the'] ?? __('The')) . ' ' . $label . ' ' . ($keywords['field_is_required'] ?? __('field is required')) . '.';

                $messages[$input->name . '.required'] = $formInputMessage;
            }
            // check if input type is 3, then check for minimum 1 selected
            if ($input->type == 3) {
                $rules["$input->name" . ".*"] = 'string|min:1';
            }
            // check if input type is 5, then check for allowed extension
            if ($input->type == 5) {
                if ($request->hasFile("$input->name")) {
                    $ext = $request->file("$input->name")->getClientOriginalExtension();
                    $allowedExts = explode(',', $request->file_extensions);

                    $onlyText = $keywords['only'] ?? __('Only');
                    $filesAreAllowedText = $keywords['files_are_allowed'] ?? __('files are allowed');

                    $rules["$input->name"] = [
                        function ($attribute, $value, $fail) use ($allowedExts, $ext, $request, $keywords, $onlyText, $filesAreAllowedText) {
                            if (!in_array($ext, $allowedExts)) {
                                $fail($onlyText . ' ' . $request->file_extensions . ' ' . $filesAreAllowedText);
                            }
                        }
                    ];
                };
            }

            $label = str_replace('_', ' ', $input->name);
            $formInputMessage = ($keywords['the'] ?? __('The')) . ' ' . $label . ' ' . ($keywords['field_is_required'] ?? __('field is required')) . '.';

            $messages[$input->name . '.required'] = $formInputMessage;

            $in_name = $input->name;
            // if the input is file, then move it to 'files' folder
            if ($input->type == 5) {
                if ($request->hasFile("$in_name")) {
                    $fileName = uniqid() . '.' . $request->file("$in_name")->getClientOriginalExtension();
                    $directory = public_path('assets/front/files/appointment/');
                    @mkdir($directory, 0775, true);
                    $request->file("$in_name")->move($directory, $fileName);
                    $fields["$in_name"]['value'] = $fileName;
                    $fields["$in_name"]['type'] = $input->type;
                }
            } else {
                if ($request["$in_name"]) {
                    $fields["$in_name"]['value'] = $request["$in_name"];
                    $fields["$in_name"]['type'] = $input->type;
                }
                if ($input->type == 3) {
                    $fields["$in_name"]['value'] = $request["$in_name"];
                    $fields["$in_name"]['type'] = $input->type;
                }
            }
        }
        $request->validate($rules, $messages);
        $user_request = [];
        $user_request['customer_form'] = $fields;
        // $user_request['customer_form'] = $request->except('_token', 'name', 'email', 'category_id');
        $user_request['name'] = $request->name;
        $user_request['email'] = $request->email;
        $user_request['category_id'] = $request->category_id;
        Session::put('user_request', $user_request);
        $us = Session::get('user_request');

        $data['user'] = $user;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }
        $ubs = User\BasicSetting::where('user_id', $user->id)->firstOrFail();
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
        } else {
            $data['folder'] = "profile";
        }
        return redirect()->route('front.user.appointment.booking', getParam());
    }

    public function appointmentBookingStep2($domain)
    {

        $user = getUser();

        $data['user'] = $user;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }
        $ubs = User\BasicSetting::where('user_id', $user->id)->firstOrFail();
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
        } elseif ($ubs->theme == 10) {
            $data['folder'] = 'profile1.theme10';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 9) {
            $data['folder'] = 'profile1.theme9';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 11) {
            $data['folder'] = 'profile1.theme11';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 12) {
            $data['folder'] = 'profile1.theme12';
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } else {
            $data['folder'] = "profile";
        }

        // get today
        $data['today'] = date('Y-m-d');
        // get toDay Name
        $day = strtolower(Carbon::parse(date('Y-m-d'))->format('l'));

        // check if todays is holiday.
        $holidays = UserHoliday::where('user_id', $user->id)
            ->where('date', date('m/d/Y'))->first();
        // check if todays is weekend.
        $isWeekend =  UserDay::where('day', $day)->where('user_id', $user->id)->first();

        $data['timeSlots']  = [];
        // if today is not holiday get todays timeslots
        if (empty($holidays) && ($isWeekend && $isWeekend->weekend == 0)) {
            // get toDay's timeslots
            $data['timeSlots'] = UserTimeSlot::where('user_id', $user->id)->where('day', $day)->get();
        }
        return view('user.profile-common.appointment-booking', $data);
    }
    public function getTimeSlot($domain, Request $request)
    {
        $user = getUser();
        $day = strtolower(Carbon::parse($request->date)->format('l'));
        $timeSlots = UserTimeSlot::where('user_id', $user->id)->where('day', $day)->get();
        return $timeSlots;
    }
    public function checkThisSlot($domain, Request $request)
    {
        $user = getUser();
        $timeSlots = UserTimeSlot::where('user_id', $user->id)->where('id', $request->slotId)->first();
        $max_booking_limit  = $timeSlots->max_booking;
        $slt = ($timeSlots->start . ' - ' . $timeSlots->end);
        $countAppointment = AppointmentBooking::where('user_id', $user->id)->where('date', $request->date)->where('time', $slt)->where('status', '!=', 4)->get();
        $countAppointment = count($countAppointment);
        if (!empty($max_booking_limit)) {
            if ($max_booking_limit == $countAppointment) {
                return 'booked';
            }
        }
        // $day = strtolower(Carbon::parse($request->date)->format('l'));
        // return true;
    }
    public function userBlogDetail($domain, $slug, $id)
    {
        $user = getUser();
        $userId = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['blog'] = User\Blog::query()->findOrFail($id);
        $data['latestBlogs'] = User\Blog::query()
            ->where('user_id', $userId)
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('id', 'DESC')
            ->limit(3)->get();
        $data['blog_categories'] = User\BlogCategory::query()
            ->where('status', 1)
            ->orderBy('serial_number', 'ASC')
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $userId)
            ->get();
        $data['allCount'] = User\Blog::query()
            ->where('user_id', $userId)
            ->where('language_id', $userCurrentLang->id)
            ->count();
        $userId = $data['blog']->user_id;
        $ubs = User\BasicSetting::select('theme')->where('user_id', $userId)->firstOrFail();
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
            $data['folder'] = "profile1.theme9";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 10) {
            $data['folder'] = "profile1.theme10";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 11) {
            $data['folder'] = "profile1.theme11";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } elseif ($ubs->theme == 12) {
            $data['folder'] = "profile1.theme12";
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
        } else {
            $data['folder'] = "profile";
        }
        return view('user.profile-common.blog-details', $data);
    }
    public function userPortfolios(Request $request, $domain)
    {
        $user = getUser();
        $id = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();
        $data['portfolio_categories'] = User\PortfolioCategory::query()
            ->where('status', 1)
            ->orderBy('serial_number', 'ASC')
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $id)
            ->get();

        $data['catId'] = $request->category;

        $data['portfolios'] = User\Portfolio::query()
            ->where('user_id', $id)
            ->latest()
            ->orderBy('serial_number', 'ASC')
            ->where('language_id', $userCurrentLang->id)
            ->get();


        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();
        if ($ubs->theme == 1) {
            return view('user.profile1.portfolios', $data);
        } elseif ($ubs->theme == 2) {
            return view('user.profile1.portfolios2', $data);
        } elseif ($ubs->theme == 3) {
            return view('user.profile1.theme3.portfolios', $data);
        } elseif ($ubs->theme == 4) {
            return view('user.profile1.theme4.portfolios', $data);
        } elseif ($ubs->theme == 5) {
            return view('user.profile1.theme5.portfolios', $data);
        } elseif ($ubs->theme == 6 || $ubs->theme == 7 || $ubs->theme == 8) {
            $data['layout'] = 'theme' . $ubs->theme;
            return view('user.profile1.theme6-8.portfolios', $data);
        } elseif ($ubs->theme == 9) {
            return view('user.profile1.theme9.portfolios', $data);
        } elseif ($ubs->theme == 10) {
            return view('user.profile1.theme10.portfolios', $data);
        } elseif ($ubs->theme == 11) {
            return view('user.profile1.theme11.portfolios', $data);
        } elseif ($ubs->theme == 12) {
            return view('user.profile1.theme12.portfolios', $data);
        } else {
            return view('user.profile.portfolios', $data);
        }
    }

    public function userPortfolioDetail($domain, $slug, $id)
    {
        $user = getUser();
        $userId = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $portfolio = User\Portfolio::query()->findOrFail($id);
        $catId = $portfolio->category_id;
        $data['relatedPortfolios'] = User\Portfolio::where('category_id', $catId)->where('id', '<>', $portfolio->id)->where('user_id', $userId)->orderBy('id', 'DESC')->limit(5);
        $data['portfolio'] = $portfolio;
        $data['portfolio_categories'] = User\PortfolioCategory::query()
            ->where('status', 1)
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $userId)
            ->orderBy('serial_number', 'ASC')
            ->get();
        $data['allCount'] = User\Portfolio::where('language_id', $userCurrentLang->id)->where('user_id', $userId)->count();

        $ubs = User\BasicSetting::select('theme')->where('user_id', $userId)->firstOrFail();
        if ($ubs->theme == 1 || $ubs->theme == 2) {
            $data['folder'] = "profile1";
        } elseif ($ubs->theme == 3) {
            $data['folder'] = "profile1.theme3";
        } elseif ($ubs->theme == 4) {
            $data['folder'] = "profile1.theme4";
        } elseif ($ubs->theme == 5) {
            $data['folder'] = "profile1.theme5";
        } elseif ($ubs->theme == 6 || $ubs->theme == 7 || $ubs->theme == 8) {
            $data['layout'] = 'theme' . $ubs->theme;
            return view('user.profile1.theme6-8.portfolio-details', $data);
        } elseif ($ubs->theme == 9) {
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            $data['folder'] = 'profile1.theme9';
        } elseif ($ubs->theme == 10) {
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            $data['folder'] = 'profile1.theme10';
        }elseif ($ubs->theme == 11) {
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            $data['folder'] = "profile1.theme11";
        } elseif ($ubs->theme == 12) {
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            $data['folder'] = "profile1.theme12";
        } else {
            $data['folder'] = "profile";
        }

        return view('user.profile-common.portfolio-details', $data);
    }

    public function userContact($domain)
    {
        $user = getUser();
        $id = $user->id;

        $ubs = User\BasicSetting::select('theme')->where('user_id', $user->id)->firstOrFail();


        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = Language::where('is_default', 1)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id]
            ])->first();

        if ($ubs->theme == 3) {
            return view('user.profile1.theme3.contact', $data);
        } elseif ($ubs->theme == 6 || $ubs->theme == 7 || $ubs->theme == 8 || $ubs->theme == 9 || $ubs->theme == 10 || $ubs->theme == 11 || $ubs->theme == 12) {
            $data['layout'] = 'theme' . $ubs->theme;
            $data['css_file'] = asset('assets/front/theme9-12/css/inner-common.css');
            return view('user.profile1.theme6-8.contact', $data);
        } else {
            return view('errors.404');
        }
    }

    public function changeLanguage($lang): \Illuminate\Http\RedirectResponse
    {
        session()->put('lang', $lang);
        app()->setLocale($lang);
        return redirect()->route('front.index');
    }
    public function changeUserLanguage(Request $request, $domain)
    {
        session()->put('user_lang', $request->code);
        app()->setlocale($request->code);
        return redirect()->back();
    }

    public function vcard($domain, $id)
    {
        $vcard = UserVcard::findOrFail($id);

        $count = $vcard->user->memberships()->where('status', '=', 1)
            ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
            ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'))
            ->count();

        // check if the vcard owner does not have membership
        if ($count == 0) {
            return view('errors.404');
        }

        $cFeatures = UserPermissionHelper::packagePermission($vcard->user_id);
        $cFeatures = json_decode($cFeatures, true);
        if (empty($cFeatures) || !is_array($cFeatures) || !in_array('vCard', $cFeatures)) {
            return view('errors.404');
        }

        $parsedUrl = parse_url(url()->current());
        $host = $parsedUrl['host'];
        // if the current host contains the website domain
        if (strpos($host, env('WEBSITE_HOST')) !== false) {
            $host = str_replace("www.", "", $host);
            // if the current URL is subdomain
            if ($host != env('WEBSITE_HOST')) {
                $hostArr = explode('.', $host);
                $username = $hostArr[0];
                if (strtolower($vcard->user->username) != strtolower($username) || !cPackageHasSubdomain($vcard->user)) {
                    return view('errors.404');
                }
            } else {
                $path = explode('/', $parsedUrl['path']);
                $username = $path[1];
                if (strtolower($vcard->user->username) != strtolower($username)) {
                    return view('errors.404');
                }
            }
        }
        // if the current host doesn't contain the website domain (meaning, custom domain)
        else {
            // Always include 'www.' at the begining of host
            if (substr($host, 0, 4) == 'www.') {
                $host = $host;
            } else {
                $host = 'www.' . $host;
            }
            // if the current package doesn't have 'custom domain' feature || the custom domain is not connected
            $cdomain = UserCustomDomain::where('requested_domain', '=', $host)->orWhere('requested_domain', '=', str_replace("www.", "", $host))->where('status', 1)->firstOrFail();
            $username = $cdomain->user->username;
            if (!cPackageHasCdomain($vcard->user) || ($username != $vcard->user->username)) {
                return view('errors.404');
            }
        }

        $infos = json_decode($vcard->information, true);

        $prefs = [];
        if (!empty($vcard->preferences)) {
            $prefs = json_decode($vcard->preferences, true);
        }

        $keywords = json_decode($vcard->keywords, true);

        $data['vcard'] = $vcard;
        $data['infos'] = $infos;
        $data['prefs'] = $prefs;
        $data['keywords'] = $keywords;
        if ($vcard->template == 1) {
            return view('vcard.index1', $data);
        } elseif ($vcard->template == 2) {
            return view('vcard.index2', $data);
        } elseif ($vcard->template == 3) {
            return view('vcard.index3', $data);
        } elseif ($vcard->template == 4) {
            return view('vcard.index4', $data);
        } elseif ($vcard->template == 5) {
            return view('vcard.index5', $data);
        } elseif ($vcard->template == 6) {
            return view('vcard.index6', $data);
        } elseif ($vcard->template == 7) {
            return view('vcard.index7', $data);
        } elseif ($vcard->template == 8) {
            return view('vcard.index8', $data);
        } elseif ($vcard->template == 9) {
            return view('vcard.index9', $data);
        } elseif ($vcard->template == 10) {
            return view('vcard.index10', $data);
        }
    }

    public function vcardImport($domain, $id)
    {
        $vcard = UserVcard::findOrFail($id);

        // define vcard
        $vcardObj = new VCard();

        // add personal data
        if (!empty($vcard->name)) {
            $vcardObj->addName($vcard->name);
        }
        if (!empty($vcard->company)) {
            $vcardObj->addCompany($vcard->company);
        }
        if (!empty($vcard->occupation)) {
            $vcardObj->addJobtitle($vcard->occupation);
        }
        if (!empty($vcard->email)) {
            $vcardObj->addEmail($vcard->email);
        }
        if (!empty($vcard->phone)) {
            $vcardObj->addPhoneNumber($vcard->phone, 'WORK');
        }
        if (!empty($vcard->address)) {
            $vcardObj->addAddress($vcard->address);
            $vcardObj->addLabel($vcard->address);
        }
        if (!empty($vcard->website_url)) {
            $vcardObj->addURL($vcard->website_url);
        }

        $vcardObj->addPhoto(public_path('assets/front/img/user/vcard/' . $vcard->profile_image));

        return \Response::make(
            $vcardObj->getOutput(),
            200,
            $vcardObj->getHeaders(true)
        );
    }

    public function cv($domain, $id)
    {
        $cv = UserCv::findOrFail($id);


        $count = $cv->user->memberships()->where('status', '=', 1)
            ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
            ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'))->count();
        // check if the cv owner does not have membership
        if ($count == 0) {
            return view('errors.404');
        }


        $cFeatures = UserPermissionHelper::packagePermission($cv->user_id);
        $cFeatures = json_decode($cFeatures, true);
        if (empty($cFeatures) || !is_array($cFeatures) || !in_array('Online CV & Export', $cFeatures)) {
            return view('errors.404');
        }

        $parsedUrl = parse_url(url()->current());
        $host = $parsedUrl['host'];
        // if the current host contains the website domain
        if (strpos($host, env('WEBSITE_HOST')) !== false) {
            $host = str_replace("www.", "", $host);
            // if the current URL is subdomain
            if ($host != env('WEBSITE_HOST')) {
                $hostArr = explode('.', $host);
                $username = $hostArr[0];
                if (strtolower($cv->user->username) != strtolower($username) || !cPackageHasSubdomain($cv->user)) {
                    return view('errors.404');
                }
            } else {
                $path = explode('/', $parsedUrl['path']);
                $username = $path[1];
                if (strtolower($cv->user->username) != strtolower($username)) {
                    return view('errors.404');
                }
            }
        }
        // if the current host doesn't contain the website domain (meaning, custom domain)
        else {
            // Always include 'www.' at the begining of host
            if (substr($host, 0, 4) == 'www.') {
                $host = $host;
            } else {
                $host = 'www.' . $host;
            }
            // if the current package doesn't have 'custom domain' feature || the custom domain is not connected
            $cdomain = UserCustomDomain::where('requested_domain', '=', $host)->orWhere('requested_domain', '=', str_replace("www.", "", $host))->where('status', 1)->firstOrFail();
            $username = $cdomain->user->username;
            if (!cPackageHasCdomain($cv->user) || ($username != $cv->user->username)) {
                return view('errors.404');
            }
        }

        $infos = json_decode($cv->cv_information, true);

        $data['cv'] = $cv;
        $data['infos'] = $infos;




        if ($cv->template == 1) {
            $lsections = $cv->user_cv_sections()->where('column', 1);
            if ($lsections->count() > 0) {
                $lsections = $lsections->orderBy('order', 'ASC')->get();
            } else {
                $lsections = [];
            }


            $rsections = $cv->user_cv_sections()->where('column', 2);
            if ($rsections->count() > 0) {
                $rsections = $rsections->orderBy('order', 'ASC')->get();
            } else {
                $rsections = [];
            }

            $data['lsections'] = $lsections;
            $data['rsections'] = $rsections;
            return view('cv.index1', $data);
        } elseif ($cv->template == 2) {
            $sections = $cv->user_cv_sections();
            if ($sections->count() > 0) {
                $sections = $sections->orderBy('order', 'ASC')->get();
            } else {
                $sections = [];
            }
            $data['sections'] = $sections;
            return view('cv.index2', $data);
        }
    }
}
