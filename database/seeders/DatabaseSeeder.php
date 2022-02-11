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
        DB::table('user_roles')->insert([
            ['id' => 1, 'name' => 'admin'],
            ['id' => 2, 'name' => 'manager'],
            ['id' => 3, 'name' => 'worker'],
            ['id' => 4, 'name' => 'driver'],
            ['id' => 5, 'name' => 'client']
        ]);

        DB::table('services')->insert([
            ['name' => 'Pranje', 'description' => "Just a test service"],
            ['name' => 'Peglanje', 'description' => "Just a test service"],
            ['name' => 'Susenje', 'description' => "Just a test service"]
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
            ['service_id' => 3, 'weight_class_id' => 4, 'value' => 1000]
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
                'services' => json_encode([["service_ids" => [1, 2], "weight_class_id" => 2, 'note'=>'just a note']]),
                'payment_info' => json_encode(['type' => 0]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+3 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
            ],
            [
                'status' => 1,
                'client_id' => 4,
                'services' => json_encode([["service_ids" => [1, 2, 3], "weight_class_id" => 1, 'note'=>'just a note']]),
                'payment_info' => json_encode(['type' => 0]),
                'takeout_date' => json_encode(["date" => date("Y-m-d",strtotime("now")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("Y-m-d",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 600,
            ]
        ]);
    }
}
