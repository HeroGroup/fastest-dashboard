<?php

namespace App\Http\Controllers;

use FastestModels\ActiveScope;
use FastestModels\AddressBook;
use FastestModels\Area;
use FastestModels\Client;
use FastestModels\Role;
use FastestModels\User;
use FastestModels\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    public function validation(array $data)
    {
        return Validator::make($data, [
            'name_en' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'mobile' => ['required', 'string', 'size:8', 'unique:users'],
            'phone' => ['nullable', 'size:8'],
            'password' => ['required', 'min:8'],
            'min_delivery_time' => ['required', 'integer'],
            'max_delivery_time' => ['required', 'integer'],
            'logo' => ['nullable', 'file'],
            'image' => ['nullable', 'file'],
            'establish_date' => ['nullable', 'string', 'min:4', 'max:10']
        ]);
    }

    public function getAdminList()
    {
        return User::withoutGlobalScope(ActiveScope::class)->whereHas("userDetails",function ($r){
            $r->where('user_type','admin');
        })->with('userDetails')->get();
    }
    public function index()
    {
        try {
            $clients = $this->getAdminList();
            return $this->success('clients retrieved successfully...', $clients);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
    public function getAdmin($adminId)
    {
        try {
            $data = User::withoutGlobalScope(ActiveScope::class)
                ->where('id', '=',$adminId)
                ->with('userDetails')
                ->whereHas("userDetails",function ($r){
                    $r->where('user_type','admin');
                })->first();
            return $this->success('item retrieved successfully', $data);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
    public function update(Request $request)
    {
        try {
            if(isset($request->id)){
                $user = User::withoutGlobalScope(ActiveScope::class)->whereHas("userDetails",function ($r){
                    $r->where('user_type','admin');
                })->find($request->id);
                if(isset($user)){
                    $userDetail=UserDetail::where('user_id',$user->id)->first();
                    $user->update([
                        'name' => $request->name,
                        'username' => $request->username,
                        'email' => $request->email,
                        'mobile' => $request->mobile,
                        'phone' => $request->phone,
                        'date_of_birth' => $request->date_of_birth
                    ]);
                    if(isset($request->password)){
                        $user->password=Hash::make($request->password);
                        $user->save();
                    }

                    if($request->is_active=="true"){
                        $userDetail->is_active=1;
                    }else{
                        $userDetail->is_active=0;
                    }
                    $userDetail->save();
                }
            }else{
                $user=new User();
                $user->name=$request->name;
                $user->username=$request->username;
                $user->email=$request->email;
                $user->mobile=$request->mobile;
                $user->phone=$request->phone;
                $user->date_of_birth=$request->date_of_birth;
                $user->password=Hash::make($request->password);
                $user->save();
                $user->userDetails()->create([
                    'user_id'=>$user->id,
                    'is_active'=>$request->is_active=="true"?1:0,
                    'user_type'=>'admin',
                    'api_token'=> Str::random(60),
                ]);
            }


            return $this->success('client updated successfully');
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function updateRoles(Request $request)
    {
        try{
            $adminId=$request->adminId;
            $admin=User::withoutGlobalScope(ActiveScope::class)
                ->where('id', '=',$adminId)
                ->whereHas("userDetails",function ($r){
                    $r->whereIn('user_type',['admin','restaurant','branch']);
                })->first();
            if(isset($admin)){
                $rolesIds=$request->rolesIds;
                $rolesIds=json_decode($rolesIds);
                $newArray=[];
                if(is_array($rolesIds)){
                    foreach ($rolesIds as $item) {
                        array_push($newArray, $item->value);
                    }
                }

                $admin->roles()->sync($newArray);
                return $this->success('Update Permission list SuccessFull');
            }
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getRoles($adminId)
    {
        $admin=User::withoutGlobalScope(ActiveScope::class)
            ->where('id', '=',$adminId)
            ->whereHas("userDetails",function ($r){
                $r->whereIn('user_type',['admin','restaurant','branch']);
            })->first();
        $roles=DB::table('roles')->select(['roles.id as value','roles.name as label'])->get()->toArray();;
        $selectedRoles= DB::table('roles')
            ->join('role_user','roles.id','=','role_user.role_id')
            ->where('role_user.user_id',$adminId)->select(['roles.id as value','roles.name as label'])->get()->toArray();

       return $this->success('',['roles'=>$roles,'selectedRoles'=>$selectedRoles,'admin'=>$admin]);

    }
    public function destroy(Request $request)
    {
        try {
               User::withoutGlobalScope(ActiveScope::class)->where('id',$request->id)->delete();
            // TODO dependencies should be checked
            return $this->success('restaurant removed successfully!', $this->getAdminList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
    public function filterAndPaginate(Request $request)
    {
        $pagesSize=$request->pageSize;
        $filterKeyVal=$request->filterKeyVal;
        $sorted=$request->sorted;

        $validKeyFilterUser=['id','name','username','mobile','created_at','is_Active'];
        $validKeyFilterUserDetails=['last_login'];
        try {
            $data =User::withoutGlobalScope(ActiveScope::class)->whereHas("userDetails",function ($r){
                $r->where('user_type','admin');
            })->with('userDetails');

            if(isset($filterKeyVal["key"])){
                if(in_array($filterKeyVal["key"],$validKeyFilterUser)){
                    $data=$data->where($filterKeyVal["key"],'like',"%".$filterKeyVal['value']."%");
                }
                if(in_array($filterKeyVal["key"],$validKeyFilterUserDetails)){
                    $data=$data->whereHas("userDetails",function ($r)use($filterKeyVal){
                        $r->where($filterKeyVal["key"],'like',"%".$filterKeyVal['value']."%");
                    });
                }
            }


            if(isset($sorted["id"]) && $sorted["id"]!=null ){
                $desc=$sorted["desc"]?'desc':'asc';
                $data=$data->orderBy($sorted["id"],$desc);
            }
            $data=$data->paginate($pagesSize);
            return $this->success('item retrieved successfully', $data);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
}
