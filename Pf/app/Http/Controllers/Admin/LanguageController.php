<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\User\LanguageController as UserLanguageController;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use App\Models\Language;
use App\Models\User\Language as UserLanguage;
use App\Models\BasicSetting as BS;
use App\Models\BasicExtended as BE;
use App\Models\Menu;
use App\Models\User;
use DB;
use Validator;
use Session;

class LanguageController extends Controller
{
    public function index($lang = false)
    {
        $data['languages'] = Language::all();
        return view('admin.language.index', $data);
    }
    public function userlanguageSettings($lang = false)
    {
        $data['language'] = UserLanguage::first();
        return view('admin.language.user-language-setting', $data);
    }
    public function userlanguagekeywords($lang = false)
    {
        $data['userlanguage'] = UserLanguage::first();
        $data['json'] = json_decode($data['userlanguage']->keywords, true);
        return view('admin.language.user-language-keywords', $data);
    }

    /**
     * admin keywords add for admin dashboard  
     */

    public function addKeyword(Request $request)
    {

        $rules = [
            'keyword' => 'required|max:255',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        //    for  admin default file 
        $jsonData = file_get_contents(resource_path('lang/') . 'admin_default.json');
        $keywords = json_decode($jsonData, true);
        $datas = [];
        $datas[$request->keyword] = $request->keyword;
        foreach ($keywords as $key => $keyword) {
            $datas[$key] = $keyword;
        }
        //put data
        $jsonData = json_encode($datas);
        $fileLocated = resource_path('lang/') . 'admin_default.json';
        // put all the keywords in the selected language file
        file_put_contents($fileLocated, $jsonData);

        //    for  admin {languages} file 
        $languages = Language::all();
        foreach ($languages as $langkey => $language) {
            $jsonData = file_get_contents(resource_path('lang/') . 'admin_' . $language->code . '.json');
            $keywords = json_decode($jsonData, true);
            $datas = [];
            $datas[$request->keyword] = $request->keyword;
            foreach ($keywords as $key => $keyword) {
                $datas[$key] = $keyword;
            }
            //put data
            $jsonData = json_encode($datas);
            $fileLocated = resource_path('lang/') . 'admin_' . $language->code . '.json';
            // put all the keywords in the selected language file
            file_put_contents($fileLocated, $jsonData);
        }

        // get all the keywords of the selected language
        Session::flash('success', __('Store successfully'));
        return 'success';
    }

    /**
     * admin keywords add for tenant user dashboard  
     */

    public function addTenantDashboardKeyword(Request $request)
    {
    
        $rules = [
            'user_keyword' => 'required|max:255',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        //    for tenant default file 
        $jsonData = file_get_contents(resource_path('lang/') . 'user_default.json');
        $keywords = json_decode($jsonData, true);
        $datas = [];
        $datas[$request->user_keyword] = $request->user_keyword;
    
        foreach ($keywords as $key => $keyword) {
            $datas[$key] = $keyword;
        }
        
        //put data
        $jsonData = json_encode($datas);
        $fileLocated = resource_path('lang/') . 'user_default.json';
        // put all the keywords in the selected language file
        file_put_contents($fileLocated, $jsonData);

        //    for  tenant {languages} file 
        $languages = Language::all();
        foreach ($languages as $langkey => $language) {
            $jsonData = file_get_contents(resource_path('lang/') . 'user_' . $language->code . '.json');
            $keywords = json_decode($jsonData, true);
            $datas = [];
            $datas[$request->user_keyword] = $request->user_keyword;
            foreach ($keywords as $key => $keyword) {
                $datas[$key] = $keyword;
            }
            //put data
            $jsonData = json_encode($datas);
            $fileLocated = resource_path('lang/') . 'user_' . $language->code . '.json';
            // put all the keywords in the selected language file
            file_put_contents($fileLocated, $jsonData);
        }
        Session::flash('success', __('Store successfully'));
        return 'success';
    }

    /**
     * admin keywords added for admin  frontend keyword
     */
    public function addAdminFrontKeyword(Request $request)
    {
        $rules = [
            'front_keyword' => 'required|max:255',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        //    for  admin default file 
        $jsonData = file_get_contents(resource_path('lang/') . 'default.json');

        $keywords = json_decode($jsonData, true);
        $datas = [];
        $datas[$request->front_keyword] = $request->front_keyword;
        foreach ($keywords as $key => $keyword) {
            $datas[$key] = $keyword;
        }
        //put data
        $jsonData = json_encode($datas);
        $fileLocated = resource_path('lang/') . 'default.json';
        // put all the keywords in the selected language file
        file_put_contents($fileLocated, $jsonData);

        //    for  admin {languages} file 
        $languages = Language::all();
        foreach ($languages as $langkey => $language) {
            $jsonData = file_get_contents(resource_path('lang/') . $language->code . '.json');
            $keywords = json_decode($jsonData, true);
            $datas = [];
            $datas[$request->front_keyword] = $request->front_keyword;
            foreach ($keywords as $key => $keyword) {
                $datas[$key] = $keyword;
            }
            //put data
            $jsonData = json_encode($datas);
            $fileLocated = resource_path('lang/') . $language->code . '.json';
            // put all the keywords in the selected language file
            file_put_contents($fileLocated, $jsonData);
        }
        // get all the keywords of the selected language
        // convert json encoded string into a php associative array
        Session::flash('success', __('New Frontend Keyword Added successfully'));
        return 'success';
    }

    public function useraddKeyword(Request $request)
    {

        $rules = [
            'keyword' => 'required|max:255',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $userlanguage = UserLanguage::first();
        $keywords = json_decode($userlanguage->keywords, true);
        $datas = [];
        $datas[$request->keyword] = $request->keyword;
        foreach ($keywords as $key => $keyword) {
            $datas[$key] = $keyword;
        }
        //put data
        $jsonData = json_encode($datas);
        $userlanguage->keywords = $jsonData;
        $userlanguage->save();


        Session::flash('success', __('New Keyword Added successfully'));
        return "success";
    }

    /**
     * admin create a new keyword
     */
    public function store(Request $request)
    {

        $rules = [
            'name' => 'required|max:255',
            'code' => [
                'required',
                'max:255',
                'unique:languages'
            ],
            'direction' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        // this code for admin frontend
        $data = file_get_contents(resource_path('lang/') . 'default.json');
        $json_file = trim(strtolower($request->code)) . '.json';
        $path = resource_path('lang/') . $json_file;
        File::put($path, $data);

        // this code for admin dashboard
        $admin_data = file_get_contents(resource_path('lang/') . 'admin_default.json');
        $admin_json_file = trim(strtolower('admin_' . $request->code)) . '.json';
        $admin_path = resource_path('lang/') . $admin_json_file;
        File::put($admin_path, $admin_data);

        // this code for tenant user dashboard
        $user_data = file_get_contents(resource_path('lang/') . 'user_default.json');
        $user_json_file = trim(strtolower('user_' . $request->code)) . '.json';
        $user_path = resource_path('lang/') .  $user_json_file;
        File::put($user_path, $user_data);

        $defaultLang = Language::where('is_default', 1)->first();
        $in['name'] = $request->name;
        $in['code'] = $request->code;
        $in['rtl'] = $request->direction;
        // $in['user_keywords'] = $defaultLang->user_keywords;
        $in['customer_keywords'] = $defaultLang->customer_keywords;

        if (Language::where('is_default', 1)->count() > 0) {
            $in['is_default'] = 0;
        } else {
            $in['is_default'] = 1;
        }
        $lang = Language::create($in);

        // admin dashboard er validation file gulo new language er admin frontend er validation folder a files gulo copy kora hocce start
        // define the admin frontend validation path in the language folder
        $langFolderPathForAdminFrontend = resource_path('lang/' . $lang->code);

        if (!file_exists($langFolderPathForAdminFrontend)) {
            mkdir($langFolderPathForAdminFrontend, 0755, true);
        }

        // define the source path for the existing language files
        $sourcePath = resource_path('lang/admin_' . $lang->code);
        // Check if the source directory exists
        if (is_dir($sourcePath)) {
            $files = scandir($sourcePath);
            foreach ($files as $file) {
                // Skip the current and parent directory indicators
                if ($file !== '.' && $file !== '..') {
                    // Copy each file to the new language folder
                    copy($sourcePath . '/' . $file, $langFolderPathForAdminFrontend . '/' . $file);
                }
            }
        }

        // admin dashboard er validation file gulo new language er admin frontend er validation folder a files gulo copy kora hocce end

        // Load validation attributes for admin
        $validationFilePath = resource_path('lang/admin_' . $lang->code . '/validation.php');

        //update existing admin dashboard keywords for validation attributes
        $newKeys = $this->dashboardAttribute();
        $this->updateValidationAttribute($newKeys, $admin_data, $validationFilePath);


        // create and copy validation file for tenant dashboard 
        $langFolderPathForUserDashboard = resource_path('lang/user_' . $lang->code);
        if (!file_exists($langFolderPathForUserDashboard)) {
            mkdir($langFolderPathForUserDashboard, 0755, true);
        }

        // define the source path for the existing language files
        $sourcePathForUserDashboard = resource_path('lang/admin_' . $lang->code);
        // Check if the source directory exists
        if (is_dir($sourcePathForUserDashboard)) {
            $files = scandir($sourcePathForUserDashboard);
            foreach ($files as $file) {
                // Skip the current and parent directory indicators
                if ($file !== '.' && $file !== '..') {
                    // Copy each file to the new language folder
                    copy($sourcePathForUserDashboard . '/' . $file, $langFolderPathForUserDashboard . '/' . $file);
                }
            }
        }

        // Load validation attributes for admin
        $validationFilePath = resource_path('lang/admin_' . $lang->code . '/validation.php');

        //update existing admin dashboard keywords for validation attributes
        $newKeys = $this->dashboardAttribute();
        $this->updateValidationAttribute($newKeys, $admin_data, $validationFilePath);

        /// language add also user_languages table for user
        if ($lang) {
            $users = User::get();
            foreach ($users as $user) {
                $userLangs = $user->languages()->get();
                $updateUserLang = false;

                if ($userLangs) {
                    foreach ($userLangs as $uLang) {
                        if ($uLang && $uLang->code == $request->code) {
                            $uLang->update([
                                'type' => 'admin'
                            ]);
                            $updateUserLang = true;
                        }
                    }
                }
                if ($updateUserLang == false) {
                    UserLanguage::create([
                        'user_id' => $user->id,
                        'type' => 'admin',
                        'name' => $request->name,
                        'code' => $request->code,
                        'is_default' => 0,
                        'rtl' => $request->direction,
                        'keywords' => $defaultLang->customer_keywords
                    ]);
                }
            }
        }

        // duplicate First row of basic_settings for current language
        $dbs = Language::where('is_default', 1)->first()->basic_setting;
        $cols = json_decode($dbs, true);
        $bs = new BS;
        foreach ($cols as $key => $value) {
            // if the column is 'id' [primary key] then skip it
            if ($key == 'id') {
                continue;
            }
            // create favicon image using default language image & save unique name in database
            if ($key == 'favicon') {
                // take default lang image
                $dimg = public_path(url('/assets/front/img/') . '/' . $dbs->favicon);

                // copy paste the default language image with different unique name
                $filename = uniqid();
                if (($pos = strpos($dbs->favicon, ".")) !== FALSE) {
                    $ext = substr($dbs->favicon, $pos + 1);
                }
                $newImgName = $filename . '.' . $ext;
                @copy($dimg, public_path('assets/front/img/' . $newImgName));
                // save the unique name in database
                $bs[$key] = $newImgName;
                // continue the loop
                continue;
            }
            // create logo image using default language image & save unique name in database
            if ($key == 'logo') {
                // take default lang image
                $dimg = public_path(url('/assets/front/img/') . '/' . $dbs->logo);

                // copy paste the default language image with different unique name
                $filename = uniqid();
                if (($pos = strpos($dbs->logo, ".")) !== FALSE) {
                    $ext = substr($dbs->logo, $pos + 1);
                }
                $newImgName = $filename . '.' . $ext;

                @copy($dimg, public_path('assets/front/img/' . $newImgName));

                // save the unique name in database
                $bs[$key] = $newImgName;

                // continue the loop
                continue;
            }
            // create logo image using default language image & save unique name in database
            if ($key == 'preloader') {
                // take default lang image
                $dimg = public_path(url('/assets/front/img/') . '/' . $dbs->preloader);

                // copy paste the default language image with different unique name
                $filename = uniqid();
                if (($pos = strpos($dbs->preloader, ".")) !== FALSE) {
                    $ext = substr($dbs->preloader, $pos + 1);
                }
                $newImgName = $filename . '.' . $ext;

                @copy($dimg, public_path('assets/front/img/' . $newImgName));

                // save the unique name in database
                $bs[$key] = $newImgName;

                // continue the loop
                continue;
            }

            // create logo image using default language image & save unique name in database
            if ($key == 'maintenance_img') {
                // take default lang image
                $dimg = public_path(url('/assets/front/img/') . '/' . $dbs->maintenance_img);

                // copy paste the default language image with different unique name
                $filename = uniqid();
                if (($pos = strpos($dbs->maintenance_img, ".")) !== FALSE) {
                    $ext = substr($dbs->maintenance_img, $pos + 1);
                }
                $newImgName = $filename . '.' . $ext;

                @copy($dimg, public_path('assets/front/img/' . $newImgName));

                // save the unique name in database
                $bs[$key] = $newImgName;

                // continue the loop
                continue;
            }

            // create breadcrumb image using default language image & save unique name in database
            if ($key == 'breadcrumb') {
                // take default lang image
                $dimg = public_path(url('/assets/front/img/') . '/' . $dbs->breadcrumb);

                // copy paste the default language image with different unique name
                $filename = uniqid();
                if (($pos = strpos($dbs->breadcrumb, ".")) !== FALSE) {
                    $ext = substr($dbs->breadcrumb, $pos + 1);
                }
                $newImgName = $filename . '.' . $ext;

                @copy($dimg, public_path('assets/front/img/' . $newImgName));

                // save the unique name in database
                $bs[$key] = $newImgName;

                // continue the loop
                continue;
            }

            // create footer_logo image using default language image & save unique name in database
            if ($key == 'footer_logo') {
                // take default lang image
                $dimg = public_path(url('/assets/front/img/') . '/' . $dbs->footer_logo);

                // copy paste the default language image with different unique name
                $filename = uniqid();
                if (($pos = strpos($dbs->footer_logo, ".")) !== FALSE) {
                    $ext = substr($dbs->footer_logo, $pos + 1);
                }
                $newImgName = $filename . '.' . $ext;

                @copy($dimg, public_path('assets/front/img/' . $newImgName));

                // save the unique name in database
                $bs[$key] = $newImgName;

                // continue the loop
                continue;
            }

            // create intro_main_image image using default language image & save unique name in database
            if ($key == 'intro_main_image') {
                // take default lang image
                $dimg = public_path(url('/assets/front/img/') . '/' . $dbs->intro_main_image);

                // copy paste the default language image with different unique name
                $filename = uniqid();
                if (($pos = strpos($dbs->intro_main_image, ".")) !== FALSE) {
                    $ext = substr($dbs->intro_main_image, $pos + 1);
                }
                $newImgName = $filename . '.' . $ext;

                @copy($dimg, public_path('assets/front/img/' . $newImgName));

                // save the unique name in database
                $bs[$key] = $newImgName;

                // continue the loop
                continue;
            }

            $bs[$key] = $value;
        }
        $bs['language_id'] = $lang->id;
        $bs->save();

        // duplicate First row of basic_extendeds for current language
        $dbe = Language::where('is_default', 1)->first()->basic_extended;
        $be = BE::firstOrFail();
        $cols = json_decode($be, true);
        $be = new BE;
        foreach ($cols as $key => $value) {
            // if the column is 'id' [primary key] then skip it
            if ($key == 'id') {
                continue;
            }
            // create hero image using default language image & save unique name in database
            if ($key == 'hero_img') {
                // take default lang image
                $dimg = public_path(url('/assets/front/img/') . '/' . $dbe->hero_img);
                // copy paste the default language image with different unique name
                $filename = uniqid();
                if (($pos = strpos($dbe->hero_img, ".")) !== FALSE) {
                    $ext = substr($dbe->hero_img, $pos + 1);
                }
                $newImgName = $filename . '.' . $ext;
                @copy($dimg, public_path('assets/front/img/' . $newImgName));
                // save the unique name in database
                $be[$key] = $newImgName;
                // continue the loop
                continue;
            }
            $be[$key] = $value;
        }
        $be['language_id'] = $lang->id;
        $be->save();
        Menu::create([
            'language_id' => $lang->id,
            'menus' => '[{"text":"Home","href":"","icon":"empty","target":"_self","title":"","type":"home"},{"text":"Profiles","href":"","icon":"empty","target":"_self","title":"","type":"profiles"},{"text":"Pricing","href":"","icon":"empty","target":"_self","title":"","type":"pricing"},{"text":"FAQs","href":"","icon":"empty","target":"_self","title":"","type":"faq"},{"text":"Blogs","href":"","icon":"empty","target":"_self","title":"","type":"blogs"}]'
        ]);
        Session::flash('success', __('Store successfully!'));
        return "success";
    }

    /**
     * Edit admin language 
     */
    public function edit($id)
    {
        if ($id > 0) {
            $data['language'] = Language::findOrFail($id);
        }
        $data['id'] = $id;
        return view('admin.language.edit', $data);
    }

    public function update(Request $request)
    {
        $language = Language::findOrFail($request->language_id);

        $rules = [
            'name' => 'required|max:255',
            'code' => [
                'required',
                'max:255',
                Rule::unique('languages')->ignore($language->id),
            ],
            'direction' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        
        $language->name = $request->name;
        $language->code = $request->code;
        $language->rtl = $request->direction;
        $language->save();
        Session::flash('success', __('Updated successfully!'));
        return "success";
    }
    public function userlanguageupdate(Request $request)
    {
        $language = UserLanguage::first();
        $rules = [
            'name' => 'required|max:255',
            'code' => [
                'required',
                'max:255',
                // Rule::unique('user_languages')->ignore($language->id),
            ],
            'direction' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $language->name = $request->name;
        $language->name = $request->name;
        $language->code = $request->code;
        $language->rtl = $request->direction;
        $language->save();

        Session::flash('success', __('Updated successfully!'));
        return "success";
    }
    /**
     * admin front page  (edit keyword)
     */
    public function editKeyword($id)
    {
        $isAdmin =  0;
        if ($id > 0) {
            $la = Language::findOrFail($id);
            $json = file_get_contents(resource_path('lang/') . $la->code . '.json');
            $json = json_decode($json, true);
            $list_lang = Language::all();
            if (empty($json)) {
                return back()->with('alert', __('File Not Found') . '.');
            }
            return view('admin.language.edit-keyword', compact('json', 'la', 'isAdmin'));
        } elseif ($id == 0) {
            $json = file_get_contents(resource_path('lang/') . 'default.json');
            $json = json_decode($json, true);
            if (empty($json)) {
                return back()->with('alert', __('File Not Found') . '.');
            }
            return view('admin.language.edit-keyword', compact('json', 'isAdmin'));
        }
    }

    /**
     * admin dashboard (edit keyword)
     */
    public function editAdminKeyword($id)
    {

        $isAdmin =  1;
        if ($id > 0) {
            $la = Language::findOrFail($id);
            $json = file_get_contents(resource_path('lang/') . 'admin_' . $la->code . '.json');
            $json = json_decode($json, true);
            
            $list_lang = Language::all();
            if (empty($json)) {
                return back()->with('alert', __('File Not Found') . '.');
            }
            return view('admin.language.edit-keyword', compact('json', 'la', 'isAdmin'));
        } elseif ($id == 0) {
            $json = file_get_contents(resource_path('lang/') . 'admin_' . 'default.json');
            $json = json_decode($json, true);
            if (empty($json)) {
                return back()->with('alert', __('File Not Found') . '.');
            }
            return view('admin.language.edit-keyword', compact('json', 'isAdmin'));
        }
    }
    /**
     * admin keyword update admin frontend and dashboard
     */
    public function updateKeyword(Request $request, $id)
    {

        $lang = Language::findOrFail($id);
        $content = json_encode($request->keys);
        if ($content === 'null') {
            return back()->with('alert', __('At Least One Field Should Be Fill-up'));
        }

        
        // Load validation attributes
        $validationFilePath = resource_path('lang/admin_' . $lang->code . '/validation.php');

        //update existing attributes
        $newKeys = $this->dashboardAttribute();
        $this->updateValidationAttribute($newKeys, $content, $validationFilePath);

        if ($request->isAdmin) {
            file_put_contents(resource_path('lang/') . 'admin_' . $lang->code . '.json', $content);
        } else {
            // Load validation attributes
            $validationFilePath = resource_path('lang/' . $lang->code . '/validation.php');

            //update existing attributes
            $newKeys = $this->frontAttribute();
            $this->updateValidationAttribute($newKeys, $content, $validationFilePath);
            file_put_contents(resource_path('lang/') . $lang->code . '.json', $content);
        }

        Session::flash('success', __('Updated successfully!'));
        return 'success';
    }

    public function updateUserKeyword(Request $request)
    {
        $lang = UserLanguage::first();

        $keywords = $request->except('_token');
        $lang->keywords = $keywords;
        $lang->save();
        return back()->with('success', __('Updated successfully!'));
    }


    public function delete($id)
    {
        $la = Language::findOrFail($id);

        $users = User::get();
        foreach ($users as $user) {
            $lang = UserLanguage::where([['user_id', $user->id], ['code', $la->code]])->first();

            if ($lang) {
                $this->userLanguageDelete($user->id, $lang->id);
            }
        }

        if ($la->is_default == 1) {
            return back()->with('warning', __('Default language cannot be deleted') . '!');
        }

        // Delete language validation folder and its contents for admin dashboard and frontend
        $dir = resource_path('lang/') . $la->code;
        if (is_dir($dir)) {
            $this->deleteDirectory($dir);
        }

        if (session()->get('lang') == $la->code) {
            session()->forget('lang');
        }

        @unlink(public_path('assets/front/img/languages/' . $la->icon));
        @unlink(resource_path('lang/') . $la->code . '.json');
        @unlink(resource_path('lang/') . 'admin_' . $la->code . '.json');

        $adminLang = session()->get('admin_lang');
        if ($adminLang == 'admin_' . $la->code) {
            session()->forget('admin_lang');
        }
        // Delete language validation folder and its contents for tenant user dashboard
        $dir = resource_path('lang/user_') . $la->code;
        if (is_dir($dir)) {
            $this->deleteDirectory($dir);
        }
        @unlink(public_path('assets/front/img/languages/' . $la->icon));
        @unlink(resource_path('lang/') . 'user_' . $la->code . '.json');

        // deleting basic_settings and basic_extended for corresponding language & unlink images
        $bs = $la->basic_setting;
        if (!empty($bs)) {

            @unlink(public_path('assets/front/img/' . $bs->favicon));

            @unlink(public_path('assets/front/img/' . $bs->logo));

            @unlink(public_path('assets/front/img/' . $bs->preloader));

            @unlink(public_path('assets/front/img/' . $bs->breadcrumb));

            @unlink(public_path('assets/front/img/' . $bs->intro_main_image));

            @unlink(public_path('assets/front/img/' . $bs->footer_logo));

            @unlink(public_path('assets/front/img/' . $bs->maintenance_img));

            $bs->delete();
        }
        $be = $la->basic_extended;
        if (!empty($be)) {
            @unlink(public_path('assets/front/img/' . $be->hero_img));
            $be->delete();
        }



        // deleting pages for corresponding language
        if (!empty($la->pages)) {
            $la->pages()->delete();
        }

        // deleting testimonials for corresponding language
        if (!empty($la->testimonials)) {
            $testimonials = $la->testimonials;
            foreach ($testimonials as $testimonial) {
                @unlink(public_path('assets/front/img/testimonials/' . $testimonial->image));
                $testimonial->delete();
            }
        }


        // deleting feature for corresponding language
        if (!empty($la->features)) {
            $features = $la->features;
            foreach ($features as $feature) {
                $feature->delete();
            }
        }


        // deleting services for corresponding language
        if (!empty($la->blogs)) {
            $blogs = $la->blogs;
            foreach ($blogs as $blog) {
                @unlink(public_path('assets/front/img/blogs/' . $blog->main_image));
                $blog->delete();
            }
        }

        // deleting blog categories for corresponding language
        if (!empty($la->bcategories)) {
            $bcategories = $la->bcategories;
            foreach ($bcategories as $bcat) {
                $bcat->delete();
            }
        }

        // deleting partners for corresponding language
        if (!empty($la->partners)) {
            $partners = $la->partners;
            foreach ($partners as $partner) {
                @unlink(public_path('assets/front/img/partners/' . $partner->image));
                $partner->delete();
            }
        }

        // deleting processes for corresponding language
        if (!empty($la->processes)) {
            $processes = $la->processes;
            foreach ($processes as $process) {
                @unlink(public_path('assets/front/img/process/' . $process->image));
                $process->delete();
            }
        }

        // deleting partners for corresponding language
        if (!empty($la->popups)) {
            $popups = $la->popups;
            foreach ($popups as $popup) {
                @unlink(public_path('assets/front/img/popups/' . $popup->background_image));
                @unlink(public_path('assets/front/img/popups/' . $popup->image));
                $popup->delete();
            }
        }

        // deleting useful links for corresponding language
        if (!empty($la->ulinks)) {
            $la->ulinks()->delete();
        }

        // deleting faqs for corresponding language
        if (!empty($la->faqs)) {
            $la->faqs()->delete();
        }

        // deleting menus for corresponding language
        if (!empty($la->menus)) {
            $la->menus()->delete();
        }

        // deleting seo for corresponding language
        if (!empty($la->seo)) {
            $la->seo->delete();
        }
        if (!empty($la->menus)) {
            $la->menus()->delete();
        }

        // if the the deletable language is the currently selected language in frontend then forget the selected language from session
        session()->forget('lang');
        session()->forget('admin_lang');
        session()->forget('currentLangCode');
        session()->forget('userDashboardLang');
        session()->forget('user_lang');

        $la->delete();
        return back()->with('success', __('Deleted successfully!'));
    }

    public function userLanguageDelete($userId, $id)
    {
        $la = UserLanguage::where('user_id', $userId)->where('id', $id)->firstOrFail();
        if ($la->is_default == 1) {
            return;
        }

        // deleting services for corresponding language
        if (!empty($la->services)) {
            $services = $la->services;
            if (!empty($services)) {
                foreach ($services as $service) {
                    @unlink(public_path('assets/front/img/user/services/' . $service->image));
                    $service->delete();
                }
            }
        }
        // deleting testimonials for corresponding language
        if (!empty($la->testimonials)) {
            $testimonials = $la->testimonials;
            if (!empty($testimonials)) {
                foreach ($testimonials as $testimonial) {
                    @unlink(public_path('assets/front/img/user/testimonials/' . $testimonial->image));
                    $testimonial->delete();
                }
            }
        }
        // deleting blogs for corresponding language
        if (!empty($la->blogs)) {
            $blogs = $la->blogs;
            if (!empty($blogs)) {
                foreach ($blogs as $blog) {
                    @unlink(public_path('assets/front/img/user/blogs/' . $blog->image));
                    $blog->delete();
                }
            }
        }
        // deleting blog categories for corresponding language
        if (!empty($la->blog_categories)) {
            $blogCategories = $la->blog_categories;
            if (!empty($blogCategories)) {
                foreach ($blogCategories as $blogCategory) {
                    $blogCategory->delete();
                }
            }
        }
        // deleting skills for corresponding language
        if (!empty($la->skills)) {
            $skills = $la->skills;
            if (!empty($skills)) {
                foreach ($skills as $skill) {
                    @unlink(public_path('assets/front/img/user/skills/' . $skill->image));
                    $skill->delete();
                }
            }
        }
        // deleting portfolios for corresponding language
        if (!empty($la->portfolios)) {
            $portfolios = $la->portfolios;
            if (!empty($portfolios)) {
                foreach ($portfolios as $portfolio) {
                    $pis = $portfolio->portfolio_images;
                    if (!empty($pis)) {
                        foreach ($pis as $key => $pi) {
                            @unlink(public_path('assets/front/img/user/portfolios/' . $pi->image));
                            $pi->delete();
                        }
                    }
                    @unlink(public_path('assets/front/img/user/portfolios/' . $portfolio->image));
                    $portfolio->delete();
                }
            }
        }
        // deleting portfolio categories for corresponding language
        if (!empty($la->portfolio_categories)) {
            $portfolioCategories = $la->portfolio_categories;
            if (!empty($portfolioCategories)) {
                foreach ($portfolioCategories as $portfolioCategory) {
                    $portfolioCategory->delete();
                }
            }
        }
        // deleting job experience for corresponding language
        if (!empty($la->job_experiences)) {
            $job_experiences = $la->job_experiences;
            if (!empty($job_experiences)) {
                foreach ($job_experiences as $job_experience) {
                    $job_experience->delete();
                }
            }
        }
        // deleting educations for corresponding language
        if (!empty($la->educations)) {
            $educations = $la->educations;
            if (!empty($educations)) {
                foreach ($educations as $education) {
                    $education->delete();
                }
            }
        }
        // deleting seos for corresponding language
        if (!empty($la->seos)) {
            $seos = $la->seos;
            if (!empty($seos)) {
                foreach ($seos as $seo) {
                    $seo->delete();
                }
            }
        }
        // deleting home page texts for corresponding language
        if (!empty($la->home_page_texts)) {
            $home_page_texts = $la->home_page_texts;
            if (!empty($home_page_texts)) {
                foreach ($home_page_texts as $homeText) {
                    @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->hero_image));
                    @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->about_image));
                    @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->skills_image));
                    @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->achievement_image));
                    $homeText->delete();
                }
            }
        }
        // deleting achievements for corresponding language
        if (!empty($la->achievements)) {
            $achievements = $la->achievements;
            if (!empty($achievements)) {
                foreach ($achievements as $achievement) {
                    $achievement->delete();
                }
            }
        }
        // deleting appointment category for corresponding language
        if (!empty($la->appointment_categories)) {
            $appointment_categories = $la->appointment_categories;
            if (!empty($appointment_categories)) {
                foreach ($appointment_categories as $category) {
                    @unlink(public_path('assets/user/img/category/' . $category->image));
                    $category->delete();
                }
            }
        }
        // deleting form inputs for corresponding language
        if (!empty($la->form_inputs)) {
            $form_inputs = $la->form_inputs;
            if (!empty($form_inputs)) {
                foreach ($form_inputs as $input) {
                    if ($input->form_input_options()->count() > 0) {
                        $input->form_input_options()->delete();
                    }
                    $input->delete();
                }
            }
        }
        $la->delete();
        return;
    }


    public function default(Request $request, $id)
    {
        Language::where('is_default', 1)->update(['is_default' => 0]);
        $lang = Language::find($id);
        $lang->is_default = 1;
        $lang->save();
        return back()->with('success', $lang->name .' ' .__('language is set as default').'.');
    }

    public function rtlcheck($langid)
    {
        if ($langid > 0) {
            $lang = Language::find($langid);
        } else {
            return 0;
        }

        return $lang->rtl;
    }

    /**
     * edit user dashboard (edit keyword)
     */

    // public function editUserKeyword($id)
    // {
    //     $la = Language::findOrFail($id);

    //     $json = json_decode($la->user_keywords, true);

    //     return view('admin.language.edit-user-keyword', compact('json', 'la'));
    // } 

    public function editUserKeyword($id)
    {
        $la = Language::findOrFail($id);

        // 1. Construct file path
        $filePath = resource_path("lang/") . 'user_' . $la->code . '.json';
        
        // 2. Verify file existence
        if (!File::exists($filePath)) {
            abort(404, __('Language file not found for') . " {$la->name}");
        }

        // 3. Read and decode JSON
        $jsonContents = File::get($filePath);
     
        $json = json_decode($jsonContents, true);
        // dd($jsonContents);
      

        // 4. Validate JSON structure
        if (json_last_error() !== JSON_ERROR_NONE) {
            abort(500, __('Invalid JSON format in') . " {$la->code} " . __('language file'));
        }

        return view('admin.language.edit-user-keyword', compact('json', 'la'));
    }

    /**
     * update tenant user dashboard keywords
     */

    // public function updateUserDashboardKeyword($id, Request $request)
    // {
        
    //     $lang = Language::findOrFail($id);
    //     $content = json_encode($request->keys);
    //     if ($content === 'null') {
    //         return back()->with('alert', __('At Least One Field Should Be Fill-up'));
    //     }

    //     $lang->user_keywords = $content;
    //     $lang->save();

    //     Session::flash('success', __('Updated successfully!'));
    //     return 'success';
    // }
    public function updateUserDashboardKeyword($id, Request $request)
    {

        $lang = Language::findOrFail($id);
        $content = json_encode($request->keys);
        if ($content === 'null') {
            return back()->with('alert', __('At Least One Field Should Be Fill-up'));
        }

        // Load validation attributes
        $validationFilePath = resource_path('lang/user_' . $lang->code . '/validation.php');

        //update existing attributes
        $newKeys = $this->tenantUserDashboardAttribute();
        $this->updateValidationAttribute($newKeys, $content, $validationFilePath);

        if ($request->isAdmin) {
            file_put_contents(resource_path('lang/') . 'user_' . $lang->code . '.json', $content);
        } else {
            // Load validation attributes
            $validationFilePath = resource_path('lang/' . 'user_'.$lang->code . '/validation.php');

            //update existing attributes
            $newKeys = $this->frontAttribute();
            $this->updateValidationAttribute($newKeys, $content, $validationFilePath);
            file_put_contents(resource_path('lang/') . 'user_' .$lang->code . '.json', $content);
        }

        Session::flash('success', __('Updated successfully!'));
        return 'success';
    }

    /**
     * edit user frontend keywords (edit keyword)
     */
    public function editCustomerKeyword($id)
    {
        $la = Language::findOrFail($id);

        $json = json_decode($la->customer_keywords, true);

        return view('admin.language.edit-customer-keyword', compact('json', 'la'));
    }
    /**
     * update user or tenant frontend keywords
     */

    public function updateCustomerKeyword($id, Request $request)
    {
        $lang = Language::findOrFail($id);
        $content = json_encode($request->keys);
        if ($content === 'null') {
            return back()->with('alert', __('At Least One Field Should Be Fill-up'));
        }

        $lang->customer_keywords = $content;
        $lang->save();

        Session::flash('success', __('Updated successfully!'));
        return 'success';
    }

    //admin dashboard custom  attribute
    public function dashboardAttribute()
    {
        $newKeys = [
            'name' => 'name',
            'username' => 'username',
            'email' => 'email address',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'password' => 'password',
            'new_password' => 'new password',
            'password_confirmation' => 'confirm password',
            'city' => 'city',
            'country' => 'country',
            'address' => 'address',
            'phone' => 'phone',
            'mobile' => 'mobile',
            'age' => 'age',
            'sex' => 'sex',
            'gender' => 'gender',
            'day' => 'day',
            'month' => 'month',
            'year' => 'year',
            'hour' => 'hour',
            'minute' => 'minute',
            'second' => 'second',
            'title' => 'title',
            'subtitle' => 'subtitle',
            'text' => 'text',
            'description' => 'description',
            'content' => 'content',
            'occupation' => 'occupation',
            'comment' => 'comment',
            'rating' => 'rating',
            'terms' => 'terms',
            'question' => 'question',
            'answer' => 'answer',
            'status' => 'status',
            'term' => 'term',
            'price' => 'price',
            'amount' => 'amount',
            'date' => 'date',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'value' => 'value',
            'type' => 'type',
            'code' => 'code',
            'url' => 'url',
            'stock' => 'stock',
            'delay' => 'delay',
            'image' => 'image',
            'tax' => 'tax',
            'language_id' => 'language',
            'serial_number' => 'serial number',
            'equipment_limit' => 'equipment limit',
            'equipment_categories_limit' => 'equipment categories limit',
            'booking_limit' => 'booking limit',
            'equipment_image_limit' => 'equipment image limit',
            'storage_limit' => 'storage limit',
            'product_limit' => 'product limit',
            'number_of_product_image' => 'number of product image',
            'order_limit' => 'order limit',
            'end_date' => 'end date',
            'start_date' => 'start date',
            'maximum_uses_limit' => 'maximum uses limit',
            'expiration_reminder' => 'expiration reminder',
            'cname_record_section_title' => 'cname record section title',
            'success_message' => 'success message',
            'cname_record_section_text' => 'cname record section text',
            'success_message' => 'success message',
            "rank" => "rank",
            "position" => "position",
            "order_number" => "order number",
            "bcategory" => "category",
            "category" => "category",
            "contact_addresses" => "contact addresses",
            "contact_numbers" => "contact numbers",
            "contact_mails" => "contact mails",
            "body" => "body",
            "subject" => "subject",
            "message" => "message",
            "key" => "key",
            "secret" => "secret",
            "status" => "status",
            "secret_key" => "secret key",
            "token" => "token",
            "category_code" => "category code",
            "profile_id" => "profile id",
            "server_key" => "server key",
            "api_endpoint" => "api endpoint",
            "country" => "country",
            "merchant_id" => "merchant id",
            "salt_index" => "salt index",
            "salt_key" => "salt key",
            "client_id" => "client id",
            "client_secret" => "client secret",
            "merchant" => "merchant",
            "website" => "website",
            "industry" => "industry",
            "api_key" => "api key",
            "transaction_key" => "transaction key",
            "login_id" => "login id",
            "midtrans_mode" => "midtrans mode",
            "website_title" => "website title",
            "favicon" => "favicon",
            "logo" => "logo",
            "preloader" => "preloader",
            "timezone" => "timezone",
            "base_currency_symbol" => "base currency symbol",
            "base_currency_symbol_position" => "base currency symbol position",
            "base_currency_text" => "base_currency text",
            "base_currency_text_position" => "base currency text position",
            "base_currency_rate" => "base currency rate",
            "max_video_size" => "max video size",
            "max_file_size" => "max file size",
            'smtp_host' => 'smtp host',
            'smtp_port' => 'smtp port',
            'encryption' => 'encryption',
            'from_name' => 'from name',
            'smtp_password' => 'smtp password',
            'smtp_username' => 'smtp username', 
            'to_mail' => 'to mail',
            'email_subject' => 'email subject',
            'mail_subject' => 'mail subject',
            'email_body' => 'email body',
            'maintainance_text' => 'maintainance text',
            'maintenance_status' => 'maintenance status',
            'cookie_alert_button_text' => 'cookie alert button text',
            'cookie_alert_text' => 'cookie alert text',
            'admin_keyword' => 'admin keyword',
            'keyword' => 'keyword',
            'direction' => 'direction',
            "user_language_id" => "language",
            "old_password" => "current password",
            "min_booking_day" => "min booking day",
            "max_booking_day" => "max booking day",
            "day_price" => "day price",
            "weekly_price" => "weekly price",
            "monthly_price" => "monthly price",
            "hourly_price" => "hourly price",
            "booking_interval" => "booking interval",
            "charge" => "charge",
            "thumbnail" => "thumbnail",
            "symbol" => "symbol",
            "current_price" => "current price",
            "feature_title" => "feature title",
            "contact_number" => "contact number",
            "email_address" => "email address",
            "about_company" => "about company",
            "copyright_text" => "copyright text",
            "google_adsense_publisher_id" => "google adsense publisher id",
            "ad_type" => "ad type",
            "resolution_type" => "resolution type",
            "button_url" => "button url",
            "button_text" => "button text",
            "background_color_opacity" => "background color opacity",
            "end_time" => "end time",
            "aws_access_key_id" => "aws access key id",
            "aws_secret_access_key" => "aws secret access key",
            "aws_default_region" => "aws default region",
            "aws_bucket" => "aws bucket",
            "disqus_short_name" => "disqus short name",
            "whatsapp_number" => "whatsapp number",
            "whatsapp_header_title" => "whatsapp header title",
            "whatsapp_popup_message" => "whatsapp popup message",
            "maintenance_msg" => "maintenance message",
            "maintenance_img" => "maintenance image",
            "cookie_alert_btn_text" => "cookie alert button text",
            "number_of_languages" => "number of languages",
            "trial_days" => "Trial days",
            "number_of_blogs" => "number_of_blogs",
            "number_of_blog_categories" => "number_of_blog_categories",
            "number_of_portfolios" => "number_of_portfolios",
            "number_of_portfolio_categories" => "number_of_portfolio_categories",
            "number_of_skills" => "number_of_skills",
            "number_of_services" => "number_of_services",
            "number_of_job_expriences" => "number_of_job_expriences",
            "number_of_education" => "number_of_education",
            "themes" => "themes",
            "number_of_vcards" => "number_of_vcards",
            "user_login_attempts" => "user_login_attempts",
            "user_registration_deactive_text" => "user_registration_deactive_text",
            "front_keyword" => "front_keyword",
            "measurement_id" => "measurement id",
            "disqus_short_name" => "disqus short name",
            "pixel_id" => "pixel id",
            "tawkto_direct_chat_link" => "tawkto_direct_chat_link",
            "percentage" => "percentage",
            "company_name" => "company_name",
            "designation" => "designation",
            "degree_name" => "degree_name",
            "count" => "count",
            "slider_images" => "slider_images",
            "start" => "start",
            "end" => "end",
            "vcard_name" => "vcard_name",
            "cv_name" => "cv_name",
            "invalid_currency" => "invalid_currency",
            "only_paytm_INR" => "only_paytm_INR",
            "only_paystack_NGN" => "only_paystack_NGN",
            "only_mercadopago_BRL" => "only_mercadopago_BRL",
            "max_booking" => "max_booking",
            "You_already_have_a_Pending_Membership_Request" => "You already have a Pending Membership Request",
            "activate_your_next_package_after_current_package_expires" => "You have another package to activate after the current package expires. You cannot purchase / extend any package, until the next package is activated",
            "preview_image" => "preview_image",
            "payment_method" => "payment_method",
            "receipt" => "receipt",
            "user_keyword" => "user_keyword",
            "sitemap_url" => "sitemap_url",
            "role_id" => "role_id",
        ];
        return $newKeys;
    }
    //tenant user dashboard custom  attribute
    public function tenantUserDashboardAttribute()
    {
        $newKeys = [
            'name' => 'name',
            'username' => 'username',
            'email' => 'email address',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'password' => 'password',
            'new_password' => 'new password',
            'password_confirmation' => 'confirm password',
            'city' => 'city',
            'country' => 'country',
            'address' => 'address',
            'phone' => 'phone',
            'mobile' => 'mobile',
            'age' => 'age',
            'sex' => 'sex',
            'gender' => 'gender',
            'day' => 'day',
            'month' => 'month',
            'year' => 'year',
            'hour' => 'hour',
            'minute' => 'minute',
            'second' => 'second',
            'title' => 'title',
            'subtitle' => 'subtitle',
            'text' => 'text',
            'description' => 'description',
            'content' => 'content',
            'occupation' => 'occupation',
            'comment' => 'comment',
            'rating' => 'rating',
            'terms' => 'terms',
            'question' => 'question',
            'answer' => 'answer',
            'status' => 'status',
            'term' => 'term',
            'price' => 'price',
            'amount' => 'amount',
            'date' => 'date',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'value' => 'value',
            'type' => 'type',
            'code' => 'code',
            'url' => 'url',
            'stock' => 'stock',
            'delay' => 'delay',
            'image' => 'image',
            'tax' => 'tax',
            'language_id' => 'language',
            'serial_number' => 'serial number',
            'equipment_limit' => 'equipment limit',
            'equipment_categories_limit' => 'equipment categories limit',
            'booking_limit' => 'booking limit',
            'equipment_image_limit' => 'equipment image limit',
            'storage_limit' => 'storage limit',
            'product_limit' => 'product limit',
            'number_of_product_image' => 'number of product image',
            'order_limit' => 'order limit',
            'end_date' => 'end date',
            'start_date' => 'start date',
            'maximum_uses_limit' => 'maximum uses limit',
            'expiration_reminder' => 'expiration reminder',
            'cname_record_section_title' => 'cname record section title',
            'success_message' => 'success message',
            'cname_record_section_text' => 'cname record section text',
            'success_message' => 'success message',
            "rank" => "rank",
            "position" => "position",
            "order_number" => "order number",
            "bcategory" => "category",
            "category" => "category",
            "contact_addresses" => "contact addresses",
            "contact_numbers" => "contact numbers",
            "contact_mails" => "contact mails",
            "body" => "body",
            "subject" => "subject",
            "message" => "message",
            "key" => "key",
            "secret" => "secret",
            "status" => "status",
            "secret_key" => "secret key",
            "token" => "token",
            "category_code" => "category code",
            "profile_id" => "profile id",
            "server_key" => "server key",
            "api_endpoint" => "api endpoint",
            "country" => "country",
            "merchant_id" => "merchant id",
            "salt_index" => "salt index",
            "salt_key" => "salt key",
            "client_id" => "client id",
            "client_secret" => "client secret",
            "merchant" => "merchant",
            "website" => "website",
            "industry" => "industry",
            "api_key" => "api key",
            "transaction_key" => "transaction key",
            "login_id" => "login id",
            "midtrans_mode" => "midtrans mode",
            "website_title" => "website title",
            "favicon" => "favicon",
            "logo" => "logo",
            "preloader" => "preloader",
            "timezone" => "timezone",
            "base_currency_symbol" => "base currency symbol",
            "base_currency_symbol_position" => "base currency symbol position",
            "base_currency_text" => "base_currency text",
            "base_currency_text_position" => "base currency text position",
            "base_currency_rate" => "base currency rate",
            "max_video_size" => "max video size",
            "max_file_size" => "max file size",
            'smtp_host' => 'smtp host',
            'smtp_port' => 'smtp port',
            'encryption' => 'encryption',
            'from_name' => 'from name',
            'smtp_password' => 'smtp password',
            'smtp_username' => 'smtp username',
            'to_mail' => 'to mail',
            'email_subject' => 'email subject',
            'mail_subject' => 'mail subject',
            'email_body' => 'email body',
            'maintainance_text' => 'maintainance text',
            'maintenance_status' => 'maintenance status',
            'cookie_alert_button_text' => 'cookie alert button text',
            'cookie_alert_text' => 'cookie alert text',
            'admin_keyword' => 'admin keyword',
            'keyword' => 'keyword',
            'direction' => 'direction',
            "user_language_id" => "language",
            "old_password" => "current password",
            "min_booking_day" => "min booking day",
            "max_booking_day" => "max booking day",
            "day_price" => "day price",
            "weekly_price" => "weekly price",
            "monthly_price" => "monthly price",
            "hourly_price" => "hourly price",
            "booking_interval" => "booking interval",
            "charge" => "charge",
            "thumbnail" => "thumbnail",
            "symbol" => "symbol",
            "current_price" => "current price",
            "feature_title" => "feature title",
            "contact_number" => "contact number",
            "email_address" => "email address",
            "about_company" => "about company",
            "copyright_text" => "copyright text",
            "google_adsense_publisher_id" => "google adsense publisher id",
            "ad_type" => "ad type",
            "resolution_type" => "resolution type",
            "button_url" => "button url",
            "button_text" => "button text",
            "background_color_opacity" => "background color opacity",
            "end_time" => "end time",
            "aws_access_key_id" => "aws access key id",
            "aws_secret_access_key" => "aws secret access key",
            "aws_default_region" => "aws default region",
            "aws_bucket" => "aws bucket",
            "disqus_short_name" => "disqus short name",
            "whatsapp_number" => "whatsapp number",
            "whatsapp_header_title" => "whatsapp header title",
            "whatsapp_popup_message" => "whatsapp popup message",
            "maintenance_msg" => "maintenance message",
            "maintenance_img" => "maintenance image",
            "cookie_alert_btn_text" => "cookie alert button text",
            "number_of_languages" => "number of languages",
            "trial_days" => "Trial days",
            "number_of_blogs" => "number_of_blogs",
            "number_of_blog_categories" => "number_of_blog_categories",
            "number_of_portfolios" => "number_of_portfolios",
            "number_of_portfolio_categories" => "number_of_portfolio_categories",
            "number_of_skills" => "number_of_skills",
            "number_of_services" => "number_of_services",
            "number_of_job_expriences" => "number_of_job_expriences",
            "number_of_education" => "number_of_education",
            "themes" => "themes",
            "number_of_vcards" => "number_of_vcards",
            "user_login_attempts" => "user_login_attempts",
            "user_registration_deactive_text" => "user_registration_deactive_text",
            "front_keyword" => "front_keyword",
            "measurement_id" => "measurement id",
            "disqus_short_name" => "disqus short name",
            "pixel_id" => "pixel id",
            "tawkto_direct_chat_link" => "tawkto_direct_chat_link",
            "percentage" => "percentage",
            "company_name" => "company_name",
            "designation" => "designation",
            "degree_name" => "degree_name",
            "count" => "count",
            "slider_images" => "slider_images",
            "start" => "start",
            "end" => "end",
            "vcard_name" => "vcard_name",
            "cv_name" => "cv_name",
            "invalid_currency" => "invalid_currency",
            "only_paytm_INR" => "only_paytm_INR",
            "only_paystack_NGN" => "only_paystack_NGN",
            "only_mercadopago_BRL" => "only_mercadopago_BRL",
            "max_booking" => "max_booking",
            "You_already_have_a_Pending_Membership_Request" => "You already have a Pending Membership Request",
            "activate_your_next_package_after_current_package_expires" => "You have another package to activate after the current package expires. You cannot purchase / extend any package, until the next package is activated",
            "preview_image" => "preview_image",
            "payment_method" => "payment_method",
            "receipt" => "receipt",
            "percentage" => "percentage",
            "detail_page" => "detail page",
            "Password_changed_successfully!" => "Password changed successfully!",
            "profile_image" => "profile_image",
            "column" => "column",
            "cv" => "cv",

        ];
        return $newKeys;
    }

        // admin frontend attribute
    public function frontAttribute()
    {
        $newKeys = [
            'name' => 'name',
            'username' => 'username',
            'email' => 'email address',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'password' => 'password',
            'password_confirmation' => 'confirm password',
            'city' => 'city',
            'country' => 'country',
            'address' => 'address',
            'phone' => 'phone',
            'gender' => 'gender',
            'company_name' => 'company name',
            "subject" => "subject",
            "message" => "message",
            "payment_method" => "payment method",
            "only_paypal_INR" => "only_paypal_INR",
            "only_paytm_INR" => "only_paytm_INR",
            "only_mercadopago_BRL" => "only_mercadopago_BRL",
            "only_paystack_NGN" => "only_paystack_NGN",
            "only_razorpay_INR" => "only_razorpay_INR",
            "only_instamojo_INR" => "only_instamojo_INR",
            "only_INR" => "only_INR",
            "invalid_currency" => "invalid_currency",
            "payment_success" => "payment_success",
            "current_password" => "current_password",
            "new_password" => "new_password",
            "new_password_confirmation" => "new_password_confirmation",
            "fullname" => "fullname",
            "receipt" => "receipt",
        ];
        return $newKeys;
    }

    // this function update the validation custom attribute and check language code exist or not 
    public function updateValidationAttribute($newKeys, $content, $validationFilePath)
    {
        
        try {
            // Load the existing validation array
            $validation = include($validationFilePath);

            // Ensure 'attributes' key exists
            if (!isset($validation['attributes']) || !is_array($validation['attributes'])) {
                $validation['attributes'] = [];
            }
        } catch (\Exception $e) {
           
            session()->flash('warning', __('Please provide a valid language code!'));
            return;
        }

        //update existing keys
        foreach ($newKeys as $key => $value) {
            if (!array_key_exists($key, $validation['attributes'])) {
                $validation['attributes'][$key] = $value;
            }
        }

        // update values which matching keys with new values
        $decodedContent = json_decode($content, true);
        if (is_array($decodedContent)) {
            foreach ($decodedContent as $key => $value) {
                if (array_key_exists($key, $validation['attributes'])) {
                    $validation['attributes'][$key] = $value;
                }
            }
        }

        //save the changes in validation attributes array
        $validationContent = "<?php\n\nreturn " . var_export($validation, true) . ";\n";
        file_put_contents($validationFilePath, $validationContent);
    }

    //delete a directory recursively 
    private function deleteDirectory($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = "$dir/$file";
            if (is_dir($filePath)) {
                $this->deleteDirectory($filePath);
            } else {
                @unlink($filePath);
            }
        }
        rmdir($dir);
    }




    protected function adminLanguageKeywords($code)
    {
        $admin_data = file_get_contents(resource_path('lang/') . 'admin_default.json');
        $admin_json_file = 'admin_' . $code . '.json';
        $admin_path = resource_path('lang/') . $admin_json_file;
        File::put($admin_path, $admin_data);

        //copy folder
        $adminSourceFolder = resource_path('lang/' . $code);
        $adminNewFolder = resource_path('lang/' . 'admin_' . $code);
        $this->duplicateFolderAndRename($adminSourceFolder, $adminNewFolder);
        $adminValidationSrc = resource_path('lang/admin_' . $code . '/validation.php');
        $this->addNameAttributeForAdmin($adminValidationSrc);
    }

    protected function duplicateFolderAndRename($sourceFolder, $newFolder)
    {
        if (is_dir($sourceFolder)) {
            $this->duplicateAndRenameFolder($sourceFolder, $newFolder);
        }

        return true;
    }
    protected function duplicateAndRenameFolder($source, $destination)
    {
        // Create the destination folder if it doesn't exist
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        // Open the source folder
        $directory = opendir($source);

        // Copy each file and subfolder
        while (($file = readdir($directory)) !== false) {
            if ($file !== '.' && $file !== '..') {
                $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
                $destinationPath = $destination . DIRECTORY_SEPARATOR . $file;

                if (is_dir($sourcePath)) {
                    // Recursively copy subfolders
                    $this->duplicateAndRenameFolder($sourcePath, $destinationPath);
                } else {
                    // Copy files
                    copy($sourcePath, $destinationPath);
                }
            }
        }

        closedir($directory);
    }
}
