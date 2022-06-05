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
