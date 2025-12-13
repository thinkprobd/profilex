<?php

use Carbon\Carbon;
use App\Models\Page;
use App\Models\User;
use App\Models\Package;
use App\Models\Language;
use App\Models\Membership;
use App\Models\PaymentGateway;
use App\Models\User\Language as UserLang;
use App\Models\User\UserCustomDomain;
use App\Models\User\UserPaymentGateway;
use App\Models\User\Language as UserLanguage;
use App\Http\Helpers\UserPermissionHelper;

if (!function_exists('checkColorCode')) {
    function checkColorCode($color)
    {
        return preg_match('/^#[a-f0-9]{6}/i', $color);
    }
}
if (!function_exists('setEnvironmentValue')) {
    function setEnvironmentValue(array $values)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {
                $str .= "\n"; // In case the searched variable is in the last line without \n
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}={$envValue}\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                }
            }
        }

        $str = substr($str, 0, -1);
        if (!file_put_contents($envFile, $str)) {
            return false;
        }
        return true;
    }
}

if (!function_exists('paytabInfo')) {
    function paytabInfo()
    {
        // Could please connect me with a support.who can tell me about live api and test api's Payment url ? Now, I am using this https://secure-global.paytabs.com/payment/request url for testing puporse. Is it work for my live api ???
        // paytabs informations
        $paytabs = PaymentGateway::where('keyword', 'paytabs')->first();
        $paytabsInfo = json_decode($paytabs->information, true);
        if ($paytabsInfo['country'] == 'global') {
            $currency = 'USD';
        } elseif ($paytabsInfo['country'] == 'sa') {
            $currency = 'SAR';
        } elseif ($paytabsInfo['country'] == 'uae') {
            $currency = 'AED';
        } elseif ($paytabsInfo['country'] == 'egypt') {
            $currency = 'EGP';
        } elseif ($paytabsInfo['country'] == 'oman') {
            $currency = 'OMR';
        } elseif ($paytabsInfo['country'] == 'jordan') {
            $currency = 'JOD';
        } elseif ($paytabsInfo['country'] == 'iraq') {
            $currency = 'IQD';
        } else {
            $currency = 'USD';
        }
        return [
            'server_key' => $paytabsInfo['server_key'],
            'profile_id' => $paytabsInfo['profile_id'],
            'url' => $paytabsInfo['api_endpoint'],
            'currency' => $currency,
        ];
    }
}

if (!function_exists('userPaytabInfo')) {
    function userPaytabInfo($user_id)
    {
        // Could please connect me with a support.who can tell me about live api and test api's Payment url ? Now, I am using this https://secure-global.paytabs.com/payment/request url for testing puporse. Is it work for my live api ???
        // paytabs informations
        $paytabs = UserPaymentGateway::where('keyword', 'paytabs')->where('user_id', $user_id)->first();
        $paytabsInfo = json_decode($paytabs->information, true);
        if ($paytabsInfo['country'] == 'global') {
            $currency = 'USD';
        } elseif ($paytabsInfo['country'] == 'sa') {
            $currency = 'SAR';
        } elseif ($paytabsInfo['country'] == 'uae') {
            $currency = 'AED';
        } elseif ($paytabsInfo['country'] == 'egypt') {
            $currency = 'EGP';
        } elseif ($paytabsInfo['country'] == 'oman') {
            $currency = 'OMR';
        } elseif ($paytabsInfo['country'] == 'jordan') {
            $currency = 'JOD';
        } elseif ($paytabsInfo['country'] == 'iraq') {
            $currency = 'IQD';
        } else {
            $currency = 'USD';
        }
        return [
            'server_key' => $paytabsInfo['server_key'],
            'profile_id' => $paytabsInfo['profile_id'],
            'url' => $paytabsInfo['api_endpoint'],
            'currency' => $currency,
        ];
    }
}

