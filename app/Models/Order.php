<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Users;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Price;
use App\Models\SubService;
use App\Models\ClothesType;

use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

abstract class OrderStatus {
    const ORDER_IN_CREATION = 0;
    const ORDER_CREATED = 1;
    const WORKER_ACCEPTED = 2;
    const DRIVER_TAKEOUT_FROM_CLIENT = 3;
    const DRIVER_DELIVERY_TO_WORKER = 4;
    const WORKER_PROCESSING = 5;
    const WORKER_FINISHED = 6;
    const DRIVER_TAKEOUT_FROM_WORKER = 7;
    const DRIVER_DELIVERY_TO_CLIENT = 8;
    const ORDER_DELIVERED = 9;

    // special statuses
    const DRIVER_UNABLE_TO_LOAD_FROM_CLIENT = 10;
    const DRIVER_UNABLE_TO_DELIVER_TO_CLIENT = 11;
    const DRIVER_UNABLE_TO_LOAD_FROM_WORKER = 12;
    const DRIVER_UNABLE_TO_DELIVER_TO_WORKER = 13;
}

class Order extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = ['status', 'client_id', 'services', 'client_id', 'order_info', 'payment_info', 'takeout_date', 'delivery_date', 'price', 'address_id', 'card_id'];

    protected $casts = [
        'services' => 'array',
        'order_info' => 'array',
        'payment_info' => 'array',
        'takeout_date' => 'array',
        'delivery_date' => 'array'
    ];

    protected $appends = ['locations'];

    protected $allowedFilters = [

    ];

    protected $allowedSorts = [
        'created_at',
        'updated_at'
    ];



    public function subservices() {
        return $this->hasMany(SubService::class);
    }

    // calculate price for the existing order, using info from the services field and saves it
    public function calculatePrice() {
        $price = 0;
        $subservices = $this->subservices;
        foreach ($subservices as $subservice) {
            if (isset($subservice->amount)) {
                $price += Price::where('service_id', $subservice->service_id)->where('weight_class_id', $subservice->subclass_type_id)->first()->value * $subservice->amount;
            }
            else {
                $price += Price::where('service_id', $subservice->service_id)->where('weight_class_id', $subservice->subclass_type_id)->first()->value * 1;
            }
        }
        $this->price = $price;
        $this->save();
        return $price;
    }

    // returns formated dates (takout or delivery) from orders
    // arguments: type : takout/delivery, flag : start/end 
    // return DateTime object
    public function getDateTime($type, $flag) {
        if ($type == 'takeout') {
            if(!isset($this->takeout_date)) return;
            if ($flag == 'start') {
                //$datetime = new \DateTime($this->takeout_date["date"] . " " . $this->takeout_date["start_time"]);
                $datetime = \DateTime::createFromFormat('d-m-Y H:i', $this->takeout_date["date"] . " " . $this->takeout_date["start_time"]);
            }
            else if ($flag == 'end') {
                //$datetime =  new \DateTime($this->takeout_date["date"] . " " . $this->takeout_date["end_time"]);
                $datetime = \DateTime::createFromFormat('d-m-Y H:i', $this->takeout_date["date"] . " " . $this->takeout_date["end_time"]);
            }
        }
        else if ($type == 'delivery') {
            if(!isset($this->delivery_date)) return;
            if ($flag == 'start') {
                //$datetime =  new \DateTime($this->delivery_date["date"] . " " . $this->delivery_date["start_time"]);
                $datetime = \DateTime::createFromFormat('d-m-Y H:i', $this->delivery_date["date"] . " " . $this->takeout_date["start_time"]);
            }
            else if ($flag == 'end') {
                //$datetime =  new \DateTime($this->delivery_date["date"] . " " . $this->delivery_date["end_time"]);
                $datetime = \DateTime::createFromFormat('d-m-Y H:i', $this->delivery_date["date"] . " " . $this->takeout_date["end_time"]);
            }
        }

        return $datetime;
    }

    /*********************************************************************************************************************************************************
                                                                            ATTRIBUTES
    *********************************************************************************************************************************************************/

    public function getProgressAttribute() {
        $progress = 0;
        switch ($this->status) {
            case OrderStatus::ORDER_IN_CREATION :
                $progress = 0.1;
                break;
            case OrderStatus::ORDER_CREATED :
                $progress = 0.2;
                break;
            case OrderStatus::WORKER_ACCEPTED :
                $progress = 0.3;
                break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT :
                $progress = 0.4;
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER :
                $progress = 0.5;
                break;
            case OrderStatus::WORKER_PROCESSING :
                $progress = 0.6;
                break;
            case OrderStatus::WORKER_FINISHED :
                $progress = 0.7;
                break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER :
                $progress = 0.8;
                break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT :
                $progress = 0.9;
                break;
            case OrderStatus::ORDER_DELIVERED :
                $progress = 1;
                break;
            case OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT :
                $progress = 0.4;
                break;
            case OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT :
                $progress = 0.9;
                break;
            case OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER :
                $progress = 0.8;
                break;
            case OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER :
                $progress = 0.5;
                break;
            default :
                $progress = 0;
                break;
        }
        return $progress;
    }

    public function getLocationsAttribute() {
        $locations = [];

        if (isset($this->client_id)){
            $locations['client_location'] = User::where('id', $this->client_id)->first()->location;
        }
        if (isset($this->driver_id)){
            $locations['driver_location'] = User::where('id', $this->driver_id)->first()->location;
        }
        if (isset($this->worker_id)){
            $locations['worker_location'] = User::where('id', $this->worker_id)->first()->location;
        }

        return $locations;
    }

    public function getSubserviceListAttribute() {
        $result = [];
        $countable = [];
        foreach ($this->subservices as $subservice){
            $service = Service::where('id',$subservice['service_id'])->first();
            $price = Price::where('service_id',$service->id)->where('weight_class_id', $subservice['subclass_type_id'])->first()->value;
            // if weightable
            if ($service->type == 0) {
                $result[] = [
                    'service_id' => $service->id,
                    'service_type' => "weightable",
                    'service_name' => $service->name,
                    'subservice_id' => $subservice['id'],
                    'amount' => WeightClass::where('id', $subservice['subclass_type_id'])->first()->name,
                    'price' => $price * 1  
                ];
            }
            else {
                $countable[$service->id][] = [
                    'subservice_id' => $subservice['id'],
                    'item' => ClothesType::where('id', $subservice['subclass_type_id'])->first()->name,
                    'amount' => $subservice['amount'],
                    'price' => $price * $subservice['amount'] 
                ];
            }   
        }
        $subresult = [];
        foreach ($countable as $key=>$c) {
            $tmp_price = 0;
            foreach ($c as $p) {
                $tmp_price += $p['price'];
            }
            $result[] = [
                'service_id' => $key,
                'service_type' => "countable",
                'service_name' => $service->name,
                'price' => $tmp_price,
                'items' => $c
            ];
        }
        return $result;
    }

    public function getSubserviceGroupedListAttribute () {
        $subservices_grouped = [];
        $result = [];
        
        foreach ($this->subservices as $subservice) {
            $service = Service::where('id',$subservice->service_id)->first();
            $local_price = Price::where('service_id',$service->id)->where('weight_class_id', $subservice->subclass_type_id)->first()->value;
            if ($service->type == 0) {
                $subclass_name = WeightClass::where('id',$subservice->subclass_type_id)->first()->name;
                if (!isset($subservices_grouped[$subservice->service_id])) {
                    $subservices_grouped[$subservice->service_id] = [
                        'price' => 0,
                        'items' => []
                    ];
                }

                $subservices_grouped[$subservice->service_id]['price'] += $local_price * 1;

                $existing = FALSE;

                foreach ($subservices_grouped[$subservice->service_id]['items'] as $key=>$item) {
                    if ($item['class_id'] == $subservice->subclass_type_id) {
                        $subservices_grouped[$subservice->service_id]['items'][$key]['amount'] ++;
                        $existing = TRUE;
                        break;
                    }
                }

                if (!$existing) {
                    $subservices_grouped[$subservice->service_id]['items'][] = [
                        'class_id' => $subservice->subclass_type_id,
                        'class_name' => $subclass_name,
                        'price' => $local_price,
                        'amount' => 1
                    ];
                }

            }
            
            else {
                $subclass_name = ClothesType::where('id',$subservice->subclass_type_id)->first()->name;
                if (!isset($subservices_grouped[$subservice->service_id])) {
                    $subservices_grouped[$subservice->service_id] = [
                        'price' => 0,
                        'items' => []
                    ];
                }

                $subservices_grouped[$subservice->service_id]['price'] += $local_price * $subservice->amount;

                $existing = FALSE;

                foreach ($subservices_grouped[$subservice->service_id]['items'] as $key=>$item) {
                    if ($item['class_id'] == $subservice->subclass_type_id) {
                        $subservices_grouped[$subservice->service_id]['items'][$key]['amount'] += $subservice->amount;
                        $existing = TRUE;
                        break;
                    }
                }

                if (!$existing) {
                    $subservices_grouped[$subservice->service_id]['items'][] = [
                        'class_id' => $subservice->subclass_type_id,
                        'class_name' => $subclass_name,
                        'price' => $local_price,
                        'amount' => $subservice->amount
                    ];
                }
                
            }
            
        }

        $full_price = 0;
        foreach ($subservices_grouped as $key=>$value) {
            $full_price += $value['price'];
            $result[] = [
                "id" => $key,
                "type" => Service::where('id',$key)->first()->name,
                "price" => $value['price'],
                "items" => $value['items']
            ];
        }


        return [
            "services" => $result,
            "fullPrice" => $full_price,
        ];
    }



