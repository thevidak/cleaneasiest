<?php

namespace Database\Seeders;

use App\Http\Controllers\UserController;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public $admin_permisions = [
        "platform.systems.roles" => TRUE,
        "platform.systems.users" => TRUE,
        "platform.systems.attachment" => TRUE,
        "platform.index" => TRUE
    ];

    public function run()
    {
        DB::table('options')->insert([
            [
                'name' => "WORKER_REJECT_REASONS",
                'value' => json_encode(["Prezauzet", "Razlog 2", "Razlog 3"])
            ],
            [
                'name' => "DRIVER_REJECT_REASONS",
                'value' => json_encode(["Prezauzet", "Lokacija Predaleko", "Razlog 3"])
            ],
            [
                'name' => "DRIVER_CANT_LOAD_FROM_CLIENT_REASONS",
                'value' => json_encode(["Klijent nije na adresi", "Klijent ne odgovara na telefon"])
            ],
        ]);

        DB::table('user_roles')->insert([
            ['id' => 1, 'name' => 'admin'],
            ['id' => 2, 'name' => 'manager'],
            ['id' => 3, 'name' => 'worker'],
            ['id' => 4, 'name' => 'driver'],
            ['id' => 5, 'name' => 'client']
        ]);

        DB::table('services')->insert([
            ['name' => 'Pranje po artiklu', 'type' => 1]
        ]);

        DB::table('services')->insert([
            ['name' => 'Pranje po kg'],
            ['name' => 'Peglanje'],
            ['name' => 'Premium pranje']
        ]);

        DB::table('clothes_types')->insert([
            ['name' => 'Košulja'],
            ['name' => 'Pantalone'],
            ['name' => 'Suknja'],
            ['name' => 'Kaput']
        ]);


        DB::table('weight_classes')->insert([
            ['name' => '0-500g'],
            ['name' => '500g-1kg'],
            ['name' => '1kg-2kg'],
            ['name' => '2kg-5kg']

        ]);

        DB::table('prices')->insert([
            // Pranje
            ['service_id' => 1, 'weight_class_id' => 1, 'value' => 300],
            ['service_id' => 1, 'weight_class_id' => 2, 'value' => 500],
            ['service_id' => 1, 'weight_class_id' => 3, 'value' => 1000],
            ['service_id' => 1, 'weight_class_id' => 4, 'value' => 2000],
            // Peglanje
            ['service_id' => 2, 'weight_class_id' => 1, 'value' => 150],
            ['service_id' => 2, 'weight_class_id' => 2, 'value' => 250],
            ['service_id' => 2, 'weight_class_id' => 3, 'value' => 500],
            ['service_id' => 2, 'weight_class_id' => 4, 'value' => 1000],
            // Susenje
            ['service_id' => 3, 'weight_class_id' => 1, 'value' => 150],
            ['service_id' => 3, 'weight_class_id' => 2, 'value' => 250],
            ['service_id' => 3, 'weight_class_id' => 3, 'value' => 500],
            ['service_id' => 3, 'weight_class_id' => 4, 'value' => 1000],
            // Susenje
            ['service_id' => 4, 'weight_class_id' => 1, 'value' => 150],
            ['service_id' => 4, 'weight_class_id' => 2, 'value' => 250],
            ['service_id' => 4, 'weight_class_id' => 3, 'value' => 500],
            ['service_id' => 4, 'weight_class_id' => 4, 'value' => 1000]
        ]);

        DB::table('users')->insert([
            [
                'name' => 'admin', 
                'email' => 'admin@test.com', 
                'password' => Hash::make('testtest'), 
                'role_id' => 1, 
                'surname' => 'test', 
                'country' => 'Srbija', 
                'address' => 'Cerska 12', 
                'city' => 'Beograd', 
                'municipality' => 'Dorcol', 
                'zip' => '11000', 
                'phone' => '0641234567', 
                'location' => json_encode(["latitude" => 44.813206, "longitude" => 20.429670]),
                'permissions' => json_encode(["platform.systems.roles" => TRUE, "platform.systems.users" => TRUE, "platform.systems.attachment" => TRUE, "platform.index" => TRUE])
            ],
            [
                'name' => 'worker', 
                'email' => 'worker@test.com', 
                'password' => Hash::make('testtest'), 
                'role_id' => 3, 
                'surname' => 'test', 
                'country' => 'Srbija', 
                'address' => 'Cerska 12', 
                'city' => 'Beograd', 
                'municipality' => 'Dorcol', 
                'zip' => '11000', 
                'phone' => '0641234567' , 
                'location' => json_encode(["latitude" => 44.81567284269272, "longitude" => 20.437035670230046]),
                'permissions' => NULL
            ],
            [
                'name' => 'driver', 
                'email' => 'driver@test.com', 
                'password' => Hash::make('testtest'), 
                'role_id' => 4, 'surname' => 'test', 
                'country' => 'Srbija', 
                'country' => 'Srbija',
                'address' => 'Cerska 12', 
                'city' => 'Beograd', 
                'municipality' => 'Dorcol', 
                'zip' => '11000', 
                'phone' => '0641234567' , 
                'location' => json_encode(["latitude" => 44.813206, "longitude" => 20.429670]),
                'permissions' => NULL
            ],
            [
                'name' => 'client', 
                'email' => 'client@test.com', 
                'password' => Hash::make('testtest'), 
                'role_id' => 5, 
                'surname' => 'test', 
                'country' => 'Srbija', 
                'country' => 'Srbija',
                'address' => 'Cerska 12', 
                'city' => 'Beograd', 
                'municipality' => 'Dorcol', 
                'zip' => '11000', 
                'phone' => '0641234567' , 
                'location' => json_encode(["latitude" => 44.81319114386074, "longitude" => 20.460338821181438]),
                'permissions' => NULL
            ]
        ]);

        DB::table('user_profiles')->insert([
            [
                'user_id' => 3,
                'licence_plate' => 'BG-023AB'
            ]
        ]);

        
        DB::table('orders')->insert([
            [
                'status' => 1,
                'client_id' => 4,
                'services' => json_encode([["service_id"=>3,"weight_class_id"=>2],["service_id"=>2,"weight_class_id"=>3]]),
                'payment_info' => json_encode(['type' => 0]),
                'order_info' => json_encode(["address"=>"Bulevar Zorana Djindjica 22, Beograd","note"=>"asdg","location"=>["latitude"=>44.833256,"longitude"=>20.42957]]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
                'worker_id' => 2,
                'driver_id' => 3,
            ],
            [
                'status' => 2,
                'client_id' => 4,
                'services' => json_encode([["service_id"=>3,"weight_class_id"=>2],["service_id"=>2,"weight_class_id"=>3]]),
                'payment_info' => json_encode(['type' => 0]),
                'order_info' => json_encode(["address"=>"Bulevar Zorana Djindjica 22, Beograd","note"=>"asdg","location"=>["latitude"=>44.833256,"longitude"=>20.42957]]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
                'worker_id' => 2,
                'driver_id' => 3,
            ],
            [
                'status' => 3,
                'client_id' => 4,
                'services' => json_encode([["service_id"=>3,"weight_class_id"=>2],["service_id"=>2,"weight_class_id"=>3]]),
                'payment_info' => json_encode(['type' => 0]),
                'order_info' => json_encode(["address"=>"Bulevar Zorana Djindjica 22, Beograd","note"=>"asdg","location"=>["latitude"=>44.833256,"longitude"=>20.42957]]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
                'worker_id' => 2,
                'driver_id' => 3,
            ],
            [
                'status' => 4,
                'client_id' => 4,
                'services' => json_encode([["service_id"=>3,"weight_class_id"=>2],["service_id"=>2,"weight_class_id"=>3]]),
                'payment_info' => json_encode(['type' => 0]),
                'order_info' => json_encode(["address"=>"Bulevar Zorana Djindjica 22, Beograd","note"=>"asdg","location"=>["latitude"=>44.833256,"longitude"=>20.42957]]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
                'worker_id' => 2,
                'driver_id' => 3,
            ],
            [
                'status' => 5,
                'client_id' => 4,
                'services' => json_encode([["service_id"=>3,"weight_class_id"=>2],["service_id"=>2,"weight_class_id"=>3]]),
                'payment_info' => json_encode(['type' => 0]),
                'order_info' => json_encode(["address"=>"Bulevar Zorana Djindjica 22, Beograd","note"=>"asdg","location"=>["latitude"=>44.833256,"longitude"=>20.42957]]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
                'worker_id' => 2,
                'driver_id' => 3,
            ],
            [
                'status' => 6,
                'client_id' => 4,
                'services' => json_encode([["service_id"=>3,"weight_class_id"=>2],["service_id"=>2,"weight_class_id"=>3]]),
                'payment_info' => json_encode(['type' => 0]),
                'order_info' => json_encode(["address"=>"Bulevar Zorana Djindjica 22, Beograd","note"=>"asdg","location"=>["latitude"=>44.833256,"longitude"=>20.42957]]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
                'worker_id' => 2,
                'driver_id' => 3,
            ],
            [
                'status' => 7,
                'client_id' => 4,
                'services' => json_encode([["service_id"=>3,"weight_class_id"=>2],["service_id"=>2,"weight_class_id"=>3]]),
                'payment_info' => json_encode(['type' => 0]),
                'order_info' => json_encode(["address"=>"Bulevar Zorana Djindjica 22, Beograd","note"=>"asdg","location"=>["latitude"=>44.833256,"longitude"=>20.42957]]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
                'worker_id' => 2,
                'driver_id' => 3,
            ],
            [
                'status' => 8,
                'client_id' => 4,
                'services' => json_encode([["service_id"=>3,"weight_class_id"=>2],["service_id"=>2,"weight_class_id"=>3]]),
                'payment_info' => json_encode(['type' => 0]),
                'order_info' => json_encode(["address"=>"Bulevar Zorana Djindjica 22, Beograd","note"=>"asdg","location"=>["latitude"=>44.833256,"longitude"=>20.42957]]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
                'worker_id' => 2,
                'driver_id' => 3,
            ],
            [
                'status' => 9,
                'client_id' => 4,
                'services' => json_encode([["service_id"=>3,"weight_class_id"=>2],["service_id"=>2,"weight_class_id"=>3]]),
                'payment_info' => json_encode(['type' => 0]),
                'order_info' => json_encode(["address"=>"Bulevar Zorana Djindjica 22, Beograd","note"=>"asdg","location"=>["latitude"=>44.833256,"longitude"=>20.42957]]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
                'worker_id' => 2,
                'driver_id' => 3,
            ],
        ]);

    }
}
