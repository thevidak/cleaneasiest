<?php

namespace App\Orchid\Screens\ClothesType;

use Illuminate\Http\Request;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Actions\Link;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\DropDown;



use App\Models\ClothesType;

class ClothesTypeListScreen extends Screen {
    public $name = 'Tipovi odeće';
    public $description = 'Spisak mogićih artikala odeće pri odaburu servisa';

    public function query(): array {
        return [
            'clothes' => ClothesType::all()
        ];
    }

    public function commandBar(): array {
        return [
            Link::make(__('Dodaj Novi'))
                ->icon('plus')
                ->route('clothes.edit'),
        ];
    }

    public function layout(): array {
        return [
            Layout::table('clothes',[
                TD::make('id', __('ID'))
                    ->render(function (ClothesType $clothes) {
                        return Link::make($clothes->id)
                            ->route('clothes.edit', $clothes);
                    }),
                
                TD::make('name', __('Ime'))
                    ->sort()
                    ->cantHide()
                    
                    ->render(function (ClothesType $clothes) {
                        return Link::make($clothes->name)
                            ->route('clothes.edit', $clothes);
                    }),
                
                TD::make(__('Akcije'))
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(function (ClothesType $clothes) {
                        return DropDown::make()
                            ->icon('options-vertical')
                            ->list([
                                Link::make(__('Promeni'))
                                    ->route('clothes.edit', $clothes->id)
                                    ->icon('pencil'),
                                Button::make(__('Obrisi'))
                                    ->icon('trash')
                                    ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                                    ->method('remove', [
                                        'id' => $clothes->id,
                                    ]),
                            ]);
                    }),
                
            ])     
        ];

    }

    public function remove(ClothesType $clothes) {
        $clothes->delete();
        Toast::info(__('Artikal je obrisan'));
        return redirect()->route('clothes.list');
    }

}
