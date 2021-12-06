<?php

namespace Database\Seeders;

use App\Http\Controllers\UserController;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin'],
            ['id' => 2, 'name' => 'manager'],
            ['id' => 3, 'name' => 'worker'],
            ['id' => 4, 'name' => 'driver'],
            ['id' => 5, 'name' => 'client']
        ]);

        DB::table('service_types')->insert([
            ['name' => 'Pranje'],
            ['name' => 'Peglanje'],
            ['name' => 'Susenje'],
            ['name' => 'Ostalo']
        ]);

        DB::table('users')->insert([
            ['name' => 'admin', 'email' => 'admin@test.com', 'password' => Hash::make('test'), 'role_id' => 1, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567'],
            ['name' => 'manager', 'email' => 'manager@test.com', 'password' => Hash::make('test'), 'role_id' => 2, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567'],
            ['name' => 'worker', 'email' => 'worker@test.com', 'password' => Hash::make('test'), 'role_id' => 3, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567'],
            ['name' => 'worker2', 'email' => 'worker2@test.com', 'password' => Hash::make('test'), 'role_id' => 3, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567'],
            ['name' => 'driver', 'email' => 'driver@test.com', 'password' => Hash::make('test'), 'role_id' => 4, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567'],
            ['name' => 'client', 'email' => 'client@test.com', 'password' => Hash::make('test'), 'role_id' => 5, 'surname' => 'test', 'country' => 'Srbija', 'country' => 'Srbija','address' => 'Cerska 12', 'city' => 'Beograd', 'municipality' => 'Dorcol', 'zip' => '11000', 'phone' => '0641234567']
        ]);

        DB::table('shops')->insert([
            ['name' => 'Test shop 1', 'description' => "Just a test shop", 'user_id' => 3],
            ['name' => 'Second 1', 'description' => "THe best shop", 'user_id' => 4],
        ]);

        DB::table('services')->insert([
            ['name' => 'Test Pranje', 'description' => "Just a test service", "service_type_id" => 1, "shop_id" => 1],
            ['name' => 'Test Peglanje', 'description' => "Just a test service", "service_type_id" => 2, "shop_id" => 1],
            ['name' => 'Test Susenje', 'description' => "Just a test service", "service_type_id" => 3, "shop_id" => 1],
            ['name' => 'Test Ostalo', 'description' => "Just a test service", "service_type_id" => 4, "shop_id" => 2]
        ]);




    }
}
