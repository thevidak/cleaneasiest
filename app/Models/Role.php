<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public const ADMIN = 1;
    public const MANAGER = 2;
    public const WORKER = 3;
    public const DRIVER = 4;
    public const CLIENT = 5;

    protected $fillable = ['name'];
}
