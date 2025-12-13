<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Models\User\BasicSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class BasicSettingController extends Controller
{


    public function information()
    {
        $data['data'] = BasicSetting::where('user_id', Auth::guard('web')->user()->id)
            ->first();
        return view('user.settings.information', $data);
    }

    public function updateInfo(Request $request)
    {
        $rules = [
            'base_currency_symbol' => 'required',
            'base_currency_symbol_position' => 'required',
            'base_currency_text' => 'required',
            'base_currency_text_position' => 'required',
            'base_currency_rate' => 'required|numeric',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        BasicSetting::where('user_id', Auth::guard('web')->user()->id)->update([
            'website_title' => $request->website_title ?? '',
            'base_currency_symbol' => $request->base_currency_symbol,
            'base_currency_symbol_position' => $request->base_currency_symbol_position,
            'base_currency_text' => $request->base_currency_text,
            'base_currency_text_position' => $request->base_currency_text_position,
            'base_currency_rate' => $request->base_currency_rate,
        ]);

        $request->session()->flash('success', __('Updated successfully') . '!');

        return 'success';
    }



    public function footerSection(Request $request)
    {
        return view('user.settings.footer');
    }
    public function updateFooterSection(Request $request)
    {

        $img = $request->file('image');
        $allowedExts = array('jpg', 'png', 'jpeg');

        $rules = [
            'image' => [
                function ($attribute, $value, $fail) use ($img, $allowedExts) {
                    if (!empty($img)) {
                        $ext = $img->getClientOriginalExtension();
                        if (!in_array($ext, $allowedExts)) {
                            return $fail(__('Only') . ' ' . implode(', ', $allowedExts) . ' ' . __('images are allowed'));
                        }
                    }
                },
            ],
        ];

        $request->validate($rules);

        if ($request->hasFile('image')) {
            $bss = BasicSetting::where('user_id', Auth::id())->first();
            $filename = uniqid() . '.' . $img->getClientOriginalExtension();
            $img->move(public_path('assets/front/img/user/footer/'), $filename);
            $bss = BasicSetting::where('user_id', Auth::id())->first();
            if (!is_null($bss)) {
                if ($bss->footer_section_image) {
                    @unlink(public_path('assets/front/img/user/footer/' . $bss->footer_section_image));
                }
                $bss->footer_section_image = $filename;
                $bss->user_id = Auth::id();
                $bss->save();
            } else {
                $bs = new BasicSetting();
                $bs->footer_section_image = $filename;
                $bs->user_id = Auth::id();
                $bs->save();
            }
        }
        Session::flash('success', __('Updated successfully') . '!');
        return back();
    }
}
