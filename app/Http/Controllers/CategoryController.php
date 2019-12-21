<?php

namespace App\Http\Controllers;

use FastestModels\ActiveScope;
use FastestModels\Categoriable;
use FastestModels\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function validation(array $data)
    {
        return Validator::make($data, [
            'name_en' => ['required', 'string'],
            'name_ar' => ['required', 'string']
        ]);
    }

    public function index($type)
    {
        try {
            $categories = $this->getCategoryList($type);
            return $this->success('categories retrieved successfully', $categories);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getParentCategories()
    {
        try {
            $categories = Category::pluck('name_en', 'id')->toArray();
            return $this->success('categories retrieved successfully', $categories);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            if ($request->id) {
                $category = Category::withoutGlobalScope(ActiveScope::class)->where('id', '=', $request->id)->first();

                $catEn = Category::where('id' ,'!=', $request->id)->where('name_en', 'LIKE', $request->name_en)->where('type', 'LIKE', $request->type)->count();
                $catAr = Category::where('id' ,'!=', $request->id)->where('name_ar', 'LIKE', $request->name_ar)->where('type', 'LIKE', $request->type)->count();

                if ($catEn > 0 || $catAr > 0)
                    return $this->fail('this name already exists!');

                $image = null;
                $icon = null;
                if ($request->hasFile('image'))
                    $category->update(['image' => $this->saveFile($request->image)]);


                if ($request->hasFile('icon'))
                    $category->update(['icon' => $this->saveFile($request->icon)]);

                $category->update([
                    'parent_category_id' => $request->parent_category_id,
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'sort_order' => $request->sort_order,
                    'description_en' => $request->description_en,
                    'description_ar' => $request->description_ar,
                    'is_active' => $request->is_active == "true" ? 1 : 0
                ]);

            } else {
                $validate = $this->validation($request->all());

                if ($validate->fails())
                    return $this->fail('Validation Failed', -1, $validate->errors());

                $catEn = Category::where('name_en', 'LIKE', $request->name_en)->where('type', 'LIKE', $request->type)->count();
                $catAr = Category::where('name_ar', 'LIKE', $request->name_ar)->where('type', 'LIKE', $request->type)->count();

                if ($catEn > 0 || $catAr > 0)
                    return $this->fail('this name already exists!');

                $category = new Category([
                    'parent_category_id' => 0,
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'image' => $request->hasFile('image') ? $this->saveFile($request->image) : null,
                    'icon' => $request->hasFile('icon') ? $this->saveFile($request->icon) : null,
                    'sort_order' => $request->sort_order,
                    'description_en' => $request->description_en,
                    'description_ar' => $request->description_ar,
                    'type' => $request->type,
                    'is_active' => $request->is_active == "true" ? 1 : 0
                ]);

                $category->save();
            }
            return $this->success('Category stored successfully', $category);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function getCategory($categoryId)
    {
        try {
            $category = Category::withoutGlobalScope(ActiveScope::class)->where('id', '=', $categoryId)->first();
            return $this->success('item retrieved successfully', $category);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {

            if($request->type=="Food"){
               $res= DB::table("categoriables")->where("categoriable_type","Food")->where('category_id',$request->id)->count();
                if($res>=1){
                    return $this->fail("Depend For Fod Item");
                }else{
                    $res= DB::table("categories")->where('id',$request->id)->delete();
                    return $this->success('Category removed successfully',$this->getCategoryList("Food"));
                }
            }else{
                $res= DB::table("categoriables")->where("categoriable_type","Restaurant")->where('category_id',$request->id)->count();
                if($res>=1){
                    return $this->fail("Depend For Rest Item");
                }else{
                    $res= DB::table("categories")->where('id',$request->id)->delete();
                    return $this->success('Category removed successfully',$this->getCategoryList("Restaurant"));
                }
            }
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    public function saveFile($file)
    {
        $fileName = time().'.'.$file->getClientOriginalName();
        $file->move('resources/assets/images/category_images/', $fileName);
        //return env('PUBLIC_PATH').('resources/assets/images/category_images/').$fileName;
        return 'category_images/'.$fileName;
    }

    private function getCategoryList($type) {
        // return  Category::withoutGlobalScope(ActiveScope::class)->get();
        $first = DB::table('categories')
            ->where('categories.type', 'LIKE', $type)
            ->select('id', 'name_en', 'name_ar', 'image', 'icon', 'sort_order', 'description_en', 'description_ar', 'is_active')
            ->orderBy('id','desc')->get();
/*
        return DB::table('categories')
            ->join('categoriables', 'categories.id', 'categoriables.category_id')
            ->where('categoriable_type', 'LIKE', $type)
            ->select('categories.name_en',
                'categories.name_ar',
                'categories.image',
                'categories.icon',
                'categories.sort_order',
                'categories.description_en',
                'categories.description_ar',
                'categories.is_active')
            ->distinct()
            ->union($first)
            ->get();*/

        return $first;
    }

    public function getCategoryListFor($type)
    {
        try {
            $categories = DB::table('categories')
                ->select('id as value', 'name_en as label')
                ->where('type', 'LIKE', $type)
                ->get()
                ->toArray();

            return $this->success('list of categories', $categories);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }

}
