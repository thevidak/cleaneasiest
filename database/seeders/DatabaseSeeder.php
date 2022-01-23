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
            ['name' => 'admin', 'email' => 'admin@test.com', 'password' => Hash::make('testtest'), 'role_id' => 1, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567', 'location' => json_encode(["latitude" => 44.813206, "longitude" => 20.429670])],
            //['name' => 'manager', 'email' => 'manager@test.com', 'password' => Hash::make('testtest'), 'role_id' => 2, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567' , 'location' => json_encode(["latitude" => 44.813206, "longitude" => 20.429670])],
            ['name' => 'worker', 'email' => 'worker@test.com', 'password' => Hash::make('testtest'), 'role_id' => 3, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567' , 'location' => json_encode(["latitude" => 44.81567284269272, "longitude" => 20.437035670230046])],
            ['name' => 'driver', 'email' => 'driver@test.com', 'password' => Hash::make('testtest'), 'role_id' => 4, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567' , 'location' => json_encode(["latitude" => 44.813206, "longitude" => 20.429670])],
            ['name' => 'client', 'email' => 'client@test.com', 'password' => Hash::make('testtest'), 'role_id' => 5, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567' , 'location' => json_encode(["latitude" => 44.81319114386074, "longitude" => 20.460338821181438])],
            //['name' => 'worker2', 'email' => 'worker2@test.com', 'password' => Hash::make('testtest'), 'role_id' => 3, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567' , 'location' => json_encode(["latitude" => 44.813206, "longitude" => 20.429670])]
        ]);

        DB::table('shops')->insert([
            ['name' => 'Test shop 1', 'description' => "Just a test shop", 'user_id' => 3],
            ['name' => 'Second 1', 'description' => "THe best shop", 'user_id' => 4],
        ]);

    }
}
