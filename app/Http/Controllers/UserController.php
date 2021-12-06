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
            'password' => 'required|string|confirmed'
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
            'role_id' => Role::CLIENT
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
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
            'password' => 'required|string|confirmed'
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
            'role_id' => Role::DRIVER
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
            'password' => 'required|string|confirmed',
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
            'role_id' => Role::WORKER
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
            'password' => 'required|string|confirmed'
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
            'role_id' => Role::CLIENT
        ]);

        $response = [
            'user' => $user,
        ];

        return response($response, 201);
    }



    public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $fields['email'])->first();

        if(!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Bad creds'
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }

    public function logout(Request $request) {
        Auth::user()->tokens()->delete();
        return [
            'message' => 'Logged out'
        ];
    }


}
