<?php

namespace App\Http\Controllers;

use FastestModels\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        try {
            $settings = DB::table('settings')->select('setting_key', 'setting_value', 'setting_placeholder')->get();
            return $this->success('settings retrieved successfully', $settings);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            foreach ($request->all() as $key => $value) {
                $setting = Setting::where('setting_key', 'LIKE', $key)->first();
                if ($setting)
                    $setting->update(['setting_value' => $value]);
            }

            if ($request->hasFile('advertisement')) {
                $setting = Setting::where('setting_key', 'LIKE', 'advertisement')->first();
                if ($setting)
                    $setting->update(['setting_value' => $this->saveFile($request->advertisement)]);
            }

            if ($request->hasFile('sidebarLogo')) {
                $setting = Setting::where('setting_key', 'LIKE', 'sidebarLogo')->first();
                if ($setting)
                    $setting->update(['setting_value' => $this->saveFile($request->sidebarLogo)]);
            }

            return $this->success('settings updated successfully.');
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function saveFile($file)
    {
        $fileName = time().'.'.$file->getClientOriginalName();
        $file->move('resources/assets/images/advertisements/', $fileName);
        return 'advertisements/'.$fileName;
    }

    public function getLoginPageCredentials()
    {
        try {
            $instagram = Setting::where('setting_key', 'LIKE', 'instagram')->first();
            $facebook = Setting::where('setting_key', 'LIKE', 'facebook')->first();
            $tweeter = Setting::where('setting_key', 'LIKE', 'tweeter')->first();
            $googlePlus = Setting::where('setting_key', 'LIKE', 'googlePlus')->first();

            $data = [
                'instagram' => $instagram ? $instagram->setting_value : '',
                'facebook' => $facebook ? $facebook->setting_value : '',
                'tweeter' => $tweeter ? $tweeter->setting_value : '',
                'googlePlus' => $googlePlus ? $googlePlus->setting_value : '',
            ];

            return $this->success('credentials returned successfully.', $data);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
}
