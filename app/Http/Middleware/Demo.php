<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Demo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 💡 এই অংশটি ডেমো সীমাবদ্ধতা আরোপ করে: POST/PUT রিকোয়েস্ট ব্লক করে।
        // ডেটা সেভ করা শুরু করতে এই if ব্লকটি কমেন্ট-আউট করুন:
        
        // if($request->isMethod('POST') || $request->isMethod('PUT')) {
        //     session()->flash('warning', 'This is Demo version. You can not change anything.');
        //     return redirect()->back();
        // }
        
        // পরিবর্তন সেভ করার জন্য শুধু এটি রাখুন:
        return $next($request);
    }
}