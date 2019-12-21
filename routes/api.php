<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('notLoggedIn', function () { return response()->json(['status' => -1, 'message' => 'You Are Not Logged In!']); })->name('notLoggedIn');
Route::post('/isValidToken','Auth\LoginController@isValidToken')->name('is_valid_token');
Route::get('/getLoginPageCredentials', ['uses' => 'SettingController@getLoginPageCredentials', 'as' => 'getLoginPageCredentials']);
Route::get('/test','UserController@test');
route::group(['middleware' => 'auth:api'], function () {
    Route::get('/user', function (Request $request) { return $request->user()->user; });
    Route::post('/sendMail', function(Request $request) {
        \Illuminate\Support\Facades\Mail::to($request->user()->user)->send(new \App\Mail\OrderCreated(58));
    });

    Route::post('/updateFcmToken','UserController@updateFcmToken')->name('updateFcmToke');

    Route::group(['prefix' => 'categories'], function () {
        Route::get('/{type}', ['uses' => 'CategoryController@index', 'as' => 'categories.index']);
        Route::get('/getParentCategories', ['uses' => 'CategoryController@getParentCategories', 'as' => 'categories.getParentCategories']);
        Route::post('/store', ['uses' => 'CategoryController@store', 'as' => 'categories.store']);
        Route::get('/getCategory/{category}', ['uses' => 'CategoryController@getCategory', 'as' => 'categories.getCategory']);
        Route::post('/destroy', ['uses' => 'CategoryController@destroy', 'as' => 'categories.destroy']);
        Route::get('/getCategoryListFor/{type}', ['uses' => 'CategoryController@getCategoryListFor', 'as' => 'categories.getCategoryListFor']);
    });

    Route::group(['prefix' => 'restaurants'], function () {
        Route::post('/', ['uses' => 'RestaurantController@index', 'as' => 'restaurants.index']);
        Route::get('/branches/{id}', ['uses' => 'RestaurantController@getBranchesList', 'as' => 'restaurants.getBranchesList']);
        Route::get('/getBranch/{id}', ['uses' => 'RestaurantController@getBranch', 'as' => 'restaurants.getBranch']);
        Route::get('/getTimes/{id}', ['uses' => 'RestaurantController@getTimes', 'as' => 'restaurants.getTimes']);
        Route::post('/branch/add', ['uses' => 'RestaurantController@addBranch', 'as' => 'restaurants.addBranch']);
        Route::post('/branch/updateBran', ['uses' => 'RestaurantController@updateBran', 'as' => 'restaurants.updateBran']);
        Route::post('/branch/delete', ['uses' => 'RestaurantController@deleteBran','as'=>'restaurants.deleteBran']);
        Route::post('/store', ['uses' => 'RestaurantController@store', 'as' => 'restaurants.store']);
        Route::get('/getRestaurant/{restaurant}', ['uses' => 'RestaurantController@getRestaurant', 'as' => 'restaurants.getRestaurant']);
        Route::post('/update', ['uses' => 'RestaurantController@update', 'as' => 'restaurants.store']);
        Route::post('/updateRestaurantTime', ['uses' => 'RestaurantController@updateRestaurantTime', 'as' => 'restaurants.store']);
        Route::post('/destroy', ['uses' => 'RestaurantController@destroy', 'as' => 'restaurants.destroy']);
    });

    Route::group(['prefix' => 'clients'], function () {
        Route::get('/', ['uses' => 'ClientController@index', 'as' => 'clients.index']);
        Route::get('/getClient/{client}', ['uses' => 'ClientController@getClient', 'as' => 'clients.getClient']);
        Route::get('/getClientAddressList/{client}', ['uses' => 'ClientController@getClientAddressList', 'as' => 'clients.getClientAddressList']);
        Route::get('/searchClient', ['uses' => 'ClientController@searchClient', 'as' => 'clients.searchClient']);
        Route::get('/getClientAddress/{address}', ['uses' => 'ClientController@getClientAddress', 'as' => 'clients.getClientAddress']);
        Route::post('/filterAndPaginate', ['uses' => 'ClientController@filterAndPaginate','as' =>'clients.filterAndPaginate']);
        Route::post('/update', ['uses' => 'ClientController@update', 'as' => 'clients.store']);
        Route::post('/destroy', ['uses' => 'ClientController@destroy', 'as' => 'clients.destroy']);
        Route::post('/addresses/update', ['uses' => 'ClientController@updateAddress', 'as' => 'clients.address.store']);
        Route::post('/addresses/destroy', ['uses' => 'ClientController@destroyAddress', 'as' => 'clients.address.destroy']);
    });
    Route::group(['prefix' => 'adminUser'], function () {
        Route::get('/', ['uses' => 'AdminUserController@index', 'as' => 'adminUser.index']);
        Route::get('/getAdmin/{admin}', ['uses' => 'AdminUserController@getAdmin', 'as' => 'adminUser.getAdmin']);
        Route::get('/roles/{admin}', ['uses' => 'AdminUserController@getRoles', 'as' => 'adminUser.getRoles']);
        Route::post('/filterAndPaginate', ['uses' => 'AdminUserController@filterAndPaginate','as' =>'adminUser.filterAndPaginate']);
        Route::post('/update', ['uses' => 'AdminUserController@update', 'as' => 'adminUser.store']);
        Route::post('/updateRoles', ['uses' => 'AdminUserController@updateRoles', 'as' => 'adminUser.updateRoles']);
        Route::post('/destroy', ['uses' => 'AdminUserController@destroy', 'as' => 'adminUser.destroy']);

    });

    Route::group(['prefix' => 'restaurantUser'], function () {
        Route::get('/', ['uses' => 'RestaurantUserController@index', 'as' => 'restaurantUser.index']);
        Route::get('/getRestaurantUser/{admin}', ['uses' => 'RestaurantUserController@getAdmin', 'as' => 'restaurantUser.getAdmin']);
        Route::get('/roles/{admin}', ['uses' => 'RestaurantUserController@getRoles', 'as' => 'restaurantUser.getRoles']);
        Route::post('/filterAndPaginate', ['uses' => 'RestaurantUserController@filterAndPaginate','as' =>'restaurantUser.filterAndPaginate']);
        Route::post('/update', ['uses' => 'RestaurantUserController@update', 'as' => 'restaurantUser.store']);
        Route::post('/updateRoles', ['uses' => 'RestaurantUserController@updateRoles', 'as' => 'restaurantUser.updateRoles']);
        Route::post('/destroy', ['uses' => 'RestaurantUserController@destroy', 'as' => 'restaurantUser.destroy']);

    });


    Route::group(['prefix' => 'foods'], function () {
        Route::get('/{restaurant?}', ['uses' => 'FoodController@index', 'as' => 'foods.index']);
        Route::get('/getFood/{food}', ['uses' => 'FoodController@getFood', 'as' => 'foods.getFood']);
        Route::get('/getFoodAddons/{restaurant}', ['uses' => 'FoodController@getFoodAddons', 'as' => 'foods.getFoodAddons']);
        Route::post('/store', ['uses' => 'FoodController@store', 'as' => 'foods.store']);
        Route::post('/destroy', ['uses' => 'FoodController@destroy', 'as' => 'foods.destroy']);
    });

    Route::group(['prefix' => 'addons'], function () {
        Route::get('/{restaurant?}', ['uses' => 'AddonController@index', 'as' => 'addons.index']);
        Route::get('/getAddon/{addon}', ['uses' => 'AddonController@getAddon', 'as' => 'addons.getAddon']);
        Route::post('/store', ['uses' => 'AddonController@store', 'as' => 'addons.store']);
        Route::post('/storeAddonWithItems', ['uses' => 'AddonController@storeAddonWithItems', 'as' => 'addons.storeAddonWithItems']);
        Route::post('/destroy', ['uses' => 'AddonController@destroy', 'as' => 'addons.destroy']);
        Route::post('/storeAddonItem', ['uses' => 'AddonController@storeAddonItem', 'as' => 'addons.storeAddonItem']);
        Route::post('/destroyAddonItem', ['uses' => 'AddonController@destroyAddonItem', 'as' => 'addons.destroyAddonItem']);
    });

    Route::group(['prefix' => 'cities'], function () {
        Route::get('/',['uses'=>'AreaController@citiesIndex', 'as' => 'cities.index']);
        Route::get('/getCities',['uses' => 'AreaController@getCities', 'as' => 'cities.getCities']);
        Route::post('/store',['uses' => 'AreaController@storeCity', 'as' => 'cities.store']);
        Route::post('/destroy',['uses' => 'AreaController@destroyCity', 'as' => 'cities.destroy']);
    });

    Route::group(['prefix' => 'areas'], function () {
        Route::get('/', ['uses' => 'AreaController@index', 'as' => 'areas.index']);
        Route::get('/getAreas', ['uses' => 'AreaController@getAreas', 'as' => 'areas.getAreas']);
        Route::post('/store', ['uses' => 'AreaController@store', 'as' => 'areas.store']);
        Route::post('/destroy', ['uses' => 'AreaController@destroy', 'as' => 'areas.destroy']);
    });

    Route::group(['prefix' => 'roles'], function () {
        Route::get('/', ['uses' => 'RolesController@index', 'as' => 'roles.index']);
        Route::get('/getAreas', ['uses' => 'RolesController@getAreas', 'as' => 'roles.getAreas']);
        Route::post('/store', ['uses' => 'RolesController@store', 'as' => 'roles.store']);
        Route::post('/destroy', ['uses' => 'RolesController@destroy', 'as' => 'roles.destroy']);
        Route::get('/permissions/{id}', ['uses' => 'RolesController@getPermissionList','as' =>'roles.getPermissionList']);
        Route::post('/syncPermissions', ['uses' => 'RolesController@syncPermissions','as' =>'roles.syncPermissions']);
    });

    Route::group(['prefix' => 'orders'], function () {
        Route::post('/filterOrders', ['uses' => 'OrderController@filterOrders','as' =>'orders.filterOrders']);
        Route::post('/setDriver', ['uses' => 'OrderController@setDriver','as' =>'orders.setDriver']);
        Route::post('/getNotifyDriverList', ['uses' => 'OrderController@getNotifyDriverList','as' =>'orders.getNotifyDriverList']);
        Route::post('/getOrderHistory', ['uses' => 'OrderController@getOrderHistory','as' =>'orders.getOrderHistory']);
        Route::get('/{id}', ['uses' => 'OrderController@getOrder','as' =>'orders.getOrder']);
        Route::get('/getOrderDetails/{id}', ['uses' => 'OrderController@getOrderDetails','as' =>'orders.getOrderDetails']);

    });

    Route::group(['prefix' => 'discount'], function () {
        Route::post('/getListDiscount', ['uses' => 'DiscountController@getListDiscount', 'as' => 'discount.getListDiscount']);
        Route::post('/store', ['uses' => 'DiscountController@createOrUpdate', 'as' => 'discount.createOrUpdate']);
        Route::get('/getDiscount/{id}', ['uses' =>'DiscountController@getDiscount','as' =>'discount.getDiscount']);
        Route::get('/getListDiscountForSelect', ['uses' =>'DiscountController@getListDiscountForSelect','as' =>'discount.getListDiscountForSelect']);
        Route::post('/getCodeList', ['uses' =>'DiscountController@filterCodeList','as' =>'discount.filterCodeList']);
        Route::post('/createCodeDiscount', ['uses' =>'DiscountController@createCodeDiscount','as' =>'discount.createCodeDiscount']);
        Route::post('/destroy', ['uses' =>'DiscountController@delete','as' =>'discount.delete']);
        Route::post('/destroyDiscountCode', ['uses' =>'DiscountController@destroyDiscountCode','as' =>'discount.delete']);

    });

        Route::group(['prefix' =>'notifications'], function () {
            Route::post('/send', ['uses' => 'NotificationsController@sendNotifications', 'as' => 'notifications.sendNotifications']);


        });

    Route::group(['prefix' => 'drivers'], function () {
        Route::get('/', ['uses' => 'DriverController@index', 'as' => 'drivers.index']);
        Route::post('/getJobHistory', ['uses' => 'DriverController@getJobHistory', 'as' => 'drivers.getJobHistory']);
        Route::get('/getDriver/{driver}', ['uses' => 'DriverController@getDriver', 'as' => 'drivers.getDriver']);
        Route::post('/store', ['uses' => 'DriverController@store', 'as' => 'drivers.store']);
        Route::post('/destroy', ['uses' => 'DriverController@destroy', 'as' => 'drivers.destroy']);
    });

    Route::group(['prefix' => 'users'], function () {
        Route::post('/updatePassword', ['uses' => 'UserController@updatePassword', 'as' => 'users.updatePassword']);
    });

    Route::group(['prefix' => 'settings'], function () {
        Route::post('/', ['uses' => 'SettingController@index', 'as' => 'settings.index']);
        Route::post('/store', ['uses' => 'SettingController@store', 'as' => 'settings.store']);
    });
});
