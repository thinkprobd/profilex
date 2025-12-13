<?php

namespace App\Http\Middleware;

use App\Http\Helpers\UserPermissionHelper;
use App\Models\User;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Session;

class CheckPackageLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $feature, $method)
    {

        // if the admin is logged in & he has a role defined then this check will be applied
        if (Auth::guard('web')->check()) {
            $user = User::find(Auth::guard('web')->user()->id);
            $package = UserPermissionHelper::currentPackagePermission($user->id);
            if (empty($package)) {
                return redirect()->route('user-dashboard');
            }
            $userFeaturesCount = UserPermissionHelper::userFeaturesCount($user->id);

            if ($method == 'store') {

                if ($feature == 'blogs') {

                    if ($package->number_of_blogs > $userFeaturesCount['blogs'] || $package->number_of_blogs == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'blogCategories') {

                    if ($package->number_of_blog_categories > $userFeaturesCount['blogCategories'] || $package->number_of_blog_categories == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'services') {

                    if ($package->number_of_services > $userFeaturesCount['services']|| $package->number_of_services == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'skills') {

                    if ($package->number_of_skills > $userFeaturesCount['skills'] || $package->number_of_skills == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'portfolios') {

                    if ($package->number_of_portfolios > $userFeaturesCount['portfolios']|| $package->number_of_portfolios == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'portfolioCategories') {

                    if ($package->number_of_portfolio_categories > $userFeaturesCount['portfolioCategories']|| $package->number_of_portfolio_categories == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'languages') {

                    if ($package->number_of_languages > $userFeaturesCount['languages']|| $package->number_of_languages == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'jobExpriences') {

                    if ($package->number_of_job_expriences > $userFeaturesCount['jobExpriences']|| $package->number_of_job_expriences == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'educations') {

                    if ($package->number_of_education > $userFeaturesCount['educations']|| $package->number_of_education == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'vcards') {

                    if ($package->number_of_vcards > $userFeaturesCount['vcards']|| $package->number_of_vcards == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }
            }

            if ($method == 'update') {
                if ($feature == 'blogs') {

                    if ($package->number_of_blogs >= $userFeaturesCount['blogs']|| $package->number_of_blogs == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'blogCategories') {

                    if ($package->number_of_blog_categories >= $userFeaturesCount['blogCategories']|| $package->number_of_blog_categories == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'services') {

                    if ($package->number_of_services >= $userFeaturesCount['services']|| $package->number_of_services == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'skills') {

                    if ($package->number_of_skills >= $userFeaturesCount['skills']|| $package->number_of_skills == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'portfolios') {

                    if ($package->number_of_portfolios >= $userFeaturesCount['portfolios']|| $package->number_of_portfolios == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'portfolioCategories') {

                    if ($package->number_of_portfolio_categories >= $userFeaturesCount['portfolioCategories']|| $package->number_of_portfolio_categories == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'languages') {

                    if ($package->number_of_languages >= $userFeaturesCount['languages'] || $package->number_of_languages == 99999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'jobExpriences') {

                    if ($package->number_of_job_expriences >= $userFeaturesCount['jobExpriences'] ||$package->number_of_job_expriences == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'educations') {

                    if ($package->number_of_education >= $userFeaturesCount['educations']|| $package->number_of_education == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }

                if ($feature == 'vcards' ) {

                    if ($package->number_of_vcards >= $userFeaturesCount['vcards'] || $package->number_of_vcards == 999999) {
                        return $next($request);
                    } else {
                        return response()->json('downgrade');
                    }
                }
            }
        }
    }
}
