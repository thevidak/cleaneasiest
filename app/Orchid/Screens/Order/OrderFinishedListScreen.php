<?php

namespace App\Orchid\Screens\Order;

use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

use App\Models\Order;
use App\Models\OrderStatus;

use App\Orchid\Layouts\OrderListLayout;
use Orchid\Screen\Actions\Link;

class OrderFinishedListScreen extends Screen {

    public $name = 'Završene Narudžbine';
    public $description = 'Spisak svih završenih narudžbina.';

    public function query(): array {
        return [
            //'orders' => Order::filters()->defaultSort('updated_at')->paginate()
            'orders' => Order::where('status', OrderStatus::ORDER_DELIVERED)->filters()->defaultSort('updated_at')->paginate()
        ];
    }

    public function commandBar(): array {
        return [
            Link::make('Kreiraj novu')
                ->icon('pencil')
                ->route('order.edit')
        ];
    }

    public function layout(): array {
        return [
            OrderListLayout::class
        ];
    }
}
