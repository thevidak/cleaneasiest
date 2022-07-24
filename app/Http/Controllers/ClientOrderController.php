<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Node\Query\OrExpr;
use Symfony\Component\Console\Input\Input;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\ServiceType;
use App\Models\Price;
use App\Models\Service;
use App\Models\User;
use App\Models\OrderRating;
use App\Models\Faq;
use App\Models\Privacy;
use App\Models\ClientInfo;
use App\Models\CreditCard;
use App\Models\SubService;
use App\Models\Address;

class ClientOrderController extends Controller{

    public function clientTest(Request $request){
        return Order::where('id',$request->id)->first()->calculatePrice();
    }

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








    public function clientCreateOrder(Request $request) {
        //$request->validate(['payment_info' => 'required', 'takeout_date' => 'required', 'order_info' => 'required']);
        $request->validate(['takeout_date' => 'required']);

        $current_order = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_IN_CREATION)->first();
        if (!isset($current_order)) return response()->json(["status" => 0, "errorMessage" => "Korpa je prazna"]);

        $order_info = $request->order_info;
        $payment_info =  $request->payment_info;

        //payment
        if (isset($payment_info)) {
            // if card payment
            if ($request->payment_info['type'] == 1) {
                if (!isset($request->payment_info['card_id'])) return response()->json(["status" => 0, "errorMessage" => "Kartica nije uneta"]);
                // if card is set we check if user already used this card, if not save it
                $card_number = $request->payment_info['card_id'];
                $card = CreditCard::where('user_id', Auth::id())->where('number', $card_number)->first();
                if (!isset($card)) {
                    $card = CreditCard::create([
                        'user_id' => Auth::id(),
                        'number' => $card_number
                    ]);
                }
                $current_order->card_id = $card->id;
            }
            $current_order->payment_info = $request->payment_info;
        }
        else if (isset($request->card_id)) {
            $card = CreditCard::where('user_id', Auth::id())->where('id', $request->card_id)->first();
            if (!isset($card)) return response()->json(["status" => 0, "errorMessage" => "Kartica nije definisana"]);

            $current_order->card_id = $request->card_id;
            $current_order->payment_info = [
                'type' => 1,
                'card_id' => $card->number
            ];
        }
        else {
            $current_order->payment_info = [
                'type' => 0
            ];
        }

        // address
        if (isset($order_info)) {
            if (!isset($order_info["address"])) return response()->json(["status" => 0, "errorMessage" => "Adresa je prazna"]);
            if (!isset($order_info["location"])) return response()->json(["status" => 0, "errorMessage" => "Lokacija je prazna"]);
            if (!isset($order_info["location"]['latitude'])) return response()->json(["status" => 0, "errorMessage" => "Latituda je prazna"]);
            if (!isset($order_info["location"]['longitude'])) return response()->json(["status" => 0, "errorMessage" => "Longituda je prazna"]);
            $current_order->order_info = $order_info;

            $address_text = $order_info['address'];
            $address = Address::where('user_id', Auth::id())->where('text', $address_text)->first();
            if (!isset($address)) {
                $address = Address::create([
                    'user_id' => Auth::id(),
                    'text' => $order_info["address"],
                    'note' => isset($order_info["note"]) ? $order_info["note"] : NULL,
                    'latitude' => $order_info["location"]['latitude'],
                    'longitude' => $order_info["location"]['longitude']
                ]);
            }
            $current_order->address_id = $address->id;
        }
        else if (isset($request->address_id)) {
            $address = Address::where('user_id', Auth::id())->where('id', $request->address_id)->first();
            if (!isset($address)) return response()->json(["status" => 0, "errorMessage" => "Adresa nije definisana"]);

            $current_order->address_id = $request->address_id;

            $current_order->order_info = [
                "address" => $address->text,
                "note" => $address->note,
                'location' => [
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude
                ]
            ];
        }
        else {
            return response()->json(["status" => 0, "errorMessage" => "Adresa nije definisana"]);
        }
        // phone
        if (isset($request->phone)) {
            $current_order->phone = $request->phone;
        }

        // takeout date
        $current_order->takeout_date = $request->takeout_date;

        $current_order->status = OrderStatus::ORDER_CREATED;
        $current_order->save();