if (!function_exists('getPaymentType')) {
    function getPaymentType($userId, $packageId)
    {
        $hasPendingMemb = UserPermissionHelper::hasPendingMembership($userId);
        $packageCount = Membership::query()
            ->where([['user_id', $userId], ['expire_date', '>=', Carbon::now()->toDateString()]])
            ->whereYear('start_date', '<>', '9999')
            ->where('status', '<>', 2)
            ->count();

        $current_membership = Membership::query()
            ->where([['user_id', $userId], ['start_date', '<=', Carbon::now()->toDateString()], ['expire_date', '>=', Carbon::now()->toDateString()]])
            ->where('status', 1)
            ->whereYear('start_date', '<>', '9999')
            ->first();

        $current_package = $current_membership ? Package::query()->where('id', $current_membership->package_id)->first() : null;

        if ($packageCount < 2 && !$hasPendingMemb) {
            if (isset($current_package->id) && $current_package->id == $packageId) {
                return 'extend';
            } else {
                return 'membership';
            }
        }
        return null;
    }
}

if (!function_exists('replaceBaseUrl')) {
    function replaceBaseUrl($html)
    {
        $startDelimiter = 'src="';
        $endDelimiter = public_path('assets/front/img/summernote');
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $contentStart = $contentEnd = 0;
        while (false !== ($contentStart = strpos($html, $startDelimiter, $startFrom))) {
            $contentStart += $startDelimiterLength;
            $contentEnd = strpos($html, $endDelimiter, $contentStart);
            if (false === $contentEnd) {
                break;
            }
            $html = substr_replace($html, url('/'), $contentStart, $contentEnd - $contentStart);
            $startFrom = $contentEnd + $endDelimiterLength;
        }

        return $html;
    }
}

if (!function_exists('convertUtf8')) {
    function convertUtf8($value)
    {
        return mb_detect_encoding($value, mb_detect_order(), true) === 'UTF-8' ? $value : mb_convert_encoding($value, 'UTF-8');
    }
}

if (!function_exists('make_slug')) {
    function make_slug($string)
    {
        $slug = preg_replace('/\s+/u', '-', trim($string));
        $slug = str_replace('/', '', $slug);
        $slug = str_replace('?', '', $slug);
        return mb_strtolower($slug, 'UTF-8');
    }
}

if (!function_exists('make_input_name')) {
    function make_input_name($string)
    {
        return preg_replace('/\s+/u', '_', trim($string));
    }
}

if (!function_exists('hasCategory')) {
    function hasCategory($version)
    {
        if (strpos($version, 'no_category') !== false) {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('isDark')) {
    function isDark($version)
    {
        if (strpos($version, 'dark') !== false) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('slug_create')) {
    function slug_create($val)
    {
        $slug = preg_replace('/\s+/u', '-', trim($val));
        $slug = str_replace('/', '', $slug);
        $slug = str_replace('?', '', $slug);
        return mb_strtolower($slug, 'UTF-8');
    }
}

if (!function_exists('hex2rgb')) {
    function hex2rgb($colour)
    {
        if ($colour[0] == '#') {
            $colour = substr($colour, 1);
        }
        if (strlen($colour) == 6) {
            [$r, $g, $b] = [$colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]];
        } elseif (strlen($colour) == 3) {
            [$r, $g, $b] = [$colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]];
        } else {
            return false;
        }
        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);
        return ['red' => $r, 'green' => $g, 'blue' => $b];
    }
}
if (!function_exists('hexToRgba')) {

    function hexToRgba($hex, $alpha = .5)
    {
        // Remove the hash at the start if it's there
        $hex = ltrim($hex, '#');

        // Parse the hex color
        if (strlen($hex) == 6) {
            list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");
        } elseif (strlen($hex) == 3) {
            list($r, $g, $b) = sscanf($hex, "%1x%1x%1x");
            $r = $r * 17;
            $g = $g * 17;
            $b = $b * 17;
        } else {
            return '10, 71, 46';
        }

        // Ensure alpha is between 0 and 1
        $alpha = min(max($alpha, 0), 1);

        // Return the rgba color code
        return "$r, $g, $b";
    }
}
if (!function_exists('getHref')) {
    function getHref($link)
    {
        $href = '#';

        if ($link['type'] == 'home') {
            $href = route('front.index');
        } elseif ($link['type'] == 'website_templates') {
            $href = route('front.templates');
        } elseif ($link['type'] == 'cv_templates') {
            $href = route('front.cv.templates');
        } elseif ($link['type'] == 'vcards') {
            $href = route('front.vcards');
        } elseif ($link['type'] == 'profiles') {
            $href = route('front.user.view');
        } elseif ($link['type'] == 'pricing') {
            $href = route('front.pricing');
        } elseif ($link['type'] == 'faq') {
            $href = route('front.faq.view');
        } elseif ($link['type'] == 'blogs') {
            $href = route('front.blogs');
        } elseif ($link['type'] == 'contact') {
            $href = route('front.contact');
        } elseif ($link['type'] == 'custom') {
            if (empty($link['href'])) {
                $href = '#';
            } else {
                $href = $link['href'];
            }
        } else {
            $pageid = (int) $link['type'];
            $page = Page::find($pageid);

            if (!empty($page)) {
                $href = route('front.dynamicPage', [$page->slug]);
            } else {
                $href = '#';
            }
        }

        return $href;
    }
}

