<?php

namespace App\Orchid\Layouts;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

use App\Models\Order;
use Orchid\Screen\Actions\Link;

use App\Models\User;

class OrderListLayout extends Table {
    protected $target = 'orders';

    protected function columns(): array {
        return [
            TD::make('id', 'JBP')
                ->render(function (Order $order) {
                    return Link::make($order->id)
                        ->route('order.show', $order);
                }),
            TD::make('statusFormated', 'Status'),
            TD::make('client_id', 'Klijent')->render(function (Order $order) {
                $client = User::where('id',$order->client_id)->first();
                if (isset($client)) {
                    return Link::make($client->name . " " . $client->surname)->route('client.edit', $client);
                }
                else {
                    return "";
                }
            }),
            TD::make('driver_id', 'Vozac')->render(function (Order $order) {
                $driver = User::where('id',$order->driver_id)->first();
                if (isset($driver)) {
                    return Link::make($driver->name . " " . $driver->surname)->route('driver.edit', $driver);
                }
                else {
                    return "";
                }
            }),
            TD::make('worker_id', 'Serviser')->render(function (Order $order) {
                $worker = User::where('id',$order->worker_id)->first();
                if (isset($worker)) {
                    return Link::make($worker->name . " " . $worker->surname)->route('worker.edit', $worker);
                }
                else {
                    return "";
                }
            }),
            TD::make('created_at', 'Kreirana')->sort()->render(function (Order $order) {
                return $order->created_at;
            }),
            TD::make('updated_at', 'Poslednja Izmena')->sort()->render(function (Order $order) {
                return $order->updated_at;
            }),      
        ];
    }
}
