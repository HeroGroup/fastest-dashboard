<?php

Route::get('/', function () {

    $discount=  FastestModels\Discount::find(20);
    if(isset($discount)){
        $count=floatval($discount->count_usage);
        $discount->count_usage=$count+1;
        $discount->save();
    }
    dd($discount);


    $userDetails= FastestModels\UserDetail::find(20);
    if(isset($userDetails)) {
        $userDetails->device_token = "crXicxSLKEn2cD4qAfckY9:APA91bFl02cxqcvX0Gl0btPwLq1SvnRiB6wNg2TVi9NZTetRbfhsgp8cq0OtzE7DmL_e6g7-JRZ-ytS0TcbtjnO_M8RCZ2sD6HXKjQSZhEWxpG5pJ08lWvsSuZ0NFxXLGyArkNCgm1yY";
        $res = $userDetails->save();
        dd($res);
    }

    $selectedPermissions= \DB::table("permissions")
        ->join('permission_role','permission_role.permission_id','=','permissions.id')
        ->leftJoin('role_user','role_user.role_id','=','permission_role.role_id')
        ->where("permission_role.role_id",12)
        ->distinct('permissions.id')
        ->select(['permissions.id as value','permissions.name as label'])->get()->toArray();
    dd( $selectedPermissions);
    return view('welcome');
});


