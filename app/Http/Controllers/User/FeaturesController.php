<?php

namespace App\Http\Controllers\User;

use App\Models\User\Feature;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class FeaturesController extends Controller
{
    public function index(Request $request)
    {
        $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();

        $data['features'] = Feature::where([['language_id', $language->id], ['user_id', Auth::guard('web')->user()->id]])
            ->latest()
            ->get();

        return view('user.features.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|mimes:png,jpg,jpeg,svg|max:2048',
            'user_language_id' => 'required',
            'title' => 'required|max:255',
            'subtitle' => 'required|max:255',
            'serial_number' => 'required|integer',
        ], [
            'user_language_id.required' => 'The language field is required.',
            'serial_number.required' => 'The serial number field is required.',
        ]);

        $featureData = new Feature();
        $featureData->user_id = Auth::guard('web')->user()->id;
        $featureData->language_id = $request->user_language_id;
        $featureData->title = $request->title;
        $featureData->subtitle = $request->subtitle;
        $featureData->serial_number = $request->serial_number;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            $file->move('assets/user/features/', $filename);
            $featureData->image = $filename;
        }
        $featureData->save();

        session()->flash('success', 'Feature Added Successfully.');
        return 'success';
    }

    public function update(Request $request)
    {
        $rules = [
            'title' => 'required|max:255',
            'subtitle' => 'required|max:255',
            'serial_number' => 'required|integer',
        ];

        $featureData =  Feature::findOrFail($request->id);

        if (is_null($featureData->image)) {
            $rules['image'] = 'required|mimes:png,jpg,jpeg,svg|max:2048';
        }

        if ($request->hasFile('image')) {
            $rules['image'] = 'mimes:png,jpg,jpeg,svg|max:2048';
        }

        $request->validate($rules);

        $featureData->user_id = Auth::guard('web')->user()->id;
        $featureData->language_id = $featureData->language_id;
        $featureData->title = $request->title;
        $featureData->subtitle = $request->subtitle;
        $featureData->serial_number = $request->serial_number;
        if ($request->hasFile('image')) {
            //first delete old image
            $imagePath = public_path('assets/user/features/' . $featureData->image);
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
            //store new image
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            $file->move('assets/user/features/', $filename);
            $featureData->image = $filename;
        } else {
            $featureData->image = $featureData->image;
        }
        $featureData->save();

        session()->flash('success', 'Feature Added Successfully.');
        return 'success';
    }

    public function delete(Request $request)
    {
        $featureData = Feature::findOrFail($request->feature_id);
        $imagePath = public_path('assets/user/features/' . $featureData->image);

        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }

        $featureData->delete();
        return redirect()->back()->with('success', 'Feature Deleted Successfully.');
    }


    public function bulkdelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $featureData = Feature::findOrFail($id);
            $imagePath = public_path('assets/user/features/' . $featureData->image);

            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }

            $featureData->delete();
        }

        session()->flash('success', 'Feature Deleted Successfully.');
        return "success";
    }
}
