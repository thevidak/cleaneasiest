<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model
{
    use HasFactory;

    public $fillable = ['user_id', 'number', 'active', 'enabled'];

    public $hidden = ['user_id', 'created_at' , 'updated_at', 'enabled'];
}
