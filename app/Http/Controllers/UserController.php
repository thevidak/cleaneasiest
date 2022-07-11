<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Shop;
use App\Models\LinkedSocialAccount;
use App\Models\ClientInfo;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\Address;
use App\Models\ClientQuestion;
use App\Models\CreditCard;
use App\Models\Options;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

use Illuminate\Auth\Events\Registered;

use Laravel\Socialite\Facades\Socialite;

use Illuminate\Support\Facades\Log;

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
        event(new Registered($user));

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
        $user = Auth::user();
        $user_info = ClientInfo::where('client_id', $user->id)->first();

        if (!isset($user_info)) {
            $user_info = ClientInfo::create([
                'client_id' => $user->id
            ]);
        }

        $client_info = [
            'status' => 1,
            "id"=> $user->id,
            "name"=> $user->name,
            "surname" => $user->surname,
            //"address" => $user->address,
            //"city" => $user->city,
            //"municipality" => $user->municipality,
            //"country" => $user->country,
            //"zip" => $user->zip,
            "phone" => $user->phone,
            "email" => $user->email,
            //"location" => $user->location,
            "profile_image" => $user->profile_image,
            //"card_id" => $user_info->card_id,
            "active_address" => $user->activeAddress,
            "active_card" => $user->activeCard

        ];

        return $client_info;
    }

    public function logout(Request $request) {
        Auth::user()->tokens()->delete();
        return [
            'status' => 1,
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
            'status' => 1,
            'message' => 'Location Updated!'
        ], 200);
    }

    public function resetPassword (Request $request) {
        //if (!isset($request['email'])) return response()->json(['status' => 1, errorMessage: 'E-mail ne postoji u bazi']);
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
                'status' => 1,
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
                    'status' => 1,
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

    /*************************************************************************************************************************************/
    // ADDRESSESS
    /*************************************************************************************************************************************/
    public function clientGetAddressList() {
        //$addresses = Auth::user()->addresses;
        $addresses = Address::where('user_id', Auth::id())->where('enabled', 1)->get();
        if (count($addresses)==0) return response()->json(['status' => 1,'addresses' => []]);

        return response()->json([
            'status' => 1,
            'addresses' => $addresses
        ]);
    }

    public function clientGetActiveAddress() {
        $active_address = Auth::user()->activeAddress;

        if (!isset($active_address)) return response()->json(['status' => 0,'message' => 'Nema aktivne adrese']);

        return response()->json([
            'status' => 1,
            'address' => $active_address
        ]);
    }

    public function clientAddAddress(Request $request) {
        $user = Auth::user();
        $request->validate([
            'address_text' => 'required',
            'latitude' => 'required',
            'longitude' => 'required'
        ]);

        $note = isset($request['note']) ? $request['note'] : NULL;

        if (isset($request['active']) && $request['active'] == TRUE) {
            $active_address = $user->activeAddress;
            if (isset($active_address)) {
                $active_address->active = FALSE;
                $active_address->save();
            }

            Address::create([
                'user_id' => Auth::id(),
                'text' => $request['address_text'],
                'latitude' => $request['latitude'],
                'longitude' => $request['longitude'],
                'note' => $note,
                'active' => TRUE
            ]);

            return response()->json(['status' => 1]);
        }
        else {
            Address::create([
                'user_id' => Auth::id(),
                'text' => $request['address_text'],
                'latitude' => $request['latitude'],
                'longitude' => $request['longitude'],
                'note' => $note,
            ]);

            return response()->json(['status' => 1]);
        }


        return response()->json(['status' => 0,'message' => 'Doslo je do greske']);

    }

    

    public function clientSetActiveAddress(Request $request) {
        $request->validate([
            'address_id' => 'required',
        ]);

        $user = Auth::user();

        $current_address = Address::where('id', $request['address_id'])->where('user_id', $user->id)->first();
        if (!isset($current_address)) return response()->json(['status' => 0,'message' => 'Los ID']); 

        $active_address = $user->activeAddress;
        if (isset($active_address)) {
            $active_address->active = FALSE;
            $active_address->save();
        }

        $current_address->active = TRUE;
        $current_address->save();

        return response()->json(['status' => 1]);
    }

    public function clientEditAddress(Request $request) {
        $request->validate([
            'address_id' => 'required',
        ]);

        $user = Auth::user();

        $current_address = Address::where('id', $request['address_id'])->where('user_id', $user->id)->first();
        if (!isset($current_address)) return response()->json(['status' => 0,'message' => 'Los ID']); 

        if (isset($request['address_text'])) $current_address->text = $request['address_text'];
        if (isset($request['latitude'])) $current_address->latitude = $request['latitude'];
        if (isset($request['longitude'])) $current_address->longitude = $request['longitude'];
        if (isset($request['note'])) $current_address->note = $request['note'];

        $current_address->save();
        
        return response()->json(['status' => 1]);
    }

    public function clientDeleteAddress(Request $request) {
        $request->validate([
            'address_id' => 'required',
        ]);

        $user = Auth::user();

        $current_address = Address::where('id', $request['address_id'])->where('user_id', $user->id)->where('enabled', 1)->first();
        if (!isset($current_address)) return response()->json(['status' => 0,'message' => 'Los ID']); 

        if($current_address->active == TRUE) {

        }
        else {
            
        }

        //$current_address->delete();
        $current_address->enabled = 0;
        $current_address->save();
        return response()->json(['status' => 1]);
    }

    /*************************************************************************************************************************************/
    // CARDS
    /*************************************************************************************************************************************/
    public function clientGetCardList() {
        //$cards = Auth::user()->cards;
        $cards = CreditCard::where('user_id',Auth::id())->where('enabled', 1)->get();
        if (count($cards)==0) return response()->json(['status' => 1,'cards' => []]);

        return response()->json([
            'status' => 1,
            'cards' => $cards
        ]);
    }

    public function clientGetActiveCard() {
        $active_card = Auth::user()->activeCard;
        if (!isset($active_card)) return response()->json(['status' => 0,'message' => 'Nema aktivne kartice']);

        return response()->json([
            'status' => 1,
            'card' => $active_card
        ]);
    }

    public function clientAddCard(Request $request) {
        $user = Auth::user();
        $request->validate([
            'card_number' => 'required'
        ]);
        $date = isset($request['expiration_date']) ? $request['expiration_date'] : '';

        if (isset($request['active']) && $request['active'] == TRUE) {
            $active_card = $user->activeCard;
            if (isset($active_card)) {
                $active_card->active = FALSE;
                $active_card->save();
            }

            CreditCard::create([
                'user_id' => Auth::id(),
                'number' => $request['card_number'],
                'date' => $date,
                'active' => TRUE
            ]);

            return response()->json(['status' => 1]);
        }
        else {
            CreditCard::create([
                'user_id' => Auth::id(),
                'number' => $request['card_number'],
                'date' => $date,
            ]);

            return response()->json(['status' => 1]);
        }


        return response()->json(['status' => 0,'message' => 'Doslo je do greske']);

    }

    

    public function clientSetActiveCard(Request $request) {
        $request->validate([
            'card_id' => 'required',
        ]);

        $user = Auth::user();

        $current_card = CreditCard::where('id', $request['card_id'])->where('user_id', $user->id)->where('enabled', 1)->first();
        if (!isset($current_card)) return response()->json(['status' => 0,'message' => 'Los ID']); 

        $active_card = $user->activeCard;
        if (isset($active_card)) {
            $active_card->active = FALSE;
            $active_card->save();
        }

        $current_card->active = TRUE;
        $current_card->save();

        return response()->json(['status' => 1]);
    }

    public function clientDeleteCard(Request $request) {
        $request->validate([
            'card_id' => 'required',
        ]);

        $user = Auth::user();

        $current_card = CreditCard::where('id', $request['card_id'])->where('user_id', $user->id)->where('enabled', 1)->first();
        if (!isset($current_card)) return response()->json(['status' => 0,'message' => 'Los ID']); 

        if($current_card->active == TRUE) {

        }
        else {
            
        }

        $current_card->enabled = 0;
        $current_card->save();
        return response()->json(['status' => 1]);
    }

    
    public function clientGetSupportText() {
        $option = Options::where('name','SUPPORT_TEXT')->first();
        if (!isset($option)) return response()->json(['status' => 0,'message' => 'Greska u bazi']);
        return response()->json([
            'title' => $option->value['title'],
            'text' => $option->value['text']
        ]);
    }




















    public function testUpload(Request $request) {
        Log::debug($request);

        if (!isset($request['image'])) {
            return response()->json(['status' => 0, 'errorMessage' => 'Slika nije poslata']);
        }

        try {
            $image_64 = $request['image']; 
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; 
            $replace = substr($image_64, 0, strpos($image_64, ',')+1); 
        
            $image = str_replace($replace, '', $image_64); 
            $image = str_replace(' ', '+', $image); 
        
            $imageName = \Str::random(10).'.'.$extension;
        
            \Storage::disk('public')->put('images/test/' . $imageName, base64_decode($image));

            return response()->json([
                'status' => 1,
                'image' => asset('storage/images/test/' . $imageName)
            ]);

        }
        catch (Exception $e) {
            return $e;
        }

        

        /*
        if($request->hasFile('image')){
            $tmp = explode(".", $request->image->getClientOriginalName());
            $filename = 'test' . '.' . end($tmp);
            $request->image->storeAs('images/test', $request->image->getClientOriginalName(),'public');

            ob_end_clean();
            return response()->json([
                'status' => 1,
                'image' => asset('storage/images/test/' . $request->image->getClientOriginalName())
            ]);

        }
        else {
            return response()->json(['status' => 0, 'errorMessage' => 'Slika nije poslata']);
        }
        */

        return response()->json(['status' => 0, 'errorMessage' => 'Nesto nije u redu. Kontaktirati Vidaka']);
        
    }

}