        try {
            \OneSignal::sendNotificationToExternalUser(
                "Nova Narudzbina!",
                ['2'],
                NULL,
                array('jbp' => $current_order->id)
            );
        }
        catch (\Throwable $e) {}

        return response()->json([
            "status" => 1
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
                
                // new subservices 
                SubService::create([
                    "order_id" => $current_order->id,
                    "service_id" => $request->service_id,
                    "subclass_type_id" => $request->weight_class_id
                ]);

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
                
                // new subservices 
                SubService::create([
                    "order_id" => $order->id,
                    "service_id" => $request->service_id,
                    "subclass_type_id" => $request->weight_class_id
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
                ]);
                $current_order->services = $tmp;
                $current_order->save();
                
                // new subservices 
                foreach ($request->clothes as $single_clothes) {
                    if ($single_clothes["count"] > 0) {
                        SubService::create([
                            "order_id" => $current_order->id,
                            "service_id" => $request->service_id,
                            "subclass_type_id" => $single_clothes["clothes_type_id"],
                            "amount" => $single_clothes["count"]
                        ]);
                    }
                }
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
                
                // new subservices 
                foreach ($request->clothes as $single_clothes) {
                    if ($single_clothes["count"] > 0) {
                        SubService::create([
                            "order_id" => $order->id,
                            "service_id" => $request->service_id,
                            "subclass_type_id" => $single_clothes["clothes_type_id"],
                            "amount" => $single_clothes["count"]
                        ]);
                    }
                }
                $order->calculatePrice();
                return response()->json([
                    "status" => 1
                ]);
            }

            
        }

       
        return response()->json(["status" => 0,"errorMessage" => "Greska!"]);
    }

    public function clientDeleteService(Request $request) {
        $request->validate(['serviceId' => 'required']);

        $current_order = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_IN_CREATION)->first();
        $service_to_remove = Service::where('id',$request->serviceId)->first();

        if (!isset($service_to_remove)) return response()->json(["status" => 0,"errorMessage" => "Pogresan ID servisa"]);

        if (isset($current_order)) {
            $all_services = $current_order->services;
            foreach ($all_services as $key=>$service) {
                if ($service['service_id'] == $service_to_remove->id) {
                    unset($all_services[$key]);
                }
            }

            # delete subservices
            foreach ($current_order->subservices as $subservice) {
                if ($subservice->service_id == $service_to_remove->id) {
                    $subservice->delete();
                }
            }

            if (empty($all_services)) {
                $current_order->delete();
                return response()->json([
                    "status" => 1
                ]);
            }

            $current_order->services = array_values($all_services);
            $current_order->save();
            $current_order->calculatePrice();
           
            return response()->json([
                "status" => 1
            ]);
        }

        return response()->json(["status" => 0,"errorMessage" => "Greska"]);
    }


    public function clientDeleteSubservice(Request $request) {
        $request->validate(['service_id' => 'required', 'class_id' => 'required']);

        $current_order = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_IN_CREATION)->first();
        if (!isset($current_order)) return response()->json(["status" => 0,"errorMessage" => "Korpa je prazna"]);

        $subservices = SubService::where('service_id',$request->service_id)
            ->where('order_id', $current_order->id)
            ->where('subclass_type_id', $request->class_id)->get();

        foreach ($subservices as $subservice) {
            $subservice->delete();
        }

        $any_subservice = SubService::where('order_id',$current_order->id)->first();
        if (!isset($any_subservice)) {
            $current_order->delete();
        }

        return response()->json([
            "status" => 1
        ]);
        


        return response()->json(["status" => 0,"errorMessage" => "Greska"]);
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
        if (!isset($order)) return response()->json(["status" => 0,"errorMessage" => "Pogresna Narudzbina"]);

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
                    'canRate' => OrderRating::where('order_id',$order->id)->where('user_id',Auth::id())->first() == null ? TRUE : FALSE,
                    "orderStatus" => "realizovano"
                ]);
            break;
            default :
                return response()->json([
                    "status" => 0,
                    'errorMessage' => 'POgresan status narudzbine'
                ]);
            break;
        }
    }

    public function clientGetPaymentInfo (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id', $request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Nedostupna narudzbina']);

        $result = $order->subserviceGroupedList;
        return response()->json([
            'status' => 1,
            "services" => $result['services'],
            "totalPrice" => $result['fullPrice'],
            'subservices' => $order->subserviceList,
            'takeout_date' => $order->takeout_date
        ]);
    }

    public function clientGetExtraData(Request $request) {
        $client = Auth::user();

        return response()->json([
            'status' => 1,
            "name" => $client->name . " " . $client->surname,
            "phone" => $client->phone,
            "email" => $client->email,
            "address" => $client->address,
            "active_address" => $client->activeAddress,
            "active_card" => $client->activeCard
        ]);
    }

    public function clientGetCurrentPaymentInfo (Request $request) {
        $order = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_IN_CREATION)->first();

        if (!isset($order)) return response()->json([
            'status' => 1,
            "services" => [],
            "totalPrice" => 0,
            "subservices" =>[]
        ]);

        $result = $order->subserviceGroupedList;
        
        return response()->json([
            'status' => 1,
            "services" => $result['services'],
            "totalPrice" => $result['fullPrice'],
            "subservices" =>$order->subserviceList
        ]);
    }

    public function clientGetTotalCartPrice (Request $request) {
        $order = Order::where('client_id', Auth::id())->where('status', OrderStatus::ORDER_IN_CREATION)->first();

        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Nedostupna narudzbina']);

        $result = $order->subserviceGroupedList;
        
        return response()->json([
            'status' => 1,
            "totalPrice" => $result['fullPrice']
        ]);
    }

    public function clientGetOrderServices (Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id', $request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Nedostupna narudzbina']);

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
            "services" => $order->subserviceGroupedList['services'],
            "subservices" => $order->subserviceList
        ]);
    }

    public function clientSetOrderRating(Request $request) {
        $request->validate(['jbp' => 'required', 'ratings' => 'required']);

        $order = Order::where('id', $request->jbp)->where('status',OrderStatus::ORDER_DELIVERED)->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Nedostupna narudzbina']);

        $rating = OrderRating::where('order_id',$request->jbp)->first();
        if (isset($rating)) return response()->json(["status" => 0, 'errorMessage' => 'Vec ocenjeno']);

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

        if (!isset($order)) return response()->json(['status' => 0, 'errorMessage' => 'Nedostupna narudzbina']);
        if (!isset($driver)) return response()->json(['status' => 0, 'errorMessage' => 'Nedostupna narudzbina']);
        if (!isset($worker)) return response()->json(['status' => 0, 'errorMessage' => 'Nedostupna narudzbina']);

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

    public function clientUpdateOrderData(Request $request) {
        $request->validate(['jbp' => 'required']);
        
        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Pogresna porudzbina']);
        if ($order->client_id != Auth::id()) return response()->json(["status" => 0, 'errorMessage' => 'Pogresna porudzbina']);

        if (isset($request->takeout_date)) {
            if (in_array($order->status, [
                OrderStatus::ORDER_IN_CREATION,
                OrderStatus::ORDER_CREATED,
                OrderStatus::WORKER_ACCEPTED,
                OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT
            ])) {
                $order->takeout_date = $request->takeout_date;
                $order->save();
            }

            else {
                return response()->json(["status" => 0, 'errorMessage' => 'Nemoguce promeniti datum']);
            }
            
        }

        return response()->json(['status' => 1]);
    }

    public function clientEmptyCart () {
        $order = Order::where('client_id', Auth::id())->where('status',OrderStatus::ORDER_IN_CREATION)->first();

        if (!isset($order)) return response()->json(['status' => 1]);

        # delete subservices
        foreach ($order->subservices as $subservice) {
            $subservice->delete();
        }
        
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


    public function clientGetOrderMap(Request $request) {
        $request->validate(['jbp' => 'required']);

        $order = Order::where('id',$request->jbp)->first();
        if (!isset($order)) return response()->json(["status" => 0, 'errorMessage' => 'Nedostupna Narudzbina']);

        $driver = User::where('id',$order->driver_id)->first();
        if (!isset($driver)) return response()->json(["status" => 0, 'errorMessage' => 'Nedostupna Narudzbina']);

        return response()->json([
            'status' => 1,
            "driverLatitude" => $driver->location["latitude"],
            "driverLongitude" => $driver->location["longitude"]
        ]);
    }


    /*
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
    */

}
