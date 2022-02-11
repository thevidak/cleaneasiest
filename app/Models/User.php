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
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
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

}
