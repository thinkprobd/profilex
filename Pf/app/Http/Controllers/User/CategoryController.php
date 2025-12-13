<?php

namespace App\Http\Controllers\User;


use Illuminate\Http\Request;
use App\Models\User\Category;
use App\Models\User\Language;
use App\Models\User\FormInput;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User\Language as UserLanguage;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    // Start category CRUD
    public function category()
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
        $data['language'] = $lang;
        $data['languages'] = Language::where('user_id', Auth::guard('web')->user()->id)->get();
        $data['categories'] = Category::where('user_id', Auth::guard('web')->user()->id)->where('language_id', $lang->id)->get();

        return view('user.appointment.category', $data);
    }
    public function categoryFeatured(Request $request)
    {
        $category = Category::find($request->category_id);
        $category->is_featured = $request->is_featured;
        $category->save();

        session()->flash('success', toastrMsg('Updated_successfully!'));
        return redirect()->back();
    }
    public function categoryStore(Request $request)
    {
        if ($request->table_id) {
            $rules = [
                'name' => 'required',
            ];
        } else {
            $rules = [
                'image' => 'mimes:jpeg,jpg,png,svg',
                'name' => 'required',
                'price' => 'required',
                'user_language_id' => 'required'
            ];
        }


        $request->validate($rules);
        if ($request->table_id) {
            $category = Category::find($request->table_id);
            if ($request->hasFile('image')) {
                $rules = [
                    'image' => 'mimes:jpeg,jpg,png,svg'
                ];

                $request->validate($rules);
                // first, delete the previous image from local storage
                @unlink(public_path('assets/user/img/category/' . $category->image));
                // get image extension
                $imageURL = $request->image;
                $fileExtension = $imageURL->extension();
                // set a name for the image and store it to local storage
                $imageName = time() . '.' . $fileExtension;
                $directory = public_path('assets/user/img/category/');
                @mkdir($directory, 0775, true);
                @copy($imageURL, $directory . $imageName);
            }
            // update existing entry
            $category->name = $request->name;
            $category->appointment_price = $request->price;
            if ($request->hasFile('image')) {
                $category->image = $request->hasFile('image') ? $imageName : null;
            }
            $category->save();
            $action = 'updated';
        } else {
            if ($request->hasFile('image')) {
                // get image extension
                $imageURL = $request->image;
                $fileExtension = $imageURL->extension();
                // set a name for the image and store it to local storage
                $imageName = time() . '.' . $fileExtension;
                $directory = public_path('assets/user/img/category/');
                @mkdir($directory, 0775, true);
                @copy($imageURL, $directory . $imageName);
            }
            // create new entry
            $category = Category::create([
                'user_id' => Auth::guard('web')->user()->id,
                'language_id' => $request->user_language_id,
                'name' => $request->name,
                'appointment_price' => $request->price,
                'image' => $request->hasFile('image') ? $imageName : null
            ]);
            $action = 'created';
        }
        $request->session()->flash('success',  __('Store successfully') . '!');
        return back();
    }
    public function categoryDelete(Category $category)
    {
        $check = Category::findOrFail($category->id);
        if ($check) {
            @unlink(public_path('assets/user/img/category/' . $category->image));
            category::find($category->id)->delete();
            $category->delete();
        }
        request()->session()->flash('success', toastrMsg('Deleted_successfully!'));
        return back();
    }
    public function categoryBulkDestroy(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $category = Category::findOrFail($id);
            @unlink(public_path('assets/user/img/category/' . $category->image));
            $category->delete();
        }
        $request->session()->flash('success', toastrMsg('Deleted_successfully!'));
        return 'success';
    }
    // End category CRUD

    // start form builder
    public function form($id = null)
    {

        $lang = Language::where([['code', request('language')], ['user_id', Auth::guard('web')->user()->id]])->firstOrFail();
        $data['lang_id'] = $lang->id;
        $data['abs'] = $lang->basic_setting;
        if ($id == null) {
            $data['back_url'] = null; // route('user.forminput') . '?language=' . $lang->code;
            $data['inputs'] = FormInput::where([['language_id', $lang->id], ['user_id', Auth::guard('web')->user()->id], ['category_id', null]])->orderBy('order_number', 'ASC')->get();
        } else {
            $data['back_url'] = route('user.appointment.category') . '?language=' . $lang->code;
            $data['inputs'] = FormInput::where([['language_id', $lang->id], ['user_id', Auth::guard('web')->user()->id], ['category_id', $id]])->orderBy('order_number', 'ASC')->get();
        }
        $data['type_details'] = Category::where('id', $id)->first();


        return view('user.appointment.form', $data);
    }
    // end form builder
}
