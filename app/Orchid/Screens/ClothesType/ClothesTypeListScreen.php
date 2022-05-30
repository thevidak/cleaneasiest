<?php

namespace App\Orchid\Screens\ClothesType;

use Illuminate\Http\Request;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Label;


use App\Models\ClothesType;

class ClothesTypeListScreen extends Screen {
    public $name = 'Clothestype';
    public $description = 'Detalji o servisu';

    public function query(): array {
        return [
            'clothes' => ClothesType::all()
        ];
    }

    public function commandBar(): array {
        return [
            Button::make('Sacuvaj Promene')
                ->icon('note')
                ->method('update'),
        ];
    }

    public function layout(): array {
        return [
            Layout::rows([
                Label::make('static')
                        ->title('Unesite sve tezine koje korisnik aplikacje moze da izabere:')
                        ->value(''),
                Matrix::make('clothes')
                    ->columns([
                        'ID' => 'id', 
                        'Ime' => 'name'
                    ]),
            ]),
        ];
    }

    public function update(Request $request) {
        $request->validate([
            'clothes' => 'required'
        ]);

        ClothesType::truncate();

        foreach ($request->clothes as $single_clothes) {
            ClothesType::create([
                'id' => $single_clothes['id'],
                'name' => $single_clothes['name']
            ]);
        }

        Toast::info(__('Test'));
        return redirect()->route('clothes.list');
            
    }
}
