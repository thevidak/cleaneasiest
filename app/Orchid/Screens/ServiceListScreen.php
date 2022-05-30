<?php

namespace App\Orchid\Screens;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;


use App\Models\Service;

use App\Orchid\Layouts\ServiceListLayout;

class ServiceListScreen extends Screen {

    public $name = 'Servisi';
    public $description = 'Spisak svih servisa';

    public function query(): array {
        return [
            'services' => Service::all()
        ];
    }

    public function commandBar(): array {
        return [
            Link::make(__('Dodaj Novi'))
                ->icon('plus')
                ->route('service.edit'),
        ];
    }

    public function layout(): array {
        return [
            ServiceListLayout::class,
        ];
    }

    public function remove(Service $service) {
        $service->delete();
        Toast::info(__('Servis je obrisan'));
        return redirect()->route('service.list');
    }
}
