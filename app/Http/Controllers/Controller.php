<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function success($message, $data=null, $status=1)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ])->header("Content-Type","application/json");
    }
    public function fail($message, $status=-1, $errors=null)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'errors' => $errors
        ])->header("Content-Type","application/json");
    }
    public function sendNotification($to=[],$body="",$title="",$click_action="",$icon="http://admin.fastestkw.com/favicon.ico")
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
       //api_key in Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key
        $server_key = env("Serverkey");
       //header with content_type api key
        $headers = array(
            'Content-Type:application/json',
            'Authorization:key='.$server_key
        );
       //CURL request to route notification to FCM connection server (provided by Google)
        if(count($to)>=1){
            foreach ($to as $item) {
                $data = json_encode([
                    "to"=>$item,
                    "notification" => [
                        "body" => $body,
                        "title" => $title,
                        "icon" =>$icon,
                        "click_action"=>$click_action
                    ]
                ]);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                $result = curl_exec($ch);
                if ($result === FALSE) {
                        //
                }
                curl_close($ch);
            }
            return $this->success("sen notification",$result);
        }
          return     $this->fail("");

    }
    public static function sendPushNotification($data, $to, $options,$driver=true)
    {
        $apiKey =$driver ? env('PUSHY_KEY_DRIVER'): env('PUSHY_KEY');
        $post = $options ?: array();
        $post['to'] = $to;
        $post['data'] = $data;
        $headers = array(
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.pushy.me/push?api_key=' . $apiKey);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post, JSON_UNESCAPED_UNICODE));
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }
    protected function logNotifications($ids,$titleEn,$titleAr,$messageEn,$messageAr,$deviceToken=null){
        foreach ($ids as $id) {
            DB::table("notifications")->insert(['user_detail_id'=>$id,'notification_title_en'=>$titleEn,'notification_title_ar'=>$titleAr,'notification_message_en'=>$messageEn,'notification_message_ar'=>$messageAr,'device_token'=>$deviceToken!=null?$deviceToken:""]);
        }
    }

}
