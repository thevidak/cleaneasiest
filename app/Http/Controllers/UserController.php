<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Support\Facades\Auth;

use Illuminate\Auth\Events\Registered;

class UserController extends Controller
{
    // register CLIENT user
    public function register(Request $request) {
        $fields = $request->validate([
            'name' => 'required|string',
            'surname' => 'required|string',
            'country' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'municipality' => 'required|string',
            'zip' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email'=> $fields['email'],
            'password' => bcrypt($fields['password']),
            'surname' => $fields['surname'],
            'country' => $fields['country'],
            'address' => $fields['address'],
            'city' => $fields['city'],
            'municipality' => $fields['municipality'],
            'zip' => $fields['zip'],
            'phone' => $fields['phone'],
            'role_id' => Role::CLIENT,
            'location' => googleAPIGetGeoLocationFromAddress($fields['address'] . ", " . $fields['city'])
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;

        //event(new Registered($user));

        return response()->json([
            'status' => 1,
            //'user' => $user,
            'token' => $token
        ]);
    }

    public function createDriver(Request $request) {
        $fields = $request->validate([
            'name' => 'required|string',
            'surname' => 'required|string',
            'country' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'municipality' => 'required|string',
            'zip' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email'=> $fields['email'],
            'password' => bcrypt($fields['password']),
            'surname' => $fields['surname'],
            'country' => $fields['country'],
            'address' => $fields['address'],
            'city' => $fields['city'],
            'municipality' => $fields['municipality'],
            'zip' => $fields['zip'],
            'phone' => $fields['phone'],
            'role_id' => Role::DRIVER,
            'location' => googleAPIGetGeoLocationFromAddress($fields['address'] . ", " . $fields['city'])
        ]);

        $response = [
            'user' => $user,
        ];

        return response($response, 201);
    }

    public function createWorker(Request $request) {
        $fields = $request->validate([
            'name' => 'required|string',
            'surname' => 'required|string',
            'country' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'municipality' => 'required|string',
            'zip' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string',
            'shop_id' => 'integer',
            'shop_name' => 'string',
            'shop_description' => 'string'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email'=> $fields['email'],
            'password' => bcrypt($fields['password']),
            'surname' => $fields['surname'],
            'country' => $fields['country'],
            'address' => $fields['address'],
            'city' => $fields['city'],
            'municipality' => $fields['municipality'],
            'zip' => $fields['zip'],
            'phone' => $fields['phone'],
            'role_id' => Role::WORKER,
            'location' => googleAPIGetGeoLocationFromAddress($fields['address'] . ", " . $fields['city'])
        ]);

        if (isset($fields['shop_id'])) {
            $workerProfile = WorkerProfile::create([
                'user_id' => $user->id,
                'shop_id' => $fields['shop_id']
            ]);
        }
        else if(isset($fields['shop_name']) && isset($fields['shop_description'])) {
            $shop = Shop::create([
                'name' => $fields['shop_name'],
                'description' => $fields['shop_description'],
                'user_id' =>  $user->id
            ]);

            $workerProfile = WorkerProfile::create([
                'user_id' => $user->id,
                'shop_id' => $shop->id
            ]);
        }

        $response = [
            'user' => $user,
        ];

        return response($response, 201);
    }

    public function createClient(Request $request) {
        $fields = $request->validate([
            'name' => 'required|string',
            'surname' => 'required|string',
            'country' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'municipality' => 'required|string',
            'zip' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email'=> $fields['email'],
            'password' => bcrypt($fields['password']),
            'surname' => $fields['surname'],
            'country' => $fields['country'],
            'address' => $fields['address'],
            'city' => $fields['city'],
            'municipality' => $fields['municipality'],
            'zip' => $fields['zip'],
            'phone' => $fields['phone'],
            'role_id' => Role::CLIENT,
            'location' => googleAPIGetGeoLocationFromAddress($fields['address'] . ", " . $fields['city'])
        ]);

        $response = [
            'user' => $user,
        ];

        event(new Registered($user));
        return response($response, 201);
    }



    public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email',$request->email)->first();

        if(!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json(["status" => 0, 'errorMessage' => 'Bad credentials']);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $user_type = NULL;
        switch ($user->role_id) {
            case 3:
                $user_type = 'worker';
            break;
            case 4:
                $user_type = 'driver';
            break;
            case 5:
                $user_type = 'client';
            break;
            default :

            break;
        }
        return response()->json([
            'status' => 1,
            'type' => $user_type, 
            'token' => $token
        ]);
    }

    public function logout(Request $request) {
        Auth::user()->tokens()->delete();
        return [
            'message' => 'Logged out'
        ];
    }

    public function updateLocation(Request $request) {
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required'
        ]);
        $user = Auth::user();
        $user->location = ['latitude' => $request['latitude'], 'longitude' => $request['longitude']];
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Location Updated!'
        ], 200);
    }
}
