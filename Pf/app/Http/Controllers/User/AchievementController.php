<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\User\Achievement;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class AchievementController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;
        if (session()->has('userDashboardLang')) {
            $lang = Language::where([
                ['code', session()->get('userDashboardLang')],
                ['user_id', $userId]
            ])->first();
            session()->put('currentLangCode', session()->get('userDashboardLang'));
        } else {
            $lang = Language::where([
                ['is_default', 1],
                ['user_id', $userId]
            ])->first();
            session()->put('currentLangCode', $lang->code);
        }
        $data['achievements'] = Achievement::where([
            ['language_id', '=', $lang->id],
            ['user_id', '=', Auth::id()],
        ])
            ->orderBy('id', 'DESC')
            ->get();
        return view('user.achievement.index', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'user_language_id' => 'required',
            'title' => 'required|max:255',
            'count' => 'required|integer',
            'serial_number' => 'required|integer'
        ];

        $userId = Auth::guard('web')->user()->id;
        $theme = DB::table('user_basic_settings')->where('user_id', $userId)->value('theme');
        if ($theme == 9 || $theme == 11 || $theme == 12) {
            $rules['image'] = 'required|mimes:png,jpg,jpeg,svg,gif|max:2048';
        }
        if ($theme == 11) {
            $rules['subtitle'] = 'required|max:255';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $input = $request->all();
        $input['language_id'] = $request->user_language_id;
        $input['user_id'] = Auth::id();

        //for image upload
        if ($theme == 9 || $theme == 11 || $theme == 12) {
            $input['symbol'] = $request->symbol;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $file->move('assets/user/images/achievement/', $filename);
                $input['image'] = $filename;
            }
        }

        $achievement = new Achievement;
        $achievement->create($input);

        Session::flash('success', __('Store successfully') . '!');
        return "success";
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return
     */
    public function edit(Achievement $achievement)
    {
        if ($achievement->user_id != Auth::guard('web')->user()->id) {
            Session::flash('warning', __('Authorization Failed'));
            return back();
        }
        $data['achievement'] = $achievement;
        return view('user.achievement.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $achievement = Achievement::findOrFail($request->achievement_id);
        $slug = make_slug($request->title);

        $rules = [
            'title' => 'required|max:255',
            'count' => 'required|integer',
            'serial_number' => 'required|integer'
        ];


        //validation for image
        $userId = Auth::guard('web')->user()->id;
        $theme = DB::table('user_basic_settings')->where('user_id', $userId)->value('theme');

        if (($theme == 9 || $theme == 11 || $theme == 12) && is_null($achievement->image)) {
            $rules['image'] = 'required|mimes:png,jpg,jpeg,svg,gif|max:2048';
        }
        if ($theme == 11) {
            $rules['subtitle'] = 'required|max:255';
        }
        if ($request->hasFile('image')) {
            $rules['image'] = 'mimes:png,jpg,jpeg,svg,gif|max:2048';
        }


        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $input = $request->all();

        if ($achievement->user_id != Auth::user()->id) {
            return;
        }
        //for image upload
        if ($theme == 9 || $theme == 11 || $theme == 12) {
            $input['symbol'] = $request->symbol;
            if ($request->hasFile('image')) {
                @unlink('assets/user/images/achievement/' . $achievement->image);
                $file = $request->file('image');
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $file->move('assets/user/images/achievement/', $filename);
                $input['image'] = $filename;
            }
        }

        $input['slug'] = $slug;
        $input['user_id'] = Auth::id();
        $achievement->update($input);
        Session::flash('success', __('Updated successfully') . '!');
        return "success";
    }

    public function delete(Request $request)
    {
        $achievement = Achievement::where('user_id', Auth::user()->id)->where('id', $request->achievement_id)->firstOrFail();
        @unlink('assets/user/images/achievement/' . $achievement->image);
        $achievement->delete();
        Session::flash('success', __('Deleted successfully') . '!');
        return back();
    }
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $achievement = Achievement::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
            @unlink('assets/user/images/achievement/' . $achievement->image);
            $achievement->delete();
        }
        Session::flash('success', __('Bulk Deleted successfully') . '!');
        return "success";
    }
}
