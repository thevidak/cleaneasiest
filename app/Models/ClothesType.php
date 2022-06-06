<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Orchid\Screen\AsSource;

class ClothesType extends Model
{
    use HasFactory, AsSource;

    protected $hidden = ['created_at', 'updated_at'];

    protected $fillable = ['name'];

    public function getPictureAttribute() {
        $picture = asset('storage/images/clothes/' . $this->id. '.png');
        if (!file_exists(public_path('storage/images/clothes/' . $this->id. '.png'))) {
            return asset('storage/images/clothes/default.png');
        }
        else {
            return $picture;
        }
    }
}
