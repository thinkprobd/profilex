<?php

namespace App\Http\Controllers\User;

use Purifier;
use Validator;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\User\UserService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User\Language as UserLanguage;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return
     */
    public function index(Request $request)
    {
        $data = null;
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

        $data['services'] = UserService::where([
            ['lang_id', '=', $lang->id],
            ['user_id', '=', Auth::id()],
        ])
            ->orderBy('id', 'DESC')
            ->get();
        return view('user.service.index', $data);
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
     * @return
     */
    public function store(Request $request)
    {
        $img = $request->file('image');
        $allowedExts = array('jpg', 'png', 'jpeg');

        $rules = [
            'name' => 'required|max:255',
            'user_language_id' => 'required',
            'content' => 'required',
            'detail_page' => 'required',
            'serial_number' => 'required|integer',
            'image' => [
                'required',
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

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        if (!isset($request->featured)) $request["featured"] = "0";
        $input = $request->all();
        $slug = make_slug($request->name);
        $input['slug'] = $slug;
        $input['user_id'] = Auth::id();
        $input['lang_id'] = $request->user_language_id;

        if ($request->hasFile('image')) {
            $filename = time() . '.' . $img->getClientOriginalExtension();
            $directory = public_path('assets/front/img/user/services/');
            if (!file_exists($directory)) mkdir($directory, 0775, true);
            $request->file('image')->move($directory, $filename);
            $input['image'] = $filename;
        }
        $input['content'] = Purifier::clean($request->content);
        $blog = new UserService();
        $blog->create($input);

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
    public function edit(UserService $service)
    {
        if ($service->user_id != Auth::guard('web')->user()->id) {
            Session::flash('warning', 'Authorization Failed');
            return back();
        }
        $data['service'] = $service;
        return view('user.service.edit', $data);
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
        $img = $request->file('image');
        $allowedExts = array('jpg', 'png', 'jpeg');
        $rules = [
            'name' => 'required|max:255',
            'content' => 'required',
            'detail_page' => 'required',
            'serial_number' => 'required|integer',
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
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        if (!isset($request->featured)) $request["featured"] = "0";
        $service = UserService::findOrFail($request->id);
        if ($service->user_id != Auth::user()->id) {
            return;
        }
        $input = $request->all();
        $slug = make_slug($request->name);
        $input['slug'] = $slug;
        $input['user_id'] = Auth::id();

        if ($request->hasFile('image')) {
            $filename = time() . '.' . $img->getClientOriginalExtension();
            $request->file('image')->move(public_path('assets/front/img/user/services/'), $filename);
            if (file_exists(public_path('assets/front/img/user/services/' . $service->image))) {
                @unlink(public_path('assets/front/img/user/services/' . $service->image));
            }

            $input['image'] = $filename;
        }
        $input['content'] = Purifier::clean($request->content);
        $service->update($input);
        Session::flash('success', __('Updated successfully') . '!');
        return "success";
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $service = UserService::where('user_id', Auth::user()->id)->where('id', $request->id)->firstOrFail();
        if (file_exists(public_path('assets/front/img/user/services/' . $service->image))) {
            @unlink(public_path('assets/front/img/user/services/' . $service->image));
        }
        $service->delete();
        Session::flash('success', __('Deleted successfully') . '!');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $service = UserService::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
            if (file_exists(public_path('assets/front/img/user/services/' . $service->image))) {
                @unlink(public_path('assets/front/img/user/services/' . $service->image));
            }
            $service->delete();
        }
        Session::flash('success', __('Bulk Deleted successfully') . '!');
        return "success";
    }
}
