<?php

namespace App\Orchid\Screens\Order;

use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

use App\Models\Order;
use App\Models\OrderStatus;

use App\Orchid\Layouts\OrderListLayout;
use Orchid\Screen\Actions\Link;

class OrderStalledListScreen extends Screen {

    public $name = 'Stopirane Narudžbine';
    public $description = 'Spisak svih aktivnih narudžbina sa problemom u preuzimanju/dostavi.';

    public function query(): array {
        return [
            //'orders' => Order::filters()->defaultSort('updated_at')->paginate()
            'orders' => Order::whereIn('status', [
                OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_CLIENT,
                OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_CLIENT,
                OrderStatus::DRIVER_UNABLE_TO_LOAD_FROM_WORKER,
                OrderStatus::DRIVER_UNABLE_TO_DELIVER_TO_WORKER,
            ])->filters()->defaultSort('updated_at')->paginate()
        ];
    }

    public function commandBar(): array {
        return [
            /*
            Link::make('Kreiraj novu')
                ->icon('pencil')
                ->route('order.edit')
            */
        ];
    }

    public function layout(): array {
        return [
            OrderListLayout::class
        ];
    }
}
