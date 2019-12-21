<?php

namespace App\Http\Controllers;

use FastestModels\ActiveScope;
use FastestModels\Addon;
use FastestModels\AddonItem;
use FastestModels\Area;
use FastestModels\Branch;
use FastestModels\Categoriable;
use FastestModels\Food;
use FastestModels\FoodAddon;
use FastestModels\Restaurant;
use FastestModels\RestaurantOpenTime;
use FastestModels\RestaurantPaymentMethod;
use FastestModels\RestaurantSupportingArea;
use FastestModels\User;
use FastestModels\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Mockery\Exception;
use function foo\func;

class RestaurantController extends Controller
{
    public function validation(array $data)
    {
        return Validator::make($data, [
            'name_en' => 'required|string',
            'email' => 'nullable|email|unique:users',
            'mobile' => 'required|string|size:8|unique:users',
            'phone' => 'nullable|min:7|max:8',
            'password' => 'required|min:8',
            'logo' => 'nullable|file',
            'image' => 'nullable|file',
            'type' => 'required|string',
            'supporting_areas'=>'required',
            'categories'=>'required'
            // 'establish_date' => 'nullable|string|min:4|max:10'
        ]);
    }

    public function editValidation(array $data)
    {
        return Validator::make($data, [
            'name_en' => 'required|string',
            // 'phone' => 'nullable|size:8',
            'supporting_areas'=>'required',
            'categories'=>'required'

            // 'establish_date' => 'nullable|string|min:4|max:10'
        ]);
    }

