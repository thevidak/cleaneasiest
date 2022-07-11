<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class SubService extends Model
{
    use HasFactory ,AsSource, Filterable;

    protected $fillable = ['order_id', 'service_id', 'subclass_type_id', 'amount'];
}
