<?php

namespace App\Http\Controllers;

use FastestModels\ActiveScope;
use FastestModels\Addon;
use FastestModels\AddonItem;
use FastestModels\Categoriable;
use FastestModels\Food;
use FastestModels\FoodAddon;
use FastestModels\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AddonController extends Controller
{
    public function validation(array $data)
    {
        return Validator::make($data, [
            'restaurant_id' => ['required'],
            'name_en' => ['required'],
            'name_ar' => ['required'],
            'status' => ['required'],
            'is_active' => ['required'],
        ]);
    }

    public function index($restaurant=null)
    {
        return $this->success('Addons retrieved successfully!', $this->getAddonsList($restaurant));
    }

    public function getAddonsList($restaurant=null)
    {
        $res = Addon::withoutGlobalScope(ActiveScope::class);

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

    public function getAddon($addonId)
    {
        try {
            $addon = null;
            if ($addonId > 0) {
                $addon = Addon::withoutGlobalScope(ActiveScope::class)->find($addonId);
                $addon->items = AddonItem::withoutGlobalScope(ActiveScope::class)->where('addon_id', '=', $addonId)->get();

                return $this->success('addon retrieved successfully.', $addon);
            }
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            if ($request->id) {
                $addon = Addon::withoutGlobalScope(ActiveScope::class)->find($request->id);
                $addon->update([
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'status' => $request->status,
                    'is_active' => $request->is_active == "true" ? 1 : 0,
                ]);

                if ($request->hasFile('icon'))
                    $addon->update(['icon' => $this->saveFile($request->icon)]);

            } else {
                $validate = $this->validation($request->all());
                if ($validate->fails())
                    return $this->fail('validation failed', -1, $validate->errors());

                $addon = new Addon([
                    'restaurant_id' => $request->restaurant_id,
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'status' => $request->status,
                    'is_active' => 1
                ]);

                $addon->save();
            }

            return $this->success('addon created successfully.', $addon);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $restaurant = Addon::withoutGlobalScope(ActiveScope::class)->find($request->id)->restaurant_id;
            $addonItems = AddonItem::withoutGlobalScope(ActiveScope::class)->where('addon_id', '=', $request->id)->delete();
            $foodAddons = FoodAddon::withoutGlobalScope(ActiveScope::class)->where('addon_id', '=', $request->id)->delete();
            $addon = Addon::withoutGlobalScope(ActiveScope::class)->find($request->id)->delete();
            return $this->success('Addon deleted successfully', $this->getAddonsList($restaurant));
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function saveFile($file)
    {
        $fileName = time().'.'.$file->getClientOriginalName();
        $file->move('resources/assets/images/addon_images/', $fileName);
        return env('PUBLIC_PATH').'resources/assets/images/addon_images/'.$fileName;
    }







    public function storeAddonItem(Request $request)
    {
        try {
            $addonItem = new AddonItem([
                'addon_id' => $request->addon_id,
                'name_en' => $request->name_en,
                'name_ar' => $request->name_ar,
                'price' => $request->price,
                'is_active' => 1
            ]);

            $addonItem->save();

            return $this->success('addon item created successfully.', AddonItem::withoutGlobalScope(ActiveScope::class)->where('addon_id', '=', $addonItem->addon_id)->get());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function destroyAddonItem(Request $request)
    {
        try {
            $addonItem = AddonItem::withoutGlobalScope(ActiveScope::class)->find($request->id);
            $addonId = $addonItem->addon_id;
            $addonItem->delete();
            return $this->success('Addon Item deleted successfully', AddonItem::withoutGlobalScope(ActiveScope::class)->where('addon_id', '=', $addonId)->get());
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function storeAddonWithItems(Request $request) {
        try {
            $validate = $this->validation($request->all());
            if ($validate->fails())
                return $this->fail('validation failed', -1, $validate->errors());

            if (!$request->items)
                return $this->fail('no items to save');

            $items = json_decode($request->items);

            foreach ($items as $item)
                if (! ($item->price >= 0))
                    return $this->fail("price must be a number");

            $addon = new Addon([
                'restaurant_id' => $request->restaurant_id,
                'name_en' => $request->name_en,
                'name_ar' => $request->name_ar,
                'status' => $request->status,
                'is_active' => 1
            ]);
            $addon->save();

            foreach ($items as $item) {
                $addonItem = new AddonItem([
                    'addon_id' => $addon->id,
                    'name_en' => $item->name_en,
                    'name_ar' => $item->name_ar,
                    'price' => $item->price,
                    'is_active' => 1
                ]);

                $addonItem->save();
            }

            return $this->success('addon saved successfully');
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }

    }

}
