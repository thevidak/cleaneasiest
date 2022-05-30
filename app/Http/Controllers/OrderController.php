<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\ServiceType;
use App\Models\Price;
use App\Models\RejectedOrders;
use App\Models\Service;
use App\Models\Shop;
use App\Models\User;
use App\Models\WeightClass;
use App\Models\OrderRating;
use App\Models\Options;
use App\Models\StatusChange;
use App\Models\Faq;
use App\Models\Privacy;
use App\Models\ClientQuestion;
use App\Models\ClientInfo;

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
        else if ($minutes < 1440){
            $hours = (int)($minutes/60);
            return $hours . "h";
        }
        else {
            $days = (int)($minutes/1440);
            return $days == 1 ? $days . " dan" : $days . " dana";
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
                'status' => OrderStatus::ORDER_IN_CREATION,
                'price' => 0
            ]);
            $order->calculatePrice();
            return $order;
        }
    }

    public function createOrder(Request $request) {

        $request->validate(['payment_info' => 'required', 'takeout_date' => 'required']);

        $current_order = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_IN_CREATION)->first();

        if (isset($current_order)) {
            $current_order->payment_info = $request->payment_info;
            // if card payment
            if ($request->payment_info['type'] == 1) {
                if (isset($request->payment_info['card_id'])) {
                   
                    $user_info = ClientInfo::where('client_id', Auth::id())->first();

                    if (!isset($user_info)) {
                        $user_info = ClientInfo::create([
                            'client_id' => $user->id,
                            'card_id' => $request->payment_info['card_id']
                        ]);
                    }
                    else {
                        $user_info->card_id = $request->payment_info['card_id'];
                        $user_info->save();
                    }
                }
            }


            $current_order->takeout_date = $request->takeout_date;
            if (isset($request->order_info)) {
                $order_info = $request->order_info;
                if (isset($order_info["address"]) && $order_info["address"] != '') {
                    $order_info["location"] = googleAPIGetGeoLocationFromAddress($order_info["address"]);
                    $current_order->order_info = $order_info;
                }
                else if (isset($order_info["location"])) {
                    $order_info['address'] = 'Bulevar Zorana Djindjica 22, Beograd';
                    $current_order->order_info = $order_info;
                }
                else {
                    return response()->json(["status" => 0, "errorMessage" => "Some address info is missing"]);
                }
            }
            $current_order->status = OrderStatus::ORDER_CREATED;
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

    // return (just) a number of new orders and accepted orders
    public function workerGetNumberOfTotalOrders() {
        // we have to exclude rejected orders from new orders
        $rejected_orders = RejectedOrders::where('user_id', Auth::id())->get();
        $pending_orders = Order::where('status',OrderStatus::ORDER_CREATED)->get();

        foreach ($rejected_orders as $rejected_order) {
            $r_order_id = $rejected_order->order_id;
            foreach ($pending_orders as $key=>$pending_order) {
                $p_order_id = $pending_order->id;
                if ($p_order_id == $r_order_id) {
                    unset($pending_orders[$key]);
                }
            }
        }

        $new_orders_count = count($pending_orders);
        $accepted_orders_count = Order::where('worker_id', Auth::id())->where('status', '!=', OrderStatus::ORDER_DELIVERED)->count();

        if ($new_orders_count == 0 && $accepted_orders_count == 0) { return response()->json(["status" => 0, "errorMessage" => 'No orders']);}

        // if the order timer is passed we do not show it
        
        $result = [];
        foreach ($pending_orders as $order) {
            $takeout_datetime = $order->getDateTime('takeout', 'end');
            //$takeout_datetime = new \DateTime($order->takeout_date["date"] . " " . $order->takeout_date["end_time"]);
            $now = new \DateTime();

            $difference_in_seconds = $takeout_datetime>$now ? $takeout_datetime->format('U') - $now->format('U') : 0;

            if ($difference_in_seconds > 0) {
                $result[] = $order;
            }
        }
        

        return response()->json([
            "status" => 1,
            "newOrders" => count($result), //$new_orders_count, 
            "acceptedOrders" => $accepted_orders_count
        ]);
    }

    // return list of new orders combined with times client defined when orders shoud be delivered
    public function workerGetListOfNewOrders() {
        
        $worker = Auth::user();

        // we have to exclude rejected orders from new orders
        $rejected_orders = RejectedOrders::where('user_id', Auth::id())->get();
        $pending_orders = Order::where('status',OrderStatus::ORDER_CREATED)->get();

        foreach ($rejected_orders as $rejected_order) {
            $r_order_id = $rejected_order->order_id;
            foreach ($pending_orders as $key=>$pending_order) {
                $p_order_id = $pending_order->id;
                if ($p_order_id == $r_order_id) {
                    unset($pending_orders[$key]);
                }
            }
        }

        if ($pending_orders->isEmpty()) {return response()->json(["status" => 0, "errorMessage" => "No new orders"]);}

        
        $result = [];
        foreach ($pending_orders as $order) {
            $takeout_datetime = $order->getDateTime('takeout', 'end');
            //$takeout_datetime = new \DateTime($order->takeout_date["date"] . " " . $order->takeout_date["end_time"]);
            $now = new \DateTime();

            $difference_in_seconds = $takeout_datetime>$now ? $takeout_datetime->format('U') - $now->format('U') : 0;

            if ($difference_in_seconds > 0) {
                $result[] = [
                    'jbp' => $order->id,
                    'time' => $this->formatTime($difference_in_seconds)
                ];
            }

            else {
                /*
                $result[] = [
                    'jbp' => $order->id,
                    'time' => 'Isteklo'
                ];
                */
            }
        }

        if (empty($result)) { return response()->json(["status" => 0, "errorMessage" => "No new orders"]);}

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
        $new_orders = Order::where('worker_id',Auth::id())->where('status', '!=', OrderStatus::ORDER_DELIVERED)->get();

        if ($new_orders->isEmpty()) return response()->json(["status" => 0, "errorMessage" => "No new orders"]);

        // caluclating time for all orders in one call

        /*
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
                "fractionFinished" => $order->progress
            ];
        }
        */

        $result = [];
        
        foreach ($new_orders as $order) {
            $takeout_datetime = $order->getDateTime('delivery', 'end');
            //$takeout_datetime = new \DateTime($order->delivery_date["date"] . " " . $order->delivery_date["end_time"]);
            $now = new \DateTime();

            $difference_in_seconds = $takeout_datetime>$now ? $this->formatTime(($takeout_datetime->format('U') - $now->format('U'))) : 'Isteklo';

            $result[] = [
                'jbp' => $order->id,
                'time' => $difference_in_seconds,
                "fractionFinished" => $order->progress
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

        foreach ($services as $single_service) {
            $service = Service::where('id',$single_service['service_id'])->first();
            if ($service->type == ServiceType::WEIGHTABLE) {
                $weight = WeightClass::where('id',$single_service['weight_class_id'])->first()->name;
                $service_list_prep[] = [
                    'type' => 'weightable',
                    'name' => $service->name, 
                    'weight' => $weight
                ];
            }
            else if ($service->type == ServiceType::COUNTABLE) {
                $count = 0;
                $clothes = $single_service['clothes'];
                foreach ($clothes as $single_clothing_item) {
                    $count += $single_clothing_item['count'];
                }
                $service_list_prep[] = [
                    'type' => 'countable',
                    'name' => $service->name, 
                    'count' => $count
                ];
            }
        }
        return response()->json([
            "status" => 1,
            "services" => $service_list_prep,
            "deliveryDate" => isset($order->delivery_date) ? $order->delivery_date : NULL
            //"clientDate" => $order->delivery_date["date"],
            //"clientTime" => $order->delivery_date["start_time"]. "-" . $order->delivery_date["end_time"]
        ]);
    }

    public function workerChangeOrderData(Request $request) {
        $request->validate(['jbp' => 'required', 'serviceAccepted' => 'required']);
        $order = Order::where('id',$request->jbp)->first();

        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Order unavailable"]);

        if ($request->serviceAccepted == FALSE) {
            if (isset($order) && $order->status == OrderStatus::ORDER_CREATED) {
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
            if ($order->status == OrderStatus::ORDER_CREATED) {
                if (!isset($request->deliveryDate)) return response()->json(["status" => 0, "errorMessage" => "Datum dostave nije izabran."]);
                $order->status = OrderStatus::WORKER_ACCEPTED;
                $order->worker_id = Auth::id();
                $order->delivery_date = $request->deliveryDate;
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
            case OrderStatus::WORKER_ACCEPTED:
                # time is not available
                break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT:
                # time from driver to client + delta time + time from client to worker
                $remaining_time = $this->calculateDistance($driver_location,$client_location)->value + 300 + $this->calculateDistance($client_location,$worker_location)->value;
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER:
                # time from driver to worker
                $remaining_time = $this->calculateDistance($driver_location,$worker_location)->value;
                break;
            case OrderStatus::WORKER_PROCESSING:
                # time not available
                break;
            case OrderStatus::WORKER_FINISHED:
                # time not available
                $remaining_time = $this->calculateDistance($driver_location,$worker_location)->value;
                break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER:
                # time from driver to worker
                $remaining_time = $this->calculateDistance($driver_location,$worker_location)->value;
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT:
                # time from driver to client
                $remaining_time = $this->calculateDistance($driver_location,$client_location)->value;
                break;
            case OrderStatus::ORDER_DELIVERED:
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
            if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_WORKER) {
                $order->status = OrderStatus::WORKER_PROCESSING;
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
            if ($order->status == OrderStatus::WORKER_FINISHED) {
                $order->status = OrderStatus::DRIVER_TAKEOUT_FROM_WORKER;
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

        if ($order->status == OrderStatus::WORKER_PROCESSING) {
            $order->status = OrderStatus::WORKER_FINISHED;
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

        $driver = User::where('id', $order->driver_id)->first();

        $order_status = '';
        $now = new \DateTime();
        $date_limit = $order->getDateTime('delivery', 'end');
        //$date_limit = $order->getDateTime('takeout', 'end')->add(new \DateInterval('P1D'));

        if ($date_limit < $now) {
            $date_limit = "Isteklo";
        }
        else {
            //$date_limit =  $date_limit->format('d.m.Y H:i');
            $date_limit = $this->timeDifference($date_limit, $now);
        }

        switch ($order->status) {
            case OrderStatus::WORKER_ACCEPTED:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'cekanje',
                    //"remainingTime" => $this->timeDifference($order->getDateTime('delivery', 'end'),$now),
                    "remainingTime" => $date_limit,
                    "serviceDate" => $order->getDateTime('takeout', 'end')->format("Y-m-d"),
                    "serviceTime" => $order->getDateTime('takeout', 'end')->format("H:i"),
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'preuzimanje',
                    //"remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),  
                    "remainingTime" =>$this->formatTime(
                        googleAPIGetTimeRemainingInSeconds($driver->location, $order->order_info['location']) + 
                        googleAPIGetTimeRemainingInSeconds($driver->location, Auth::user()->location)),
                    //"serviceDate" => $order->getDateTime('takeout', 'end')->format("Y-m-d"),
                    //"serviceTime" => $order->getDateTime('takeout', 'end')->format("H:i"),
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'preuzimanje',
                    //"remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                    "remainingTime" =>$this->formatTime(
                        googleAPIGetTimeRemainingInSeconds($driver->location, Auth::user()->location)),
                    //"serviceDate" => $order->getDateTime('takeout', 'end')->format("Y-m-d"),
                    //"serviceTime" => $order->getDateTime('takeout', 'end')->format("H:i"),
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderStatus::WORKER_PROCESSING:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'usluga',
                    "remainingTime" => $this->timeDifference($order->getDateTime('delivery', 'end'),$now),
                    //"remainingTime" => $date_limit,
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderStatus::WORKER_FINISHED:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'usluga',
                    "serviceFinished" => true,
                    //"remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                    "remainingTime" => 0,
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("Y-m-d"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'dostava',
                    //"remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                    "remainingTime" =>$this->formatTime(
                        googleAPIGetTimeRemainingInSeconds($driver->location, Auth::user()->location) + 
                        googleAPIGetTimeRemainingInSeconds(Auth::user()->location, $order->order_info['location'])),
                ]);
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'dostava',
                    //"remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                    "remainingTime" =>$this->formatTime(
                        googleAPIGetTimeRemainingInSeconds($driver->location, $order->order_info['location'])),
                ]);
                break;
            case OrderStatus::ORDER_DELIVERED:
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

    public function workerRejectReasons () {
        $options = Options::where('name','WORKER_REJECT_REASONS')->first();

        if (!isset($options)) return response()->json(["status" => 0, 'errorMessage' => 'Nema informacija na serveru']);

        return response()->json([
            "status" => 1,
            "options" => $options->value
        ]);
    }

    public function workerOrderData(Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Order unavailable"]);
        
        $remaining_time = 0;
        $driver = $order->driver_id == NULL ? NULL : User::where('id',$order->driver_id)->first();

        $worker_location = Auth::user()->location;
        $driver_location = isset($driver) ? $driver->location : NULL;
        $client_location = User::where('id',$order->client_id)->first()->location;

        $acceptedStatus = NULL;
        $order_status = NULL;

        // this api is called only during delivery and takeout
        switch ($order->status) {
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT:
                //$remaining_time = $this->calculateDistance($driver_location,$client_location)->value + 300 + $this->calculateDistance($client_location,$worker_location)->value;
                $remaining_time = $this->formatTime(
                    googleAPIGetTimeRemainingInSeconds($driver->location, $order->order_info['location']) + 
                    googleAPIGetTimeRemainingInSeconds($driver->location, Auth::user()->location));
                $order_status = 'preuzimanje';
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER:
                //$remaining_time = $this->calculateDistance($driver_location,$worker_location)->value;
                $remainingTime =$this->formatTime(
                    googleAPIGetTimeRemainingInSeconds($driver->location, Auth::user()->location));
                $order_status = 'preuzimanje';
                break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER:
                # time from driver to worker
                //$remaining_time = $this->calculateDistance($driver_location,$worker_location)->value;
                $remaining_time = $this->formatTime(
                    googleAPIGetTimeRemainingInSeconds($driver->location, Auth::user()->location) + 
                    googleAPIGetTimeRemainingInSeconds(Auth::user()->location, $order->order_info['location']));
                $order_status = 'dostava';
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT:
                # time from driver to client
                //$remaining_time = $this->calculateDistance($driver_location,$client_location)->value;
                $remaining_time = $this->formatTime(
                    googleAPIGetTimeRemainingInSeconds($driver->location, $order->order_info['location']));
                $order_status = 'dostava';
                break;

            case OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT:
                # time unavailable
                $acceptedStatus = NULL;
            case OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT:
                # time unavailable
                $acceptedStatus = NULL;
            case OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER:
                # time unavailable
                $acceptedStatus = NULL;
            case OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER:
                # time unavailable
                $acceptedStatus = NULL;
                break;
            default:
                # nothing
                break;
        }
        return response()->json([
            "status" => 1,
            "orderStatus" => $order_status,
            "remainingTime" => $remaining_time,
            "driverName" => isset($driver) ? $driver->name . " " . $driver->surname : NULL,
            "driverPhone" => isset($driver) ? $driver->phone : NULL,
            "licencePlate" => isset($driver) ? $driver->profile->licence_plate : NULL,
            "acceptedStatus" => $acceptedStatus,
            "driverLatitude" => isset($driver) ? $driver->location['latitude'] : NULL,
            "driverLongitude" => isset($driver) ? $driver->location['longitude'] : NULL
        ]);
    }


    public function workerChangeOrderStatus (Request $request) {
        $request->validate(['jbp' => 'required', 'status' => 'required', 'type' => 'required']);
        $note = isset($request->note) ? $request->note : NULL;

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Order unavailable"]);

        switch ($request->type) {
            case 'load' :
                // order loaded from driver
                if ($request->status == TRUE) {
                    if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_WORKER) {
                        $order->status = OrderStatus::WORKER_PROCESSING;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                }
                else {

                }
                break;
            case 'ready' : 
                // finished order processing, order ready for takeout
                if ($request->status == TRUE) {
                    if ($order->status == OrderStatus::WORKER_PROCESSING) {
                        $order->status = OrderStatus::WORKER_FINISHED;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                }
                else {
                    
                }
                break;
            case 'delivery' :
                // driver took the order
                if ($request->status == TRUE) {
                    if ($order->status == OrderStatus::WORKER_FINISHED) {
                        $order->status = OrderStatus::DRIVER_TAKEOUT_FROM_WORKER;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                }
                else {
                    
                }
                break;
            default :
                // unknown type
                return response()->json(["status" => 0, "errorMessage" => "Type unrecognized"]); 
                break;
        }

        return response()->json(["status" => 0, "errorMessage" => "Error changing the order status"]); 
    }

    /*********************************************************************************************************************************************************
                                                                        
    
    
                                                                            DRIVERS
    
    
    
    *********************************************************************************************************************************************************/

    public function driverGetNumberOfTotalOrders (Request $request) {
        
        $rejected_orders = RejectedOrders::where('user_id', Auth::id())->get();
        $pending_orders = Order::where('status',OrderStatus::WORKER_ACCEPTED)
            ->orWhere('status',OrderStatus::WORKER_FINISHED)->get();

        foreach ($rejected_orders as $rejected_order) {
            $r_order_id = $rejected_order->order_id;
            foreach ($pending_orders as $key=>$pending_order) {
                $p_order_id = $pending_order->id;
                if ($p_order_id == $r_order_id) {
                    unset($pending_orders[$key]);
                }
            }
        }

        $new_orders_count = count($pending_orders);
        //$accepted_orders_count = Order::where('driver_id', Auth::id())->count();
        $accepted_orders_count = Order::where('driver_id',Auth::id())
            ->where('status', '!=', OrderStatus::WORKER_PROCESSING)
            ->where('status', '!=', OrderStatus::ORDER_DELIVERED)
            ->where('status', '!=', OrderStatus::WORKER_FINISHED)->count();

        if ($new_orders_count == 0 && $accepted_orders_count == 0) return response()->json(["status" => 0, "errorMessage" => "No orders avalable"]);

        return response()->json([
            'status' => 1,
            "newOrders" => $new_orders_count,
            "acceptedOrders" => $accepted_orders_count
        ]);
    }

    public function driverGetListOfNewOrders() {
        $driver = Auth::user();

        $rejected_orders = RejectedOrders::where('user_id', Auth::id())->get();
        $pending_orders = Order::where('status',OrderStatus::WORKER_ACCEPTED)
            ->orWhere('status',OrderStatus::WORKER_FINISHED)->get();

        foreach ($rejected_orders as $rejected_order) {
            $r_order_id = $rejected_order->order_id;
            foreach ($pending_orders as $key=>$pending_order) {
                $p_order_id = $pending_order->id;
                if ($p_order_id == $r_order_id) {
                    unset($pending_orders[$key]);
                }
            }
        }

        if ($pending_orders->isEmpty()) {return response()->json(["status" => 0, "errorMessage" => "No new orders"]);}

        $destinations = "";

        $result = [];
        $now = new \DateTime();

        foreach ($pending_orders as $order) {
            $takeout_end_datetime = $order->getDateTime('takeout', 'start');
            //dd($takeout_end_datetime);

            $result[] = [
                'jbp' => $order->id,
                'type' => $order->status == OrderStatus::WORKER_ACCEPTED ? 'preuzimanje' : 'dostava',
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
        $new_orders = Order::where('driver_id',Auth::id())
            ->where('status', '!=', OrderStatus::WORKER_PROCESSING)
            ->where('status', '!=', OrderStatus::ORDER_DELIVERED)
            ->where('status', '!=', OrderStatus::WORKER_FINISHED)->get();

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
            $time = 0;
            try {
                $time = $calulated_distances->rows[0]->elements[$counter]->duration->text;
            }
            catch (\Exception $e) {
                $time = 0;
            }
            
            $counter++;
            $result[] = [
                'jbp' => $order->id,
                'time' => $time,
                "fractionFinished" => $order->progress
            ];
        }
        return response()->json([
            'status' => 1,
            'result' => $result
        ]);
    }

    public function driverGetOrderStatus (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->where('driver_id',Auth::id())->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        $client = User::where('id', $order->client_id)->first();
        $worker = User::where('id', $order->worker_id)->first();

        switch ($order->status) {
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT:
                return response()->json([
                    'status' => 1,
                    'orderStatus' => 'preuzimanje',
                    'type' => 'takeout',
                    'target' => [
                        'address' => $order->order_info['address'],
                        'distance' => googleAPIGetDistanceAndDurationFormated(Auth::user()->location, $order->order_info['location']),
                        'name' => $client->name . ' ' . $client->surname,
                        'phone' => $client->phone,
                        'location' => $order->order_info['location']
                    ]
                ]);
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER:
                return response()->json([
                    'status' => 1,
                    'orderStatus' => 'isporuka',
                    'type' => 'takeout',
                    'target' => [
                        'address' => $worker->address,
                        'distance' => googleAPIGetDistanceAndDurationFormated(Auth::user()->location, $worker->location),
                        'name' => $worker->name . ' ' . $worker->surname,
                        'phone' => $worker->phone,
                        'location' => $worker->location
                    ]
                ]);
                break;

            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER:
                return response()->json([
                    'status' => 1,
                    'orderStatus' => 'preuzimanje',
                    'type' => 'delivery',
                    'target' => [
                        'address' => $worker->address,
                        'distance' => googleAPIGetDistanceAndDurationFormated(Auth::user()->location, $worker->location),
                        'name' => $worker->name . ' ' . $worker->surname,
                        'phone' => $worker->phone,
                        'location' => $worker->location
                    ]
                ]);
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT:
                return response()->json([
                    'status' => 1,
                    'orderStatus' => 'isporuka',
                    'type' => 'delivery',
                    'target' => [
                        'address' => $order->order_info['address'],
                        'distance' => googleAPIGetDistanceAndDurationFormated(Auth::user()->location, $order->order_info['location']),
                        'name' => $client->name . ' ' . $client->surname,
                        'phone' => $client->phone,
                        'location' => $order->order_info['location']
                    ]
                ]);
                break;


            case OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT:
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
            if ($order->status == OrderStatus::WORKER_ACCEPTED) {
                $order->status = OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT;
                $order->driver_id = Auth::id();
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            else if ($order->status == OrderStatus::WORKER_FINISHED) {
                $order->status = OrderStatus::DRIVER_TAKEOUT_FROM_WORKER;
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
            $note = (isset($request->note)) ? $request->note : NULL;
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

    // data for new orders
    public function driverNewOrderData(Request $request) {
        $request->validate(['jbp' => 'required', 'driverLatitude' => 'required', 'driverLongitude' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        if ($order->status != OrderStatus::WORKER_ACCEPTED && $order->status != OrderStatus::WORKER_FINISHED) {
            return response()->json(["status" => 0,"errorMessage" => "Narudzbina nedostupna"]);
        }

        $client = User::where("id",$order->client_id)->first();
        $worker = User::where("id",$order->worker_id)->first();

        Auth::user()->location = [
            "latitude" => $request->driverLatitude,
            "longitude" => $request->driverLongitude
        ];
        Auth::user()->save();

        return response()->json([
            'status' => 1,
            'type' => $order->status == OrderStatus::WORKER_ACCEPTED ? 'preuzimanje' : 'dostava',
            'client' => [
                'address' => $order->order_info['address'],
                'distance' => googleAPIGetDistanceAndDurationFormated(Auth::user()->location, $order->order_info['location']),
                'name' => $client->name . " " . $client->surname,
                'phone' => $client->phone
            ],
            'worker' => [
                'address' => $worker->address,
                'distance' => googleAPIGetDistanceAndDurationFormated(Auth::user()->location, $worker->location),
                'name' => $worker->name . " " . $worker->surname,
                'phone' => $worker->phone
            ]
        ]); 
    }

    public function driverSetLoadedAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required', 'isLoaded' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);

        if ($request->isLoaded == TRUE) {
            if ($order->status == OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT || $order->status == OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT) {
                $order->status = OrderStatus::DRIVER_DELIVERY_TO_WORKER;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
        }
        else if ($request->isLoaded == FALSE) {
            if ($order->status == OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT) {
                $order->status = OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT;
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
            if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_WORKER || $order->status == OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER) {
                $order->status = OrderStatus::WORKER_PROCESSING;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            else if ($order->status == OrderStatus::WORKER_PROCESSING) {
                return response()->json([
                    "status" => 1
                ]);
            }
            return response()->json(["status" => 0, "errorMessage" => "Unable to set new status"]);
        }
        else if ($request->isDelivered == FALSE) {
            if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_WORKER) {
                $order->status = OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER;
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
            if ($order->status == OrderStatus::WORKER_FINISHED || $order->status == OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER) {
                $order->status = OrderStatus::DRIVER_DELIVERY_TO_CLIENT;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            return response()->json(["status" => 0,"errorMessage" => "Cannot accept"]);
        }
        else if ($request->isLoaded == FALSE) {
            if ($order->status == OrderStatus::WORKER_FINISHED) {
                $order->status = OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER;
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
            if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_CLIENT || $order->status == OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT) {
                $order->status = OrderStatus::ORDER_DELIVERED;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            return response()->json(["status" => 0, 'errorMessage' => 'Ubable to accept order, status invalid']);
        }
        else if ($request->isDelivered == FALSE) {
            if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_CLIENT) {
                $order->status = OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT;
                $order->save();
                return response()->json([
                    "status" => 1
                ]);
            }
            return response()->json(["status" => 0, 'errorMessage' => 'Ubable to reject order, status invalid']);
        }
        return response()->json(["status" => 0,"errorMessage" => "Order invalid"]);
    }

    public function driverGetRejectReasons () {
        $options = Options::where('name','DRIVER_REJECT_REASONS')->first();

        if (!isset($options)) return response()->json(["status" => 0, 'errorMessage' => 'Nema informacija na serveru']);

        return response()->json([
            "status" => 1,
            "options" => $options->value
        ]);
    }

    public function driverGetUnableToLoadFromClientReasons () {
        $options = Options::where('name','DRIVER_CANT_LOAD_FROM_CLIENT_REASONS')->first();

        if (!isset($options)) return response()->json(["status" => 0, 'errorMessage' => 'Nema informacija na serveru']);

        return response()->json([
            "status" => 1,
            "options" => $options->value
        ]);
    }

    public function driverOrderData(Request $request) {
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

        $now = new \DateTime();

        $api_result = NULL;
        $isClient = TRUE;
        $acceptedStatus = NULL;
        $type = 'preuzimanje';

        $delivery_time = null;

        switch ($order->status) {
            case OrderStatus::WORKER_ACCEPTED :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $client_location);
                $isClient = TRUE;
                $type = 'cekanje';
            break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $client_location);
                $isClient = TRUE;
                $type = 'preuzimanje';
            break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $worker_location);
                $isClient = FALSE;
                $type = 'isporuka';
            break;
            case OrderStatus::WORKER_PROCESSING :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $worker_location);
                $isClient = FALSE;
                $type = 'isporuka';
                $acceptedStatus = TRUE;
            break;
            case OrderStatus::WORKER_FINISHED :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $worker_location);
                $isClient = FALSE;
                $type = 'preuzimanje';
                $acceptedStatus = TRUE;
            break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $worker_location);
                $isClient = FALSE;
                $type = 'isporuka';
            break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $client_location);
                $isClient = TRUE;
                $type = 'isporuka';
                $delivery_time = $this->timeDifference($order->getDateTime('delivery', 'end'),$now);
            break;
            case OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $client_location);
                $isClient = TRUE;
                $type = 'preuzimanje';
                $acceptedStatus = FALSE;
            break;
            case OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $client_location);
                $isClient = TRUE;
                $type = 'isporuka';
                $acceptedStatus = FALSE;
            break;
            case OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $worker_location);
                $isClient = FALSE;
                $type = 'preuzimanje';
                $acceptedStatus = FALSE;
            break;
            case OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER :
                $api_result = googleAPIGetDistanceAndDurationFormated($driver_location, $worker_location);
                $isClient = FALSE;
                $type = 'isporuka';
                $acceptedStatus = FALSE;
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
                "remainingTime" => $delivery_time != null ? $delivery_time : $duration,
                "clientName" => $client->name . " " . $client->surname,
                "clientPhone" => $client->phone,
                "clientAddress" => $client->address . ", " . $client->city,
                "clientLatitude" => $client->location["latitude"],
                "clientLongitude" => $client->location["longitude"],
                "distance" => $distance,
                'acceptedStatus' => $acceptedStatus
            ]); 
        }
        else {
            return response()->json([
                'status' => 1,
                "type" => $type,
                "remainingTime" => $delivery_time != null ? $delivery_time : $duration,
                "clientName" => $worker->name . " " . $client->surname,
                "clientPhone" => $worker->phone,
                "clientAddress" => $worker->address . ", " . $client->city,
                "clientLatitude" => $worker->location["latitude"],
                "clientLongitude" => $worker->location["longitude"],
                "distance" => $distance,
                'acceptedStatus' => $acceptedStatus
            ]); 
        }
    }

    public function driverChangeOrderStatus(Request $request) {
        // type : loadClient -> deliveryWorker -> loadWorker -> deliveryClient
        $request->validate(['jbp' => 'required', 'status' => 'required', 'type' => 'required']);
        $note = isset($request->note) ? $request->note : NULL;

        $order = Order::where('id',$request->jbp)->where('driver_id', Auth::id())->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Unavailable order']);

        switch ($request->type) {
            case 'loadClient':
                if ($request->status == TRUE) {
                    if ($order->status == OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT || $order->status == OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT) {
                        $order->status = OrderStatus::DRIVER_DELIVERY_TO_WORKER;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                }
                else {
                    if ($order->status == OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT) {
                        $order->status = OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                    return response()->json(["status" => 0, "errorMessage" => "Order status invalid"]);
                }
                break;
            case 'deliveryWorker' :
                if ($request->status == TRUE) {
                    if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_WORKER || $order->status == OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER) {
                        $order->status = OrderStatus::WORKER_PROCESSING;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                    else if ($order->status == OrderStatus::WORKER_PROCESSING) {
                        return response()->json(["status" => 1]);
                    }
                    return response()->json(["status" => 0, "errorMessage" => "Unable to set new status"]);
                }
                else {
                    if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_WORKER) {
                        $order->status = OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                    return response()->json(["status" => 0, "errorMessage" => "Unable to reject"]);
                }
                break;
            case 'loadWorker' :
                if ($request->status == TRUE) {
                    if ($order->status == OrderStatus::DRIVER_TAKEOUT_FROM_WORKER || $order->status == OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER) {
                        $order->status = OrderStatus::DRIVER_DELIVERY_TO_CLIENT;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                    return response()->json(["status" => 0,"errorMessage" => "Cannot accept"]);
                }
                else {
                    if ($order->status == OrderStatus::DRIVER_TAKEOUT_FROM_WORKER) {
                        $order->status = OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                    return response()->json(["status" => 0,"errorMessage" => "Cannot reject"]);
                }
                break;
            case 'deliveryClient' :
                if ($request->status == TRUE) {
                    if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_CLIENT || $order->status == OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT) {
                        $order->status = OrderStatus::ORDER_DELIVERED;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                    return response()->json(["status" => 0, 'errorMessage' => 'Ubable to accept order, status invalid']);
                }
                else {
                    if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_CLIENT) {
                        $order->status = OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT;
                        $order->save();
                        return response()->json(["status" => 1]);
                    }
                    return response()->json(["status" => 0, 'errorMessage' => 'Unable to reject order, status invalid']);
                }
                break;
            default :
                return response()->json(["status" => 0, 'errorMessage' => 'Unknown type']);
                break;
        }
        return response()->json(["status" => 0, 'errorMessage' => 'Unable tp change status']);
    }


    /*********************************************************************************************************************************************************
                                                                        
    
    
                                                                            CLIENT
    
    
    
    *********************************************************************************************************************************************************/

    public function clientGetServicePrices(Request $request) {
        $request->validate(['service_id' => 'required']);

        return response()->json([
            'status' => 1,
            'result' => Service::getPrices($request->service_id)
        ]);
    }
    
    
    public function clientAddNewService(Request $request) {
        $request->validate(['service_id' => 'required']);
        $current_order = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_IN_CREATION)->first();
        $service_type = Service::where('id',$request->service_id)->first()->type;

        // For weightable services
        if ($service_type == ServiceType::WEIGHTABLE) {
            if (!isset($request->weight_class_id)) return response()->json(["status" => 0,"errorMessage" => "Polje weight_class_id ne postoji"]);
            if (isset($current_order)) {
                $tmp = $current_order->services;
                array_push($tmp, [
                    "service_id" => $request->service_id,
                    "weight_class_id" => $request->weight_class_id
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
                        "service_id" => $request->service_id,
                        "weight_class_id" => $request->weight_class_id
                    ]],
                    'client_id' => Auth::id(),
                    'status' => OrderStatus::ORDER_IN_CREATION,
                    'price' => 0
                ]);
                $order->calculatePrice();
                return response()->json([
                    "status" => 1
                ]);
            }
        }
        
        // For countable services
        else {
            if (!isset($request->clothes)) return response()->json(["status" => 0,"errorMessage" => "Polje clothes ne postoji"]);
            if (isset($current_order)) {
                $tmp = $current_order->services;
                array_push($tmp, [
                    "service_id" => $request->service_id,
                    "clothes" => $request->clothes
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
                        "service_id" => $request->service_id,
                        "clothes" => $request->clothes
                    ]],
                    'client_id' => Auth::id(),
                    'status' => OrderStatus::ORDER_IN_CREATION,
                    'price' => 0
                ]);
                $order->calculatePrice();
                return response()->json([
                    "status" => 1
                ]);
            }
        }

       
        return response()->json(["status" => 0,"errorMessage" => "Error"]);
    }

    public function clientDeleteService(Request $request) {
        $request->validate(['serviceId' => 'required']);

        $current_order = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_IN_CREATION)->first();
        $service_to_remove = Service::where('id',$request->serviceId)->first();

        if (!isset($service_to_remove)) return response()->json(["status" => 0,"errorMessage" => "Unavailable service name"]);

        if (isset($current_order)) {
            $all_services = $current_order->services;
            foreach ($all_services as $key=>$service) {
                if ($service['service_id'] == $service_to_remove->id) {
                    unset($all_services[$key]);
                }
            }

            if (empty($all_services)) {
                $current_order->delete();
                return response()->json([
                    "status" => 1
                ]);
            }

            $current_order->services = $all_services;
            $current_order->save();
            $current_order->calculatePrice();
           
            return response()->json([
                "status" => 1
            ]);
        }

        return response()->json(["status" => 0,"errorMessage" => "Error"]);
    }

    public function clientGetTotalNuberOfOrders () {
        $orders = Order::where('client_id',Auth::id())
            ->where('status', '!=', OrderStatus::ORDER_IN_CREATION)->get();
            //->where('status', '!=', OrderStatus::ORDER_CREATED)->get();

        foreach ($orders as $key=>$order) {
            $order_rating = OrderRating::where('order_id', $order->id)->first();
            if (isset($order_rating)) {
                unset($orders[$key]);
            }
        }
        
        return response()->json([
            'status' => 1,
            "orders" => count($orders)
        ]);
    }

    public function clientGetOrderList () {
        $orders = Order::where('client_id',Auth::id())
            ->where('status', '!=', OrderStatus::ORDER_IN_CREATION)->get();
            //->where('status', '!=', OrderStatus::ORDER_CREATED)->get();
        foreach ($orders as $key=>$order) {
            $order_rating = OrderRating::where('order_id', $order->id)->first();
            if (isset($order_rating)) {
                unset($orders[$key]);
            }
        }
        $result = [];
        foreach ($orders as $order) {
            /*
            $takeout_datetime = new \DateTime($order->takeout_date["date"] . " " . $order->takeout_date["end_time"]);
            $now = new \DateTime();
            $difference_in_seconds = $takeout_datetime->format('U') - $now->format('U');
            $time = $this->formatTime($difference_in_seconds);
            */
            $result[] = [
                "jbp" => $order->id
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
            case OrderStatus::ORDER_CREATED :
                // kreirano
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "kreirano",
                    "loadDate" => $order->takeout_date["date"],
                    "loadTime" => $order->takeout_date["end_time"],
                    //"deliveryDate" =>  $order->delivery_date["date"],
                    //"deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderStatus::WORKER_ACCEPTED :
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
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT :
                // preuzimanje
                $driver = User::where('id',$order->driver_id)->first();
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "preuzimanje",
                    "remainingTime" => googleAPIGetTimeRemainingFormated($driver->location, $order->order_info['location']),
                    "deliveryDate" =>  $order->delivery_date["date"],
                    "deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER :
                // preuzimanje
                $driver = User::where('id',$order->driver_id)->first();
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "usluga",
                    //"remainingTime" => googleAPIGetTimeRemainingFormated($driver->location, $worker->location),
                    "deliveryDate" =>  $order->delivery_date["date"],
                    "deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderStatus::WORKER_PROCESSING :
                // usluga
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "usluga",
                    "deliveryDate" =>  $order->delivery_date["date"],
                    "deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderStatus::WORKER_FINISHED :
                // usluga
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "usluga",
                    "deliveryDate" =>  $order->delivery_date["date"],
                    "deliveryTime" => $order->delivery_date["end_time"]
                ]);
            break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER :
                // dostava
                $driver = User::where('id',$order->driver_id)->first();
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "dostava",
                    "remainingTime" => $this->formatTime(
                        googleAPIGetTimeRemainingInSeconds($driver->location, $worker->location) + 
                        googleAPIGetTimeRemainingInSeconds($worker->location, $order->order_info['location'])
                    ),
                ]);
            break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT :
                // dostava
                $driver = User::where('id',$order->driver_id)->first();
                return response()->json([
                    'status' => 1,
                    "orderStatus" => "dostava",
                    "remainingTime" => googleAPIGetTimeRemainingFormated($driver->location, $order->order_info['location']),
                ]);
            break;
            case OrderStatus::ORDER_DELIVERED :
                // realizovano
                return response()->json([
                    'status' => 1,
                    'canRate' => OrderRating::where('order_id',$order->id)->where('user_id',Auth::user())->first() == null ? TRUE : FALSE,
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
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT :
                $remaining_time = googleAPIGetTimeRemainingFormated($driver->location, $order->order_info['location']);
            break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER :
                $remaining_time = googleAPIGetTimeRemainingFormated($driver->location, $worker->location);
            break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER :
                $remaining_time = googleAPIGetTimeRemainingFormated($driver->location, $worker->location);
            break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT :
                $remaining_time = googleAPIGetTimeRemainingFormated($driver->location, $order->order_info['location']);
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

        $result = $order->servicesGroupFormated();
        return response()->json([
            'status' => 1,
            "services" => $result['services'],
            "totalPrice" => $result['fullPrice']
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
        $order = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_IN_CREATION)->first();

        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => 'No pending orders']);

        $result = $order->servicesGroupFormated();
        
        return response()->json([
            //'status' => 1,
            "services" => $result['services'],
            "totalPrice" => $result['fullPrice']
        ]);
    }

    public function clientGetTotalCartPrice (Request $request) {
        $order = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_IN_CREATION)->first();

        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Unavailable order']);

        $result = $order->servicesGroupFormated();
        
        return response()->json([
            'status' => 1,
            "totalPrice" => $result['fullPrice']
        ]);
    }

    public function clientGetOrderServices (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id', $request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Unavailable order']);

        $result = [];

        foreach ($order->services as $service) {
            $is_new_service = TRUE;
            $service_obj = Service::where('id',$service['service_id'])->first();
            foreach ($result as $single) {
                if ($single['id'] == $service_obj->id) {
                    $is_new_service = FALSE;
                }
            }
            if ($is_new_service) {
                $result[] = ["name" => $service_obj->name, "id" => $service_obj->id];
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
                'user_id' => Auth::id(),
                'service_ratings' => $request->ratings,
                'note' => $request->note
            ]);
        }
        else {
            OrderRating::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'service_ratings' => $request->ratings
            ]);
        }

        return response()->json([
            "status" => 1
        ]);

    }

    public function clientOrderData (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        $worker = User::where('id',$order->worker_id)->first();
        $driver = User::where('id',$order->driver_id)->first();

        if (!isset($order)) return response()->json(['status' => 0, 'errorMessage' => 'Unavailable order']);
        if (!isset($driver)) return response()->json(['status' => 0, 'errorMessage' => 'Unavailable driver']);
        if (!isset($worker)) return response()->json(['status' => 0, 'errorMessage' => 'Unavailable worker']);

        $remaining_time = NULL;
        $fractal_distance = 0.5;

        switch ($order->status) {
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT :
                $remaining_time = googleAPIGetTimeRemainingFormated($driver->location, $order->order_info['location']);
            break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER :
                //$remaining_time = googleAPIGetTimeRemainingFormated($driver->location, $worker->location);
                $remaining_time = "Nedostupno";
            break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER :
                //$remaining_time = googleAPIGetTimeRemainingFormated($driver->location, $worker->location);
                $remaining_time = $this->formatTime(
                    googleAPIGetTimeRemainingInSeconds($driver->location, $worker->location) + 
                    googleAPIGetTimeRemainingInSeconds($worker->location, $order->order_info['location'])
                );
            break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT :
                $remaining_time = googleAPIGetTimeRemainingFormated($driver->location, $order->order_info['location']);
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
            "fractal" => $fractal_distance,
            "driverLatitude" => $driver->location["latitude"],
            "driverLongitude" => $driver->location["longitude"],
            "clientLatitude" => $order->order_info['location']['latitude'],
            "clientLongitude" => $order->order_info['location']['longitude'],

        ]);
    }

    public function clientEmptyCart () {
        $order = Order::where('client_id', Auth::id())->where('status',OrderStatus::ORDER_IN_CREATION)->first();

        if (!isset($order)) return response()->json(['status' => 0, 'errorMessage' => 'Cart already empty']);

        $order->delete();
        return response()->json(['status' => 1]);
    }

    public function clientGetPrivacy () {
        $privacy = Privacy::where('name','Default')->first();

        if (!isset($privacy)) return response()->json(["status" => 0, 'errorMessage' => 'Nema informacija na serveru']);

        return response()->json([
            "status" => 1,
            "privacy" => $privacy->text
        ]);
    }

    public function clientGetFaqs () {
        $faqs = Faq::all();

        if (!isset($faqs)) return response()->json(["status" => 0, 'errorMessage' => 'Nema informacija na serveru']);

        return response()->json([
            "status" => 1,
            "faq" => $faqs
        ]);
    }


    public function clientGetOrderHistory () {
        $completed_orders = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_DELIVERED)->get();
        $orders = [];

        foreach ($completed_orders as $completed_order) {
            if ($completed_order->status == OrderStatus::ORDER_DELIVERED) {
                $orders[] = [
                    "id" => $completed_order->id,
                    "status" => "Realizovano"
                ];
            }
        }

        return response()->json([
            "status" => 1,
            "orders" => $orders
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
        $pending_orders = Order::where('status', '=', OrderStatus::ORDER_CREATED)->get();

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
        if (isset($order) && $order->status == OrderStatus::ORDER_CREATED) {
            $order->status = OrderStatus::WORKER_ACCEPTED;
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
        if (isset($order) && $order->status == OrderStatus::ORDER_CREATED) {
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
        Order::where('id',$id)->update(['status' => OrderStatus::WORKER_FINISHED]);
        return response()->json([
            "status" => "Success",
            "message" => "Order Ready"
        ]);
    }

    // DRIVER
    public function driverGetPendingOrders() {
        return Order::whereIn('status',[OrderStatus::WORKER_ACCEPTED, OrderStatus::WORKER_FINISHED])->get();
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

        if ($order->status == OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT) {
            $order->status = OrderStatus::DRIVER_DELIVERY_TO_WORKER;
        }
        else if ($order->status == OrderStatus::DRIVER_TAKEOUT_FROM_WORKER) {
            $order->status = OrderStatus::DRIVER_DELIVERY_TO_CLIENT;
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

        if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_WORKER) {
            $order->status = OrderStatus::WORKER_PROCESSING;
        }
        else if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_CLIENT) {
            $order->status = OrderStatus::ORDER_DELIVERED;
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

    public function test(Request $request) {
        if($request->hasFile('image')){
            $filename = $request->image->getClientOriginalName();
            $request->image->storeAs('images/profile','1-' . $filename,'public');
            //Auth()->user()->update(['image'=>$filename]);
            return asset('storage/images/profile/1-' . $filename);
        }
    }
}
