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

class DriverOrderController extends Controller{
    
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
                    "deliveryDate" => $order->getDateTime('takeout', 'end')->format("d-m-Y"),
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



    
}
