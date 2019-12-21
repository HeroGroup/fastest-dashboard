<?php

namespace App\Http\Controllers;

use FastestModels\ActiveScope;
use FastestModels\Area;
use FastestModels\City;
use FastestModels\RestaurantSupportingArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AreaController extends Controller
{
    public function validator(array $data)
    {
        return Validator::make($data, [
            'city_id' => 'required|integer',
            'name_en' => 'required|string|unique:areas',
            'name_ar' => 'required|string|unique:areas',
            'latitude' => 'required',
        ]);
    }

    public function editAreaValidator(array $data, $id)
    {
        return Validator::make($data, [
            'name_en' => 'required|string',
            'name_ar' => 'required|string',
        ]);
    }
    public function cityValidator(array $data)
    {
        return Validator::make($data, [
            'name_en' => 'required|string|unique:cities',
            'name_ar' => 'required|string|unique:cities',
        ]);
    }
    public function editCityValidator(array $data, $id)
    {
        return Validator::make($data, [
            'name_en' => 'required|string|unique:cities,'.$id,
            'name_ar' => 'required|string|unique:cities,'.$id,
        ]);
    }

    public function getAreasWithGroups()
    {
        $cities = City::get(['id', 'name_en']);
        $areas = [];
        foreach ($cities as $city) {
            $label = $city->name_en;
            $options = DB::table('areas')
                ->select('id as value', 'name_en as label', 'latitude', 'longitude')
                ->where('city_id', '=', $city->id)
                ->get()
                ->toArray();

            $item = ['label' => $label, 'options' => $options];

            array_push($areas, $item);
        }

        return $areas;
    }

    public function getAreas()
    {
        try {
            return $this->success('Areas retrieved successfully', $this->getAreasWithGroups());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getAreaList()
    {
        return Area::with('city')->get(); // withoutGlobalScope(ActiveScope::class)
    }

    public function index()
    {
        try {
            return $this->success('Areas retrieved successfully', $this->getAreaList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            if (!$request->city_id > 0) {
                return $this->fail('please choose city');
            }

            if ($request->id > 0) { // update
                $validate = $this->editAreaValidator($request->all(), $request->id);
                if ($validate->fails())
                    return $this->fail('validation failed', -1, $validate->errors());

                $area = Area::withoutGlobalScope(ActiveScope::class)->find($request->id);
                if ($area) {
                    $area->update([
                        'city_id' => $request->city_id,
                        'name_en' => $request->name_en,
                        'name_ar' => $request->name_ar,
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                    ]);
                } else {
                    return $this->fail('invalid area');
                }
            } else { // new
                $validate = $this->validator($request->all());
                if ($validate->fails())
                    return $this->fail('validation failed', -1, $validate->errors());

                $area = new Area([
                    'city_id' => $request->city_id,
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ]);
                $area->save();
            }
            return $this->success('new area created successfully', $this->getAreaList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getLine().': '.$exception->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            RestaurantSupportingArea::where('area_id', '=', $request->id)->delete();
            $area = Area::withoutGlobalScope(ActiveScope::class)->find($request->id)->delete();

            return $this->success('area deleted successfully', $this->getAreaList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function citiesIndex(Request $request)
    {
        try {
            return $this->success('Cities retrieved successfully', $this->getCityList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getCityList()
    {
        return City::withoutGlobalScope(ActiveScope::class)->get();
    }

    public function storeCity(Request $request)
    {
        try {
            $validate = $this->cityValidator($request->all());
            if ($validate->fails())
                return $this->fail('validation failed', -1, $validate->errors());

            if ($request->id > 0) { // update
                $city = City::withoutGlobalScope(ActiveScope::class)->find($request->id);
                if ($city) {
                    $city->update([
                        'name_en' => $request->name_en,
                        'name_ar' => $request->name_ar,
                    ]);
                } else {
                    return $this->fail('invalid city');
                }
            } else { // new
                $city = new City([
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                ]);
                $city->save();
            }
            return $this->success('city updated successfully', $this->getCityList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function destroyCity(Request $request)
    {
        try {
            RestaurantSupportingArea::whereIn('area_id', Area::where('city_id', '=', $request->id)->get()->toArray())->delete();
            Area::where('city_id', '=', $request->id)->delete();
            City::withoutGlobalScope(ActiveScope::class)->find($request->id)->delete();

            return $this->success('city deleted successfully', $this->getCityList());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getCities()
    {
        try {
            $areas = DB::table('cities')->select('id as value', 'name_en as label')->get()->toArray();

            return $this->success('Cities retrieved successfully', $areas);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }
}
