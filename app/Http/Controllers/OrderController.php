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
use App\Models\OrderRating;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Node\Query\OrExpr;
use Symfony\Component\Console\Input\Input;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller{
    
    private function calculateDistance($starting_location, $ending_location) {
    
        $start = $starting_location['latitude'] . "," . $starting_location['longitude'];
        $end = $ending_location['latitude'] . "," . $ending_location['longitude'];

        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $start,
            'destinations' => $end,
            'key' => env('GOOGLE_API_KEY',false)
        ]);

        $calulated_distances = json_decode($response->body());

        $time_remaining = $calulated_distances->rows[0]->elements[0]->duration;

        return $time_remaining;
    }

    private function formatTime ($seconds) {
        $minutes = (int)($seconds/60);
        if ($minutes < 60) {
            return $minutes . "min";
        }
        else {
            $hours = (int)($minutes/60);
            return $hours . "h " . $minutes%60 . "min";
        }
    }

    private function timeDifference($start, $end) {
        $difference_in_seconds = abs($start->format('U') - $end->format('U'));
        return $this->formatTime($difference_in_seconds);
    }

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

        $request->validate(['payment_info' => 'required', 'takeout_date' => 'required', 'delivery_date' => 'required']);

        $current_order = Order::where('client_id', Auth::id())->where('status', 0)->first();

        if (isset($current_order)) {
            $current_order->payment_info = $request->payment_info;
            $current_order->takeout_date = $request->takeout_date;
            $current_order->delivery_date = $request->delivery_date;
            if (isset($request->order_info)) {
                $order_info = $request->order_info;
                if (isset($order_info["country"]) and isset($order_info["address"]) and isset($order_info["city"]) and isset($order_info["municipality"]) and isset($order_info["zip"])) {
                    $current_order->order_info = $order_info;
                }
                else {
                    return response()->json(["status" => 0, "errorMessage" => "Some address info is missing"]);
                }
            }
            $current_order->status = 1;
            $current_order->save();
            return response()->json([
                "status" => 1
            ]);
        }
        else {
            return response()->json(["status" => 0, "errorMessage" => "Cart is empty"]);
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



    /*********************************************************************************************************************************************************
                                                                        
    
    
                                                                            WORKERS
    
    
    
    *********************************************************************************************************************************************************/

    // return number of new orders and accepted orders
    public function workerGetNumberOfTotalOrders() {
        //need to implement rejected orders
        $new_orders_count = Order::where('status',OrderdStatus::ORDER_CREATED)->count();
        $accepted_orders_count = Order::where('worker_id', Auth::id())->count();

        if ($new_orders_count == 0 && $accepted_orders_count == 0) {
            return response()->json(["status" => 0, "errorMessage" => 'No orders']);
        }

        return response()->json([
            "status" => 1,
            "newOrders" => $new_orders_count,
            "acceptedOrders" => $accepted_orders_count
        ]);
    }

    // return list of new orders combined with times client defined when orders shoud be delivered
    public function workerGetListOfNewOrders() {
        
        $worker = Auth::user();
        $new_orders = Order::where('status',OrderdStatus::ORDER_CREATED)->get();

        if ($new_orders->isEmpty()) {
            return response()->json(["status" => 0, "errorMessage" => "No new orders"]);
        }

        $result = [];

        foreach ($new_orders as $order) {
            $takeout_datetime = new \DateTime($order->takeout_date["date"] . " " . $order->takeout_date["end_time"]);
            $now = new \DateTime();

            $difference_in_seconds = $takeout_datetime>$now ? $takeout_datetime->format('U') - $now->format('U') : 0;

            if ($difference_in_seconds > 0) {
                $result[] = [
                    'jbp' => $order->id,
                    'time' => $this->formatTime($difference_in_seconds)
                ];
            }
        }

        if (empty($result)) {
            return response()->json(["status" => 0, "errorMessage" => "No new orders"]);
        }

        return response()->json([
            "status" => 1,
            "result" => $result
        ]);

        // Comented code calculates the distance in minutes from clients to services
        /*

        $destinations = "";

        foreach ($new_orders as $order) {
            $driver = User::where('id',$order->client_id)->first();
            $destinations .= $driver->city . "," . $driver->address . "|";
        }
        $destinations = substr($destinations, 0, -1);

        $order_info = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $worker->city . "," . $worker->address,
            'destinations' => $destinations,
            'key' => env('GOOGLE_API_KEY',false)
        ]);

        $calulated_distances = json_decode($order_info->body());

        $result = [];
        $counter = 0;
        
        foreach ($new_orders as $order) {
            $time = $calulated_distances->rows[0]->elements[$counter]->duration->text;
            $counter++;
            $result[] = [
                'jbp' => $order->id,
                'time' => $time
            ];
        }
        return $result;
        */
    }

    // reurn list of accepted orders
    public function workerGetListOfAcceptdeOrders() {
        $worker = Auth::user();
        $destinations = "";
        $new_orders = Order::where('worker_id',Auth::id())->get();

        if ($new_orders->isEmpty()) {
            return response()->json(["status" => 0, "errorMessage" => "No new orders"]);
        }

        foreach ($new_orders as $order) {
            $driver = User::where('id',$order->client_id)->first();
            $destinations .= $driver->city . "," . $driver->address . "|";
        }
        $destinations = substr($destinations, 0, -1);

        $order_info = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $worker->city . "," . $worker->address,
            'destinations' => $destinations,
            'key' => env('GOOGLE_API_KEY',false)
        ]);

        $calulated_distances = json_decode($order_info->body());

        $result = [];
        $counter = 0;
        
        foreach ($new_orders as $order) {
            $time = $calulated_distances->rows[0]->elements[$counter]->duration->text;
            $counter++;
            $result[] = [
                'jbp' => $order->id,
                'time' => $time,
                "fractionFinished" => $order->status / 10
            ];
        }
        return response()->json([
            "status" => 1,
            "result" => $result
        ]);
    }

    // Get order data
    public function workerGetOrderData(Request $request) {
        $request->validate(['jbp' => 'required']);
        $order = Order::where('id',$request->jbp)->first();

        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Order unavailable"]);

        $services = $order->services;

        $service_list_prep = [];

        foreach ($services as $service_group) {
            $weight = WeightClass::where('id',$service_group['weight_class_id'])->first()->name;
            foreach ($service_group["service_ids"] as $service_id) {
                $name = Service::where('id',$service_id)->first()->name;
                $service_list_prep[] = ['type' => $name, 'weight' => $weight];
            }
        }
        return response()->json([
            "status" => 1,
            "services" => $service_list_prep,
            "clientDate" => $order->takeout_date["date"],
            "clientTime" => $order->takeout_date["start_time"]. "-" . $order->takeout_date["end_time"]
        ]);
    }

    public function workerChangeOrderData(Request $request) {
        $request->validate(['jbp' => 'required', 'serviceAccepted' => 'required']);
        $order = Order::where('id',$request->jbp)->first();

        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Order unavailable"]);

        if ($request->serviceAccepted == FALSE) {
            if (isset($order) && $order->status == OrderdStatus::ORDER_CREATED) {
                $rejected_order = RejectedOrders::where('order_id',$order->id)->where('user_id', Auth::id())->first();
                if (isset($rejected_order)) {
                    return response()->json(["status" => 0, "errorMessage" => "Order Already Rejected"]);
                }

                RejectedOrders::create([
                    'order_id' => $request->jbp,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    "status" => 1
                ]);
            }
            else {
                return response()->json(["status" => 0, "errorMessage" => "Order Not Rejected"]);
            }
        }
        else {
            if ($order->status == OrderdStatus::ORDER_CREATED) {
                $order->status = OrderdStatus::WORKER_ACCEPTED;
                $order->worker_id = Auth::id();
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            else {
                return response()->json(["status" => 0, "errorMessage" => "Order Not Accepted"]);
            }
        }
    }


    public function workerLoadAcceptedOrderData(Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Order unavailable"]);
        
        $remaining_time = 0;
        $driver = $order->driver_id == NULL ? NULL : User::where('id',$order->driver_id)->first();

        $worker_location = Auth::user()->location;
        $driver_location = isset($driver) ? $driver->location : NULL;
        $client_location = User::where('id',$order->client_id)->first()->location;

        switch ($order->status) {
            case OrderdStatus::WORKER_ACCEPTED:
                # time is not available
                break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT:
                # time from driver to client + delta time + time from client to worker
                $remaining_time = $this->calculateDistance($driver_location,$client_location)->value + 300 + $this->calculateDistance($client_location,$worker_location)->value;
                break;
            case OrderdStatus::DRIVER_DELIVERY_TO_WORKER:
                # time from driver to worker
                $remaining_time = $this->calculateDistance($driver_location,$worker_location)->value;
                break;
            case OrderdStatus::WORKER_PROCESSING:
                # time not available
                break;
            case OrderdStatus::WORKER_FINISHED:
                # time not available
                break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER:
                # time from driver to worker
                $remaining_time = $this->calculateDistance($driver_location,$worker_location)->value;
                break;
            case OrderdStatus::DRIVER_DELIVERY_TO_CLIENT:
                # time from driver to client
                $remaining_time = $this->calculateDistance($driver_location,$client_location)->value;
                break;
            case OrderdStatus::ORDER_DELIVERED:
                # time unavailable
                break;
            default:
                # nothing
                break;
        }
        return response()->json([
            "status" => 1,
            "orderStatus" => $order->status,
            "remainingTime" => $this->formatTime($remaining_time),
            "driverName" => isset($driver) ? $driver->name . " " . $driver->surname : NULL,
            "driverPhone" => isset($driver) ? $driver->phone : NULL,
            "licencePlate" => isset($driver) ? $driver->profile->licence_plate : NULL
        ]);
    }

    public function workerLoadAcceptedOrderMap(Request $request) {
        $request->validate(['jbp' => 'required']);
        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Order unavailable"]);

        $driver = $order->driver_id == NULL ? NULL : User::where('id',$order->driver_id)->first();
        if (!isset($driver)) return response()->json(["status" => 0,"errorMessage" => "Order has no asigned driver"]);

        return response()->json([
            'status' => 1,
            "driverLatitude" => $driver->location['latitude'],
            "driverLongitude" => $driver->location['longitude']
        ]);
    }

    public function workerSetLoadedAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required', 'isLoaded' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Order unavailable"]);

        if ($request->isLoaded == TRUE) {
            if ($order->status == OrderdStatus::DRIVER_DELIVERY_TO_WORKER) {
                $order->status = OrderdStatus::WORKER_PROCESSING;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
        }

        return response()->json(["status" => 0, "errorMessage" => "Error loading the order"]); 
    }

    public function workerSetDeliveredAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required', 'isDelivered' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        if ($request->isDelivered == TRUE) {
            if ($order->status == OrderdStatus::WORKER_FINISHED) {
                $order->status = OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
        }

        return response()->json([
            "status" => 0,
            "errorMessage" => "Error delivering the order"
        ]); 
    }

    public function workerSetReadydAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        if ($order->status == OrderdStatus::WORKER_PROCESSING) {
            $order->status = OrderdStatus::WORKER_FINISHED;
            $order->save();
            return response()->json([
                "status" => 1
            ]);
        }

        return response()->json(["status" => 0, "errorMessage" => "Error"]); 
    }

    public function workerGetOrderStatus (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        $order_status = '';
        $now = new \DateTime();

        switch ($order->status) {
            case OrderdStatus::WORKER_ACCEPTED:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'isporuka',
                    "remainingTime" => $this->timeDifference($order->getDateTime('delivery', 'end'),$now),
                    "serviceDate" => $order->getDateTime('takeout', 'end')->format("Y-m-d"),
                    "serviceTime" => $order->getDateTime('takeout', 'end')->format("H:i"),
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'isporuka',
                    "remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                    "serviceDate" => $order->getDateTime('takeout', 'end')->format("Y-m-d"),
                    "serviceTime" => $order->getDateTime('takeout', 'end')->format("H:i"),
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderdStatus::DRIVER_DELIVERY_TO_WORKER:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'isporuka',
                    "remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                    "serviceDate" => $order->getDateTime('takeout', 'end')->format("Y-m-d"),
                    "serviceTime" => $order->getDateTime('takeout', 'end')->format("H:i"),
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderdStatus::WORKER_PROCESSING:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'usluga',
                    "remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderdStatus::WORKER_FINISHED:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'preuzimanje',
                    "remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                ]);
                break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'preuzimanje',
                    "remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                ]);
                break;
            case OrderdStatus::DRIVER_DELIVERY_TO_CLIENT:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'realizovano'
                ]);
                break;
            default:
                return response()->json(["status" => 0, "errorMessage" => "Order status unavailable"]);
                break;
        }
    }


    /*********************************************************************************************************************************************************
                                                                        
    
    
                                                                            DRIVERS
    
    
    
    *********************************************************************************************************************************************************/

    public function driverGetNumberOfTotalOrders (Request $request) {
        //need to implement rejected orders
        $new_orders_count = Order::where('status',OrderdStatus::WORKER_ACCEPTED)->count();
        $accepted_orders_count = Order::where('driver_id', Auth::id())->count();

        if ($new_orders_count == 0 && $accepted_orders_count == 0) return response()->json(["status" => 0, "errorMessage" => "No orders avalable"]);

        return response()->json([
            'status' => 1,
            "newOrders" => $new_orders_count,
            "acceptedOrders" => $accepted_orders_count
        ]);
    }

    public function driverGetListOfNewOrders() {
        $driver = Auth::user();
        $destinations = "";
        $new_orders = Order::where('status',OrderdStatus::WORKER_ACCEPTED)->get();

        if ($new_orders->isEmpty()) {
            return response()->json(["status" => 0, "errorMessage" => "No new orders"]);
        }

        $result = [];
        $now = new \DateTime();

        foreach ($new_orders as $order) {
            $takeout_end_datetime = $order->getDateTime('takeout', 'end');

            $result[] = [
                'jbp' => $order->id,
                'time' => $this->timeDifference($takeout_end_datetime,$now)
            ];
        }

        return response()->json([
            'status' => 1,
            'result' => $result
        ]);
    }

    public function driverGetListOfAcceptdeOrders() {
        $driver = Auth::user();
        $destinations = "";
        $new_orders = Order::where('driver_id',Auth::id())->get();

        if ($new_orders->isEmpty()) {
            return response()->json(["status" => 0, "errorMessage" => "No new orders"]);
        }

        foreach ($new_orders as $order) {
            $client = User::where('id',$order->client_id)->first();
            $destinations .= $client->city . "," . $client->address . "|";
        }
        $destinations = substr($destinations, 0, -1);

        $order_info = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $driver->location["latitude"] . "," . $driver->location["longitude"],
            'destinations' => $destinations,
            'key' => env('GOOGLE_API_KEY',false)
        ]);

        $calulated_distances = json_decode($order_info->body());

        $result = [];
        $counter = 0;
        
        foreach ($new_orders as $order) {
            $time = $calulated_distances->rows[0]->elements[$counter]->duration->text;
            $counter++;
            $result[] = [
                'jbp' => $order->id,
                'time' => $time,
                "fractionFinished" => $order->status / 10
            ];
        }
        return response()->json([
            'status' => 1,
            'result' => $result
        ]);
    }

    public function DriverGetOrderStatus (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        $order_status = '';
        $now = new \DateTime();

        switch ($order->status) {
            case OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT:
            case OrderdStatus::DRIVER_DELIVERY_TO_WORKER:
                return response()->json([
                    'status' => 1,
                    "orderStatus" => 'preuzimanje',
                    "remainingTime" => $this->timeDifference($order->getDateTime('delivery', 'end'),$now),
                    "deliveryDate" => $order->getDateTime('takeout', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('takeout', 'end')->format("H:i"),
                ]);
                break;
            case OrderdStatus::WORKER_PROCESSING:
            case OrderdStatus::WORKER_FINISHED:
                return response()->json([
                    'status' => 1,
                    "orderStatus" => 'izvrsilac',
                    "isLoadAndNotDelivery" => false,
                    "remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER:
            case OrderdStatus::DRIVER_DELIVERY_TO_CLIENT:
                return response()->json([
                    'status' => 1,
                    "orderStatus" => 'isporuka',
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderdStatus::ORDER_DELIVERED:
                return response()->json([
                    'status' => 1,
                    "orderStatus" => 'realizovano'
                ]);
                break;
            case OrderdStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT:
                return response()->json([
                    'status' => 1,
                    "orderStatus" => 'preuzimanje',
                    'warning' => 'Klijent nije na adresi',
                    "deliveryDate" => $order->getDateTime('takeout', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('takeout', 'end')->format("H:i"),
                ]);
                break;
            default:
                return response()->json([
                    'status' => 0,
                    "errorMessage" => 'order unavailable'
                ]);
                break;
        }
    }

    public function driverAcceptOrder(Request $request) {
        $request->validate(['jbp' => 'required', 'orderAccepted' => 'required']);
        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        if ($request->orderAccepted == TRUE) {
            if ($order->status == OrderdStatus::WORKER_ACCEPTED) {
                $order->status = OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT;
                $order->driver_id = Auth::id();
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            else if ($order->status == OrderdStatus::WORKER_FINISHED) {
                $order->status = OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER;
                $order->driver_id = Auth::id();
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            else {
                return response()->json([
                    "status" => 0,
                    "errorMessage" => "Cannot accept order"
                ]);
            }
        }
        else if ($request->orderAccepted == FALSE) {
            $note = (isset($request->rejectedNote)) ? $request->rejectedNote : NULL;
            $rejected_order = RejectedOrders::where('order_id',$order->id)->where('user_id', Auth::id())->first();
            if (isset($rejected_order)) {
                return response()->json([
                    "status" => 0,
                    "errorMessage" => "Order Already Rejected"
                ]);
            }

            RejectedOrders::create([
                'order_id' => $request->jbp,
                'user_id' => Auth::id(),
                'note' => $note
            ]);
            
            
            return response()->json([
                "status" => 1
            ]);
        }
        else {
            return response()->json([
                "status" => 0,
                "errorMessage" => "orderAccepted invalid"
            ]); 
        }
    }

    public function driverGetOrderData(Request $request) {
        $request->validate(['jbp' => 'required', 'driverLatitude' => 'required', 'driverLongitude' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        $client = User::where("id",$order->client_id)->first();
        $worker = User::where("id",$order->worker_id)->first();

        $driver_location = [
            "latitude" => $request->driverLatitude,
            "longitude" => $request->driverLongitude
        ];
        $client_location = $client->location;
        $worker_location = $worker->location;

        $api_result = NULL;
        $isClient = TRUE;
        $type = 'preuzimanje';

        switch ($order->status) {
            case OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $client_location);
                $isClient = TRUE;
                $type = 'preuzimanje';
            break;
            case OrderdStatus::DRIVER_DELIVERY_TO_WORKER :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $worker_location);
                $isClient = FALSE;
                $type = 'isporuka';
            break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $worker_location);
                $isClient = FALSE;
                $type = 'preuzimanje';
            break;
            case OrderdStatus::DRIVER_DELIVERY_TO_CLIENT :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $client_location);
                $isClient = TRUE;
                $type = 'isporuka';
            break;
            default :
                $api_result = ["duration"=>"", "distance"=>""];
            break;
        }

        $duration = $api_result["duration"];
        $distance = $api_result["distance"];

        if ($isClient) {
            return response()->json([
                'status' => 1,
                "type" => $type,
                "remainingTime" => $duration,
                "clientName" => $client->name . " " . $client->surname,
                "clientPhone" => $client->phone,
                "clientAddress" => $client->address . ", " . $client->city,
                "clientLatitude" => $client->location["latitude"],
                "clientLongitude" => $client->location["longitude"],
                "distance" => $distance
            ]); 
        }
        else {
            return response()->json([
                'status' => 1,
                "type" => $type,
                "remainingTime" => $duration,
                "workerName" => $worker->name . " " . $client->surname,
                "workerPhone" => $worker->phone,
                "workerAddress" => $worker->address . ", " . $client->city,
                "workerLatitude" => $worker->location["latitude"],
                "workerLongitude" => $worker->location["longitude"],
                "distance" => $distance
            ]); 
        }
    }

    public function driverSetLoadedAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required', 'isLoaded' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        if ($request->isLoaded == TRUE) {
            if ($order->status == OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT || $order->status == OrderdStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT) {
                $order->status = OrderdStatus::DRIVER_DELIVERY_TO_WORKER;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
        }
        else if ($request->isLoaded == FALSE) {
            if ($order->status == OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT) {
                $order->status = OrderdStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT;
                $order->save();
                
                return response()->json([
                    "status" => 1
                ]);
            }

            return response()->json([
                "status" => 0,
                "errorMessage" => "Order status invalid"
            ]);
        }

        return response()->json(["status" => 0, "errorMessage" => "Order invalid"]); 
    }

    public function driverSetDeliveredToWorkerAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required', 'isDelivered' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        if ($request->isDelivered == TRUE) {
            if ($order->status == OrderdStatus::DRIVER_DELIVERY_TO_WORKER || $order->status == OrderdStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER) {
                $order->status = OrderdStatus::WORKER_PROCESSING;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            else if ($order->status == OrderdStatus::WORKER_PROCESSING) {
                return response()->json([
                    "status" => 1
                ]);
            }
            return response()->json(["status" => 0, "errorMessage" => "Unable to set new status"]);
        }
        else if ($request->isDelivered == FALSE) {
            if ($order->status == OrderdStatus::DRIVER_DELIVERY_TO_WORKER) {
                $order->status = OrderdStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            return response()->json(["status" => 0, "errorMessage" => "Unable to reject"]);
        }
        return response()->json(["status" => 0, "errorMessage" => "Order unavailable"]); 
    }

    public function driverSetLoadedFromWorkerAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required', 'isLoaded' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        if ($request->isLoaded == TRUE) {
            if ($order->status == OrderdStatus::WORKER_FINISHED || $order->status == OrderdStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER) {
                $order->status = OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            return response()->json(["status" => 0,"errorMessage" => "Cannot accept"]);
        }
        else if ($request->isLoaded == FALSE) {
            if ($order->status == OrderdStatus::WORKER_FINISHED) {
                $order->status = OrderdStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            return response()->json(["status" => 0,"errorMessage" => "Cannot reject"]);
        }
        return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);
    }

    public function driverSetDeliveredToClientAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required', 'isDelivered' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        if ($request->isDelivered == TRUE) {
            if ($order->status == OrderdStatus::DRIVER_DELIVERY_TO_CLIENT || $order->status == OrderdStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT) {
                $order->status = OrderdStatus::ORDER_DELIVERED;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            return response()->json(["status" => 0, 'errorMessage' => 'Ubable to accept order, status invalid']);
        }
        else if ($request->isDelivered == FALSE) {
            if ($order->status == OrderdStatus::DRIVER_DELIVERY_TO_CLIENT) {
                $order->status = OrderdStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            return response()->json(["status" => 0, 'errorMessage' => 'Ubable to reject order, status invalid']);
        }
        return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);
    }




    /*********************************************************************************************************************************************************
                                                                        
    
    
                                                                            CLIENT
    
    
    
    *********************************************************************************************************************************************************/

    public function clientAddNewService(Request $request) {
        $request->validate(['types' => 'required', 'weight' => 'required']);

        $note = (isset($request->note)) ? $request->note : NULL;
        $current_order = Order::where('client_id', Auth::id())->where('status', OrderdStatus::ORDER_IN_CREATION)->first();

        if (isset($current_order)) {
            $tmp = $current_order->services;
            array_push($tmp, [
                "service_ids" => $request->types,
                "weight_class_id" => $request->weight,
                "note" => $note
            ]
        );
            $current_order->services = $tmp;
            $current_order->save();
            $current_order->calculatePrice();
            return response()->json([
                "status" => 1
            ]);
        }
        else {
            $order = Order::create([
                'services' => [[
                    "service_ids" => $request->types,
                    "weight_class_id" => $request->weight,
                    "note" => $note
                ]],
                'client_id' => Auth::id(),
                'status' => OrderdStatus::ORDER_IN_CREATION,
                'price' => 0
            ]);
            $order->calculatePrice();
            return response()->json([
                "status" => 1
            ]);
        }
        return response()->json(["status" => 0,"errorMessage" => "Error"]);
    }

    public function clientGetTotalNuberOfOrders () {
        $orders_count = Order::where('client_id',Auth::id())->count();
        return response()->json([
            'status' => 1,
            "orders" => $orders_count
        ]);
    }

    public function clientGetOrderList () {
        $orders = Order::where('client_id',Auth::id())->get();
        $result = [];
        foreach ($orders as $order) {
            //$time = $this->formatTime($this->calculateDistance(Auth::user()->location,Auth::user()->location));
            $time = rand(10,40) . "min";
            $result[] = [
                "jbp" => $order->id,
                "time" =>$time,
                "fractionFinished" => $order->status / 10
            ]; 
        }
        return response()->json([
            'status' => 1,
            'result' => $result
        ]);
    }

    public function clientGetOrderStatus (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        $worker = User::where('id',$order->worker_id)->first();

        switch ($order->status) {
            case OrderdStatus::ORDER_CREATED :
                // kreirano
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "kreirano",
                    "loadDate" => $order->takeout_date["date"],
                    "loadTime" => $order->takeout_date["end_time"],
                    "deliveryDate" =>  $order->delivery_date["date"],
                    "deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderdStatus::WORKER_ACCEPTED :
                // kreirano
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "kreirano",
                    "loadDate" => $order->takeout_date["date"],
                    "loadTime" => $order->takeout_date["end_time"],
                    "deliveryDate" =>  $order->delivery_date["date"],
                    "deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT :
                // preuzimanje
                $driver = User::where('id',$order->driver_id)->first();
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "preuzimanje",
                    "remainingTime" => googleAPIGetTimeRemainingFormated($driver->location, Auth::user()->location),
                    "deliveryDate" =>  $order->delivery_date["date"],
                    "deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderdStatus::DRIVER_DELIVERY_TO_WORKER :
                // preuzimanje
                $driver = User::where('id',$order->driver_id)->first();
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "preuzimanje",
                    "remainingTime" => googleAPIGetTimeRemainingFormated($driver->location, $worker->location),
                    "deliveryDate" =>  $order->delivery_date["date"],
                    "deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderdStatus::WORKER_PROCESSING :
                // usluga
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "usluga",
                    "deliveryDate" =>  $order->delivery_date["date"],
                    "deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderdStatus::WORKER_FINISHED :
                // usluga
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "usluga",
                    "deliveryDate" =>  $order->delivery_date["date"],
                    "deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER :
                // dostava
                $driver = User::where('id',$order->driver_id)->first();
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "dostava",
                    "remainingTime" => googleAPIGetTimeRemainingFormated($driver->location, $worker->location),
                ]);
            break;
            case OrderdStatus::DRIVER_DELIVERY_TO_CLIENT :
                // dostava
                $driver = User::where('id',$order->driver_id)->first();
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "dostava",
                    "remainingTime" => googleAPIGetTimeRemainingFormated($driver->location, Auth::user()->location),
                ]);
            break;
            case OrderdStatus::ORDER_DELIVERED :
                // realizovano
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "realizovano"
                ]);
            break;
            default :
                return response()->json([
                    "status" => 0,
                    'errorMessage' => 'Unavailable status'
                ]);
            break;
        }
    }

    public function clientGetOrderData (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        $worker = User::where('id',$order->worker_id)->first();
        $driver = User::where('id',$order->driver_id)->first();

        if (!isset($order)) return response()->json(['status' => 0, 'errorMessage' => 'Unavailable order']);
        if (!isset($driver)) return response()->json(['status' => 0, 'errorMessage' => 'Unavailable driver']);
        if (!isset($worker)) return response()->json(['status' => 0, 'errorMessage' => 'Unavailable worker']);

        $remaining_time = NULL;

        switch ($order->status) {
            case OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT :
                $remaining_time = googleAPIGetTimeRemainingFormated($driver->location, Auth::user()->location);
            break;
            case OrderdStatus::DRIVER_DELIVERY_TO_WORKER :
                $remaining_time = googleAPIGetTimeRemainingFormated($driver->location, $worker->location);
            break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER :
                $remaining_time = googleAPIGetTimeRemainingFormated($driver->location, $worker->location);
            break;
            case OrderdStatus::DRIVER_DELIVERY_TO_CLIENT :
                $remaining_time = googleAPIGetTimeRemainingFormated($driver->location, Auth::user()->location);
            break;
            default :
                $remaining_time = "Nedostupno";
            break;
        }
        return response()->json([
            'status' => 1,
            "remainingTime" => $remaining_time,
            "driverName" => $driver->name . " " . $driver->surname,
            "driverPhone" => $driver->phone,
            "licencePlate" => $driver->profile->licence_plate,
        ]);
    }

    public function clientGetOrderMap(Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Unavailable order']);

        $driver = User::where('id',$order->driver_id)->first();
        if (!isset($driver)) return response()->json(["status" => 0, 'errorMessage' => 'Unavailable driver']);

        return response()->json([
            'status' => 1,
            "driverLatitude" => $driver->location["latitude"],
            "driverLongitude" => $driver->location["longitude"]
        ]);
    }

    public function clientGetPaymentInfo (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id', $request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Unavailable order']);

        $result = [];
        $full_price = 0;

        foreach ($order->services as $service_group) {
            foreach ($service_group["service_ids"] as $service_id) {
                $service = Service::where('id',$service_id)->first();
                $price = Price::where('service_id',$service_id)->where('weight_class_id',$service_group["weight_class_id"])->first();
                $result[] = [
                    "type" => $service->name,
                    "price" => $price->value
                ];
                $full_price += $price->value;
            }
        }

        return response()->json([
            'status' => 1,
            "services" => $result,
            "totalPrice" => $full_price
        ]);
    }

    public function clientGetExtraData(Request $request) {
        $client = Auth::user();

        return response()->json([
            'status' => 1,
            "name" => $client->name . " " . $client->surname,
            "phone" => $client->phone,
            "email" => $client->email,
            "address" => $client->address
        ]);
    }

    public function clientGetCurrentPaymentInfo (Request $request) {
        $order = Order::where('client_id', Auth::id())->where('status', OrderdStatus::ORDER_IN_CREATION)->first();

        if (!isset($order)) return response()->json(["status" => 'ERROR',"message" => 'No pending orders']);

        $result = [];
        $full_price = 0;

        foreach ($order->services as $service_group) {
            foreach ($service_group["service_ids"] as $service_id) {
                $service = Service::where('id',$service_id)->first();
                $price = Price::where('service_id',$service_id)->where('weight_class_id',$service_group["weight_class_id"])->first();
                $result[] = [
                    "type" => $service->name,
                    "price" => $price->value
                ];
                $full_price += $price->value;
            }
        }

        return response()->json([
            "services" => $result,
            "totalPrice" => $full_price
        ]);
    }

    public function clientGetOrderServices (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id', $request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Unavailable order']);

        $result = [];

        foreach ($order->services as $service_group) {
            foreach ($service_group["service_ids"] as $service_id) {
                $is_new_service = TRUE;
                $service = Service::where('id',$service_id)->first();
                foreach ($result as $single) {
                    if ($single['id'] == $service_id) {
                        $is_new_service = FALSE;
                    }
                }
                if ($is_new_service) {
                    $result[] = ["name" => $service->name, "id" => $service->id];
                }
            }
        }

        return response()->json([
            "status" => 1,
            "services" => $result
        ]);
    }

    public function clientSetOrderRating(Request $request) {
        $request->validate(['jbp' => 'required', 'ratings' => 'required']);

        $order = Order::where('id', $request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Unavailable order']);

        $rating = OrderRating::where('order_id',$request->jbp)->first();
        if (isset($rating)) return response()->json(["status" => 0, 'errorMessage' => 'Already rated']);

        if (isset($request->note)) {
            OrderRating::create([
                'order_id' => $order->id,
                'service_ratings' => $request->ratings,
                'note' => $request->note
            ]);
        }
        else {
            OrderRating::create([
                'order_id' => $order->id,
                'service_ratings' => $request->ratings
            ]);
        }

        return response()->json([
            "status" => 1
        ]);

    }


























    


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
        $request->validate(['services' => 'required']);

        return response()->json([
            'status' => 1,
            'result' => Service::getPrices($request->services)
        ]);
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
