<?php

namespace App\Http\Controllers;

use FastestModels\ActiveScope;
use FastestModels\AddressBook;
use FastestModels\Area;
use FastestModels\Branch;
use FastestModels\Client;
use FastestModels\Driver;
use FastestModels\Order;
use FastestModels\OrderHistory;
use FastestModels\Restaurant;
use FastestModels\User;
use FastestModels\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function filterOrders(Request $request)
    {
        //dd(auth()->user(),'lkjjdkd');

        $userTYpe= auth()->user()->user_type;
        $user= auth()->user();

        $branchId=0;

        if($userTYpe=="admin" || $userTYpe=="branch"){
            if($userTYpe=="branch"){
                $b=Branch::where('user_id',$user->user_id)->first();
                $branchId=$b->id;
            }
            $restId=0;
        } else{
            $userId=$user->user_id;
            $rest=Restaurant::where('user_id',$userId)->first();
            $restId=$rest->id;
        }
        $pagesSize=intval($request->pagesSize)>=1?intval($request->pagesSize):20;
        $restaurantId=$request->restaurantId;


        $filterKeyVal=json_decode($request->filterKeyVal);
        $sorted=json_decode($request->sorted);


        $clients=json_decode($request->clients);
        $restaurants=json_decode($request->restaurants);
        $drivers=json_decode($request->drivers);
        $status=json_decode($request->status);

        $newClients=[];
        $newRestaurants=[];
        $newDrivers=[];
        $newStatus=[];

        if(!empty($clients)){
            foreach ($clients as $client) {
                array_push($newClients,intval($client->value));
            }
        }


        if(!empty($restaurants)){
            foreach ($restaurants as $restaurant) {
                array_push($newRestaurants,$restaurant->value);
            }
        }
        if(!empty($drivers)){
            foreach ($drivers as $driver) {
                array_push($newDrivers,$driver->value);
            }
        }
        if(!empty($status)){
            foreach ($status as $stat) {
                array_push($newStatus,$stat->value);
            }
        }






        $validKeyValOrder=['unique_number'];

      // if($userTYpe=="admin"){

        $orders= Order::with(["client.user","restaurant.user","driver.user","items.food"]);

        if($restaurantId!=0){
            $orders=$orders->whereHas("restaurant",function ($d)use($restaurantId){
                $d->where('id',$restaurantId);
            });
        }else{
            if(count($newRestaurants)>=1){
                $orders=$orders->whereHas("restaurant",function ($d)use($newRestaurants){
                    $d->whereIn('user_id',$newRestaurants);
                });
            }

        }


        if($branchId!=0){
            $orders=$orders->whereHas("items",function ($d)use($branchId){
                $d->where('branch_id',$branchId);
            });
        }else{
            if($userTYpe!="admin"){
                $orders->whereHas("restaurant.user",function ($d)use($newRestaurants,$user){
                    $d->where('users.id',$user->user_id);
                });
            }
        }


            if(count($newStatus)>=1){
                $orders=$orders->whereIn('status',$newStatus);
            }

            if(count($newDrivers)>=1){
                $orders=$orders->whereHas("driver.user",function ($d)use($newDrivers){
                    $d->whereIn('id',$newDrivers);
                });

            }

            if(count($newClients)>=1){
                $orders=$orders->whereHas("client.user",function ($r)use($newClients){
                    $r->whereIn('id',$newClients);
                });
            }


            if(isset($filterKeyVal)){
                if(in_array($filterKeyVal->key,$validKeyValOrder)){
                    $orders=$orders->where($filterKeyVal->key,'like',"%".$filterKeyVal->value."%");
                }
            }


            if(isset($request->start) && $request->start!="null" ){
                $orders=$orders->where('created_at','>=',$request->start);
            }
            if(isset($request->end) && $request->end!="null"){

                $orders=$orders->where('created_at','<=',$request->end);
            }


            if(isset($sorted->id)){
                if($sorted->desc){
                    $orders=$orders->orderBy($sorted->id,'desc');
                }else{
                    $orders=$orders->orderBy($sorted->id,'asc');
                }
            }else{
                $orders=$orders->orderBy('id','desc');
            }





      //  }

  //      else{

//            $userId=$user->user_id;
//            $rest=Restaurant::where('user_id',$userId)->first();
//
//            $orders=DB::table("foods")
//                ->join("order_items","foods.id","=","order_items.food_id")
//                ->join("orders","order_items.order_id","=","orders.id")
//                ->join("orders","order_items.order_id","=","orders.id")
//                ->join("client","orders.client_id","=","client.id")
//                ->join("client","orders.client_id","=","client.id")
//                ->where("foods.restaurant_id",$rest->id);
      //  }

           $orders=$orders->paginate($pagesSize);
          return $this->success('item retrieved successfully',['orders'=>$orders,'type'=>$userTYpe,'id'=>$restId,'branchId'=>$branchId]);


    }
    public function setDriver(Request $request)
    {
        try{
            $driverId=$request->driver_id;
            $orderId=$request->order_id;
            $order= Order::find($orderId);
            $driver= Driver::with(["user.userDetails"=>function($r){
                $r->where('user_type','driver')->first();
            }])->where("user_id",$driverId)->first();
            if(isset($order) && isset($driver)){

//                $order->status_driver="accepted";
//                $order->driver_id=$driver->id;
//                $order->save();
//                OrderHistory::create(['order_id'=>$orderId,'driver_id'=>$driver->id,'status'=>"assigned","description"=>" assign new  order to driver  by admin"]);

                //temp
                $r[]=optional(optional($driver->user)->userDetails->first())->device_token;
                $ids[]=optional(optional($driver->user)->userDetails->first())->id;

                $data = array("title" =>"New OrderListeners ","message" =>"Assigned New OrderListeners","jobId"=>$order->id,"force"=>false);
                $res=parent::sendPushNotification($data,$r,[]);
                $this->logNotifications($ids,"New OrderListeners","New OrderListeners"," New OrderListeners"," New OrderListeners");

                return $this->success('item retrieved successfully',$res);
            }
            return $this->fail("order or driver not found!");
        }catch (\Exception $r){
            return $this->fail($r->getMessage());
        }
    }
    public function getOrder($id)
    {
        $order= Order::with(["client.user","restaurant.user","driver.user"])->where('id',$id)->first();
        return $this->success('item retrieved successfully',$order);
    }
    public function getOrderDetails($id)
    {

        $user= auth()->user();
        $userTYpe= $user->user_type;
        if($userTYpe=="admin"){
            $restId=0;
        }else{
            $userId=$user->user_id;
            $rest=Restaurant::where('user_id',$userId)->first();
            $restId=$rest->id;
        }

        $order= Order::with(["client.user.userDetails","driver.user.userDetails","items.food.restaurant.times","items.food.restaurant.user.userDetails","destination.area","items.addons","histories"=>function($r){
            $r->whereIn('status',['finish','assigned'])->get()->keyBy('status');
        }])->where('id',$id)->first();
        if(!isset($order)||empty($order)){
              return $this->fail("order or driver not found!",404);
        }
        $rest=[];
        $restPush=[];
        $items=[];
        $itemsGroup=[];
        $limitPrice=0;
        if(!empty($order)){
            $i=0;

            foreach ($order->items as $item) {
                if(isset(optional($item->food)->restaurant)){

                    if(!in_array($item->food->restaurant_id,$restPush)){
                        array_push($restPush,$item->food->restaurant_id);
                        array_push($rest,$item->food->restaurant);
                    }
                    $addons="";
                    if(!empty($item->addons) && $item->addons->count()>=1){
                        $ff=0;
                        foreach ($item->addons as $addon) {
                            if($ff>0){
                                $addons.=" | ";
                            }
                            $ff+=1;
                            $addons.=$addon->addon_name_en." : ".$addon->addon_item_name_en;
                        }
                    }else{
                        $addons="----";
                    }
                    if($restId==0){
                        $itemsGroup[$item->food->restaurant->id][]=[++$i,$item->food->name_en,$item->food->image,$item->price,$item->count,$addons,$item->food->restaurant->title_en];
                        array_push($items,[++$i,$item->food->name_en,$item->image,$item->price,$item->count,$addons,$item->food->restaurant->title_en]);
                    }else{
                        if($item->food->restaurant_id==$restId){
                            $limitPrice+=$item->price;
                            array_push($items,[++$i,$item->food->name_en,$item->image,$item->price,$item->count,$addons,$item->food->restaurant->title_en]);
                        }
                    }
                }
            }
        }
        $newGroup=[];
        if(count($itemsGroup)>=1){
            foreach ($itemsGroup as $id=>$list) {
                array_push($newGroup,$list);
            }
        }
        return $this->success('item retrieved successfully',['order'=>$order,'restaurant'=>$rest,'items'=>$items,'id'=>$restId,'limitPrice'=>$limitPrice,'itemsGroup'=>$newGroup]);
    }
    public function getNotifyDriverList(Request $request)
    {
        $orderId=$request->orderId;
        $result=DB::table("order_notifications")
            ->join("drivers","order_notifications.driver_id","=","drivers.id")
            ->join("user_details","drivers.user_id","=","user_details.user_id")
            ->join("users","drivers.user_id","=","users.id")
            ->where('order_notifications.order_id',$orderId)
            ->where('user_details.user_type','driver')
            ->select(['users.name as driver','order_notifications.driver_distance','order_notifications.status','order_notifications.created_at'])->paginate(30);
        return $this->success('item retrieved successfully',['list'=>$result]);


    }
    public function getOrderHistory(Request $request)
    {
          $list=[];
          $res= OrderHistory::with(['order.driver.user','order.client.user'])->where('order_id',$request->orderId)->get();
            foreach ($res as $re) {
                if($re->status=="created"){
                    $user=isset($re->client)?optional($re->client->user)->name:"Guest";
                    $list[]=['title'=>"new order by -->".$user,'description'=>$re->description,'created_at'=>date("Y-m-d H:m",strtotime($re->created_at))];
                }else{
                    if($re->status=="onway"){
                        $driver=optional(optional($re->driver)->user)->name;
                        $list[]=['title'=>"onway  by driver -->".$driver,'description'=>$re->description,'created_at'=>date("Y-m-d H:m",strtotime($re->created_at))];
                    }else{
                        $list[]=['title'=>$re->status,'description'=>$re->description,'created_at'=>date("Y-m-d H:m",strtotime($re->created_at))];

                    }
                }
              }
             return $this->success('item retrieved successfully',['list'=>$list]);

    }
}
