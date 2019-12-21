<?php

namespace App\Http\Controllers;

use FastestModels\ActiveScope;
use FastestModels\Area;
use FastestModels\RestaurantSupportingArea;
use FastestModels\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RolesController extends Controller
{
    public function validator(array $data)
    {
        return Validator::make($data, [
            'name_en' => 'required|string',
        ]);
    }

    public function getAreas()
    {
        try {
            $areas = DB::table('roles')->select('id as value', 'name as label')->get()->toArray();
            return $this->success('roles retrieved successfully', $areas);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
    public function getAreaList()
    {
        return Role::orderBy('id','desc')->get();
    }
    public function index()
    {
        try {
            return $this->success('Roles retrieved successfully', $this->getAreaList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
    public function store(Request $request)
    {
        try {
            $validate = $this->validator($request->only(['name_en']));
            if ($validate->fails())
                return $this->fail('validation failed', -1, $validate->errors());

            if(isset($request->id) && $request->id>0){
                $role = Role::find($request->id);
                if(isset($role)){
                    $role->name=$request->name_en;
                }
            }else{
                $role = new Role([
                    'name' => $request->name_en,
                ]);
            }
            $role->save();

            return $this->success('new area created successfully', $this->getAreaList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
    public function destroy(Request $request)
    {
        try {
            if (DB::table("role_user")->where('role_id','=',$request->id)->count() == 0)
                Role::find($request->id)->delete();
            else
                return $this->fail('Unable to delete thid item because of dependencies.');
            return $this->success('roles deleted successfully', $this->getAreaList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
    public function getPermissionList($id)
    {
        try {
        $role=Role::find($id);
        if(isset($role)){
            $permissions=DB::table("permissions")->select(['permissions.id as value','permissions.name as label'])->get()->toArray();
            $selectedPermissions= DB::table("permissions")
                ->join('permission_role','permission_role.permission_id','=','permissions.id')
                ->where("permission_role.role_id",$id)
                ->distinct('permissions.id')
                ->select(['permissions.id as value','permissions.name as label'])->get()->toArray();
               return $this->success('',['role'=>$role,'selectedPermissions'=>$selectedPermissions,'permissions'=>$permissions]);
          }
            return $this->fail("role not found");
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
    public function syncPermissions(Request $request)
    {
        try{
             $roleId=$request->roleId;
             $role=Role::find($roleId);
             $permissionIds=$request->permissionIds;
             $permissionIds=json_decode($permissionIds);
              $newArray=[];
                  if(is_array($permissionIds)){
                      foreach ($permissionIds as $item) {
                          array_push($newArray, $item->value);
                      }
                  }

                $role->permissions()->sync($newArray);
                return $this->success('Update Permission list SuccessFull',['permissions'=>$request->all(),'d'=>$permissionIds]);

        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
}
