<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {return view('welcome');})->name('home');
Route::get('/reset-password/success', function () { return view('auth.password-reseted'); })->name('password.reset.success');

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    //$request->fulfill();
    redirect()->route('home');
})->name('verification.verify');

//})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');



Route::get('/reset-password/{token}', function ($token, Request $request) {
    //return view('welcome');
    return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
})->name('password.reset');
//})->middleware('guest')->name('password.reset');


Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        //'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);
/*
    $tmp = DB::table('password_resets')->where('token',$request->token)->first();
    
    if (!isset($tmp)) return $request->token;
 */
    $user = User::where('email',$request->email)->first();

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        //['email'=>$user->email, 'password'=> $request->password, 'password_confirmation'=> $request->password_confirmation, 'token'=> $request->token],
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->setRememberToken(Str::random(60));
 
            $user->save();
 
            event(new PasswordReset($user));
        }
    );
 
    return $status === Password::PASSWORD_RESET
                ? redirect()->route('password.reset.success')->with('status', __($status))
                : back()->withErrors(['email' => [__($status)]]);
})->name('password.update');
//})->middleware('guest')->name('password.update');