if (!function_exists('create_menu')) {
    function create_menu($arr)
    {
        echo '<ul class="sub-menu">';

        foreach ($arr['children'] as $el) {
            // determine if the class is 'submenus' or not
            $class = 'class="nav-item"';
            if (array_key_exists('children', $el)) {
                $class = 'class="nav-item submenus"';
            }
            // determine the href
            $href = getHref($el);

            echo '<li ' . $class . '>';
            echo '<a  href="' . $href . '" target="' . $el['target'] . '">' . $el['text'] . '</a>';
            if (array_key_exists('children', $el)) {
                create_menu($el);
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}

if (!function_exists('format_price')) {
    function format_price($value): string
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $bex = $currentLang->basic_extended;
        if ($bex->base_currency_symbol_position == 'left') {
            return $bex->base_currency_symbol . $value;
        } else {
            return $value . $bex->base_currency_symbol;
        }
    }
}

if (!function_exists('getParam')) {
    function getParam()
    {
        $parsedUrl = parse_url(url()->current());
        $host = str_replace('www.', '', $parsedUrl['host']);

        // if it is path based URL, then return {username}
        if (strpos($host, env('WEBSITE_HOST')) !== false && $host == env('WEBSITE_HOST')) {
            $path = explode('/', $parsedUrl['path']);
            return $path[1];
        }

        // if it is a subdomain / custom domain , then return the host (username.domain.ext / custom_domain.ext)
        return $host;
    }
}

// checks if 'current package has subdomain ?'

if (!function_exists('cPackageHasSubdomain')) {
    function cPackageHasSubdomain($user)
    {
        $currPackageFeatures = UserPermissionHelper::packagePermission($user->id);
        $currPackageFeatures = json_decode($currPackageFeatures, true);

        // if the current package does not contain subdomain
        if (empty($currPackageFeatures) || !is_array($currPackageFeatures) || !in_array('Subdomain', $currPackageFeatures)) {
            return false;
        }
        return true;
    }
}

// checks if 'current package has custom domain ?'
if (!function_exists('cPackageHasCdomain')) {
    function cPackageHasCdomain($user)
    {
        $currPackageFeatures = UserPermissionHelper::packagePermission($user->id);
        $currPackageFeatures = json_decode($currPackageFeatures, true);

        if (empty($currPackageFeatures) || !is_array($currPackageFeatures) || !in_array('Custom Domain', $currPackageFeatures)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('getCdomain')) {
    function getCdomain($user)
    {
        $cdomains = $user->custom_domains()->where('status', 1);
        return $cdomains->count() > 0 ? $cdomains->orderBy('id', 'DESC')->first()->requested_domain : false;
    }
}

if (!function_exists('toastrMsg')) {
    function toastrMsg($msg)
    {
        if (Auth::check()) {
            if (session()->has('userDashboardLang')) {
                $lang = Language::where('code', session()->get('userDashboardLang'))->first();
            } else {
                $lang = Language::where('is_default', 1)->first();
            }

            $keywords = json_decode($lang->user_keywords, true);

            return $keywords[$msg] ?? '';
        }
    }
}

if (!function_exists('getUser')) {
    function getUser()
    {
        $parsedUrl = parse_url(url()->current());

        $host = $parsedUrl['host'];

        // if the current URL contains the website domain
        if (strpos($host, env('WEBSITE_HOST')) !== false) {
            $host = str_replace('www.', '', $host);
            // if current URL is a path based URL
            if ($host == env('WEBSITE_HOST')) {
                $path = explode('/', $parsedUrl['path']);
                $username = $path[1];
            }
            // if the current URL is a subdomain
            else {
                $hostArr = explode('.', $host);
                $username = $hostArr[0];
            }

            if ($host == $username . '.' . env('WEBSITE_HOST') || $host . '/' . $username == env('WEBSITE_HOST') . '/' . $username) {
                $user = User::where('username', $username)
                    ->where('online_status', 1)
                    ->where('status', 1)
                    ->whereHas('memberships', function ($q) {
                        $q->where('status', '=', 1)
                            ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                            ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
                    })
                    ->firstOrFail();

                // if the current url is a subdomain
                if ($host != env('WEBSITE_HOST')) {
                    if (!cPackageHasSubdomain($user)) {
                        return view('errors.404');
                    }
                }

                return $user;
            }
        }

        // Always include 'www.' at the begining of host
        if (substr($host, 0, 4) == 'www.') {
            $host = $host;
        } else {
            $host = 'www.' . $host;
        }

        $user = User::where('online_status', 1)
            ->where('status', 1)
            ->whereHas('user_custom_domains', function ($q) use ($host) {
                $q->where('status', '=', 1)->where(function ($query) use ($host) {
                    $query->where('requested_domain', '=', $host)->orWhere('requested_domain', '=', str_replace('www.', '', $host));
                });
                // fetch the custom domain , if it matches 'with www.' URL or 'without www.' URL
            })
            ->whereHas('memberships', function ($q) {
                $q->where('status', '=', 1)
                    ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
            })
            ->firstOrFail();

        if (!cPackageHasCdomain($user)) {
            return view('errors.404');
        }

        return $user;
    }
}

if (!function_exists('detailsUrl')) {
    function detailsUrl($user)
    {
        return '//' . env('WEBSITE_HOST') . '/' . $user->username;
    }
}

if (!function_exists('getUserLanguageKeywords')) {
    function getUserLanguageKeywords($user)
    {
        $tenantWebsiteLangCode = app()->getLocale();

        $query = UserLanguage::where('user_id', $user->id);

        if ($tenantWebsiteLangCode) {
            $query->where('code', $tenantWebsiteLangCode);
        } else {
            $query->where('is_default', 1);
        }

        $userCurrentLang = $query->select('keywords')->first();

        return $userCurrentLang ? json_decode($userCurrentLang->keywords, true) : [];
    }
}

if (!function_exists('mb_strrev')) {
    function mb_strrev($string)
    {
        // Check if the string is RTL (contains Arabic or Hebrew characters)
        if (preg_match('/\p{Arabic}|\p{Hebrew}/u', $string)) {
            preg_match_all('/./us', $string, $array);
            return implode('', array_reverse($array[0]));
        }

        // Return original string if not RTL
        return $string;
    }
}

function rtlAwareText($text, $isRtl)
{
    return $isRtl === 'rtl' ? mb_strrev($text) : $text;
}
