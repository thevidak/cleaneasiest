<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Orchid\Screen\AsSource;

class StatusChange extends Model
{
    use HasFactory, AsSource;

    protected $fillable = ['order_id', 'user_id', 'previous_status', 'new_status'];

}
