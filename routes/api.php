<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ClientOrderController;
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
        //Route::post('/orders/accepted-order-load-data', [OrderController::class, 'workerLoadAcceptedOrderData']);
        //Route::post('/orders/accepted-order-delivery-data', [OrderController::class, 'workerLoadAcceptedOrderData']);
        # new
        Route::post('/orders/order-data', [OrderController::class, 'workerOrderData']);

        # old
        //Route::post('/orders/accepted-order-set-loaded', [OrderController::class, 'workerSetLoadedAcceptedOrder']);
        //Route::post('/orders/accepted-order-set-delivered', [OrderController::class, 'workerSetDeliveredAcceptedOrder']);
        //Route::post('/orders/accepted-order-set-ready', [OrderController::class, 'workerSetReadydAcceptedOrder']);
        # new 
        Route::post('/orders/change-status', [OrderController::class, 'workerChangeOrderStatus']);

        

    });
    // requires Driver role
    Route::group(['prefix' => 'driver','middleware'=>['is.driver']], function (){

        Route::get('/orders/total-number', [OrderController::class, 'driverGetNumberOfTotalOrders']);
        Route::get('/orders/new-list', [OrderController::class, 'driverGetListOfNewOrders']);
        Route::get('/orders/accepted-list', [OrderController::class, 'driverGetListOfAcceptdeOrders']);
        Route::post('/orders/accepted-order-status', [OrderController::class, 'driverGetOrderStatus']);
        Route::post('/orders/new-order-data', [OrderController::class, 'driverNewOrderData']);
        Route::post('/orders/accept-new-order', [OrderController::class, 'driverAcceptOrder']);

        Route::get('/reject-reasons', [OrderController::class, 'driverGetRejectReasons']);
        Route::get('/cant-load-from-client-reasons', [OrderController::class, 'driverGetUnableToLoadFromClientReasons']);
        
        Route::post('/orders/order-data', [OrderController::class, 'driverOrderData']);

        Route::post('/orders/change-status', [OrderController::class, 'driverChangeOrderStatus']);
        
    });
    // requires Client role
    Route::group(['prefix' => 'client','middleware'=>['is.client']], function (){

        Route::get('/service-types', [ServiceController::class, 'clientGetAllServiceTypes']);
        Route::post('/service-prices', [ServiceController::class, 'clientGetServicePrices']);
        
        Route::get('/service-weights', [WeightClassController::class, 'index']);
        
        Route::get('/service-clothes', [ClothesTypeController::class, 'clientGetAllClothesTypes']);

        Route::get('/client-info', [UserController::class, 'info']);
        Route::post('/update-info', [UserController::class, 'clientUpdateInfo']);
        Route::get('/address/list', [UserController::class, 'clientGetAddressList']);
        Route::get('/address/active', [UserController::class, 'clientGetActiveAddress']);
        Route::post('/address/add', [UserController::class, 'clientAddAddress']);
        Route::post('/address/delete', [UserController::class, 'clientDeleteAddress']);
        Route::post('/address/set-active', [UserController::class, 'clientSetActiveAddress']);
        Route::post('/address/edit', [UserController::class, 'clientEditAddress']);
        Route::get('/card/list', [UserController::class, 'clientGetCardList']);
        Route::get('/card/active', [UserController::class, 'clientGetActiveCard']);
        Route::post('/card/add', [UserController::class, 'clientAddCard']);
        Route::post('/card/delete', [UserController::class, 'clientDeleteCard']);
        Route::post('/card/set-active', [UserController::class, 'clientSetActiveCard']);
        Route::get('/support-text', [UserController::class, 'clientGetSupportText']);
        
        Route::post('/add-new-service', [ClientOrderController::class, 'clientAddNewService']);
        Route::post('/create-new-order', [ClientOrderController::class, 'clientCreateOrder']);
        Route::get('/orders/total-number', [ClientOrderController::class, 'clientGetTotalNuberOfOrders']);
        Route::get('/orders/list', [ClientOrderController::class, 'clientGetOrderList']);
        Route::post('/orders/order-status', [ClientOrderController::class, 'clientGetOrderStatus']);
        Route::post('/orders/order-data', [ClientOrderController::class, 'clientOrderData']);
        
        // missing?
        //Route::post('/orders/order-map', [ClientOrderController::class, 'clientGetOrderMap']);

        Route::post('/orders/payment-price', [ClientOrderController::class, 'clientGetPaymentInfo']);
        Route::get('/orders/order-default-data', [ClientOrderController::class, 'clientGetExtraData']);
        Route::get('/orders/current-service-prices', [ClientOrderController::class, 'clientGetCurrentPaymentInfo']);
        Route::post('/orders/order-services', [ClientOrderController::class, 'clientGetOrderServices']);
        Route::post('/orders/order-rating', [ClientOrderController::class, 'clientSetOrderRating']);
        Route::get('/orders/current-total-price', [ClientOrderController::class, 'clientGetTotalCartPrice']);
        Route::post('/remove-service', [ClientOrderController::class, 'clientDeleteService']);
        Route::get('/orders/empty-cart', [ClientOrderController::class, 'clientEmptyCart']);
        Route::get('/privacy', [ClientOrderController::class, 'clientGetPrivacy']);
        Route::get('/faq', [ClientOrderController::class, 'clientGetFaqs']);
        Route::get('/orders/history', [ClientOrderController::class, 'clientGetOrderHistory']);
    });
});