/*
    public function client() {
        return $this->hasMany(User::class, 'id', 'client_id');
    }
*/


    public function getClientNameAttribute () {
        $client = User::where('id', $this->client_id)->first();
        return $client->name . " " . $client->surname;
    }
    public function getStatusFormatedAttribute () {
        switch ($this->status) {
            case OrderStatus::ORDER_IN_CREATION :
                return 'Narudzbina se kreira';
            break;
            case OrderStatus::ORDER_CREATED :
                return 'Narudzbina kreirana';
            break;
            case OrderStatus::WORKER_ACCEPTED :
                return 'Serviser prihvatio';
            break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_CLIENT :
                return 'Vozac preuzeo od klijenta';
            break;
            case OrderStatus::DRIVER_DELIVERY_TO_WORKER :
                return 'Vozac prevozi do servisera';
            break;
            case OrderStatus::WORKER_PROCESSING :
                return 'Servis';
            break;
            case OrderStatus::WORKER_FINISHED :
                return 'Serviser zavrsio';
            break;
            case OrderStatus::DRIVER_TAKEOUT_FROM_WORKER :
                return 'Vozac preuzima od servisera';
            break;
            case OrderStatus::DRIVER_DELIVERY_TO_CLIENT :
                return 'Vozac dostavlja do klijenta';
            break;
            case OrderStatus::ORDER_DELIVERED :
                return 'Narudzbina zavrsena';
            break;
            default :
            return 'Nepoznat status';
            break;
        }
    }

    public function getCreatedAtFormatedAttribute () {
        return $this->created_at;
    }

    public function getServiceTextAttribute () {
        return '<div>123</div>';
        return json_encode($this->services);
    }
}
