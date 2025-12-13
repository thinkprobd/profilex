<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\User\UserVcard;
use App\Http\Controllers\Controller;
use App\Models\User\UserVcardProject;
use App\Models\User\UserVcardService;
use Illuminate\Support\Facades\Session;
use App\Models\User\UserVcardTestimonial;

class VcardController extends Controller
{
    public function index()
    {
        $data['vcards'] = UserVcard::orderBy('id', 'DESC')->get();

        return view('admin.register_user.vcard.index', $data);
    }

    public function statusUpdate(Request $request)
    {
        $status = $request->status;
        $vcard_id = $request->vcard_id;

        UserVcard::where('id', $vcard_id)->update(['status' => $status]);
        Session::flash('success', __('Updated successfully') . '!');
        return back();
    }

    public function addPtemplate(Request $request)
    {
        $vcard = UserVcard::find($request->vcard_id);

        if ($request->hasFile('preview_template_image')) {
            $img = $request->file('preview_template_image');
            $uploadPath = public_path('assets/front/img/user/prevtemplate/');

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            if (!empty($vcard->preview_template_image) && file_exists($uploadPath . $vcard->preview_template_image)) {
                @unlink($uploadPath . $vcard->preview_template_image);
            }

            // Save new image
            $filename = uniqid('tpl_') . '.' . $img->getClientOriginalExtension();
            $img->move($uploadPath, $filename);

            $vcard->preview_template_image = $filename;
        }


        $vcard->preview_template_status = 1;
        $vcard->preview_template_serial_number = $request->preview_template_serial_number;
        $vcard->preview_template_name = $request->preview_template_name;
        $vcard->save();

        Session::flash('success', __('Updated successfully') . '!');
        return back();
    }

    public function editPtemplate(Request $request)
    {

        $vcard = UserVcard::find($request->vcard_id);

        if ($request->hasFile('preview_template_image')) {
            $img = $request->file('preview_template_image');
            $uploadPath = public_path('assets/front/img/user/prevtemplate/');

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            if (!empty($vcard->preview_template_image) && file_exists($uploadPath . $vcard->preview_template_image)) {
                @unlink($uploadPath . $vcard->preview_template_image);
            }

            // Save new image
            $filename = uniqid('tpl_') . '.' . $img->getClientOriginalExtension();
            $img->move($uploadPath, $filename);

            $vcard->preview_template_image = $filename;
        } else {
            $vcard->preview_template_image = $vcard->preview_template_image;
        }


        $vcard->preview_template_status = 1;
        $vcard->preview_template_serial_number = $request->preview_template_serial_number;
        $vcard->preview_template_name = $request->preview_template_name;
        $vcard->save();

        Session::flash('success', __('Updated successfully') . '!');
        return back();
    }

    public function deletePrevTemplate(Request $request)
    {
        $vcard = UserVcard::find($request->vcard_id);
        @unlink(public_path('assets/front/img/user/prevtemplate/' . $vcard->preview_template_image));
        $vcard->preview_template_status = 0;
        $vcard->save();
        Session::flash('success', __('Deleted successfully') . '!');
        return back();
    }


    public function delete(Request $request)
    {
        UserVcard::where('id', $request->vcard_id)->firstOrFail();
        $this->deleteVcard($request->vcard_id);
        Session::flash('success', __('Deleted successfully') . '!');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $this->deleteVcard($id);
        }
        Session::flash('success', __('Bulk Deleted successfully') . '!');
        return "success";
    }


    public function deleteVcard($id)
    {
        $vcard = UserVcard::findOrFail($id);
        /*
            ** Services Delete
            */
        $services = $vcard->user_vcard_services()->get();
        foreach ($services as $service) {
            $this->deleteService($service->id);
        }
        /*
            ** Project Delete
            */
        $projects = $vcard->user_vcard_projects()->get();
        foreach ($projects as $project) {
            $this->deleteProject($project->id);
        }
        /*
            ** Testimonial Delete
            */
        $testimonials = $vcard->user_vcard_testimonials()->get();
        foreach ($testimonials as $testimonial) {
            $this->deleteTestimonial($testimonial->id);
        }

        @unlink(public_path('assets/front/img/user/vcard/' . $vcard->profile_image));
        @unlink(public_path('assets/front/img/user/vcard/' . $vcard->cover_image));
        $vcard->delete();
    }


    public function deleteService($id)
    {
        $service = UserVcardService::findOrFail($id);
        @unlink(public_path('assets/front/img/user/services/' . $service->image));
        $service->delete();
    }
    public function deleteProject($id)
    {
        $project = UserVcardProject::findOrFail($id);
        @unlink(public_path('assets/front/img/user/projects/' . $project->image));
        $project->delete();
    }
    public function deleteTestimonial($id)
    {
        $testimonial = UserVcardTestimonial::findOrFail($id);
        @unlink(public_path('assets/front/img/user/testimonials/' . $testimonial->image));
        $testimonial->delete();
    }
}
