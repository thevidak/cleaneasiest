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
use App\Models\Address;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Node\Query\OrExpr;
use Symfony\Component\Console\Input\Input;
use Illuminate\Support\Facades\Http;

class WorkerOrderController extends Controller{

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

        if ($new_orders_count == 0 && $accepted_orders_count == 0) {
            return response()->json([
                "status" => 1, 
                "newOrders" => 0, 
                "acceptedOrders" => 0
            ]);
        }

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
        $result = [];
        if ($pending_orders->isEmpty()) return response()->json(["status" => 1, "result" => $result]);

        
        
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

        if (empty($result)) return response()->json(["status" => 1, "result" => $result]);

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

        $result = [];
        if ($new_orders->isEmpty()) return response()->json(["status" => 1, "result" => $result]);

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

        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Nedostupna narudzbina"]);

        $services = $order->services;

        $service_list_prep = [];

        $is_ready = FALSE;
        if ($order->status == OrderStatus::WORKER_FINISHED) {
            $is_ready = TRUE;
        }

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
            "subservices" => $order->subserviceList,
            "deliveryDate" => isset($order->delivery_date) ? $order->delivery_date : NULL,
            "takeoutDate" => isset($order->takeout_date) ? $order->takeout_date : NULL,
            "isReady" => $is_ready
            //"clientDate" => $order->delivery_date["date"],
            //"clientTime" => $order->delivery_date["start_time"]. "-" . $order->delivery_date["end_time"]
        ]);
    }

    public function workerChangeOrderData(Request $request) {
        $request->validate(['jbp' => 'required', 'serviceAccepted' => 'required']);
        $order = Order::where('id',$request->jbp)->first();

        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Nedostupna narudzbina"]);

        if ($request->serviceAccepted == FALSE) {
            if (isset($order) && $order->status == OrderStatus::ORDER_CREATED) {
                $rejected_order = RejectedOrders::where('order_id',$order->id)->where('user_id', Auth::id())->first();
                if (isset($rejected_order)) {
                    return response()->json(["status" => 0, "errorMessage" => "Narudzbina vec odbijena"]);
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
                return response()->json(["status" => 0, "errorMessage" => "Narudzbina nije odbijena"]);
            }
        }
        else {
            if ($order->status == OrderStatus::ORDER_CREATED) {
                $delivery_date = NULL;
                if (!isset($request->deliveryDate)) {
                    //return response()->json(["status" => 0, "errorMessage" => "Datum dostave nije izabran."]);
                    $delivery_date = [
                        'date' => \DateTime::createFromFormat('d-m-Y', $order->takeout_date['date'])->add(\DateInterval::createFromDateString('1 day'))->format('d-m-Y'),
                        'start_time' =>$order->takeout_date['start_time'],
                        'end_time' =>$order->takeout_date['end_time'], 
                    ];
                }
                else {
                    $delivery_date = $request->deliveryDate;
                }
                $order->status = OrderStatus::WORKER_ACCEPTED;
                $order->worker_id = Auth::id();
                $order->delivery_date = $delivery_date;
                $order->save();

                try {
                    \OneSignal::sendNotificationToExternalUser(
                        "Porudzbina prihvacena!",
                        [(string)$order->client_id],
                        NULL,
                        array('jbp' => $order->id, 'type' => 'ORDER_ACCEPTED')
                    );
                }
                catch (\Throwable $e) {} 
                /*
                try {
                    \OneSignal::sendNotificationToExternalUser(
                        "Nova Narudzbina!",
                        ['3'],
                        NULL,
                        array('jbp' => $current_order->id)
                    );
                }
                catch (\Throwable $e) {}
                */
                return response()->json([
                    "status" => 1
                ]);
            }
            else {
                return response()->json(["status" => 0, "errorMessage" => "Porudzbina je vec prihvacena"]);
            }
        }
    }


    public function workerLoadAcceptedOrderData(Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Nedostupna narudzbina"]);
        
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
        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Nedostupna narudzbina"]);

        $driver = $order->driver_id == NULL ? NULL : User::where('id',$order->driver_id)->first();
        if (!isset($driver)) return response()->json(["status" => 0,"errorMessage" => "Narudzbina nema dodeljenog vozaca"]);

        return response()->json([
            'status' => 1,
            "driverLatitude" => $driver->location['latitude'],
            "driverLongitude" => $driver->location['longitude']
        ]);
    }

    public function workerSetLoadedAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required', 'isLoaded' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Nedostupna narudzbina"]);

        if ($request->isLoaded == TRUE) {
            if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_WORKER) {
                $order->status = OrderStatus::WORKER_PROCESSING;
                $order->save();
                try {
                    \OneSignal::sendNotificationToExternalUser(
                        "Porudzbina dostavljena serviseru!",
                        [(string)$order->client_id],
                        NULL,
                        array('jbp' => $order->id)
                    );
                }
                catch (\Throwable $e) {}

                return response()->json([
                    "status" => 1
                ]);
            }
        }

        return response()->json(["status" => 0, "errorMessage" => "Greska"]); 
    }

    public function workerSetDeliveredAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required', 'isDelivered' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Nedostupna narudzbina"]);

        if ($request->isDelivered == TRUE) {
            if ($order->status == OrderStatus::WORKER_FINISHED) {
                $order->status = OrderStatus::DRIVER_TAKEOUT_FROM_WORKER;
                $order->save();

                try {
                    \OneSignal::sendNotificationToExternalUser(
                        "Vozac preuzima porudzbinu od servisera!",
                        [(string)$order->client_id],
                        NULL,
                        array('jbp' => $order->id)
                    );
                }
                catch (\Throwable $e) {}  

                return response()->json([
                    "status" => 1
                ]);
            }
        }

        return response()->json([
            "status" => 0,
            "errorMessage" => "Greska"
        ]); 
    }

    public function workerSetReadydAcceptedOrder (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Nedostupna narudzbina"]);

        if ($order->status == OrderStatus::WORKER_PROCESSING) {
            $order->status = OrderStatus::WORKER_FINISHED;

            try {
                \OneSignal::sendNotificationToExternalUser(
                    "Serviser zavrsio!",
                    [(string)$order->client_id],
                    NULL,
                    array('jbp' => $order->id)
                );
            }
            catch (\Throwable $e) {}  

            try {
                \OneSignal::sendNotificationToExternalUser(
                    "Nova Narudzbina!",
                    ['3'],
                    NULL,
                    array('jbp' => $current_order->id)
                );
            }
            catch (\Throwable $e) {}

            $order->save();
            return response()->json([
                "status" => 1
            ]);
        }

        return response()->json(["status" => 0, "errorMessage" => "Greska"]); 
    }

    public function workerGetOrderStatus (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Narudzbina nedostupna"]);

        $order_address = Address::where('id', $order->address_id)->first();
        $order_location = [
            'latitude' => $order_address->latitude,
            'longitude' => $order_address->longitude
        ];

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
                    "serviceDate" => $order->getDateTime('takeout', 'end')->format("d-m-Y"),
                    "serviceTime" => $order->getDateTime('takeout', 'end')->format("H:i"),
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("d-m-Y"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'preuzimanje',
                    //"remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),  
                    "remainingTime" =>$this->formatTime(
                        googleAPIGetTimeRemainingInSeconds($driver->location, $order_location) + 
                        googleAPIGetTimeRemainingInSeconds($driver->location, Auth::user()->location)),
                    //"serviceDate" => $order->getDateTime('takeout', 'end')->format("Y-m-d"),
                    //"serviceTime" => $order->getDateTime('takeout', 'end')->format("H:i"),
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("d-m-Y"),
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
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("d-m-Y"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderStatus::WORKER_PROCESSING:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'usluga',
                    "remainingTime" => $this->timeDifference($order->getDateTime('delivery', 'end'),$now),
                    //"remainingTime" => $date_limit,
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("d-m-Y"),
                    "deliveryTime" => $order->getDateTime('delivery', 'end')->format("H:i"),
                ]);
                break;
            case OrderStatus::WORKER_FINISHED:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'usluga',
                    "serviceFinished" => true,
                    "remainingTime" => $this->timeDifference($order->getDateTime('delivery', 'end'),$now),
                    //"remainingTime" => 0,
                    "deliveryDate" => $order->getDateTime('delivery', 'end')->format("d-m-Y"),
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
                        googleAPIGetTimeRemainingInSeconds(Auth::user()->location, $order_location)),
                ]);
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'dostava',
                    //"remainingTime" => $this->timeDifference($order->getDateTime('takeout', 'end'),$now),
                    "remainingTime" =>$this->formatTime(
                        googleAPIGetTimeRemainingInSeconds($driver->location, $order_location)),
                ]);
                break;
            case OrderStatus::ORDER_DELIVERED:
                return response()->json([
                    "status" => 1,
                    "orderStatus" => 'realizovano'
                ]);
                break;
            default:
                return response()->json(["status" => 0, "errorMessage" => "Status Narudzbine nedostupan"]);
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

        $order = Order::where('id',$request->jbp)->where('worker_id',Auth::id())->first();
        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Narudzbina nedostupna"]);
        
        $remaining_time = 0;
        $driver = $order->driver_id == NULL ? NULL : User::where('id',$order->driver_id)->first();

        $worker_location = Auth::user()->location;
        if (!isset($worker_location)) $worker_location = ['latitude' => Auth::user()->activeAddress->latitude, 'longitude' => Auth::user()->activeAddress->longitude];
        $driver_location = isset($driver) ? $driver->location : NULL;
        //$client_location = User::where('id',$order->client_id)->first()->location;
        $order_address = Address::where('id', $order->address_id)->first();
        $client_location = [
            'latitude' => $order_address->latitude,
            'longitude' => $order_address->longitude
        ];

        $show_prompt = FALSE;
        $order_status = NULL;

        // this api is called only during delivery and takeout
        switch ($order->status) {
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT:
                //$remaining_time = $this->calculateDistance($driver_location,$client_location)->value + 300 + $this->calculateDistance($client_location,$worker_location)->value;
                $remaining_time = $this->formatTime(
                    googleAPIGetTimeRemainingInSeconds($driver_location,  $client_location) + 
                    googleAPIGetTimeRemainingInSeconds($driver_location, $worker_location));
                $order_status = 'preuzimanje';
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER:
               $remaining_time =$this->formatTime(googleAPIGetTimeRemainingInSeconds($driver_location, $worker_location));
                $order_status = 'preuzimanje';
                $show_prompt = TRUE;
                break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER:
                # time from driver to worker
                //$remaining_time = $this->calculateDistance($driver_location,$worker_location)->value;
                $remaining_time = $this->formatTime(
                    googleAPIGetTimeRemainingInSeconds($driver_location, $worker_location) + 
                    googleAPIGetTimeRemainingInSeconds($worker_location, $client_location));
                $order_status = 'dostava';
                $show_prompt = TRUE;
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT:
                # time from driver to client
                //$remaining_time = $this->calculateDistance($driver_location,$client_location)->value;
                $remaining_time = $this->formatTime(
                    googleAPIGetTimeRemainingInSeconds($driver_location, $client_location));
                $order_status = 'dostava';
                break;

            case OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT:
                # time unavailable
            case OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT:
                # time unavailable
            case OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER:
                # time unavailable
            case OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER:
                # time unavailable
                break;
            default:
                # nothing
                return response()->json(["status" => 0, "errorMessage" => "Porudzbina nije u tranzitu"]);
                break;
        }
        return response()->json([
            "status" => 1,
            "orderStatus" => $order_status,
            "remainingTime" => $remaining_time,
            "driverName" => isset($driver) ? $driver->name . " " . $driver->surname : NULL,
            "driverPhone" => isset($driver) ? $driver->phone : NULL,
            "licencePlate" => isset($driver) ? $driver->profile->licence_plate : NULL,
            "showPrompt" => $show_prompt,
            "driverLatitude" => isset($driver) ? $driver->location['latitude'] : NULL,
            "driverLongitude" => isset($driver) ? $driver->location['longitude'] : NULL,
            "workerLatitude" => $worker_location['latitude'],
            "workerLongitude" => $worker_location['longitude']
        ]);
    }


    public function workerChangeOrderStatus (Request $request) {
        $request->validate(['jbp' => 'required', 'status' => 'required', 'type' => 'required']);
        $note = isset($request->note) ? $request->note : NULL;

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, "errorMessage" => "Nedostupna narudzbina"]);

        switch ($request->type) {
            case 'load' :
                // order loaded from driver
                if ($request->status == TRUE) {
                    if ($order->status == OrderStatus::DRIVER_DELIVERY_TO_WORKER) {
                        
                        $order->status = OrderStatus::WORKER_PROCESSING;
                        $order->save();

                        try {
                            \OneSignal::sendNotificationToExternalUser(
                                "Dostavljeno serviseru!",
                                [(string)$order->client_id],
                                NULL,
                                array('jbp' => $order->id)
                            );
                        }
                        catch (\Throwable $e) {}  

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

                        try {
                            \OneSignal::sendNotificationToExternalUser(
                                "Serviser zavrsio!",
                                [(string)$order->client_id],
                                NULL,
                                array('jbp' => $order->id)
                            );
                        }
                        catch (\Throwable $e) {}  

                        try {
                            \OneSignal::sendNotificationToExternalUser(
                                "Nova Narudzbina!",
                                ['3'],
                                NULL,
                                array('jbp' => $current_order->id)
                            );
                        }
                        catch (\Throwable $e) {}

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

                        try {
                            \OneSignal::sendNotificationToExternalUser(
                                "Vozac preuzima od servisera!",
                                [(string)$order->client_id],
                                NULL,
                                array('jbp' => $order->id)
                            );
                        }
                        catch (\Throwable $e) {}  

                        return response()->json(["status" => 1]);
                    }
                }
                else {
                    
                }
                break;
            default :
                // unknown type
                return response()->json(["status" => 0, "errorMessage" => "Nepoznat Tip"]); 
                break;
        }

        return response()->json(["status" => 0, "errorMessage" => "Greska"]); 
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

    public function workerGetOrderHistory () {
        $completed_orders = Order::where('worker_id', Auth::id())->where('status', OrderStatus::ORDER_DELIVERED)->get();
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

    /*
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
    */
    /*
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
    */
    /*
    public function workerOrderReady (Request $request, $id) {
        Order::where('id',$id)->update(['status' => OrderStatus::WORKER_FINISHED]);
        return response()->json([
            "status" => "Success",
            "message" => "Order Ready"
        ]);
    }
    */
    
}
