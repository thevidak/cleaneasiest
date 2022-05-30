<?php

namespace App\Orchid\Layouts;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;

use App\Models\Service;

class ServiceListLayout extends Table {

    protected $target = 'services';

    protected function columns(): array {
        return [
            TD::make('id', __('ID'))
            ->render(function (Service $service) {
                return Link::make($service->id)
                    ->route('service.edit', $service);
            }),

            TD::make('name', __('Ime'))
                ->sort()
                ->cantHide()
                
                ->render(function (Service $service) {
                    return Link::make($service->name)
                        ->route('service.edit', $service);
                }),
            
            TD::make('description', __('Opis'))
                ->sort()
                ->cantHide()
            ,
            
            TD::make(__('Akcije'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (Service $service) {
                    return DropDown::make()
                        ->icon('options-vertical')
                        ->list([
                            Link::make(__('Promeni'))
                                ->route('service.edit', $service->id)
                                ->icon('pencil'),
                            Button::make(__('Obrisi'))
                                ->icon('trash')
                                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                                ->method('remove', [
                                    'id' => $service->id,
                                ]),
                        ]);
                }),
                
            ];
            
    }
}
