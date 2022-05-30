<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientQuestion extends Model
{
    use HasFactory;

    public $fillable = ['client_id', 'question', 'title'];
}
