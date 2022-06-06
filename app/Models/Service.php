<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;

abstract class ServiceType {
    const WEIGHTABLE = 0;
    const COUNTABLE = 1;
}

class Service extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = ['name', 'type'];

    public $timestamps = false;

    public static function getPrices($service_id) {
        $prices =[];
        $service = Service::where('id', $service_id)->first();
        if (!isset($service)) return [];

        if ($service->type == ServiceType::WEIGHTABLE) {
            $weights = WeightClass::all();
            foreach ($weights as $weight) {
                $prices[] = [
                    "weight_class_id" => $weight->id,
                    "weight_class_name" => $weight->name,
                    "price" => Price::where('service_id', '=', $service_id)->where('weight_class_id', "=",$weight->id)->first()->value
                ];
            }
            return [
                'service_type' => 'weightable',
                'prices' => $prices
            ];
        }

        else if ($service->type == ServiceType::COUNTABLE) {
            $clothes = ClothesType::all();
            foreach ($clothes as $piece) {
                $prices[] = [
                    "clothes_type_id" => $piece->id,
                    "clothes_type_name" => $piece->name,
                    "price" => Price::where('service_id', '=', $service_id)->where('weight_class_id', "=",$piece->id)->first()->value
                ];
            }
            return [
                'service_type' => 'countable',
                'prices' => $prices
            ];
        }

        return [];
    }

    public function getPictureAttribute() {
        $picture = asset('storage/images/services/' . $this->id. '.png');
        if (!file_exists(public_path('storage/images/services/' . $this->id. '.png'))) {
            return asset('storage/images/services/default.png');
        }
        else {
            return $picture;
        }
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

    public static function boot() {
        parent::boot();

        self::deleting(function($service) {
            $prices = Price::where('service_id', $service->id)->get();
             foreach ($prices as $price) {
                $price->delete();
            }
            
        });
        self::created(function($service) {
            if ($service->type == ServiceType::WEIGHTABLE){
                $weights = WeightClass::all();
                foreach ($weights as $weight) {
                    Price::create([
                        'service_id' => $service->id,
                        'weight_class_id' => $weight->id,
                        'value' => 500 	
                    ]);
                }
            }
            else if ($service->type == ServiceType::COUNTABLE){
                $clothes = ClothesType::all();
                foreach ($clothes as $single_clothes) {
                    Price::create([
                        'service_id' => $service->id,
                        'weight_class_id' => $single_clothes->id,
                        'value' => 500 	
                    ]);
                }
            }
        });
    }

}
