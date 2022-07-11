<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Models\UserProfile;

use Orchid\Platform\Models\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail {

    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'permissions',
        'role_id',
        'surname',
        'country',
        'address',
        'city',
        'municipality',
        'zip',
        'phone',
        'location',
        'profile_image',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
        'email_verified_at',
        'role_id',
    ];

    protected $casts = [
        'permissions'          => 'array',
        'email_verified_at'    => 'datetime',
        'location' => 'array',
    ];

    protected $allowedFilters = [
        'id',
        'name',
        'email',
        'permissions',
    ];

    protected $allowedSorts = [
        'id',
        'name',
        'email',
        'updated_at',
        'created_at',
    ];

    public function linkedSocialAccounts() {
        return $this->hasMany(LinkedSocialAccount::class);
    }

    public function addresses() {
        return $this->hasMany(Address::class);
    }

    public function cards() {
        return $this->hasMany(CreditCard::class);
    }

    public function profile() {
        return $this->hasOne(UserProfile::class);
    }

    public function isAdmin() {
        return $this->role_id === Role::ADMIN ? true : false;
    }

    public function userRole() {
        return $this->role_id;
    }

    public function getFullNameAttribute () {
        return $this->name . " " . $this->surname; 
    }

    // Attributes
    public function getActiveAddressAttribute () {
        $active_address = NULL;
        foreach ($this->addresses as $address) {
            if ($address->active && $address->enabled == 1) {
                $active_address = $address;
                break;
            }
        }

        if (isset($active_address)) {
            return $active_address;
        }
        else {
            foreach ($this->addresses as $address) {
                if ($address->enabled == 1) {
                    $address->active = 1;
                    $address->save();
                    return $address;
                }
            }
        }
        return NULL;
    }

    public function getActiveCardAttribute () {
        $active_card = NULL;
        foreach ($this->cards as $card) {
            if ($card->active && $card->enabled == 1) {
                $active_card = $card;
                break;
            }
        }
        
        if (isset($active_card)) {
            return $active_card;
        }
        else {
            foreach ($this->cards as $card) {
                if ($card->enabled == 1) {
                    $card->active = 1;
                    $card->save();
                    return $card;
                }
            }
        }
        return NULL;
    }
}
