<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class FacebookController extends Controller
{
    public function edit()
    {
        return view('settings.facebook', [
            'app_id' => Setting::get('facebook_app_id'),
            'app_secret' => Setting::get('facebook_app_secret'),
            'access_token' => Setting::get('facebook_access_token'),
            'verify_token' => Setting::get('facebook_verify_token'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_id' => 'required|string',
            'app_secret' => 'required|string',
            'access_token' => 'required|string',
            'verify_token' => 'required|string',
        ]);

        Setting::set('facebook_app_id', $request->app_id);
        Setting::set('facebook_app_secret', $request->app_secret);
        Setting::set('facebook_access_token', $request->access_token);
        Setting::set('facebook_verify_token', $request->verify_token);

        return back()->with('success', 'Facebook settings updated successfully.');
    }
}
