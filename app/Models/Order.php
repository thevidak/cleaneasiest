<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['status', 'client_id', 'services', 'client_id', 'order_info', 'paymment_info', 'takeout_date', 'delivery_date', 'price'];

    protected $casts = [
        'services' => 'array',
        'order_info' => 'array',
        'paymment_info' => 'array',
        'takeout_date' => 'array',
        'delivery_date' => 'array'
    ];

    protected $appends = ['locations'];

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
}
