<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClothesType;

class ClothesTypeController extends Controller {
    
    public function clientGetAllClothesTypes() {
        $clothes = ClothesType::all();

        return response()->json([
            "status" => 1,
            "result" => $clothes
        ]);
    }
}
