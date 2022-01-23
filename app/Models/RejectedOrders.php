<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RejectedOrders extends Model
{
    use HasFactory;

    protected $fillable = ['order_id','worker_id'];
}
