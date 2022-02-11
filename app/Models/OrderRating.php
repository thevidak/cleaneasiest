<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderRating extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'service_ratings', 'note'];

    protected $casts = [
        'service_ratings' => 'array'
    ];
}
