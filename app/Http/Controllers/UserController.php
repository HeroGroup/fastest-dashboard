<?php

namespace App\Http\Controllers;

use App\User;
use FastestModels\ActiveScope;
use FastestModels\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function validatePassword(array $data)
    {
        return Validator::make($data, [
            'password' => 'required|string|min:8|confirmed'
        ]);
    }

    public function updatePassword(Request $request)
    {
        try {
            $validate = $this->validatePassword($request->only(['password', 'password_confirmation']));
            if ($validate->fails())
                return $this->fail('validation failed', -1, $validate->errors());

            $user = User::withoutGlobalScope(ActiveScope::class)->find($request->userId);
            if ($user)
                $user->update(['password' => Hash::make($request->password)]);

            return $this->success('password changed successfully.');
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function updateFcmToken(Request $request)
    {
        $fcmToken=$request->fcm_token;
        $apiToken=$request->api_token;

        if($fcmToken==null || $fcmToken=="null" || !isset($apiToken) || empty($fcmToken) ){
            return $this->fail("....");
        }
       $userDetails= UserDetail::find(auth()->user()->id);
        if(isset($userDetails)){
            $userDetails->device_token = $fcmToken;
            $res = $userDetails->save();
           return $this->success("update fcm token",['result'=>$res,'api_token'=>$apiToken,'res'=>$res,'fcm_token'=>$fcmToken,'user_id'=>$userDetails->user_id,'userDetail'=>$userDetails,'requestAll'=>$request->all()]);
        }
        return $this->fail(auth()->user()->user_id);
         //$res=  $res=  DB::table("user_details")->where("api_token","like",str_replace("\"","",$apiToken))->update(['device_token'=>"sadasds"]);
    }

    public function test()
    {

       return   $this->sendNotification(["cZfZ_v4jHk-G5lpT_rWJ7u:APA91bFImI6KUpma8Tm_L3AnBD0p-ROFGvBUQjW10lPrxRetbJhDLkz86bgeBxUYvDR7L0Nr1RqregIpQD3sMeHI1bvx0ReafDwZvfU1KXnx77alt3ASwcJQAddWRA5sHuMTZ_NUdk2M"],"New OrderListeners Register","New OrderListeners","http://localhost:3000/admin/orders");

    }
}
