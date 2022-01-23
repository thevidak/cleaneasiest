<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public static function getPrices($service_ids) {
        $weights = WeightClass::all();

        $prices =[];

        foreach ($weights as $weight) {
            $price = 0;

            foreach ($service_ids as $service_id) {
                $price+= Price::where('service_id', '=', $service_id)->where('weight_class_id', "=",$weight->id)->first()->value;
            }

            $prices[] = [
                "weight_class_id" => $weight->id,
                "weight_class_name" => $weight->name,
                "price" => $price
            ];
        }
        return $prices;
    }

    public static function calculatePrices($services) {
        $weights = WeightClass::all();

        $price = 0;

        foreach ($services as $service) {
            $weight = WeightClass::where('name','=',$service["weight"])->first();
            foreach ($service["service_ids"] as $service_id) {
                $price += Price::where('service_id', '=', $service_id)->where('weight_class_id', "=",$weight->id)->first()->value;
            }
        }

        return $price;
    }

}
