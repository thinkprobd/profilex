<?php

namespace App\Http\Controllers\Admin;

use App\Models\User\UserCv;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class CvController extends Controller
{
    public function cv()
    {
        $data['cvs'] = UserCv::orderBy('id', 'DESC')->get();
        return view('admin.cv.index', $data);
    }

    public function statusUpdate(Request $request)
    {
        $status = $request->status;
        $vcard_id = $request->cv_id;

        UserCv::where('id', $vcard_id)->update(['status' => $status]);
        Session::flash('success', __('Updated successfully') . '!');
        return back();
    }


    public function addPtemplate(Request $request)
    {
        $cvContent = UserCv::find($request->cv_id);

        if ($request->hasFile('preview_template_image')) {
            $img = $request->file('preview_template_image');
            $uploadPath = public_path('assets/front/img/user/prevtemplate/');

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            if (!empty($cvContent->preview_template_image) && file_exists($uploadPath . $cvContent->preview_template_image)) {
                @unlink($uploadPath . $cvContent->preview_template_image);
            }

            // Save new image
            $filename = uniqid('tpl_') . '.' . $img->getClientOriginalExtension();
            $img->move($uploadPath, $filename);

            $cvContent->preview_template_image = $filename;
        }


        $cvContent->preview_template_status = 1;
        $cvContent->preview_template_serial_number = $request->preview_template_serial_number;
        $cvContent->preview_template_name = $request->preview_template_name;
        $cvContent->save();

        Session::flash('success', __('Updated successfully') . '!');
        return back();
    }

    public function editPtemplate(Request $request)
    {
        $cvContent = UserCv::find($request->cv_id);

        if ($request->hasFile('preview_template_image')) {
            $img = $request->file('preview_template_image');
            $uploadPath = public_path('assets/front/img/user/prevtemplate/');

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            if (!empty($cvContent->preview_template_image) && file_exists($uploadPath . $cvContent->preview_template_image)) {
                @unlink($uploadPath . $cvContent->preview_template_image);
            }

            // Save new image
            $filename = uniqid('tpl_') . '.' . $img->getClientOriginalExtension();
            $img->move($uploadPath, $filename);

            $cvContent->preview_template_image = $filename;
        } else {
            $cvContent->preview_template_image = $cvContent->preview_template_image;
        }


        $cvContent->preview_template_status = 1;
        $cvContent->preview_template_serial_number = $request->preview_template_serial_number;
        $cvContent->preview_template_name = $request->preview_template_name;
        $cvContent->save();

        Session::flash('success', __('Updated successfully') . '!');
        return back();
    }

    public function deletePrevTemplate(Request $request)
    {
        $cvContent = UserCv::find($request->cv_id);
        @unlink(public_path('assets/front/img/user/prevtemplate/' . $cvContent->preview_template_image));
        $cvContent->preview_template_status = 0;
        $cvContent->save();
        Session::flash('success', __('Deleted successfully') . '!');
        return back();
    }
}
