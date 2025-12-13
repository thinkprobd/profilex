<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\Education;
use App\Models\User\Language as UserLanguage;
use App\Models\User\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Purifier;
use Validator;

class EducationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     *
     */
    public function index(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;
        if (session()->has('userDashboardLang')) {
            $lang = UserLanguage::where([
                ['code', session()->get('userDashboardLang')],
                ['user_id', $userId]
            ])->first();
            session()->put('currentLangCode', session()->get('userDashboardLang'));
        } else {
            $lang = UserLanguage::where([
                ['is_default', 1],
                ['user_id', $userId]
            ])->first();
            session()->put('currentLangCode', $lang->code);
        }
        $data['educations'] = Education::query()
            ->where('lang_id', $lang->id)
            ->where('user_id', Auth::id())
            ->get();
        return view('user.user_education.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $slug = make_slug($request->degree_name);
        $rules = [
            'user_language_id' => 'required',
            'degree_name' => 'required|max:255',
            'serial_number' => 'required|integer'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $input = $request->all();
        $input['slug'] = $slug;
        $input['user_id'] = Auth::id();
        $input['lang_id'] = $request->user_language_id;
        $input['short_description'] = Purifier::clean($request->short_description);
        $education = new Education();
        $education->create($input);

        Session::flash('success', __('Store successfully') . '!');
        return "success";
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return
     */
    public function edit(Education $education)
    {

        if ($education->user_id != Auth::guard('web')->user()->id) {
            Session::flash('warning',  __('Authorization Failed'));
            return back();
        }
        $data['education'] = $education;
        return view('user.user_education.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $slug = make_slug($request->degree_name);

        $rules = [
            'degree_name' => 'required|max:255',
            'serial_number' => 'required|integer'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $input = $request->all();
        $education = Education::findOrFail($request->id);
        if ($education->user_id != Auth::user()->id) {
            return;
        }
        $input['slug'] = $slug;
        $input['user_id'] = Auth::id();
        $input['short_description'] = Purifier::clean($request->short_description);
        $education->update($input);
        Session::flash('success', __('Updated successfully') . '!');
        return "success";
    }

    public function delete(Request $request)
    {
        $education = Education::where('user_id', Auth::user()->id)->where('id', $request->id)->firstOrFail();
        $education->delete();
        Session::flash('success', __('Deleted successfully') . '!');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $education = Education::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
            $education->delete();
        }
        Session::flash('success', __('Bulk Deleted successfully') . '!');
        return "success";
    }
}