    public function index(Request $request)
    {
        try {
           // $restaurants = $this->getRestaurantsList();

            $pagesSize = intval($request->pagesSize) >= 1 ? intval($request->pagesSize) : 20;
            $filterKeyVal = json_decode($request->filterKeyVal);
            $sorted = json_decode($request->sorted);
            $filterType=$request->filterType;
            $carId=$request->catId;

            $list=  Restaurant::withoutGlobalScope(ActiveScope::class)->with(["user","times"])->where('type',$filterType);


            if($carId!=0 && $carId!=null){
                $list=$list->whereHas("tempCategories",function($r)use($carId){
                    $r->withoutGlobalScope(ActiveScope::class)->where('categories.id',$carId);
                });
            }


            $userFilterList=['name','mobile','phone'];
            $filter=['title_en'];

            if(isset($filterKeyVal)){
                if(isset($filterKeyVal->key)){
                    if(in_array($filterKeyVal->key, $userFilterList)){
                        $list=$list->whereHas("user",function($q)use($filterKeyVal){
                            $q->where($filterKeyVal->key,"like","%".$filterKeyVal->value."%");
                        });
                    }else{
                        if(in_array($filterKeyVal->key,$filter)) {
                            $list = $list->where($filterKeyVal->key, "like", "%" . $filterKeyVal->value . "%");
                        }
                    }
                }

            }

            if (isset($sorted->id)) {
                if ($sorted->desc) {
                    $list = $list>orderBy($sorted->id, 'desc');
                } else {
                    $list = $list>orderBy($sorted->id, 'asc');
                }
            } else {
                $list = $list->orderBy('id', 'desc');
            }
            $list=$list->paginate($pagesSize);
            return $this->success('restaurants retrieved successfully...',$list);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validate = $this->validation($request->all());

            if ($validate->fails())
                return $this->fail('Validation Failed', -1, $validate->errors());

            // create user
            if (User::where('mobile', 'LIKE', $request->mobile)->count() == 0) {
                $user = new User([
                    'name' => $request->name_en,
                    'username' => $request->mobile,
                    'email' => ($request->email && $request->email != "null") ? $request->email : null,
                    'password' => Hash::make($request->password),
                    'mobile' => $request->mobile,
                    'phone' => ($request->phone && $request->phone != "null") ? $request->phone : null,
                ]);
                $user->save();
            } else {
                $user = User::where('mobile', 'LIKE', $request->mobile)->first();
            }

            // create user_detail
            $userDetail = new UserDetail([
                'user_id' => $user->id,
                'user_type' => 'restaurant'
            ]);
            $userDetail->save();

            $restaurant = new Restaurant([
                'user_id' => $user->id,
                'logo' => $request->has('logo') ? $this->saveFile($request->logo) : null,
                'image' => $request->has('image') ? $this->saveFile($request->image) : null,
                'title_en' => $request->name_en,
                'title_ar' => $request->name_ar,
                'caption_en' => $request->caption_en,
                'caption_ar' => $request->caption_ar,
                'min_order' => $request->min_order,
                // 'establish_date' => ($request->establish_date && $request->establish_date != "null") ? $request->establish_date : null,
                // 'min_delivery_time' => ($request->min_delivery_time && $request->min_delivery_time != "null") ? $request->min_delivery_time : 0,
                // 'max_delivery_time' => ($request->max_delivery_time && $request->max_delivery_time != "null") ? $request->max_delivery_time : 0,
                'type' => $request->type,
                // 'delivery_type' => $request->delivery_type == "null" ? null : $request->delivery_type,
                'delivery_type' => 'By App',
                'delivery_fee' => $request->delivery_fee == "null" ? 0 : $request->delivery_fee,
                'status' => 'open',
                'is_active' => 1,
                'address' => $request->address,
                'area_id' => isset($request->area_id) ? $request->area_id : null,
                'latitude' => $request->latitude == "null" ? null : $request->latitude,
                'longitude' => $request->longitude == "null" ? null : $request->longitude,
            ]);
            $restaurant->save();

            $branch=new Branch();
            $branch->user_id=$user->id;
            $branch->restaurant_id=$restaurant->id;
            $branch->title_en=$request->name_en;
            $branch->title_ar=$request->name_ar;
            $branch->address=$request->address;
            $branch->area_id=isset($request->area_id) ? $request->area_id : null;
            $branch->latitude=$request->latitude == "null" ? null : $request->latitude;
            $branch->longitude=$request->longitude == "null" ? null : $request->longitude;
            $branch->save();

            if ($request->supporting_areas) {
                $newAreas = json_decode($request->supporting_areas);
                foreach ($newAreas as $item) {
                    RestaurantSupportingArea::create([
                        'restaurant_id' => $restaurant->id,
                        'area_id' => $item->value,
                        'branch_id'=>$branch->id,
                    ]);
                }
            }
            if ($request->categories) {
                $new = json_decode($request->categories);
                foreach ($new as $item) {
                    Categoriable::create([
                        'categoriable_id' => $restaurant->id,
                        'categoriable_type' => 'Restaurant',
                        'category_id' => $item->value,
                    ]);
                }
            }
            return $this->success('restaurant created successfully', $restaurant);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getRestaurant($restaurant)
    {
        try {
            $post = Restaurant::withoutGlobalScope(ActiveScope::class)->where('id', '=', $restaurant)->with('user')->first();

            if($post->area_id!=null && $post->area_id!=""){
                $area=Area::find($post->area_id);
                if(isset($area)){
                    $area=['value'=>$area->id,"label"=>$area->name_en];
                }else{
                    $area=[];
                }

            }else{
                $area=[];
            }


            $supportingAreas = DB::table('restaurant_supporting_areas')
                ->join('areas', 'areas.id', 'restaurant_supporting_areas.area_id')
                ->where('restaurant_supporting_areas.restaurant_id', '=', $restaurant)
                ->select('areas.id as value', 'areas.name_en as label')
                ->distinct('areas.id')
                ->get()
                ->toArray();

            $categories = DB::table('categories')
                ->join('categoriables', 'categories.id', 'categoriables.category_id')
                ->select('id as value', 'name_en as label')
                ->where('categoriables.categoriable_type', 'LIKE', 'Restaurant')
                ->where('categoriables.categoriable_id', '=', $restaurant)
                ->select('categories.id as value', 'categories.name_en as label')
                ->distinct('categories.id')
                ->get()
                ->toArray();

            $post->supporting_areas = $supportingAreas;
            $post->categories = $categories;
            $post->selectedArea=$area;

            return $this->success('item retrieved successfully', $post);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $validate = $this->editValidation($request->all());

            if ($validate->fails())
                return $this->fail('Validation Failed', -1, $validate->errors());

            if ($request->phone && $request->phone != "null" && strlen($request->phone) != 8)
                return $this->fail('phone number must be 8 characters.');

            $restaurant = Restaurant::withoutGlobalScope(ActiveScope::class)->where('id', '=', $request->id)->first();
            if ($restaurant) {
                $restaurant->update([
                    'caption_en' => $request->caption_en == "null" ? null : $request->caption_en,
                    'caption_ar' => $request->caption_ar == "null" ? null : $request->caption_ar,
                    'title_en' => $request->name_en,
                    'title_ar' => $request->name_ar,
                    'establish_date' => $request->establish_date == "null" ? null : $request->establish_date,
                    'type' => $request->type == "null" ? null : $request->type,
                    'is_active' => $request->is_active == "true" ? 1 : 0,
                    'address' => $request->address == "null" ? null : $request->address,
                    'area_id' => $request->area_id == "null" ? null : $request->area_id,
                    'min_order' => $request->min_order == "null" ? null : $request->min_order,
                    'delivery_type' => $request->delivery_type == "null" ? null : $request->delivery_type,
                    'delivery_fee' => $request->delivery_fee == "null" ? 0 : $request->delivery_fee,
                    'latitude' => $request->latitude == "null" ? null : $request->latitude,
                    'longitude' => $request->longitude == "null" ? null : $request->longitude,
                ]);

                $branch=Branch::where('user_id',$restaurant->user_id)->where('restaurant_id',$restaurant->id)->first();
                if(isset($branch)){
                    $branch->update([
                        'address' => $request->address == "null" ? null : $request->address,
                        'area_id' => $request->area_id == "null" ? null : $request->area_id,
                        'latitude' => $request->latitude == "null" ? null : $request->latitude,
                        'longitude' => $request->longitude == "null" ? null : $request->longitude,
                    ]);
                }else{
                    $branch=new Branch();
                    $branch->user_id=$restaurant->user_id;
                    $branch->restaurant_id=$restaurant->id;
                    $branch->title_en=$request->name_en;
                    $branch->title_ar=$request->name_ar;
                    $branch->address=$request->address;
                    $branch->area_id=isset($request->area_id) ? $request->area_id : null;
                    $branch->latitude=$request->latitude == "null" ? null : $request->latitude;
                    $branch->longitude=$request->longitude == "null" ? null : $request->longitude;
                    $branch->save();
                }

                if ($request->hasFile('logo'))
                    $restaurant->update(['logo' => $this->saveFile($request->logo)]);

                if ($request->hasFile('image'))
                    $restaurant->update(['image' => $this->saveFile($request->image)]);

                $user = User::find($restaurant->user_id);
                $user->update([
                    'name' => $request->name_en,
                    'email' => ($request->email && $request->email != "null") ? $request->email : null,
                    'mobile' => $request->mobile,
                    'phone' => ($request->phone && $request->phone != "null") ? $request->phone : null,
                ]);
                if (!empty($request->supporting_areas)) {
                     RestaurantSupportingArea::where('restaurant_id', '=', $request->id)->delete();
                    $newAreas = json_decode($request->supporting_areas);
                    if (is_array($newAreas)) {
                        foreach ($newAreas as $item) {
                            RestaurantSupportingArea::create([
                                'restaurant_id' => $request->id,
                                'area_id' => $item->value,
                                'branch_id'=>isset($branch)?$branch->id:null
                            ]);
                        }
                    }

                }
                if ($request->categories) {
                    Categoriable::where('categoriable_id',$request->id)->where('categoriable_type','Restaurant')->delete();
                    $new = json_decode($request->categories);
                    foreach ($new as $item) {
                        Categoriable::create([
                            'categoriable_id' => $request->id,
                            'categoriable_type' => 'Restaurant',
                            'category_id' => $item->value,
                        ]);
                    }
                }
            }
            return $this->success('restaurant updated successfully');
        } catch (\Exception $exception) {
            return $this->fail($exception->getLine() . ": " . $exception->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            // delete restaurant supporting areas
            RestaurantSupportingArea::where('restaurant_id', '=', $request->id)->delete();

            // delete restaurant times
            RestaurantOpenTime::where('restaurant_id', '=', $request->id)->delete();

            // delete restaurant payment methods
            RestaurantPaymentMethod::where('restaurant_id', '=', $request->id)->delete();

            // delete restaurant categories
            Categoriable::where('categoriable_id', '=', $request->id)->where('categoriable_type', '=', 'Restaurant')->delete();

            // delete restaurant food addons
            $foodIds = Food::where('restaurant_id', '=', $request->id)->get(['id'])->toArray();
            $addonIds = Addon::where('restaurant_id', '=', $request->id)->get(['id'])->toArray();
            FoodAddon::whereIn('food_id', $foodIds)->delete();

            // delete restaurant addons
            AddonItem::whereIn('addon_id', $addonIds)->delete();
            Addon::where('restaurant_id', '=', $request->id)->delete();

            // delete restaurant foods
            Categoriable::whereIn('categoriable_id', $foodIds)->where('categoriable_type', '=', 'Food')->delete();
            Food::where('restaurant_id', '=', $request->id)->delete();

            // delete restaurant user
            $userId = Restaurant::find($request->id)->user_id;
            UserDetail::where('user_id', '=', $userId)->where('user_type', '=', 'restaurant')->delete();

            // delete restaurant
            Restaurant::find($request->id)->delete();

            return $this->success('restaurant removed successfully!', $this->getRestaurantsList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function saveFile($file)
    {
        $fileName = time().'.'.$file->getClientOriginalName();
        $file->move('resources/assets/images/restaurant_images/', $fileName);
      //  return env('PUBLIC_PATH').('/resources/assets/images/restaurant_images/').$fileName;
        return 'restaurant_images/'.$fileName;
    }

    public function getRestaurantsList()
    {
        return Restaurant::withoutGlobalScope(ActiveScope::class)->with('user')->get();
    }

    public function getBranchesList($id)
    {
        $ac = new AreaController();
        $areas = $ac->getAreasWithGroups();
         $res = $this->getBra($id);
         return $this->success('item retrieved successfully',['areas'=>$areas,'branches'=>$res]);

    }

    public function addBranch(Request $request)
    {
        try{
            $id=$request->id;
            $bId=$request->bId;
            $selectedArea=$request->selectedArea;
            $supporting=$request->supporting_areas;
            $address=$request->address;
            $titleAr=$request->title_ar;
            $titleEn=$request->title_en;
            $phone=($request->phone && $request->phone != "null") ? $request->phone : null ;
            $mobile=($request->mobile && $request->mobile!="null")?$request->mobile:null;
            $password=$request->password;
            if(isset($selectedArea)&&isset($id)&&isset($address) && isset($titleEn) && isset($mobile) && isset($phone)){
                if(isset($bId)&& $bId!=0){

                    $update=true;
                    $branch=Branch::find($bId);
                    if(isset($branch)){
                        if(isset($request->latitude)){
                            $branch->latitude=$request->latitude;
                            $branch->longitude=$request->longitude;
                        }
                        $branch->address=$address;
                        $branch->title_en=$titleEn;
                        $branch->title_ar=$titleAr;
                        $branch->area_id=$selectedArea;
                        $branch->save();
                    }


                    $user=  User::whereId($branch->user_id)->first();

                  $validate=  Validator::make($request->all(), [
                        'mobile' => 'required|size:8|unique:users,mobile,'.$branch->user_id,
                    ]);

                    if ($validate->fails())
                        return $this->fail($validate->errors()->first());




                    if(isset($user)){
                        $user->phone=$phone;
                        $user->mobile=$mobile;
                        if(isset($password)&& !empty($password)){
                                $user->password=bcrypt($password);
                        }

                        $user->save();

                    }else{

                        $user = new User([
                            'name' =>$titleEn,
                            'username' => $request->mobile,
                            'password' => Hash::make($request->password),
                            'mobile' =>$mobile,
                            'phone' => $mobile,
                        ]);
                        $user->save();
                        $branch->user_id=$user->id;
                        $userDetail = new UserDetail([
                            'user_id' => $user->id,
                            'user_type' => 'branch'
                        ]);
                        $userDetail->save();

                    }
                }else{

                    $validate=  Validator::make($request->all(), [
                        'mobile' => 'required|size:8|unique:users',
                    ]);
                    if ($validate->fails())
                        return $this->fail($validate->errors()->first());



                    $update=false;
                    $branch=New Branch();
                    $user = new User([
                        'name' =>$titleEn,
                        'username' => $request->mobile,
                        'password' => Hash::make($request->password),
                        'mobile' =>$mobile,
                        'phone' => $mobile,
                    ]);
                    $user->save();

                    $branch->user_id=$user->id;

                    $userDetail = new UserDetail([
                        'user_id' => $user->id,
                        'user_type' => 'branch'
                    ]);
                    $userDetail->save();



                }
                if(isset($request->latitude) && !empty($request->latitude)){
                    $branch->latitude=$request->latitude;
                    $branch->longitude=$request->longitude;
                }


                $branch->address=$address;
                $branch->title_en=$titleEn;
                $branch->title_ar=$titleAr;
                $branch->area_id=$selectedArea;
                $branch->restaurant_id=$id;
                $branch->save();
                if (!empty($supporting)) {
                    $newAreas = json_decode($supporting);
                    if($update){
                         RestaurantSupportingArea::where('branch_id',$bId)->delete();
                    }
                    foreach ($newAreas as $item) {
                        RestaurantSupportingArea::create([
                            'restaurant_id' => $id,
                            'area_id' => $item->value,
                            'branch_id'=>$branch->id,
                        ]);
                    }
                }
                $res= $this->getBra($id);
                return $this->success('item retrieved successfully',$res);
            }
            return $this->fail("Please enter all fields");
        }catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }


    }
    public function updateBran(Request $request)
    {
        $bId=$request->id;
        $restId=$request->restId;
        $selectedAreaId=$request->selectedArea;
        $supporting=$request->supporting_areas;
        $address=$request->address;
        $res=  DB::table("branches")->where('id',$bId)->first();
        if(!empty($res)){
            DB::table("branches")->update(['address'=>$address,'area_id'=>$selectedAreaId]);



            $res= $this->getBra($restId);
            return $this->success('item retrieved successfully',$res);

        }
    }

    public function getBranch($id)
    {
        $bId=$id;
        $res=  DB::table("branches")->where('id',$bId)->first();
//        $restId=$res->restaurant_id;
//        $res= $this->getBra($restId);
        $areas = DB::table("areas")
            ->select('areas.id as value', 'areas.name_en as label')
            ->where('id',$res->area_id)
            ->get()
            ->toArray();

        $supportingAreas = DB::table('restaurant_supporting_areas')
            ->join('areas', 'areas.id', 'restaurant_supporting_areas.area_id')
            ->where('restaurant_supporting_areas.branch_id',$bId)
            ->select('areas.id as value', 'areas.name_en as label')
            ->get()
            ->toArray();
           $user=User::with("userDetails")->whereId($res->user_id)->first();
        return $this->success('item retrieved successfully',['branch'=>$res,'areas'=>$areas,'supportingAreas'=>$supportingAreas,'user'=>$user]);

    }

    public function getTimes($id)
    {
        $rest= Restaurant::find($id);

        if(!isset($rest)||empty($rest)){
            return $this->fail("Not found",404);
        }

         $times= DB::table("restaurant_activity_hours")->select(['*'])->where('restaurant_id',$id)->get()->keyBy('day_name')->toArray();
         return $this->success('item retrieved successfully',$times);

    }

    public function updateRestaurantTime(Request $request)
    {
        $id=$request->id;
        $array=[];



        $valid=true;
        foreach ($request->only(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']) as $day=>$objectTime) {
                $e=json_decode($objectTime);
                $array[$day]=$e;

            if($e->start==''||$e->end==''||empty($e->start)||empty($e->end)){
                    $valid=false;
                }
         }
        if(!$valid){
            return $this->fail("Please select for all days!");
        }
        foreach ($array as $dayName=>$times) {
                 $exist= DB::table('restaurant_activity_hours')->where('restaurant_id',$id)->where('day_name',$dayName)->count();
                 if($exist<=0){
                     DB::table('restaurant_activity_hours')->insert(['day_name'=>$dayName,'restaurant_id'=>$id,'start'=>$times->start,'end'=>$times->end,'created_at'=>date("Y-m-d H:i:s",time()),'updated_at'=>date("Y-m-d H:i:s",time())]);
                 }else{
                     DB::table('restaurant_activity_hours')->where('restaurant_id',$id)->where('day_name',$dayName)->update(['start'=>$times->start,'end'=>$times->end,'updated_at'=>date("Y-m-d H:i:s",time())]);
                 }
        }
        return $this->success('save times successfully',$array);
    }


    public function deleteBran(Request $request)
    {
        $bId=$request->id;
        $restId=$request->restId;
        $branch=Branch::where('id',$bId)->first();
        //confirm delete order and other ?
        UserDetail::where('user_id',$branch->user_id)->delete();
        User::where('id',$branch->user_id)->delete();
        RestaurantSupportingArea::where('branch_id',$bId)->delete();
        $branch->delete();
        $res= $this->getBra($restId);
        return $this->success('item retrieved successfully',$res);

    }

    public function getBra($id)
    {
        return   DB::table('branches')
                       ->join('users','branches.user_id','=','users.id')
                       ->join("areas",'branches.area_id','=','areas.id')
                       ->where("restaurant_id",$id)->select(["areas.name_en","branches.address","branches.id","areas.id as area_id","users.name","users.phone","users.mobile","users.id as user_id"])->get()->toArray();

    }

}

