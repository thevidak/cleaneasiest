<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Shop;
use App\Models\LinkedSocialAccount;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

use Illuminate\Auth\Events\Registered;

use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    // register CLIENT user

    public function createUser($userData) {
        $user = User::create([
            'name' => $userData['name'],
            'surname' => $userData['surname'],
            'email'=> $userData['email'],
            'password' => isset($userData['password']) ? bcrypt($userData['password']) : NULL,
            'role_id' => $userData['role'],
            'phone' => isset($userData['phone']) ? $userData['phone'] : NULL,

            /*
            'address' => $fields['address'],
            'country' => isset($fields['country']) ? $fields['country'] : NULL,
            'city' => $fields['city'],
            'municipality' => $fields['municipality'],
            'zip' => isset($fields['zip']) ? $fields['zip'] : NULL,
            'location' => googleAPIGetGeoLocationFromAddress($fields['address'] . ", " . $fields['city'])
            */
        ]);
        
        $token = $user->createToken('myapptoken')->plainTextToken;
        //event(new Registered($user));

        return [
            'token' => $token,
            'user' => $user
        ];
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'surname' => 'required',
            'phone' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('email',$request->email)-> first();
        
        if (isset($user)) return response()->json([
            'status' => 0, 
            'errorMessage' => 'Korisnik je već registrovan',
        ]);

        $createdUserData = $this->createUser([
            'name' => $request->name,
            'surname' => $request->surname,
            'email'=> $request->email,
            'password' => $request->password,
            'role' => Role::CLIENT,
            'phone' => $request->phone
        ]);

        return response()->json([
            'status' => 1,
            'token' => $createdUserData['token']
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

    public function clientUpdateInfo(Request $request) {

        $user = Auth::user();

        if (isset($request->name)) $user->name = $request->name;
        if (isset($request->surname)) $user->surname = $request->surname;
        if (isset($request->address)) $user->address = $request->address;
        if (isset($request->city)) $user->city = $request->city;
        if (isset($request->municipality)) $user->municipality = $request->municipality;
        if (isset($request->phone)) $user->phone = $request->phone;
        if (isset($request->country)) $user->country = $request->country;
        if (isset($request->zip)) $user->zip = $request->zip;
        if (isset($request->email)) $user->email = $request->email;

        if (isset($request->address)) $user->location = googleAPIGetGeoLocationFromAddress($request->address . ", " . $user->city);

        //$profile_image_link ="";

        if($request->hasFile('profile_image')){
            $tmp = explode(".", $request->profile_image->getClientOriginalName());
            $filename = Auth::id() . '.' . end($tmp);
            
            $request->profile_image->storeAs('images/profile', $filename,'public');
            $user->profile_image =  asset('storage/images/profile/' . $filename);
            $user->save();
        }
        else {
            /*
            return response()->json([
                'profile_image' => $request->profile_image
            ]);
            */
        }

        $user->save();

        return response()->json([
            'status' => 1,
            'user' => $user
        ]);
    }

    public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email',$request->email)->first();

        if (!isset($user)) return response()->json([
            "status" => 0, 
            'errorMessage' => 'Pogrešan email',
            'errorType' => 'email'
        ]);

        if (!Hash::check($fields['password'], $user->password)) return response()->json([
            "status" => 0, 
            'errorMessage' => 'Pogrešna šifra',
            'errorType' => 'password'
        ]);

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

    public function info(Request $request) {
        return Auth::user();
    }

    public function logout(Request $request) {
        Auth::user()->tokens()->delete();
        return [
            'message' => 'Logged out'
        ];
    }

    public function checkEmail(Request $request) {
        $request->validate(['email' => 'required']);
        $user = User::where('email',$request->email)->first();
        if (isset($user)) {
            return response()->json([
                'status' => '0',
                'errorMessage' => 'Email already taken'
            ], 200);
        }
        else {
            return response()->json([
                'status' => '1'
            ], 200);
        }
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

    public function resetPassword (Request $request) {
        $request->validate(['email' => 'required']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'status' => 1,
        ], 200);
    }

    public function socialLogin(Request $request) {
        $token = $request->idToken;

        if (!isset($token))   return response()->json(['status' => 0]);

        $social = LinkedSocialAccount::where('provider_id',$token)->first();

        if (isset($social)) {
            //login
            $user = User::where('id', $social->user_id)->first();
            $token = $user->createToken('myapptoken')->plainTextToken;
            
            return [
                'token' => $token,
                'user' => $user
            ];
        }

        else {
            //register
            $userData = NULL;
            try {
                $userData=Socialite::driver('google')->userFromToken($token)->user;
            }
            catch (\GuzzleHttp\Exception\ClientException $e){
                return response()->json(['status' => 0, 'errorMessage' => 'Nispravan token!']);
            }
            
            
            $userName = $userData['given_name'];
            $userSurname = $userData['family_name'];
            $userEmail = $userData['email'];
            $userSocialId = $userData['id'];
            $userPicture = $userData['picture'];

            $existingUser = User::where('email', $userData['email'])->first();
            if (isset($existingUser)) {
                // if user with email exists in database there are two options
                // 1. user already used social login/register but token expired
                // 2. user registered manualy and now wants to login via social
                // in both cases we do the same, add user data to the LinkedSocialAccount table
                
                LinkedSocialAccount::create([
                    'provider_id' => $token,
                    'provider_name' => 'google',
                    'user_id' => $existingUser->id
                ]);
                return [
                    'token' => $existingUser->createToken('myapptoken')->plainTextToken,
                    'user' => $existingUser
                ];
            }

            else {
                $result = $this->createUser([
                    'name' => $userData['given_name'],
                    'surname' => $userData['family_name'],
                    'email'=> $userData['email'],
                    'role' => Role::CLIENT
                ]);
    
                LinkedSocialAccount::create([
                    'provider_id' => $token,
                    'provider_name' => 'google',
                    'user_id' => $result['user']->id
                ]);
    
                return $result;
            }
        }
        
        return response()->json([            'status' => 1,        ], 200);
    }




    public function testUpload(Request $request) {
        if($request->hasFile('image')){
            $tmp = explode(".", $request->image->getClientOriginalName());
            $filename = 'test' . '.' . end($tmp);
            $request->image->storeAs('images/test', $filename,'public');

            return response()->json([
                'status' => 1,
                'image' => asset('storage/images/test/' . $filename)
            ]);

        }
        else {
            return response()->json(['status' => 0, 'errorMessage' => 'Slika nije poslata']);
        }

        return response()->json(['status' => 0, 'errorMessage' => 'Nesto nije u redu. Kontaktirati Vidaka']);
        
    }

}
