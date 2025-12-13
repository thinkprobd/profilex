<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Package;
use App\Models\Language;
use App\Models\Membership;
use App\Models\BasicSetting;
use Illuminate\Http\Request;
use App\Models\BasicExtended;
use App\Models\PaymentGateway;
use App\Http\Helpers\MegaMailer;
use App\Jobs\SubscriptionExpiredMail;
use App\Jobs\SubscriptionReminderMail;
use App\Http\Helpers\UserPermissionHelper;

class CronJobController extends Controller
{
    public function expired() {
        $bs = BasicSetting::first();
        $be = BasicExtended::first();

        $exMembers = Membership::whereDate('expire_date', Carbon::now()->subDays(1))->where('modified', '<>', 1)->get();
        foreach ($exMembers as $key => $exMember) {
            if (!empty($exMember->user)) {
                $user = $exMember->user;
                $currPackage = UserPermissionHelper::userPackage($user->id);

                if (is_null($currPackage)) {
                    SubscriptionExpiredMail::dispatch($user, $bs, $be);
                }
            }
        }

        $rmdMembers = Membership::whereDate('expire_date', Carbon::now()->addDays($be->expiration_reminder))->get();
        foreach ($rmdMembers as $key => $rmdMember) {
            if (!empty($rmdMember->user)) {
                $user = $rmdMember->user;
                $nextPackageCount = Membership::query()->where([
                    ['user_id', $user->id],
                    ['start_date', '>', Carbon::now()->toDateString()]
                ])->where('status', '<>', 2)->whereYear('start_date', '<>', '9999')->count();

                if ($nextPackageCount == 0) {
                    SubscriptionReminderMail::dispatch($user, $bs, $be, $rmdMember->expire_date);
                }
            }
        }

        \Artisan::call("queue:work --stop-when-empty");
    }


    public function checkPayment()
    {
        //check iyzico pending  membership
        $this->checkPendingMemberships();

    }



    protected function checkPendingMemberships()
    {
        $iyzico_pending_memberships = Membership::where([['status', 0], ['payment_method', 'Iyzico']])->get();

        foreach ($iyzico_pending_memberships as $iyzico_pending_membership) {
            if (!is_null($iyzico_pending_membership->conversation_id)) {
                $result = $this->IyzicoPaymentStatus($iyzico_pending_membership->conversation_id);
                if ($result == 'success') {
                    $this->updateIyzicoPendingMemership($iyzico_pending_membership->id, 1);
                } else {
                    $this->updateIyzicoPendingMemership($iyzico_pending_membership->id, 2);
                }
            }
        }
    }


      // get iyzico payment status from iyzico server

    private function IyzicoPaymentStatus($conversation_id)
    {
        // dd($conversation_id);
        $paymentMethod = PaymentGateway::where('keyword', 'iyzico')->first();
        $paydata = json_decode($paymentMethod->information, true);

        $options = new \Iyzipay\Options();
        $options->setApiKey($paydata['api_key']);
        $options->setSecretKey($paydata['secret_key']);
        if ($paydata['iyzico_mode'] == 1) {
            $options->setBaseUrl("https://sandbox-api.iyzipay.com");
        } else {
            $options->setBaseUrl("https://api.iyzipay.com");
        }

        $request = new \Iyzipay\Request\ReportingPaymentDetailRequest();
        $request->setPaymentConversationId($conversation_id);

        $paymentResponse = \Iyzipay\Model\ReportingPaymentDetail::create($request, $options);
        $result = (array) $paymentResponse;

        foreach ($result as $key => $data) {
            $data = json_decode($data, true);
            if ($data['status'] == 'success' && !empty($data['payments'])) {
                if (is_array($data['payments'])) {
                    if ($data['payments'][0]['paymentStatus'] == 1) {
                        return 'success';
                    } else {
                        return 'not found';
                    }
                } else {
                    return 'not found';
                }
            } else {
                return 'not found';
            }
        }
        return 'not found';
    }

