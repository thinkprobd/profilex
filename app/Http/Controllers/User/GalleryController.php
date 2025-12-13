<?php

namespace App\Http\Controllers\User;

use App\Models\User\Gallery;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();

        $data['galleries'] = Gallery::where([['language_id', $language->id], ['user_id', Auth::guard('web')->user()->id]])
            ->latest()
            ->get();

        return view('user.gallery.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|mimes:png,jpg,jpeg,svg,gif|max:2048',
            'name' => 'required|max:255',
            'serial_number' => 'required|integer',
            'user_language_id' => 'required'
        ], [
            'user_language_id.required' => 'The language field is required.',
            'serial_number.required' => 'The serial number field is required.',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $fileName = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('assets/user/gallery/');
            $image->move($destinationPath, $fileName);
        }

        $gallery = new Gallery();
        $gallery->image = $fileName;
        $gallery->name = $request->name;
        $gallery->serial_number = $request->serial_number;
        $gallery->language_id = $request->user_language_id;
        $gallery->user_id = Auth::guard('web')->user()->id;
        $gallery->save();

        session()->flash('success', 'Gallery added successfully.');
        return 'success';
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'serial_number' => 'required|integer',
        ], [
            'serial_number.required' => 'The serial number field is required.',
        ]);

        $gallery = Gallery::findOrFail($request->id);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $fileName = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('assets/user/gallery/');
            $image->move($destinationPath, $fileName);
            @unlink('assets/user/gallery/' . $gallery->image);
        }


        $gallery->image = $request->hasFile('image') ? $fileName : $gallery->image;
        $gallery->name = $request->name;
        $gallery->serial_number = $request->serial_number;
        $gallery->language_id = $gallery->language_id;
        $gallery->user_id = Auth::guard('web')->user()->id;
        $gallery->save();

        session()->flash('success', 'Gallery updated successfully.');
        return 'success';
    }

    public function delete(Request $request)
    {
        $gallery = Gallery::findOrFail($request->gallery_id);
        @unlink('assets/user/gallery/' . $gallery->image);
        $gallery->delete();

        session()->flash('success', 'Gallery deleted successfully.');
        return redirect()->back();
    }

    public function bulkdelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $gallery = Gallery::findOrFail($id);
            $imagePath = public_path('assets/user/gallery/' . $gallery->image);

            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }

            $gallery->delete();
        }

        session()->flash('success', 'Gallery Deleted Successfully.');
        return "success";
    }
}
