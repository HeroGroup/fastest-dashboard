<?php

namespace App\Http\Controllers;

use FastestModels\ActiveScope;
use FastestModels\Addon;
use FastestModels\Categoriable;
use FastestModels\Food;
use FastestModels\FoodAddon;
use FastestModels\Restaurant;
use FastestModels\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FoodController extends Controller
{
    public function validation(array $data)
    {
        return Validator::make($data, [
            'name_en' => 'required',
            'name_ar' => 'required',
            'price' => 'required|numeric',
            'is_active' => 'required',
        ]);
    }

    public function index($restaurant=null)
    {
        return $this->success('foods retrieved successfully!', $this->getFoodsList($restaurant));
    }

    public function getFoodsList($restaurant=null)
    {
        $res = Food::withoutGlobalScope(ActiveScope::class);

        if ($restaurant)
            $res->where('restaurant_id', '=', $restaurant);

        $user = \request()->user();
        if ($user->user_type == 'restaurant')
            $res=$res->whereHas('restaurant', function ($r) use($user){
                $r->where('user_id', $user->user_id);
            });

        $res = $res->with('restaurant.user')->get();
        return $res;
    }

    public function getFood($foodId)
    {
        $food = null;
        if ($foodId > 0) {
            $food = Food::withoutGlobalScope(ActiveScope::class)->find($foodId);
            $categories = DB::table('categoriables')
                ->join('categories', 'categories.id', 'categoriables.category_id')
                ->where('categoriables.categoriable_id', '=', $foodId)
                ->where('categoriables.categoriable_type', '=', 'Food')
                ->select('categories.id as value', 'categories.name_en as label')
                ->get();

            $addons = DB::table('food_addons')
                ->join('addons', 'addons.id', 'food_addons.addon_id')
                ->where('food_addons.food_id', '=', $foodId)
                ->select('addons.id as value', 'addons.name_en as label')
                ->get();

            $food->categories = $categories;
            $food->addons = $addons;
        }

        return $this->success('foods retrieved successfully!', ['food' => $food]);
    }

    public function store(Request $request)
    {
        try {
            if (isset($request->id)) {
                $food = Food::withoutGlobalScope(ActiveScope::class)->find($request->id);

                if ($request->price_on_selection == "true" &&
                    !isset($request->addons) &&
                    FoodAddon::where('food_id', $food->id)->count() == 0)
                    return $this->fail('Addon is required!');

                $food->update([
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'description_en' => ($request->description_en != "null" && $request->description_en != "undefined") ? $request->description_en : null,
                    'description_ar' => ($request->description_ar != "null" && $request->description_ar != "undefined") ? $request->description_ar : null,
                    'price' => $request->price,
                    'preparation_time_minutes' => $request->preparation_time_minutes ? $request->preparation_time_minutes : 0,
                    'is_active' => $request->is_active == "true" ? 1 : 0,
                    'price_on_selection' => $request->price_on_selection== "true" ? 1 : 0,
                ]);

                if ($request->hasFile('image'))
                    $food->update(['image' => $this->saveFile($request->image)]);

                if ($request->hasFile('icon'))
                    $food->update(['icon' => $this->saveFile($request->icon)]);

            } else {
                if ($request->price_on_selection == "true" && !isset($request->addons))
                    return $this->fail('Addon is required!');

                $validate = $this->validation($request->all());
                if ($validate->fails())
                    return $this->fail('validation failed', -1,  $validate->errors());

                $food = new Food([
                    'restaurant_id' => $request->restaurant_id,
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'description_en' => ($request->description_en != "null" && $request->description_en != "undefined") ? $request->description_en : null,
                    'description_ar' => ($request->description_ar != "null" && $request->description_ar != "undefined") ? $request->description_ar : null,
                    'image' => $request->hasFile('image') ? $this->saveFile($request->image) : null,
                    'icon' => $request->hasFile('icon') ? $this->saveFile($request->icon) : null,
                    'price' => $request->price,
                    'preparation_time_minutes' => $request->preparation_time_minutes ? $request->preparation_time_minutes : 0,
                    'is_active' => 1,
                    'price_on_selection' => $request->price_on_selection== "true" ? 1 : 0,
                ]);

                $food->save();
            }

            if (isset($request->categories)) {
                $newCategories = json_decode($request->categories);
                if ($newCategories) {
                    Categoriable::where('categoriable_id',$food->id)->where('categoriable_type', '=', 'Food')->delete();
                    foreach ($newCategories as $newCategory) {
                        Categoriable::create([
                            'category_id' => $newCategory->value,
                            'categoriable_id' => $food->id,
                            'categoriable_type' => 'Food',
                            'is_active' => 1
                        ]);
                    }
                }else{
                    Categoriable::where('categoriable_id',$food->id)->where('categoriable_type','Food')->delete();
                }
            }else{
                Categoriable::where('categoriable_id',$food->id)->where('categoriable_type','Food')->delete();
            }

            if (isset($request->addons)) {
                $newAddons = json_decode($request->addons);
                if ($newAddons) {
                    FoodAddon::where('food_id',$food->id)->delete();
                    foreach ($newAddons as $newAddon) {
                        FoodAddon::create([
                            'addon_id' => $newAddon->value,
                            'food_id' => $food->id,
                            'status' => Addon::find($newAddon->value) ? Addon::find($newAddon->value)->status : '',
                            'is_active' => 1
                        ]);
                    }
                }else{
                    FoodAddon::where('food_id',$food->id)->delete();
                }
            }else{
                FoodAddon::where('food_id',$food->id)->delete();
            }

            return $this->success('food created successfully.', $food);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $food = Food::withoutGlobalScope(ActiveScope::class)->find($request->id);
            $restaurant = $food->restaurant_id;
            FoodAddon::withoutGlobalScope(ActiveScope::class)->where('food_id', '=', $request->id)->delete();
            Categoriable::withoutGlobalScope(ActiveScope::class)->where('categoriable_id', '=', $request->id)->where('categoriable_type', 'LIKE', 'Food')->delete();
            $food->delete();
            return $this->success('food deleted successfully', $this->getFoodsList($restaurant));
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }

    }

    public function getFoodAddons($restaurant)
    {
        try {
            $addons = DB::table('addons')
                ->select('id as value', 'name_en as label')
                ->where('restaurant_id', 'LIKE', $restaurant)
                ->get()
                ->toArray();

            return $this->success('list of addons', $addons);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function saveFile($file)
    {
        $fileName = time().'.'.$file->getClientOriginalName();
        $file->move('resources/assets/images/food_images/', $fileName);
       // return env('PUBLIC_PATH').'resources/assets/images/food_images/'.$fileName;
        return 'food_images/'.$fileName;
    }

}
