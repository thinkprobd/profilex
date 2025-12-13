<?php

namespace App\Http\Middleware;

use Route;
use Closure;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Language;
use Illuminate\Http\Request;

class MenuPermissionsCheck
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

        $lang = null;

        if (session()->has('lang')) {
            $lang = Language::where('code', session()->get('lang'))->first();
        }

        // If $lang is still null, fallback to the default language
        if (is_null($lang)) {
            $lang = Language::where('is_default', 1)->first();
        }

        $langMenu =  Menu::where('language_id', $lang->id)->first();
        $menus = json_decode($langMenu->menus, true);
        foreach ($menus as $key => $link) {
            if ($link["type"] == 'home') {
                $href = route('front.index');
                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            } elseif ($link["type"] == 'profiles') {
                $href = route('front.user.view');
                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            } elseif ($link["type"] == 'website_templates') {
                $href = route('front.templates');

                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            } elseif ($link["type"] == 'cv_templates') {
                $href = route('front.cv.templates');
                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            } elseif ($link["type"] == 'vcards') {
                $href = route('front.vcards');
                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            } elseif ($link["type"] == 'pricing') {
                $href = route('front.pricing');
                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            } elseif ($link["type"] == 'faq') {
                $href = route('front.faq.view');
                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            } elseif ($link["type"] == 'blogs') {
                if ($request->routeIs('front.blogdetails')) {
                    return $next($request);
                }
                $href = route('front.blogs');
                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            } elseif ($link["type"] == 'contact') {
                $href = route('front.contact');
                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            } elseif ($link["type"] == 'custom' && array_key_exists("children", $link)) {
                $submens = $link["children"];

                foreach ($submens as $menu) {
                    $pageid = (int) $menu["type"];
                    $page = Page::find($pageid);

                    if (!empty($page)) {
                        $href = route('front.dynamicPage', [$page->slug]);
                    } else {
                        $href = null;
                    }
                    if ($menu["type"] == 'website_templates') {
                        $href = route('front.templates');
                    }
                    if ($menu["type"] == 'vcards') {
                        $href = route('front.vcards');
                    }
                    if ($menu["type"] == 'cv_templates') {
                        $href = route('front.cv.templates');
                    }

                    if (!is_null($href) && (url()->current() == $href)) {
                        return $next($request);
                    }
                }
            } elseif ($link["type"] == 'custom') {
                $href = $link["href"];
                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            } else {

                $pageid = (int) $link["type"];
                $page = Page::find($pageid);
                if (!empty($page)) {
                    $href = route('front.dynamicPage', [$page->slug]);
                } else {
                    $href = null;
                }
                if (!is_null($href) && (url()->current() == $href)) {
                    return $next($request);
                }
            }
        }
        return abort('404');
    }
}
