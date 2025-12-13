<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User\Language as UserLanguage;

class UserLangLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (session()->has('userDashboardLang')) {
            app()->setLocale('user_' . session()->get('userDashboardLang'));
        } else {
            $defaultLang = UserLanguage::where('is_default', 1)->first();
            if (!empty($defaultLang)) {
                app()->setLocale('user_' . $defaultLang->code);
            }
        }

        return $next($request);
    }
}
