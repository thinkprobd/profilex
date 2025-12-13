<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\JobExperience;
use App\Models\User\Language;
use App\Models\User\Language as UserLanguage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Purifier;
use Validator;


class JobExperienceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return
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
        $data['job_experiences'] = JobExperience::where([
            ['lang_id', '=', $lang->id],
            ['user_id', '=', Auth::id()],
        ])
            ->orderBy('id', 'DESC')
            ->get();
           
        return view('user.job_experience.index', $data);
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
   
        $rules = [
            'user_language_id' => 'required',
            'company_name' => 'required',
            'designation' => 'required',
            'start_date' => 'required',
            'serial_number' => 'required',
        ];
        if (!array_key_exists('is_continue', $request->all())) {
            $rules['end_date'] = 'required';
            $request['is_continue'] = 0;
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $newJobExperience = new JobExperience();
        $newJobExperience->company_name = $request->company_name;
        $newJobExperience->designation = $request->designation;
        $newJobExperience->content = Purifier::clean($request->content);
        $newJobExperience->start_date = $request->start_date;
        $newJobExperience->end_date = $request->is_continue === "1"
            ? null : $request->end_date;
        $newJobExperience->is_continue = $request->is_continue;
        $newJobExperience->serial_number = $request->serial_number;
        $newJobExperience->lang_id = $request->user_language_id;
        $newJobExperience->user_id = Auth::id();
        $newJobExperience->save();
        Session::flash('success', __('Store successfully') . '!');
        return "success";
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return
     */
    public function edit(JobExperience $experience)
    {
        if ($experience->user_id != Auth::guard('web')->user()->id) {
            Session::flash('warning', __('Authorization Failed'));
            return back();
        }
        $data['jobExperience'] = $experience;
        return view('user.job_experience.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return
     */
    public function update(Request $request)
    {
        $rules = [
            'company_name' => 'required',
            'designation' => 'required',
            'start_date' => 'required',
            'serial_number' => 'required',
        ];
        if (!array_key_exists('is_continue', $request->all())) {
            $rules['end_date'] = 'required';
            $request['is_continue'] = 0;
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $newJobExperience = JobExperience::query()->findOrFail($request->id);
        if ($newJobExperience->user_id != Auth::user()->id) {
            return;
        }
        $newJobExperience->company_name = $request->company_name;
        $newJobExperience->designation = $request->designation;
        $newJobExperience->content = Purifier::clean($request->content);
        $newJobExperience->start_date = $request->start_date;
        $newJobExperience->end_date = $request->is_continue === "on" ? null : $request->end_date;
        $newJobExperience->is_continue = $request->is_continue === "on" ? 1 : 0;
        $newJobExperience->serial_number = $request->serial_number;
        $newJobExperience->user_id = Auth::id();
        $newJobExperience->save();
        Session::flash('success', __('Updated successfully') . '!');
        return "success";
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function delete(Request $request)
    {
        JobExperience::where('user_id', Auth::user()->id)->where('id', $request->id)->firstOrFail()->delete();
        Session::flash('success', __('Deleted successfully') . '!');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            JobExperience::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail()->delete();
        }
        Session::flash('success', __('Bulk Deleted successfully') . '!');
        return "success";
    }
}
