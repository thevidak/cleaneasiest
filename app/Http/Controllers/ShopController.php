<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    public function index(){
        return Shop::all();
    }

    public function show($id) {
        return Shop::find($id);
    }

    public function update(Request $request, $id) {
        $shop = Shop::find($id);
        $shop->update($request->all());
    }

    public function destroy($id) {
        return Shop::destroy($id);
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required',
        ]);

        return Shop::create($request->all());
    }

    public function search(Request $request) {
        return Shop::where('name', 'like', '%' . $request->name . '%')->get();
    }


    public function get($id) {
        return Shop::find($id);
    }
    /*  WORKER SPECIFIC FUNCTIONS  */
    public function workerShop(Request $request) {
        return Shop::where('user_id',Auth::id())->first();
    }



}
