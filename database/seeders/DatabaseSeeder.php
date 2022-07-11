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
            [
                'name' => "SUPPORT_TEXT",
                'value' => json_encode(["title"=>"Podrska", "text"=> "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."])
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
            // Premium pranje
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
                'location' => json_encode(["latitude" => 44.79602117476604, "longitude" => 20.478572200000002]),
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
                'address' => 'Bulevar Zorana Djindjica 22', 
                'city' => 'Beograd', 
                'municipality' => 'Novi Beograd', 
                'zip' => '11070', 
                'phone' => '0641234567' , 
                'location' => json_encode(["latitude" => 44.81328890158943, "longitude" => 20.428234940455322]),
                'permissions' => NULL
            ]
        ]);

        DB::table('user_profiles')->insert([
            [
                'user_id' => 3,
                'licence_plate' => 'BG-023AB'
            ]
        ]);

        /*
        DB::table('orders')->insert([
            [
                'status' => 1,
                'client_id' => 4,
                'services' => json_encode([["service_id"=>3,"weight_class_id"=>2],["service_id"=>2,"weight_class_id"=>3]]),
                'payment_info' => json_encode(['type' => 0]),
                'order_info' => json_encode(["address"=>"Bulevar Zorana Djindjica 22, Beograd","note"=>"asdg","location"=>["latitude"=>44.833256,"longitude"=>20.42957]]),
                'takeout_date' => json_encode(["date" => date("d-m-Y",strtotime("+1 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("d-m-Y",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
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
                'takeout_date' => json_encode(["date" => date("d-m-Y",strtotime("+1 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("d-m-Y",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
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
                'takeout_date' => json_encode(["date" => date("d-m-Y",strtotime("+1 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("d-m-Y",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
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
                'takeout_date' => json_encode(["date" => date("d-m-Y",strtotime("+1 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("d-m-Y",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
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
                'takeout_date' => json_encode(["date" => date("d-m-Y",strtotime("+1 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("d-m-Y",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
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
                'takeout_date' => json_encode(["date" => date("d-m-Y",strtotime("+1 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("d-m-Y",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
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
                'takeout_date' => json_encode(["date" => date("d-m-Y",strtotime("+1 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("d-m-Y",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
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
                'takeout_date' => json_encode(["date" => date("d-m-Y",strtotime("+1 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("d-m-Y",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
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
                'takeout_date' => json_encode(["date" => date("d-m-Y",strtotime("+1 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'delivery_date' => json_encode(["date" => date("d-m-Y",strtotime("+2 day")), "start_time" => "12:00", 'end_time'=>'22:00']),
                'price' => 750,
                'worker_id' => 2,
                'driver_id' => 3,
            ],
        ]);

        DB::table('sub_services')->insert([
            [
                "order_id" => 1,
                "service_id" => 3,
                "subclass_type_id" => 2
            ],
            [
                "order_id" => 1,
                "service_id" => 2,
                "subclass_type_id" => 3
            ],
            [
                "order_id" => 2,
                "service_id" => 3,
                "subclass_type_id" => 2
            ],
            [
                "order_id" => 2,
                "service_id" => 2,
                "subclass_type_id" => 3
            ],
            [
                "order_id" => 3,
                "service_id" => 3,
                "subclass_type_id" => 2
            ],
            [
                "order_id" => 3,
                "service_id" => 2,
                "subclass_type_id" => 3
            ],
            [
                "order_id" => 4,
                "service_id" => 3,
                "subclass_type_id" => 2
            ],
            [
                "order_id" => 4,
                "service_id" => 2,
                "subclass_type_id" => 3
            ],
            [
                "order_id" => 5,
                "service_id" => 3,
                "subclass_type_id" => 2
            ],
            [
                "order_id" => 5,
                "service_id" => 2,
                "subclass_type_id" => 3
            ],
            [
                "order_id" => 6,
                "service_id" => 3,
                "subclass_type_id" => 2
            ],
            [
                "order_id" => 6,
                "service_id" => 2,
                "subclass_type_id" => 3
            ],
            [
                "order_id" => 7,
                "service_id" => 3,
                "subclass_type_id" => 2
            ],
            [
                "order_id" => 7,
                "service_id" => 2,
                "subclass_type_id" => 3
            ],
            [
                "order_id" => 8,
                "service_id" => 3,
                "subclass_type_id" => 2
            ],
            [
                "order_id" => 8,
                "service_id" => 2,
                "subclass_type_id" => 3
            ],
            [
                "order_id" => 9,
                "service_id" => 3,
                "subclass_type_id" => 2
            ],
            [
                "order_id" => 9,
                "service_id" => 2,
                "subclass_type_id" => 3
            ],
        ]);
        */
        DB::table('faqs')->insert([
            [
                'question' => 'Prvo Pitanje',
                'answer' => 'Donec tincidunt pellentesque diam quis finibus. Sed ex mi, porttitor in nisi eu, dignissim volutpat lectus. Nam a nisl elementum nisl aliquam fermentum scelerisque et nisi. Cras vel ullamcorper diam, ut semper eros. Curabitur eu fermentum nunc, feugiat finibus arcu. Aenean sit amet odio ligula. Maecenas eu sem at mi lobortis semper. Donec tellus diam, pulvinar sit amet nunc non, feugiat imperdiet nunc. Nullam malesuada tincidunt enim a efficitur. Cras sollicitudin vitae neque ac viverra. Nunc nisi ante, vulputate sed interdum sit amet, porttitor at lectus. '
            ],
            [
                'question' => 'Drugo Pitanje',
                'answer' => 'Donec tincidunt pellentesque diam quis finibus. Sed ex mi, porttitor in nisi eu, dignissim volutpat lectus. Nam a nisl elementum nisl aliquam fermentum scelerisque et nisi. Cras vel ullamcorper diam, ut semper eros. Curabitur eu fermentum nunc, feugiat finibus arcu. Aenean sit amet odio ligula. Maecenas eu sem at mi lobortis semper. Donec tellus diam, pulvinar sit amet nunc non, feugiat imperdiet nunc. Nullam malesuada tincidunt enim a efficitur. Cras sollicitudin vitae neque ac viverra. Nunc nisi ante, vulputate sed interdum sit amet, porttitor at lectus. '
            ]
        ]);

        DB::table('privacies')->insert([
            [
                'name' => 'Default',
                'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi libero orci, sodales vitae ipsum quis, varius maximus ligula. Donec dapibus, ex ac hendrerit tincidunt, libero dui interdum augue, et elementum nunc purus quis sapien. Aliquam varius nunc ut arcu fringilla scelerisque. Integer lacinia dictum tincidunt. Praesent accumsan dolor et magna faucibus posuere. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Donec risus ligula, aliquam ut leo id, imperdiet pharetra elit. Sed malesuada, leo vel ultrices ultricies, magna metus pharetra urna, sit amet varius felis erat ut eros. Fusce ullamcorper arcu eu mi accumsan luctus. Sed gravida nunc at quam bibendum tempus. Vestibulum placerat sodales mauris, quis suscipit urna pretium id.

                Donec tincidunt pellentesque diam quis finibus. Sed ex mi, porttitor in nisi eu, dignissim volutpat lectus. Nam a nisl elementum nisl aliquam fermentum scelerisque et nisi. Cras vel ullamcorper diam, ut semper eros. Curabitur eu fermentum nunc, feugiat finibus arcu. Aenean sit amet odio ligula. Maecenas eu sem at mi lobortis semper. Donec tellus diam, pulvinar sit amet nunc non, feugiat imperdiet nunc. Nullam malesuada tincidunt enim a efficitur. Cras sollicitudin vitae neque ac viverra. Nunc nisi ante, vulputate sed interdum sit amet, porttitor at lectus.
                
                Etiam ex mi, dictum ac consequat at, fermentum id libero. Etiam placerat lacinia lectus et egestas. Nam nec malesuada neque, ut sollicitudin ante. Suspendisse ut diam eu tortor placerat condimentum. Vivamus nisl massa, blandit at tortor sit amet, fermentum consequat lectus. Vivamus vel dignissim ante. Nam a pulvinar augue. Integer gravida, dui ut imperdiet faucibus, lacus ante faucibus ante, sed ullamcorper lectus magna at lacus. Aliquam nec maximus arcu, sed malesuada ipsum. Sed porta diam risus, molestie pellentesque urna placerat sed.
                
                Etiam in risus orci. Fusce maximus urna non condimentum tempus. Vivamus vel lacus magna. In ac sapien vitae nisi dictum mattis eget vitae arcu. Nam in nulla pulvinar, eleifend justo non, tempus diam. Duis nec tellus at mi aliquam rutrum pellentesque quis ligula. Donec mollis porta arcu, id eleifend nibh. Nam porttitor orci sit amet pulvinar lacinia. In hac habitasse platea dictumst. Sed nec imperdiet leo, scelerisque commodo massa. Morbi sed augue velit. Praesent eu odio efficitur, consequat nisl tempor, iaculis dolor.
                
                Pellentesque ut ante bibendum, pulvinar nulla vehicula, ultrices odio. Nam eu velit vehicula, volutpat sem at, fermentum arcu. Etiam molestie, enim id accumsan tempus, tellus nulla fringilla enim, id pretium tortor dui eu magna. Aliquam dapibus ac dolor id varius. Fusce sagittis non lorem venenatis vestibulum. Suspendisse potenti. Nulla facilisi. Morbi euismod tortor enim, a convallis nisi convallis vitae. Nulla scelerisque, nunc sed facilisis mollis, nulla felis ornare justo, eu ultricies lacus lectus tristique tellus. Proin lacus sem, placerat et purus eu, ultrices sollicitudin augue. Sed ultrices non tortor a accumsan. Fusce faucibus turpis nec arcu interdum bibendum. Suspendisse turpis ligula, tempor non purus eu, consectetur cursus eros. Vestibulum fringilla, tortor a convallis aliquet, nulla sapien suscipit enim, a volutpat velit metus volutpat velit. '
            ]
        ]);

    }
}
