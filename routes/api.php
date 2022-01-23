<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;

Route::get('test', function()  {
    return "No such route";
}) -> name('login');


Route::resource('/shops', ShopController::class);
Route::resource('/services', ServiceController::class);

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Routes requiring (any role) authentication
Route::group(['middleware'=>['auth:sanctum']], function (){
    // requires Admin role
    Route::group(['prefix' => 'admin','middleware'=>['is.admin']], function (){
        Route::post('/driver', [UserController::class, 'createDriver']);
        Route::post('/worker', [UserController::class, 'createWorker']);
        Route::post('/client', [UserController::class, 'createClient']);
        Route::post('/shop', [ShopController::class, 'createShop']);
    });
    // requires Manager role
    Route::group(['prefix' => 'manager','middleware'=>['is.manager']], function (){

    });
    // requires Worker role
    Route::group(['prefix' => 'worker','middleware'=>['is.worker']], function (){
        Route::get('/orders/current', [OrderController::class, 'workerGetCurentOrders']);
        Route::get('/orders/pending', [OrderController::class, 'workerGetPendingOrders']);
        Route::put('/orders/accept', [OrderController::class, 'workerAcceptOrder']);
        Route::put('/orders/reject', [OrderController::class, 'workerRejectOrder']);
        Route::put('/orders/{id}/ready', [OrderController::class, 'workerOrderReady']);

        Route::get('/shop', [ShopController::class, 'workerShop']);
        Route::get('/services', [ServiceController::class, 'workerServices']);

    });
    // requires Driver role
    Route::group(['prefix' => 'driver','middleware'=>['is.driver']], function (){
        Route::get('/orders/pending', [OrderController::class, 'driverGetPendingOrders']);
        Route::get('/orders/current', [OrderController::class, 'driverGetCurrentOrders']);
        Route::put('/orders/accept', [OrderController::class, 'driverAcceptOrder']);
        Route::put('/orders/taken', [OrderController::class, 'driverTakenOrder']);
        Route::put('/orders/delivered', [OrderController::class, 'driverDeliveredOrder']);

        Route::put('/location', [UserController::class, 'updateLocation']);
    });
    // requires Client role
    Route::group(['prefix' => 'client','middleware'=>['is.client']], function (){
        Route::get('/order', [OrderController::class, 'getCurentClientOrders']);
        Route::get('/services', [ServiceController::class, 'index']);

        Route::put('/cart', [OrderController::class, 'store']);
        Route::put('/order', [OrderController::class, 'createOrder']);
        Route::get('/cart', [OrderController::class, 'getCart']);
        Route::post('/prices', [OrderController::class, 'getPrices']);
    });

    Route::get('/shops/search/{name}', [ShopController::class, 'search']);
    Route::post('/logout', [UserController::class, 'logout']);
});
