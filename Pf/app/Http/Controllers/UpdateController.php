<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use App\Models\User\UserPaymentGateway;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;

class UpdateController extends Controller
{
    public function version()
    {
        return view('updater.version');
    }

    public function recurse_copy($src, $dst)
    {
        // dd(base_path($src), base_path($dst));
        $dir = opendir(base_path($src));
        @mkdir(base_path($dst), 0775, true);
        while (false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..') {
                if (is_dir(base_path($src) . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy(base_path($src . '/' . $file), base_path($dst) . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function upversion(Request $request)
    {
        $assets = [
        ['path' => 'app', 'type' => 'folder', 'action' => 'replace'], 
        ['path' => 'resources/views', 'type' => 'folder', 'action' => 'replace'], 
        ['path' => 'config', 'type' => 'folder', 'action' => 'replace'], 
        ['path' => 'database/migrations', 'type' => 'folder', 'action' => 'replace'], 
        ['path' => 'routes', 'type' => 'folder', 'action' => 'replace'], 
        ['path' => 'storage', 'type' => 'folder', 'action' => 'replace'], 
        ['path' => 'composer.json', 'type' => 'file', 'action' => 'replace'], 
        ['path' => 'composer.lock', 'type' => 'file', 'action' => 'replace'], 
        ['path' => 'version.json', 'type' => 'file', 'action' => 'add']
    ];

        foreach ($assets as $key => $asset) {
            $des = '';
            if (strpos($asset['path'], 'assets/') !== false) {
                $des = 'public/' . $asset['path'];
            } else {
                $des = $asset['path'];
            }
            // if updater need to replace files / folder (with/without content)
            if ($asset['action'] == 'replace') {
                if ($asset['type'] == 'file') {
                    copy(base_path('public/updater/' . $asset['path']), base_path($des));
                }
                if ($asset['type'] == 'folder') {
                    $this->delete_directory(base_path($des));
                    $this->recurse_copy('public/updater/' . $asset['path'], $des);
                }
            } elseif ($asset['action'] == 'add') {
                if ($asset['type'] == 'folder') {
                    $this->recurse_copy('public/updater/' . $asset['path'], $des);
                }
                if ($asset['type'] == 'file') {
                    copy(base_path('public/updater/' . $asset['path']), base_path($des));
                }
            }
        }

        $arr = [];
        $arr['MYFATOORAH_CALLBACK_URL'] = url('/product/myfatoorah/notify');
        $arr['MYFATOORAH_ERROR_URL'] = url('/product/myfatoorah/cancel');
        $arr['PUSHER_APP_CLUSTER'] = 'ap2';

        setEnvironmentValue($arr);

        $this->addPaymentGateways();

        // Artisan::call('config:clear');
        Artisan::call('migrate');

        Session::flash('success', 'Updated successfully');
        return redirect('updater/success.php');
    }

    function delete_directory($dirname)
    {
        if (!is_dir($dirname)) {
            return false;
        }

        $dir_handle = opendir($dirname);
        if (!$dir_handle) {
            return false;
        }

        while ($file = readdir($dir_handle)) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $dirname . DIRECTORY_SEPARATOR . $file;
                if (is_dir($filePath)) {
                    $this->delete_directory($filePath); // If in a class, use $this->delete_directory
                } else {
                    unlink($filePath);
                }
            }
        }

        closedir($dir_handle);
        return rmdir($dirname); // Only returns true if directory was successfully removed
    }

    public function addPaymentGateways()
    {
        //new admin payment gateway start
        $gatewaysData = [
            ['name' => 'Midtrans', 'type' => 'automatic', 'keyword' => 'midtrans', 'information' => null, 'status' => 1],
            ['name' => 'Iyzico', 'type' => 'automatic', 'keyword' => 'iyzico', 'information' => null, 'status' => 1],
            ['name' => 'Paytabs', 'type' => 'automatic', 'keyword' => 'paytabs', 'information' => null, 'status' => 1],
            ['name' => 'Toyyibpay', 'type' => 'automatic', 'keyword' => 'toyyibpay', 'information' => null, 'status' => 1],
            ['name' => 'Phonepe', 'type' => 'automatic', 'keyword' => 'phonepe', 'information' => null, 'status' => 1],
            ['name' => 'Yoco', 'type' => 'automatic', 'keyword' => 'yoco', 'information' => null, 'status' => 1],
            ['name' => 'Myfatoorah', 'type' => 'automatic', 'keyword' => 'myfatoorah', 'information' => null, 'status' => 1],
            ['name' => 'Xendit', 'type' => 'automatic', 'keyword' => 'xendit', 'information' => null, 'status' => 1],
            ['name' => 'Perfect Money', 'type' => 'automatic', 'keyword' => 'perfect_money', 'information' => null, 'status' => 1],
            // Add more gateways as needed
        ];

        foreach ($gatewaysData as $gatewayData) {
            $newgatewayData = new PaymentGateway();
            $newgatewayData->name = $gatewayData['name'];
            $newgatewayData->type = $gatewayData['type'];
            $newgatewayData->keyword = $gatewayData['keyword'];
            $newgatewayData->information = $gatewayData['information'];
            $newgatewayData->status = $gatewayData['status'];
            $newgatewayData->save();
        }
        //new admin payment gateway end


        //new user payment gateway start

        $users = User::all();
        foreach ($users as $key => $user) {
            foreach ($gatewaysData as $gatewayData) {
                $newgatewayData = new UserPaymentGateway();
                $newgatewayData->name = $gatewayData['name'];
                $newgatewayData->user_id = $user->id;
                $newgatewayData->type = $gatewayData['type'];
                $newgatewayData->keyword = $gatewayData['keyword'];
                $newgatewayData->information = $gatewayData['information'];
                $newgatewayData->status = $gatewayData['status'];
                $newgatewayData->save();
            }
        }

        //new user payment gateway end
        return 'New Gateways added successfully!';
    }

    // public function redirectToWebsite(Request $request) {
    //     $arr = ['WEBSITE_HOST' => $request->website_host];
    //     setEnvironmentValue($arr);
    //     \Artisan::call('config:clear');
    //     return redirect()->route('front.index');
    // }
}
