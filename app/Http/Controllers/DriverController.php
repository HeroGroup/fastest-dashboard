<?php

namespace App\Http\Controllers;

use FastestModels\ActiveScope;
use FastestModels\Driver;
use FastestModels\Order;
use FastestModels\User;
use FastestModels\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
    public function validation(array $data,$id=0)
    {
        if($id==0){
            return Validator::make($data, [
                'name' => 'required',
                'mobile' => 'required|size:8|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|size:8',
                'email'=>'required|unique:users',
                'license'=>'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]);
        }else{
            return Validator::make($data, [
                'name' => 'required',
                'mobile' => 'required|size:8|unique:users,mobile,'.$id,
                'phone' => 'nullable|size:8',
                'email'=>'required|unique:users,email,'.$id,
            ]);
        }
    }

    public function index()
    {
        try {
            $drivers = $this->getDriversList();
            return $this->success('drivers retrieved successfully...', $drivers);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getJobHistory(Request $request)
    {


       //return $request->all();

        $filterKeyVal=$request->filterKeyVal;
        $pageSize=$request->pageSize;
       // $sorted=json_decode($request->sorted);






        $driverId=$request->driverId;
        $result=  Order::where('driver_id',$driverId)->with(["restaurant.user.userDetails","client.user.userDetails","items.branch","histories"=>function($r){
            $r->orderBy('id','desc')->limit(1);
        }])->orderBy('id','desc');

        if(isset($filterKeyVal)){
            if($filterKeyVal['key']=="orderId"){

                    $result=$result->where('unique_number','like',"%".$filterKeyVal['value']."%");
            }elseif ($filterKeyVal['key']=="client"){
                $result=$result->whereHas('client.user',function ($r)use($filterKeyVal){
                    $r->where('name','like',"%".$filterKeyVal['value']."%");
                });
            }elseif ($filterKeyVal['key']=="restaurant"){
                $result=$result->whereHas('restaurant.user',function ($r)use($filterKeyVal){
                    $r->where('name','like',"%".$filterKeyVal['value']."%");
                });

            }
        }

        $result=$result->paginate($pageSize);



        return $this->success('driver created successfully',['list'=>$result]);

    }

    public function getDriversList()
    {
        return Driver::withoutGlobalScope(ActiveScope::class)->whereHas('user.userDetails')->with('user')->orderBy('id','desc')->get();
    }

    public function store(Request $request)
    {
        try {
            if ($request->id > 0) {
                $driver = Driver::withoutGlobalScope(ActiveScope::class)->where('id', '=', $request->id)->first();
                $user = User::withoutGlobalScope(ActiveScope::class)->where('id', '=', $driver->user_id)->first();

                $validate = $this->validation($request->only(['name','license','mobile','email']),$user->id);
                if ($validate->fails())
                    return $this->fail('validation failed', -1, $validate->errors());

                $driver->update([
                    'plate_number' => $request->plate_number == "null" ? null : $request->plate_number,
                    'status' => $request->status,
                    'is_active' => $request->is_active == "true" ? 1 : 0
                ]);
                if ($request->hasFile('license'))
                    $driver->update(['driving_license' => $this->saveFile($request->license)]);


                $user->update([
                    'name' => $request->name,
                    'email' => $request->email == "null" ? null : $request->email,
                    'mobile' => $request->mobile,
                    'phone' => $request->phone == "null" ? null : $request->phone,
                    'date_of_birth' => $request->date_of_birth == "null" ? null : $request->date_of_birth,
                ]);
                if ($request->hasFile('profile_photo')) {
                    $userDetail = UserDetail::where('user_id', '=', $user->id)->where('user_type', 'LIKE', 'driver')->first();
                    if ($userDetail)
                        $userDetail->update(['profile_photo' => $this->saveFile($request->profile_photo)]);
                }
            } else {
                $validate = $this->validation($request->only(['name','license','mobile', 'password', 'password_confirmation','email']));
                if ($validate->fails())
                    return $this->fail('validation failed', -1, $validate->errors());

                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'mobile' => $request->mobile,
                    'phone' => $request->phone,
                    'date_of_birth' => trim($request->date_of_birth),
                ]);
                $user->save();

                $userDetail = UserDetail::create([
                    'user_id' => $user->id,
                    'user_type' => 'driver',
                    'profile_photo' => $request->hasFile('profile_photo') ? $this->saveFile($request->profile_photo) : null,
                    'is_active' => 1
                ]);
                $userDetail->save();

                $driver = Driver::create([
                    'user_id' => $user->id,
                    // 'transport_mode_en' => $request->transportation,
                    // 'transport_mode_ar' => $request->transportation,
                    'driving_license' => $request->hasFile('license') ? $this->saveFile($request->license) : null,
                    'plate_number' => $request->plate_number,
                    'status' => 0,
                    'is_active' => 1
                ]);
                $driver->save();
            }
            return $this->success('driver created successfully', $driver);
        } catch (\Exception $exception) {
            return $this->fail($exception->getLine().': '.$exception->getMessage());
        }
    }

    public function getDriver($driverId)
    {
        try {
            if ($driverId > 0) {
                $driver = Driver::withoutGlobalScope(ActiveScope::class)->with('user')->where('id', '=', $driverId)->first();
                return $this->success('drivers retrieved successfully!', $driver);
            } else {
                return $this->fail('invalid driver');
            }
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            if (Order::where('driver_id', '=', $request->id)->count() > 0)
                return $this->fail('Driver has jobs, unable to delete driver!');
            $driver = Driver::withoutGlobalScope(ActiveScope::class)->find($request->id);
            $userDetail = UserDetail::where('user_id', '=', $driver->user_id)->where('user_type', 'LIKE', 'driver')->delete();
            $user = User::find($driver->user_id);
            $user->update(['email' => null, 'mobile' => null]);
            $driver->delete();
            return $this->success('driver deleted successfully', $this->getDriversList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function saveFile($file)
    {
        $fileName = time().'.'.$file->getClientOriginalName();
        $file->move('resources/assets/images/driver_images/', $fileName);
       // return env('PUBLIC_PATH').'resources/assets/images/driver_images/'.$fileName;
        return 'driver_images/'.$fileName;
    }

}