    //update pending memberships if payment is successfull
    private function updateIyzicoPendingMemership($id, $status)
    {
          $currentLang = session()->has('lang') ? Language::where('code', session()->get('lang'))->first() : Language::where('is_default', 1)->first();
        $be = $currentLang->basic_extended;
          $bs = $currentLang->basic_setting;
        $membership = Membership::query()->findOrFail($id);
        $user = User::query()->findOrFail($membership->user_id);

        // Get vendor info
        // $vendorInfo = $this->getVendorDetails($membership->seller_id);
        $package = Package::query()->findOrFail($membership->package_id);

        $count_membership = Membership::query()->where('user_id', $membership->user_id)->count();

        //comparison date
        $date1 = Carbon::createFromFormat('m/d/Y', \Carbon\Carbon::parse($membership->start_date)->format('m/d/Y'));
        $date2 = Carbon::createFromFormat('m/d/Y', \Carbon\Carbon::now()->format('m/d/Y'));

        $result = $date1->gte($date2);

        if ($result) {
            $data['start_date'] = $membership->start_date;
            $data['expire_date'] = $membership->expire_date;
        } else {

            $data['start_date'] = Carbon::today()->format('d-m-Y');
            if ($package->term === "daily") {
                $data['expire_date'] = Carbon::today()->addDay()->format('d-m-Y');
            } elseif ($package->term === "weekly") {
                $data['expire_date'] = Carbon::today()->addWeek()->format('d-m-Y');
            } elseif ($package->term === "monthly") {
                $data['expire_date'] = Carbon::today()->addMonth()->format('d-m-Y');
            } elseif ($package->term === "lifetime") {
                $data['expire_date'] = Carbon::maxValue()->format('d-m-Y');
            } else {
                $data['expire_date'] = Carbon::today()->addYear()->format('d-m-Y');
            }

            $membership->update(['start_date' =>  Carbon::parse($data['start_date'])]);
            $membership->update(['expire_date' =>  Carbon::parse($data['expire_date'])]);
        }

        // if previous membership package is lifetime, then exipre that membership
        $previousMembership = Membership::query()
            ->where([
                ['user_id', $user->id],
                ['start_date', '<=', Carbon::now()->toDateString()],
                ['expire_date', '>=', Carbon::now()->toDateString()]
            ])
            ->where('status', 1)
            ->orderBy('created_at', 'DESC')
            ->first();

        if (!is_null($previousMembership)) {
            $previousPackage = Package::query()
                ->select('term')
                ->where('id', $previousMembership->package_id)
                ->first();
            if ($previousPackage->term === 'lifetime' || $previousMembership->is_trial == 1) {
                $yesterday = Carbon::yesterday()->format('d-m-Y');
                $previousMembership->expire_date = Carbon::parse($yesterday);
                $previousMembership->save();
            }
        }

        // Update seller status to 1 (active) only for new memberships
        if ($count_membership <= 1) {
            $user->update(['status' => 1]);
        }

        // process invoice data
        $membershipInvoiceData = [
            'name'      => $user->first_name,
            'username'  => $user->username,
            'email'     => $user->email,
            'phone'     => $user->phone,
            'order_id'  => $membership->transaction_id,
            'base_currency_text_position'  => $be->base_currency_text_position,
            'base_currency_text'  => $be->base_currency_text,
            'base_currency_symbol'  => $be->base_currency_symbol,
            'base_currency_symbol_position'  => $be->base_currency_symbol_position,
            'amount'  => $package->price,
            'payment_method'  => 'Iyzico',
            'package_title'  => $package->title,
            'start_date'  => $data["start_date"] ?? $membership->start_date,
            'expire_date'  => $data["expire_date"] ?? $membership->expire_date,
            'website_title'  => $be->website_title,
            'logo'  => $be->logo,
        ];
        $file_name = $this->makeInvoice($membershipInvoiceData, 'membership', $user, null, $membershipInvoiceData['price'], $membershipInvoiceData['payment_method'], $membershipInvoiceData['phone'], $be->base_currency_symbol_position, $be->base_currency_symbol, $be->base_currency_text, $membership->transaction_id, $package->title);

        $paymentFor = getPaymentType($membership->user_id, $membership->package_id);

        $currencyFormat = function ($amount) use ($be) {
            return ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '')
                . $amount
                . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : '');
        };

        //process mail data
        $mailData = [
            'toMail' => $user->email,
            'toName' => $user->fname,
            'username' => $user->username,
            'package_title' => $package->title,
            'package_price' => $currencyFormat($package->price),
            'total' => $currencyFormat($membership->price),
            'activation_date' => $data["start_date"] ?? $membership->start_date,
            'expire_date' => $data["expire_date"] ?? $membership->expire_date,
            'membership_invoice' => $file_name,
            'website_title' => $bs->website_title,
            'templateType' => $status == 2
                ? 'payment_rejected_for_registration_offline_gateway'
                : ($paymentFor == 'membership'
                    ? 'registration_with_premium_package'
                    : 'membership_extend'),

            'type' => $paymentFor == 'membership'
                ? 'registrationWithPremiumPackage'
                : 'membershipExtend'
        ];

        (new MegaMailer())->mailFromAdmin($mailData);
        @unlink(public_path('assets/front/invoices/' . $file_name));

        $membership->update(['status' => $status]);

        $transaction = [
            'order_number' => $membership->id,
            'transaction_type' => 5,
            'user_id' => null,
            'seller_id' => $membership->seller_id,
            'payment_status' => 'completed',
            'payment_method' => $membership->payment_method,
            'sub_total' => $membership->price,
            'grand_total' => $membership->price,
            'tax' => null,
            'gateway_type' => 'online',
            'currency_symbol' => $membership->currency_symbol,
            'currency_symbol_position' => $be->base_currency_symbol_position,
        ];
        storeTransaction($transaction);

        $earnings = [
            'life_time_earning' => $membership->price,
            'total_profit' => $membership->price,
        ];
        storeEarnings($earnings);
    }
    
}
