<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\User\WorkProcess;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;


class WorkProcessController extends Controller
{
    public function index(Request $request)
    {
        $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();

        $data['work_process'] = WorkProcess::where([['language_id', $language->id], ['user_id', Auth::guard('web')->user()->id]])
            ->latest()
            ->get();

        return view('user.work-process.index', $data);
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

        $work_process = new WorkProcess();
        $work_process->user_id = Auth::guard('web')->user()->id;
        $work_process->language_id = $request->user_language_id;
        $work_process->title = $request->title;
        $work_process->subtitle = $request->subtitle;
        $work_process->serial_number = $request->serial_number;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            $file->move('assets/user/work-process/', $filename);
            $work_process->image = $filename;
        }
        $work_process->save();

        session()->flash('success', 'Work Process Added Successfully.');
        return 'success';
    }

    public function update(Request $request)
    {
        $rules = [
            'title' => 'required|max:255',
            'subtitle' => 'required|max:255',
            'serial_number' => 'required|integer',
        ];

        $work_process =  WorkProcess::findOrFail($request->id);

        if (is_null($work_process->image)) {
            $rules['image'] = 'required|mimes:png,jpg,jpeg,svg|max:2048';
        }

        if ($request->hasFile('image')) {
            $rules['image'] = 'mimes:png,jpg,jpeg,svg|max:2048';
        }

        $request->validate($rules);

        $work_process->user_id = Auth::guard('web')->user()->id;
        $work_process->language_id = $work_process->language_id;
        $work_process->title = $request->title;
        $work_process->subtitle = $request->subtitle;
        $work_process->serial_number = $request->serial_number;
        if ($request->hasFile('image')) {
            //first delete old image
            $imagePath = public_path('assets/user/work-process/' . $work_process->image);
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
            //store new image
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            $file->move('assets/user/work-process/', $filename);
            $work_process->image = $filename;
        } else {
            $work_process->image = $work_process->image;
        }
        $work_process->save();

        session()->flash('success', 'Work Process Added Successfully.');
        return 'success';
    }

    public function delete(Request $request)
    {
        $process = WorkProcess::findOrFail($request->process_id);
        $imagePath = public_path('assets/user/work-process/' . $process->image);

        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }

        $process->delete();
        return redirect()->back()->with('success', 'Work Process Deleted Successfully.');
    }


    public function bulkdelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $process = WorkProcess::findOrFail($id);
            $imagePath = public_path('assets/user/work-process/' . $process->image);

            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }

            $process->delete();
        }

        session()->flash('success', 'Work Process Deleted Successfully.');
        return "success";
    }
}
