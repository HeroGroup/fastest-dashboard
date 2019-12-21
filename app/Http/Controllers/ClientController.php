<?php

namespace App\Http\Controllers;

use FastestModels\ActiveScope;
use FastestModels\AddressBook;
use FastestModels\Area;
use FastestModels\Client;
use FastestModels\User;
use FastestModels\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use function Sodium\add;

class ClientController extends Controller
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

    public function geClientsList()
    {
        return Client::withoutGlobalScope(ActiveScope::class)->with('user')->orderBy('id','desc')->get();
    }

    public function index()
    {
        try {
            $clients = $this->geClientsList();
            return $this->success('clients retrieved successfully...', $clients);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getClient($client)
    {
        try {
            $data = Client::withoutGlobalScope(ActiveScope::class)
                ->where('id', '=', $client)
                ->with('user')
                ->first();
            return $this->success('item retrieved successfully', $data);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $client = Client::withoutGlobalScope(ActiveScope::class)->find($request->id);
            $user = User::withoutGlobalScope(ActiveScope::class)->find($client->user_id);
            $client->update(['is_active' => $request->is_active == "true" ? 1 : 0 ]);
            $user->update([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'mobile' => $request->mobile,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth
            ]);

            return $this->success('client updated successfully');
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $client = Client::withoutGlobalScope(ActiveScope::class)->find($request->id)->delete();
            // TODO dependencies should be checked
            return $this->success('restaurant removed successfully!', $this->getRestaurantsList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getAreas()
    {
        try {
            $x = Area::pluck('name_en', 'id')->toArray();
            $areas = [];
            foreach ($x as $key => $value)
                array_push($areas, ['id' => $key, 'name' => $value]);

            return $areas;
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function getPlaceTypes()
    {
        try {
            $x = Config::get('enums.place_type');
            $placeTypes = [];
            foreach ($x as $key => $value)
                array_push($placeTypes, ['id' => $key, 'name' => $value]);

            return $placeTypes;
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function getClientAddressList($clientId)
    {
        try {
            $client = Client::find($clientId);
            $user = User::find($client->user_id);
            $addresses = AddressBook::withoutGlobalScope(ActiveScope::class)
                ->where('user_id' ,'=', $client->user_id)
                ->with('area')
                ->get();

            return $this->success('client addresses retrieved successfully', $addresses);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getClientAddress($addressId)
    {
        try {
            $address = AddressBook::withoutGlobalScope(ActiveScope::class)
                ->where('id', '=', $addressId)
                ->with('area')
                ->first();

            return $this->success('address retrieved successfully', ['address' => $address, 'areas' => $this->getAreas(), 'placeTypes' => $this->getPlaceTypes()]);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function updateAddress(Request $request)
    {
        try {
            $address = AddressBook::withoutGlobalScope(ActiveScope::class)->find($request->id);
            if ($address) {
                $address->update([
                    'area_id' => $request->area_id,
                    'block' => $request->block,
                    'street' => $request->street,
                    'avenue' => $request->avenue,
                    'building_number' => $request->building_number,
                    'floor' => $request->floor,
                    'jadda' => $request->jadda,
                    'place_type' => $request->place_type,
                    'phone_number' => $request->phone_number,
                    'is_default' => $request->is_default == "true" ? 1 : 0,
                    'is_active' => $request->is_active == "true" ? 1 : 0
                ]);

                return $this->success('address updated successfully');
            } else {
                return $this->fail("invalid address id $request->id");
            }
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function destroyAddress(Request $request)
    {
        try {
            $address = AddressBook::withoutGlobalScope(ActiveScope::class)->find($request->id);
            if ($address) {
                $address->delete();
                return $this->success('address deleted successfully');
            }
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function filterAndPaginate(Request $request)
    {
        $pagesSize=$request->pageSize;
        $filterKeyVal=$request->filterKeyVal;
        $sorted=$request->sorted;

        $validKeyFilterUser=['id','name','mobile','is_Active'];
        try {
            $data =Client::withoutGlobalScope(ActiveScope::class)->with('user');

                if(isset($filterKeyVal["key"])){
                    if(in_array($filterKeyVal["key"],$validKeyFilterUser)){
                        $data=$data->whereHas("user",function ($r)use($filterKeyVal){
                            $r->where($filterKeyVal["key"],'like',"%".$filterKeyVal['value']."%");
                        });
                    }
                }



                if(isset($sorted["id"]) && $sorted["id"]!=null ){
                    $desc=$sorted["desc"]?'desc':'asc';
                    $data=$data->orderBy($sorted["id"],$desc);
                }else{
                    $data->orderBy('id','desc');
                }

               $data=$data->paginate($pagesSize);
            return $this->success('item retrieved successfully', $data);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function searchClient(Request $request)
    {

        $users = DB::table('user_details')
            ->join('users', 'user_details.user_id','users.id')
            ->where('user_details.user_type',$request->type)
            ->where(function($r)use($request){
                $r->where('users.username','like','%'.$request->value.'%')->orWhere('users.name','like','%'.$request->value.'%')->orWhere('users.mobile','like','%'.$request->value.'%');
            })
            ->select('users.id as value','users.name as label')
            ->orderBy('users.id','desc')
            ->limit(20)
            ->get()
            ->toArray();
        return $this->success('item retrieved successfully', $users);

    }
}
