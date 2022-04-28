<?php

namespace App\Http\Controllers;

use App\Models\Price;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index() {
        $services = Service::all();
        return response()->json([
            "status" => 1,
            "result" => $services
        ]);
    }

    public function create() {
        //
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required',
        ]);

        return Service::create($request->all());
    }

    public function show($id) {
        //
    }

    public function edit($id) {
        //
    }

    public function update(Request $request, $id) {
        //
    }

    public function destroy($id) {
        //
    }

    public function search(Request $request) {
        return Service::where('name', 'like', '%' . $request->name . '%')->get();
    }

    public function shop($id) {
        return Service::where('shop_id', '=', $id)->get();
    }


    /*  WORKER SPECIFIC FUNCTIONS  */
    public function workerServices(Request $request) {
        $shop = Shop::where('user_id',Auth::id())->first();
        return Service::where('shop_id',$shop->id)->get();
    }

    public function clientGetAllServiceTypes() {
        $services = Service::all();

        $service_output = [];

        foreach ($services as $service) {
            $service_output[] = [
                'id' => $service->id,
                'name' => $service->name,
                'type' => $service->type == 0 ? 'weightable' : 'countable'
            ];
        }

        return response()->json([
            "status" => 1,
            "result" => $service_output
        ]);
    }
}
