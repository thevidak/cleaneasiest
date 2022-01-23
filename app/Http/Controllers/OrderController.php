<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderdStatus;
use App\Models\Price;
use App\Models\RejectedOrders;
use App\Models\Service;
use App\Models\Shop;
use App\Models\User;
use App\Models\WeightClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Node\Query\OrExpr;
use Symfony\Component\Console\Input\Input;

class OrderController extends Controller
{
    public function index() {
        return Order::all();
    }

    public function create() {}

    public function store(Request $request) {

        $request->validate([
            'service' => 'required',
        ]);

        $current_order = Order::where('client_id', Auth::id())->where('status', 0)->first();

        if (isset($current_order)) {
            $tmp = $current_order->services;
            array_push($tmp, $request->service);
            $current_order->services = $tmp;
            $current_order->save();
            $current_order->calculatePrice();
            return $current_order;
        }
        else {
            $order = Order::create([
                'services' => [$request->service],
                'client_id' => Auth::id(),
                'status' => OrderdStatus::ORDER_IN_CREATION,
                'price' => 0
            ]);
            $order->calculatePrice();
            return $order;
        }
    }

    public function createOrder(Request $request) {

        $request->validate([
            'paymment_info' => 'required',
            'takeout_date' => 'required',
            'delivery_date' => 'required'
        ]);

        $current_order = Order::where('client_id', Auth::id())->where('status', 0)->first();

        if (isset($current_order)) {
            $current_order->paymment_info = $request->paymment_info;
            $current_order->takeout_date = $request->takeout_date;
            $current_order->delivery_date = $request->delivery_date;
            if (isset($request->order_info)) {
                $order_info = $request->order_info;
                if (isset($order_info["country"]) and isset($order_info["address"]) and isset($order_info["city"]) and isset($order_info["municipality"]) and isset($order_info["zip"])) {
                    $current_order->order_info = $order_info;
                }
                else {
                    return response()->json([
                        "status" => "error",
                        "message" => "Some adress info is missing"
                    ]);
                }
            }
            $current_order->status = 1;
            $current_order->save();
            return $current_order;
        }
        else {
            return response()->json([
                "status" => "error",
                "message" => "Cart is empty"
            ]);
        }
    }

    public function show($id) {

    }

    public function edit($id) {

    }

    public function update(Request $request, $id) {

    }

    public function destroy($id) {

    }



    public function getCurentClientOrders() {
        return Order::where('client_id', '=', Auth::id())->get();
    }



    // WORKERS

    public function workerGetCurentOrders() {
        return Order::where('worker_id', Auth::id())->get();
        /*
        $result = [];
        $orders = Order::where('worker_id', Auth::id())->get();
        foreach ($orders as $order) {
            $parsed_order = (array) $order;
            if (isset($order->client_id)){
                $parsed_order['client_location'] = User::where('id', $order->client_id)->first()->location;
            }
            if (isset($order->driver_id)){
                $parsed_order['driver_location'] = User::where('id', $order->driver_id)->first()->location;
            }
            if (isset($order->worker_id)){
                $parsed_order['worker_location'] = User::where('id', $order->worker_id)->first()->location;
            }
            array_push($result, $parsed_order);
        }
        return $result;
        */
    }

    public function workerGetPendingOrders() {
        $rejected_orders = RejectedOrders::where('worker_id',Auth::id())->get();
        $pending_orders = Order::where('status', '=', OrderdStatus::ORDER_CREATED)->get();

        foreach ($rejected_orders as $rejected_order) {
            $r_order_id = $rejected_order->order_id;
            foreach ($pending_orders as $key=>$pending_order) {
                $p_order_id = $pending_order->id;
                if ($p_order_id == $r_order_id) {
                    unset($pending_orders[$key]);
                }
            }
        }

        return $pending_orders;
    }

    public function workerAcceptOrder (Request $request) {
        $request->validate([
            'order_id' => 'required'
        ]);
        $order = Order::where('id',$request->order_id)->first();
        if (isset($order) && $order->status == OrderdStatus::ORDER_CREATED) {
            $order->status = OrderdStatus::WORKER_ACCEPTED;
            $order->worker_id = Auth::id();
            $order->save();
            return response()->json([
                "status" => "success",
                "message" => "Order Accepted"
            ]);
        }
        else {
            return response()->json([
                "status" => "error",
                "message" => "Order Not Accepted"
            ]);
        }
    }

