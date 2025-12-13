<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\Language;
use App\Models\User\Language as UserLanguage;
use App\Models\User\PortfolioCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Validator;

class PortfolioCategoryController extends Controller
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
        $data['categories'] = PortfolioCategory::where([
            ['language_id', '=', $lang->id],
            ['user_id', '=', Auth::id()],
        ])
            ->orderBy('id', 'DESC')
            ->get();
        return view('user.portfolio.bcategory.index', $data);
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
            'name' => 'required|max:255',
            'status' => 'required',
            'serial_number' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $bcategory = new PortfolioCategory();
        $bcategory->language_id = $request->user_language_id;
        $bcategory->name = $request->name;
        $bcategory->status = $request->status;
        $bcategory->user_id = Auth::id();
        $bcategory->serial_number = $request->serial_number;
        $bcategory->save();

        Session::flash('success', __('Store successfully') . '!');
        return "success";
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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

        $rules = [
            'name' => 'required|max:255',
            'status' => 'required',
            'serial_number' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $bcategory = PortfolioCategory::findOrFail($request->bcategory_id);
        if ($bcategory->user_id != Auth::user()->id) {
            return;
        }
        $bcategory->name = $request->name;
        $bcategory->status = $request->status;
        $bcategory->serial_number = $request->serial_number;
        $bcategory->save();

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

    public function delete(Request $request){
        $bcategory = PortfolioCategory::where('user_id', Auth::user()->id)->where('id', $request->bcategory_id)->firstOrFail();
        if ($bcategory->portfolios()->count() > 0) {
            Session::flash('warning', __('First, delete all the portfolios under this category') . '.');
            return back();
        }
        $bcategory->delete();
        Session::flash('success', __('Deleted successfully') . '!');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        foreach ($ids as $id) {
            $bcategory = PortfolioCategory::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
            if ($bcategory->portfolios()->count() > 0) {
                Session::flash('warning', __('First, delete all the portfolios under the selected categories') . '.');
                return "success";
            }
        }

        foreach ($ids as $id) {
            $bcategory = PortfolioCategory::findOrFail($id);
            $bcategory->delete();
        }

        Session::flash('success', __('Bulk Deleted successfully') . '!');
        return "success";
    }
}
