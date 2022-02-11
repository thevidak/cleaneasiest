<?php

namespace App\Orchid\Screens;

use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

use App\Models\Order;

use App\Orchid\Layouts\OrderListLayout;
use Orchid\Screen\Actions\Link;

class OrderListScreen extends Screen {

    public $name = 'Lista Narudzbina';

    public function query(): array {
        return [
            'orders' => Order::filters()->defaultSort('updated_at')->paginate()
        ];
    }

    public function commandBar(): array {
        return [
            Link::make('Create new')
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
