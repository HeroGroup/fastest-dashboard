<?php
namespace App\Http\Controllers;
use FastestModels\Notification;
use FastestModels\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NotificationsController extends Controller{

    public function sendNotifications(Request $request)
    {
        $users=json_decode($request->users);
        $type=$request->type;
        $title=$request->title;
        $titleAr=$request->titleAr;
        $target=$request->target;
        $message=$request->message;
        $messageAr=$request->messageAr;
        $selectedUser=[];

        if(is_array($users)){
            if(count($users)>=1){
                foreach ($users as $user) {
                    array_push($selectedUser,$user->value);
                }
            }
        }
        $driver=false;

        if(!isset($target) || $target=="null"){
            return $this->fail('Please Select Target Notifications');
        }

        if(!isset($message) || !isset($title)||!isset($titleAr)||!isset($messageAr)){
            return $this->fail('Please complete the subject and message fields');
        }
        $t=UserDetail::select(['id','device_token','user_id','user_type','device_type'])->whereNotNull('device_token')->where("device_token","!=","null")->where("device_token","!=","");
        $usePushy=true;
        if(isset($target)){
            $t=$t->where('user_type',$target);
            if($target=="restaurant"){
                $usePushy=false;
            }elseif ($target=="client"){
                $driver=false;
            }else{
                $driver=true;
            };
        }


//        if(isset($type)){
//            if($type!="all")
//            $t=$t->where('device_type',$type);
//        }



        if(count($selectedUser)){
            $t=$t->whereIn('user_id',$selectedUser);
        }


        if($t->count()<=400){
            $t=$t->get();
            $to= $t->pluck('device_token');
            $ids=$t->pluck('id');
            if($usePushy){
                $data = array("title" =>$title,"message" =>$message);
               $res=parent::sendPushNotification($data,$to,[],$driver);
               return $this->success("sasa",$res);
            }else{
                $this->sendNotification($to,$message,$title);
            }
            $this->logNotifications($ids,$title,$titleAr,$message,$messageAr);
        }else{

            // handel queue

            return $this->success("send Notification Scheduled");
        }
        return $this->success("send Notification SuccessFully");

    }
    public function logNotifications($ids,$titleEn,$titleAr,$messageEn,$messageAr,$deviceToken=null){
        foreach ($ids as $id) {
            DB::table("notifications")->insert(['user_detail_id'=>$id,'notification_title_en'=>$titleEn,'notification_title_ar'=>$titleAr,'notification_message_en'=>$messageEn,'notification_message_ar'=>$messageAr,'device_token'=>$deviceToken!=null?$deviceToken:""]);
        }
    }

}