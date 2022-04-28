<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WeightClassController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClothesTypeController;

use App\Models\User;
use App\Mail\NewUserNotification;

Route::get('test', function()  {
    \Mail::to('thevidak@yahoo.com')->send(new \App\Mail\NewUserNotification);
});
Route::post('test', [UserController::class, 'test']);
Route::post('upload-test', [UserController::class, 'testUpload']);

//Route::resource('/shops', ShopController::class);
//Route::resource('/services', ServiceController::class);
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/social-login', [UserController::class, 'socialLogin']);
Route::post('/check-email', [UserController::class, 'checkEmail']);
Route::post('/reset-password', [UserController::class, 'resetPassword']);

// Routes requiring (any role) authentication
Route::group(['middleware'=>['auth:sanctum']], function (){
    
    Route::get('/shops/search/{name}', [ShopController::class, 'search']);
    Route::post('/logout', [UserController::class, 'logout']);
    
    // requires Admin role
    Route::group(['prefix' => 'admin','middleware'=>['is.admin']], function (){
        Route::post('/driver', [UserController::class, 'createDriver']);
        Route::post('/worker', [UserController::class, 'createWorker']);
        Route::post('/client', [UserController::class, 'createClient']);
        Route::post('/shop', [ShopController::class, 'createShop']);
    });

    // requires Worker role
    Route::group(['prefix' => 'worker','middleware'=>['is.worker']], function (){
        // old routes
        /*
        Route::get('/orders/current', [OrderController::class, 'workerGetCurentOrders']);
        Route::get('/orders/pending', [OrderController::class, 'workerGetPendingOrders']);
        Route::put('/orders/accept', [OrderController::class, 'workerAcceptOrder']);
        Route::put('/orders/reject', [OrderController::class, 'workerRejectOrder']);
        Route::put('/orders/{id}/ready', [OrderController::class, 'workerOrderReady']);
        Route::get('/shop', [ShopController::class, 'workerShop']);
        Route::get('/services', [ServiceController::class, 'workerServices']);
        */

        Route::get('/orders/total-number', [OrderController::class, 'workerGetNumberOfTotalOrders']);
        Route::get('/orders/new-list', [OrderController::class, 'workerGetListOfNewOrders']);
        Route::get('/orders/accepted-list', [OrderController::class, 'workerGetListOfAcceptdeOrders']);
        Route::post('/orders/new-order-data', [OrderController::class, 'workerGetOrderData']);
        Route::post('/orders/change-new-order-data', [OrderController::class, 'workerChangeOrderData']);
        Route::post('/orders/accepted-order-map', [OrderController::class, 'workerLoadAcceptedOrderMap']);
        Route::post('/orders/accepted-order-info', [OrderController::class, 'workerGetOrderData']);
        Route::post('/orders/accepted-order-status', [OrderController::class, 'workerGetOrderStatus']);
        Route::get('/reject-reasons', [OrderController::class, 'workerRejectReasons']);

        # old
        Route::post('/orders/accepted-order-load-data', [OrderController::class, 'workerLoadAcceptedOrderData']);
        Route::post('/orders/accepted-order-delivery-data', [OrderController::class, 'workerLoadAcceptedOrderData']);
        # new
        Route::post('/orders/order-data', [OrderController::class, 'workerOrderData']);

        # old
        Route::post('/orders/accepted-order-set-loaded', [OrderController::class, 'workerSetLoadedAcceptedOrder']);
        Route::post('/orders/accepted-order-set-delivered', [OrderController::class, 'workerSetDeliveredAcceptedOrder']);
        Route::post('/orders/accepted-order-set-ready', [OrderController::class, 'workerSetReadydAcceptedOrder']);
        # new 
        Route::post('/orders/change-status', [OrderController::class, 'workerChangeOrderStatus']);

        

    });
    // requires Driver role
    Route::group(['prefix' => 'driver','middleware'=>['is.driver']], function (){
        // old routes
        /*
        Route::get('/orders/pending', [OrderController::class, 'driverGetPendingOrders']);
        Route::get('/orders/current', [OrderController::class, 'driverGetCurrentOrders']);
        Route::put('/orders/accept', [OrderController::class, 'driverAcceptOrder']);
        Route::put('/orders/taken', [OrderController::class, 'driverTakenOrder']);
        Route::put('/orders/delivered', [OrderController::class, 'driverDeliveredOrder']);
        Route::put('/location', [UserController::class, 'updateLocation']);
        */
        Route::get('/orders/total-number', [OrderController::class, 'driverGetNumberOfTotalOrders']);
        Route::get('/orders/new-list', [OrderController::class, 'driverGetListOfNewOrders']);
        Route::get('/orders/accepted-list', [OrderController::class, 'driverGetListOfAcceptdeOrders']);
        Route::post('/orders/accepted-order-status', [OrderController::class, 'driverGetOrderStatus']);
        Route::post('/orders/new-order-data', [OrderController::class, 'driverNewOrderData']);
        Route::post('/orders/accept-new-order', [OrderController::class, 'driverAcceptOrder']);

        Route::get('/reject-reasons', [OrderController::class, 'driverGetRejectReasons']);
        Route::get('/cant-load-from-client-reasons', [OrderController::class, 'driverGetUnableToLoadFromClientReasons']);
        
        # old
        /*
        Route::post('/orders/accepted-order-load-data', [OrderController::class, 'driverGetOrderData']);
        Route::post('/orders/accepted-order-delivery-data', [OrderController::class, 'driverGetOrderData']);
        Route::post('/orders/accepted-order-load-delivery-to-worker-data', [OrderController::class, 'driverGetOrderData']);
        Route::post('/orders/accepted-order-load-client-map', [OrderController::class, 'driverGetOrderData']);
        Route::post('/orders/accepted-order-delivery-client-map', [OrderController::class, 'driverGetOrderData']);
        */
        # new
        Route::post('/orders/order-data', [OrderController::class, 'driverOrderData']);

        # old
        /*
        Route::post('/orders/accepted-order-set-loaded', [OrderController::class, 'driverSetLoadedAcceptedOrder']);
        Route::post('/orders/accepted-order-set-delivered-to-worker', [OrderController::class, 'driverSetDeliveredToWorkerAcceptedOrder']);
        Route::post('/orders/accepted-order-set-loaded-from-worker', [OrderController::class, 'driverSetLoadedFromWorkerAcceptedOrder']);
        Route::post('/orders/accepted-order-set-delivered', [OrderController::class, 'driverSetDeliveredToClientAcceptedOrder']);
        */
        # new
        Route::post('/orders/change-status', [OrderController::class, 'driverChangeOrderStatus']);
        
    });
    // requires Client role
    Route::group(['prefix' => 'client','middleware'=>['is.client']], function (){
        // old routes
        /*
        Route::get('/order', [OrderController::class, 'getCurentClientOrders']);
        Route::get('/services', [ServiceController::class, 'index']);
        Route::put('/cart', [OrderController::class, 'store']);
        Route::put('/order', [OrderController::class, 'createOrder']);
        Route::get('/cart', [OrderController::class, 'getCart']);
        Route::post('/prices', [OrderController::class, 'getPrices']);
        */
        Route::get('/service-types', [ServiceController::class, 'clientGetAllServiceTypes']);
        Route::get('/service-weights', [WeightClassController::class, 'index']);
        Route::get('/service-clothes', [ClothesTypeController::class, 'clientGetAllClothesTypes']);
        
        //del
        Route::post('/service-weights-prices', [OrderController::class, 'getPrices']);
        
        Route::post('/service-prices', [OrderController::class, 'clientGetServicePrices']);
        Route::post('/add-new-service', [OrderController::class, 'clientAddNewService']);
        Route::post('/create-new-order', [OrderController::class, 'createOrder']);



        Route::get('/orders/total-number', [OrderController::class, 'clientGetTotalNuberOfOrders']);
        Route::get('/orders/list', [OrderController::class, 'clientGetOrderList']);
        Route::post('/orders/order-status', [OrderController::class, 'clientGetOrderStatus']);

        # old
        Route::post('/orders/order-load-data', [OrderController::class, 'clientGetOrderData']);
        Route::post('/orders/order-delivery-data', [OrderController::class, 'clientGetOrderData']);
        # new
        Route::post('/orders/order-data', [OrderController::class, 'clientOrderData']);

        Route::post('/orders/order-map', [OrderController::class, 'clientGetOrderMap']);
        Route::post('/orders/payment-price', [OrderController::class, 'clientGetPaymentInfo']);
        Route::get('/orders/order-default-data', [OrderController::class, 'clientGetExtraData']);
        Route::get('/orders/current-service-prices', [OrderController::class, 'clientGetCurrentPaymentInfo']);
        Route::post('/orders/order-services', [OrderController::class, 'clientGetOrderServices']);
        Route::post('/orders/order-rating', [OrderController::class, 'clientSetOrderRating']);

        Route::get('/orders/current-total-price', [OrderController::class, 'clientGetTotalCartPrice']);

        Route::get('/client-info', [UserController::class, 'info']);
        Route::post('/update-info', [UserController::class, 'clientUpdateInfo']);
        Route::post('/remove-service', [OrderController::class, 'clientDeleteService']);
        Route::get('/orders/empty-cart', [OrderController::class, 'clientEmptyCart']);
    });
});
