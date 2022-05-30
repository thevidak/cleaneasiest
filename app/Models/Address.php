<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    public $fillable = ['user_id', 'text', 'latitude', 'longitude', 'active', 'enabled', 'note'];

    public $hidden = ['user_id', 'created_at' , 'updated_at', 'enabled'];
}
