<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Users;

use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

abstract class OrderdStatus {
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

    protected $fillable = ['status', 'client_id', 'services', 'client_id', 'order_info', 'payment_info', 'takeout_date', 'delivery_date', 'price'];

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

    // calculate price for the existing order, using info from the services field
    public function calculatePrice() {
        $services = $this->services;
        $price = 0;

        foreach ($services as $service_group) {
            foreach ($service_group["service_ids"] as $service_id) {
                $service_price = Price::where("service_id", $service_id)->where("weight_class_id", $service_group["weight_class_id"])->first()->value;
                $price += $service_price;
            }
        }

        $this->price = $price;
        $this->save();
        return $price;
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

    public function getDateTime($type, $flag) {
        if ($type == 'takeout') {
            if ($flag == 'start') {
                $datetime = new \DateTime($this->takeout_date["date"] . " " . $this->takeout_date["start_time"]);
            }
            else if ($flag == 'end') {
                $datetime =  new \DateTime($this->takeout_date["date"] . " " . $this->takeout_date["end_time"]);
            }
        }
        else if ($type == 'delivery') {
            if ($flag == 'start') {
                $datetime =  new \DateTime($this->delivery_date["date"] . " " . $this->delivery_date["start_time"]);
            }
            else if ($flag == 'end') {
                $datetime =  new \DateTime($this->delivery_date["date"] . " " . $this->delivery_date["end_time"]);
            }
        }

        return $datetime;
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
            case OrderdStatus::ORDER_IN_CREATION :
                return 'Narudzbina se kreira';
            break;
            case OrderdStatus::ORDER_CREATED :
                return 'Narudzbina kreirana';
            break;
            case OrderdStatus::WORKER_ACCEPTED :
                return 'Serviser prihvatio';
            break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_CLIENT :
                return 'Vozac preuzeo od klijenta';
            break;
            case OrderdStatus::DRIVER_DELIVERY_TO_WORKER :
                return 'Vozac prevozi do servisera';
            break;
            case OrderdStatus::WORKER_PROCESSING :
                return 'Servis';
            break;
            case OrderdStatus::WORKER_FINISHED :
                return 'Serviser zavrsio';
            break;
            case OrderdStatus::DRIVER_TAKEOUT_FROM_WORKER :
                return 'Vozac preuzima od servisera';
            break;
            case OrderdStatus::DRIVER_DELIVERY_TO_CLIENT :
                return 'Vozac dostavlja do klijenta';
            break;
            case OrderdStatus::ORDER_DELIVERED :
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
