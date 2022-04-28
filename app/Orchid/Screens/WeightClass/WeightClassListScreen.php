<?php

namespace App\Orchid\Screens\WeightClass;

use Illuminate\Http\Request;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Label;


use App\Models\WeightClass;

class WeightClassListScreen extends Screen {
    public $name = 'WeightClassListScreen';
    public $description = 'Detalji o servisu';

    public function query(): array {
        return [
            'weights' => WeightClass::all()
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
                Matrix::make('weights')
                    ->columns([
                        'ID' => 'id', 
                        'Ime' => 'name'
                    ]),
            ]),
        ];
    }

    public function update(Request $request) {
        $request->validate([
            'weights' => 'required'
        ]);

        WeightClass::truncate();

        foreach ($request->weights as $weight) {
            WeightClass::create([
                'id' => $weight['id'],
                'name' => $weight['name']
            ]);
        }

        Toast::info(__('Test'));
        return redirect()->route('weight.list');
            
    }
}
