<?php
namespace App\Http\Controllers;
use FastestModels\Discount;
use FastestModels\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller {


    public $listChar=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
    public $arrayNumber=["0","1","2","3","4","5","6","7","8","9"];

    public function validator(array $data,$id=0)
    {
        return Validator::make($data, [
            'name_en' => 'required|string',
            'name_ar' => 'required|string',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
            'min_price_order' => 'required|numeric',
            'count_limit_user' => 'required|numeric',
            'count_limit' => 'required|numeric',
            'prefix' =>$id==0?'required|string|unique:discounts,prefix':'required|string|unique:discounts,prefix,'.$id,
            'value' => 'required|numeric',
            'type' => 'required',
            'type_discount' => 'required',

        ]);
    }
    public function createOrUpdate(Request $request)
    {
        try{
            $validate = $this->validator($request->all(),$request->id);
            if ($validate->fails())
                return $this->fail('validation failed', -1, $validate->errors());


            if ($request->id > 0) {
                $discount=  Discount::find($request->id);
                $edit=true;
            }else {
                $discount = new Discount();
                $edit=false;
              }

             $discount->name_en=$request->name_en;
             $discount->name_ar=$request->name_ar;
             $discount->start=$request->start;
             $discount->end=$request->end;
             $discount->min_price_order=$request->min_price_order;
             $discount->count_limit_user=$request->count_limit_user;
             $discount->count_limit=$request->count_limit;
             $discount->prefix=$request->prefix;
             $discount->value=$request->value;
             $discount->type=$request->type;
             $discount->type_discount=$request->type_discount;
             $discount->is_active= $request->is_active == "true" ? 1 : 0;
             $discount->save();
             $array=json_decode($request->selectedRestaurant);
             if(is_array($array)){
                 if(count($array)>=1){
                     $e=[];
                     foreach ($array as $item) {
                         array_push($e,$item->value);
                     }
                     $e=$this->getRestId($e);
                     $discount->restaurants()->sync($e,true);
                 }else{
                     $discount->restaurants()->sync([]);
                 }
             }else{
                 $discount->restaurants()->sync([]);
             }


            if($request->type_discount=="General"){
                if($edit){
                    DiscountCode::where('discount_id',$discount->id)->update(['code'=>$request->prefix]);
                }else{
                    DiscountCode::create(['discount_id'=>$discount->id,'code'=>$request->prefix]);
                }
            }

            return $this->success(" save Discount success fully");
        }catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }



    }
    public function getRestId($e)
    {
        if(count($e)>=1){
          return  DB::table('restaurants')->whereIn('user_id',$e)->pluck('id')->toArray();
        }else{
            return [];
        }

    }
    public function getListDiscountForSelect(Request $request)
    {
        $res=  DB::table('discounts')->select(['id as value','name_en as label']);
        if(strlen($request->search>=1)){
            $res=$res->where("name","like","%".$request->search."%");
        }
        $res=$res->orderBy('id','desc')->limit(30)->get()->toArray();
        return $this->success(" save Discount success fully",$res);
    }
    public function getListDiscount(Request $request)
    {
        list($newRestaurants, $orders) = $this->filterListDiscounts($request);
        return $this->success('item retrieved successfully',['discount'=>$orders]);
    }
    public function filterCodeList(Request $request)
    {
        list($newDiscounts, $codes) = $this->filterCodesList($request);
        return $this->success('item retrieved successfully',['codes'=>$codes]);



    }
    public function getDiscount($id)
    {
        $disCount=Discount::with(["restaurants.user"])->where('id',$id)->first();
        $array=[];
        if($disCount->restaurants->count()>=1){
            foreach ($disCount->restaurants as $restaurant) {
                array_push($array,['value'=>$restaurant->user->id,'label'=>$restaurant->user->name]);
            }
        }
        return $this->success('item retrieved successfully',['discount'=>$disCount,'rest'=>$array]);

    }
    public function createCodeDiscount(Request $request)
    {

        try{
            $id=$request->discount_id;
            $res= Discount::where('id',$id)->with(["codes"=>function($r){
                $r->pluck("code")->all();
            }])->first();

            if(intval($request->length_code)<4){
                return $this->fail('The code length must be greater than or equal  4');
            }

            $listOldCodeDiscount= collect($res->codes)->toArray();
            $countOldCode=count($listOldCodeDiscount);
            $arrayValidCode=[];

            if($request->type==1){
                $t= array_merge($this->arrayNumber,$this->listChar);
                $final=collect($t)->shuffle()->toArray();
            }elseif($request->type==2){
                $final=collect($this->listChar)->shuffle()->toArray();
            }elseif($request->type==3){
                $final=collect($this->arrayNumber)->shuffle()->toArray();
            }else{
                $final=collect($this->listChar)->shuffle()->toArray();
            }

            $resultValidCode=$this->getRandomValue($final,$request->length_code,$request->count_code,$res->prefix,$listOldCodeDiscount);
            if(count($resultValidCode)>=1){
                foreach ($resultValidCode as $item) {
                    array_push( $arrayValidCode,$item);
                    $res->codes()->create(['code'=>$item,'discount_id'=>$id]);
                }
            }

            $codes= DiscountCode::with(["discount"])->whereHas("discount",function ($d)use($id){
                $d->where('discount_id',$id);
            })->orderBy('id','desc')->paginate(20);

            return $this->success('item retrieved successfully',['codes'=>$codes]);
        }catch (\Exception $e){
            return $this->fail('error server');
        }



    }
    public function delete(Request $request)
    {
        $res=Discount::where('id',$request->id)->delete();
         DiscountCode::where('discount_id',$request->id)->delete();
        list($newRestaurants, $orders) = $this->filterListDiscounts($request);
        return $this->success('Delete Item Successfully',['discount'=>$orders]);
    }

    public function destroyDiscountCode(Request $request)
    {
        DiscountCode::where('id',$request->id)->delete();
        list($newDiscounts, $codes) = $this->filterCodesList($request);
        return $this->success('Delete Item Successfully',['codes'=>$codes]);

    }

    public function irresponsibleCode(Request $request)
    {
        $code= CodeDiscount::find($request->id);
        if($code->status==0){
            $code->status=2;
        }elseif($code->status==2){
            $code->status=0;
        }else{
            return response()->json(array(
                'success' =>false,
                'msg' => "این کد استفاده شده و قابل ابطال نمی باشد."
            ));
        }
        $result=  $code->save();
        return response()->json(array(
            'success' => $result?true:false,
            'msg' => dic("Status updated successfully !")
        ));

    }
    public function irresponsibleAllCode(Request $request)
    {
        $listDiscount= Discount::where('id',$request->id)->with(["Listcode"=>function($r){
            $r->where('status',0);
        }])->first();
        foreach ($listDiscount->Listcode as $code) {
            $code->status=2;
            $result=  $code->save();
        }

        return response()->json(array(
            'success' => $result?true:false,
            'msg' => dic("Status updated successfully !")
        ));

    }
    public function search(Request $request)
    {

        if($request->search!=null){
            $rows=Discount::orderBy('id','desc')->where('name','LIKE',"%$request->search%")->get();
            return \response()->json(['items'=>$rows]);

        }else{
            if($request->name!=null){
                $rows=Discount::orderBy('end','desc')->where('name','LIKE',"%$request->name%")->paginate(20);
            }else{
                $rows=Discount::orderBy('end','desc')->paginate(20);
            }
            $view = view('show::admin.discount.partials.list', compact('rows'))->render();

            return response()->json(['success' => true, 'data' => $view]);
        }


    }
    public function statusChange(Request $request)
    {
        $stock= Discount::find($request->id);
        $stock->is_enable=$request->status;
        $result=  $stock->save();
        return response()->json(array(
            'success' => $result?true:false,
            'msg' => dic("Status updated successfully !")
        ));
    }

    public function findById(Request $request)
    {
        $item=Discount::where('id',$request->id)->with(['showTimes','venue','venueCat','show','showCat','organization'])->first();
        if(!is_null($item->valid_day_showtime)){
            $validDay= json_decode($item->valid_day_showtime);

        }else{
            $validDay=[];
        }



        return response()->json(['item'=>$item,'valid_day'=>is_null($item->valid_day_showtime)?null:$validDay,'start'=>convertTimeStampToJalai($item->start),'end'=>convertTimeStampToJalai($item->end)]);
    }
    public function makeDiscountCode(Request $request)
    {



        $result=ValidatorManager::formValidator($request->all(),['related_discount_code'=>'required','count_code'=>'required|numeric'],['required'=>'مقدار این فیلد الزامی است!']);
        if (!$result['success']){
            return ValidatorManager::responseToJson(false,$result['msg'],"arr");
        }

        $id=$request->related_discount_code;
        $res= Discount::where('id',$id)->with(["codes"=>function($r){
            $r->pluck("code")->all();
        }])->first();
        $listOldCodeDiscount= collect($res->Listcode)->toArray();
        $countOldCode=count($listOldCodeDiscount);
        $result= $this->returnTypeCodeDiscount($res,$countOldCode,$request->count_code);
        $arrayValidCode=[];
        if(array_key_exists('noObject',$result)){
            return response()->json(['success'=>false,'msg'=>"تخفیف مورد نظر یافت نشد!"]);
        }elseif (array_key_exists('capacity',$result)){
            return response()->json(['success'=>false,'msg'=>"بیشتر از ظرفیت ثبت شده برای تولید کد!!"]);
        }elseif (array_key_exists('typeUsage',$result)){
            return response()->json(['success'=>false,'msg'=>"این تخفیف از نوع عمومی بوده و کد تخفیف ان موقع ثبت تولید شده است."]);

        } else{
            $resultValidCode=$this->getRandomValue($result['listCharAndNumber'],$result['lengthUnique'],$request->count_code,$result['prefix'],$listOldCodeDiscount);
            if(count($resultValidCode)>=1){
                foreach ($resultValidCode as $item) {
                    array_push( $arrayValidCode,$item);
                    $res->codes()->create(['code'=>$item,'discount_id'=>$id]);
                }
            }

        }
        //$this->reportExcelCode($id,0);

//        $data = CodeDiscount::where('discount_id',$id)->whereIn('code',$status)->select(['id','code'])->get()->toArray();
//        return Excel::create($name, function($excel) use ($data) {
//            $excel->sheet('mySheet', function($sheet) use ($data)
//            {
//                $sheet->fromArray($data);
//            });
//        })->download('xls');

        $link="/sayna/discount/reportExcelCodeLastCreated/".$id."/".encrypt(json_encode($arrayValidCode));
        return response(['success'=>true,'msg'=>"با موفقیت انجام شد",'link'=>$link]);


    }
    public function helpAndInfoForMakeCodeDiscount(Request $request)
    {
        $prefix=$request->prefix_code;
        $sampleCode=$prefix;
        $length_unique=$request->length_unique;
        $max_count_code=$request-> max_count_code;
        $numeric=$request->numeric;
        $UpperCase=$request->UpperCase;



        if($numeric==1 && $UpperCase==1){
            $maxGenarateCode=pow(36,$length_unique);
            $t= array_merge($this->arrayNumber,$this->listChar);
            $final=collect($t)->shuffle()->toArray();
            $sampleCode.=$this->getRandomValue($final,$length_unique,1)[0];



        }elseif ($numeric==0 && $UpperCase==1){
            $maxGenarateCode=pow(26,$length_unique);
            $final=collect($this->listChar)->shuffle()->toArray();
            $sampleCode.=$this->getRandomValue($final,$length_unique,1)[0];

        }elseif ($numeric==1 && $UpperCase==0){
            $maxGenarateCode=pow(10,$length_unique);
            $final=collect($this->arrayNumber)->shuffle()->toArray();
            $sampleCode.=$this->getRandomValue($final,$length_unique,1)[0];
        }else{
            $maxGenarateCode=1;
        }
        $possibility=ceil(floatval(($max_count_code/$maxGenarateCode))*100);
        if($possibility>100){
            $possibility=100;
        }
        $discount= Discount::where("prefix_code",$prefix)->where('id','!=',$request->edit_id)->first();
        if(count($discount)>=1){
            $hasErrorUniqPrefix=true;
        }else{
            $hasErrorUniqPrefix=false;
        }

        return response()->json(['possibility'=>$possibility,'maxCount'=>$maxGenarateCode,'sample_discount_code'=>$sampleCode,'hasErrorUniqPrefix'=>$hasErrorUniqPrefix]);

    }
    public function getRandomValue($array,$LengthValue,$countValue,$prefix="",$listOldCode=[])
    {

        $countValue=intval($countValue)>=1?intval($countValue):10;
        $LengthValue=intval($LengthValue)>=4?intval($LengthValue):4;

        $finalArray=[];
        for ($j=0;$j<$countValue;$j++){
            $value=$prefix;
            for($i=0;$i<$LengthValue;$i++){
                $value.=$array[array_rand($array)];
            }
            if(!in_array($value,$finalArray) && !in_array($value,$listOldCode)){
                array_push($finalArray,$value);
            }else{
                --$j;
            }
        }
        return $finalArray;
    }




    public function returnTypeCodeDiscount($discount,$countOldCode=0,$countRequest)
    {

        if(is_object($discount)){
            $prefix=$discount->prefix;

                    $acceptNumeric=$discount->accept_numeric_code;
                    $acceptCharactersCode=$discount->accept_characters_code;


                    if($acceptCharactersCode==1 && $acceptNumeric==1){
                        $t= array_merge($this->arrayNumber,$this->listChar);
                        $final=collect($t)->shuffle()->toArray();
                    }elseif($acceptCharactersCode==1 && $acceptNumeric==0){
                        $final=collect($this->listChar)->shuffle()->toArray();
                    }elseif($acceptCharactersCode==0 && $acceptNumeric==1){
                        $final=collect($this->arrayNumber)->shuffle()->toArray();
                    }else{
                        $final=collect($this->listChar)->shuffle()->toArray();
                    }
                    return ['prefix'=>$prefix,'listCharAndNumber'=>$final,'lengthUnique'=>"",'maxUsage'=>""];

        }
        return ['noObject'=>true];

    }
    public function changeStatusCode(Request $request)
    {
        $stock= CodeDiscount::find($request->id);
        $stock->is_enable=$request->status;
        $result=  $stock->save();
        return response()->json(array(
            'success' => $result?true:false,
            'msg' => dic("Status updated successfully !")
        ));

    }
    public function reportExcelCode($id,$status)
    {
        $discount=Discount::find($id);
        $name=$discount->name;
        if($status==1){
            $name.="-"."استفاده شده";
        }
        $data = CodeDiscount::where('discount_id',$id)->where('status',$status)->select(['id','code'])->get()->toArray();
        return Excel::create($name, function($excel) use ($data) {
            $excel->sheet('mySheet', function($sheet) use ($data)
            {
                $sheet->fromArray($data);
            });
        })->download('xls');
    }
    public function reportExcelCodeLastCreated($id,$jsonEncriptedCode)
    {

        $json=decrypt($jsonEncriptedCode);
        $arrayCode=json_decode($json);
        $discount=Discount::find($id);
        $name=count($arrayCode)."_".$discount->name;

        $data = CodeDiscount::where('discount_id',$id)->whereIn('code',$arrayCode)->select(['id','code'])->get()->toArray();
        return Excel::create($name, function($excel) use ($data) {
            $excel->sheet('mySheet', function($sheet) use ($data)
            {
                $sheet->fromArray($data);
            });
        })->download('xls');
    }

    /**
     * @param $discountId
     * @return mixed
     */
    public function returnShowTimeListDiscount($discountId)
    {
        $discount1 = Discount::with(["showTimes.venue", "showTimes.show"])->where('id', $discountId)->first();
        $rows = $discount1->showTimes;
        $view = view('show::admin.discount.partials.list-showTime', compact('rows'))->render();
        return $view;
    }
    private function formatedTimeRange($request,$key){
        if(isset($request->$key)){
            return array_chunk(explode(",",$request->$key),2);
        }
        return [];

    }

    public function tempUpdateDiscount()
    {
        $d=json_encode(["saturday"=>[["00:00","23:59"]],"sunday"=>[["00:00","23:59"]],"monday"=>[["00:00","23:59"]],"tuesday"=>[["00:00","23:59"]],"wednesday"=>[["00:00","23:59"]],"thursday"=>[["00:00","23:59"]],"friday"=>[["00:00","23:59"]]]);
        $res= Discount::where('id','>',0)->update(['valid_day_showtime'=>$d]);

        return $res;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function filterListDiscounts(Request $request)
    {
        $pagesSize = intval($request->pagesSize) >= 1 ? intval($request->pagesSize) : 20;
        $filterKeyVal = json_decode($request->filterKeyVal);
        $sorted = json_decode($request->sorted);
        $restaurants = json_decode($request->restaurants);

        $newRestaurants = [];
        if (!empty($restaurants)) {
            foreach ($restaurants as $restaurant) {
                array_push($newRestaurants, $restaurant->value);
            }
        }


        $validKeyValOrder = ['name_en', 'prefix'];

        // if($userTYpe=="admin"){

        $orders = Discount::with(["restaurants"]);

        if (count($newRestaurants) >= 1) {
            $orders = $orders->whereHas("restaurants", function ($d) use ($newRestaurants) {
                $d->whereIn('user_id', $newRestaurants);
            });
        }


        if (isset($filterKeyVal)) {
            if (in_array($filterKeyVal->key, $validKeyValOrder)) {
                $orders = $orders->where($filterKeyVal->key, 'like', "%" . $filterKeyVal->value . "%");
            }
        }
        if (isset($sorted->id)) {
            if ($sorted->desc) {
                $orders = $orders->orderBy($sorted->id, 'desc');
            } else {
                $orders = $orders->orderBy($sorted->id, 'asc');
            }
        } else {
            $orders = $orders->orderBy('id', 'desc');
        }

        $orders = $orders->paginate($pagesSize);
        return array($newRestaurants, $orders);
    }

    /**
     * @param Request $request
     * @return array
     */
    private function filterCodesList(Request $request)
    {
        $pagesSize = intval($request->pagesSize) >= 1 ? intval($request->pagesSize) : 20;
        $filterKeyVal = json_decode($request->filterKeyVal);
        $sorted = json_decode($request->sorted);
        $discounts = json_decode($request->discounts);

        $discountId = $request->discountId;


        $newDiscounts = [];
        if (!empty($discounts)) {
            foreach ($discounts as $item) {
                array_push($newDiscounts, $item->value);
            }
        }

        $validKeyValOrder = ['code'];
        $validKeyValDiscount = ['discount'];


        $codes = DiscountCode::with(["discount"]);


        if ($discountId != 0) {
            $codes = $codes->whereHas("discount", function ($d) use ($discountId) {
                $d->where('discount_id', $discountId);
            });
        }

        if (count($newDiscounts) >= 1) {
            $codes = $codes->whereHas("discount", function ($d) use ($newDiscounts) {
                $d->whereIn('discount_id', $newDiscounts);
            });
        }


        if (isset($filterKeyVal)) {
            if (in_array($filterKeyVal->key, $validKeyValOrder)) {
                $codes = $codes->where($filterKeyVal->key, 'like', "%" . $filterKeyVal->value . "%");
            } elseif (in_array($filterKeyVal->key, $validKeyValDiscount)) {
                $codes = $codes->whereHas("discount", function ($d) use ($filterKeyVal) {
                    $d->where('name_en', 'like', "%" . $filterKeyVal->value . "%");
                });
            }
        }
        if (isset($sorted->id)) {
            if ($sorted->desc) {
                $codes = $codes->orderBy($sorted->id, 'desc');
            } else {
                $codes = $codes->orderBy($sorted->id, 'asc');
            }
        } else {
            $codes = $codes->orderBy('id', 'desc');
        }

        $codes = $codes->paginate($pagesSize);
        return array($newDiscounts, $codes);
    }


}














































