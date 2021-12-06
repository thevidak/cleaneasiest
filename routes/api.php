<?php

use App\Http\Controllers\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;

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
        Route::get('/shop', [ShopController::class, 'workerShop']);
        Route::get('/services', [ServiceController::class, 'workerServices']);
    });
    // requires Driver role
    Route::group(['prefix' => 'driver','middleware'=>['is.driver']], function (){

    });
    // requires Client role
    Route::group(['prefix' => 'client','middleware'=>['is.client']], function (){
        Route::get('/shops', [ShopController::class, 'index']);
        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/shops/search/{name}', [ShopController::class, 'search']);
        Route::get('/services/search/{name}', [ServiceController::class, 'search']);
    });

    Route::get('/shops/search/{name}', [ShopController::class, 'search']);
    Route::post('/logout', [UserController::class, 'logout']);

});