    public function workerRejectOrder (Request $request) {
        $request->validate([
            'order_id' => 'required'
        ]);

        $order = Order::where('id',$request->order_id)->first();
        if (isset($order) && $order->status == OrderdStatus::ORDER_CREATED) {
            $rejected_order = RejectedOrders::where('order_id',$order->id)->where('worker_id', Auth::id())->first();
            if (isset($rejected_order)) {
                return response()->json([
                    "status" => "error",
                    "message" => "Order Already Rejected"
                ]);
            }
            return RejectedOrders::create([
                'order_id' => $request->order_id,
                'worker_id' => Auth::id()
            ]);
        }
        else {
            return response()->json([
                "status" => "error",
                "message" => "Order Not Rejected"
            ]);
        }
    }

    public function workerOrderReady (Request $request, $id) {
        Order::where('id',$id)->update(['status' => OrderdStatus::WORKER_FINISHED]);
        return response()->json([
            "status" => "Success",
            "message" => "Order Ready"
        ]);
    }

    // DRIVER
    public function driverGetPendingOrders() {
        return Order::whereIn('status',[OrderdStatus::WORKER_ACCEPTED, OrderdStatus::WORKER_FINISHED])->get();
    }

    public function driverGetCurrentOrders() {
        return Order::where('driver_id',Auth::id())->get();
    }

    public function driverAcceptOrder(Request $request) {
        $request->validate([
            'order_id' => 'required'
        ]);
        $order = Order::where('id',$request->order_id)->first();
        if ($order->status == OrderdStatus::WORKER_ACCEPTED) {
            $order->status = OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT;
        }
        else if ($order->status == OrderdStatus::WORKER_FINISHED) {
            $order->status = OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER;
        }
        $order->driver_id = Auth::id();
        $order->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Driver accepted order'
        ]);
    }

    public function driverTakenOrder(Request $request) {
        $request->validate([
            'order_id' => 'required'
        ]);
        $order = Order::where('id',$request->order_id)->first();
        if ($order->driver_id != Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Driver cannot change status of this order. It is connected to another driver'
            ]);
        }

        if ($order->status == OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT) {
            $order->status = OrderdStatus::DRIVER_DELIVERY_TO_WORKER;
        }
        else if ($order->status == OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER) {
            $order->status = OrderdStatus::DRIVER_DELIVERY_TO_CLIENT;
        }
        $order->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Driver took order'
        ]);
    }

    public function driverDeliveredOrder(Request $request) {
        $request->validate([
            'order_id' => 'required'
        ]);
        $order = Order::where('id',$request->order_id)->first();
        if ($order->driver_id != Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Driver cannot change status of this order. It is connected to another driver'
            ]);
        }

        if ($order->status == OrderdStatus::DRIVER_DELIVERY_TO_WORKER) {
            $order->status = OrderdStatus::WORKER_PROCESSING;
        }
        else if ($order->status == OrderdStatus::DRIVER_DELIVERY_TO_CLIENT) {
            $order->status = OrderdStatus::ORDER_DELIVERED;
        }
        $order->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Driver delivered order'
        ]);
    }



    public function getPrices(Request $request) {
        $request->validate([
            'services' => 'required'
        ]);

        return Service::getPrices($request->services);


    }

    public function getCart(Request $request) {
        $order = Order::where("client_id", Auth::id())->where("status", 0)->first();

        if (isset($order)) {
            $result = array();
            //$price = 0;
            $services = $order->services;
            foreach ($services as $service) {
                $service_arr = array();
                foreach ($service["service_ids"] as $service_id) {
                    $service_price = Price::where("service_id", $service_id)->where("weight_class_id", $service["weight_class_id"])->first()->value;
                    array_push( $service_arr, [
                        "service_id" => $service_id,
                        "service_name" => Service::where('id',$service_id)->first()->name,
                        "weight_class_id" => $service["weight_class_id"],
                        "weight_class_name" => WeightClass::where('id',$service["weight_class_id"])->first()->name,
                        "price" => $service_price
                    ]);
                    //$price += $service_price;
                }
                array_push($result, ["service_group" => $service_arr]);
            }

            //$order->price = $price;
            //$order->save();

            return ["order" => ["service_groups" => $result], "price" => $order->price];
        }
        else {
            return [];
        }

        return Service::getPrices($request->services);


    }
}
