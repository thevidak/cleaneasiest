<?php

namespace App\Orchid\Screens;

use Illuminate\Http\Request;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Facades\Alert;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\TextArea;

use App\Models\Service;
use App\Models\Price;
use App\Models\WeightClass;

class ServiceEditScreen extends Screen {

    public $name = 'Dodaj novi servis';
    public $description = 'Detalji o servisu';
    public $exists = false;

    public $prices = [];

    public function query(Service $service): array {
        $this->exists = $service->exists;

        //$weight_classes = WeightClass::all();

        if($this->exists){
            $this->name = 'Izmeni Servis';

            $tmp = Price::where('service_id',$service->id)->get();
            foreach($tmp as $single_price) {
                $this->prices[] = [
                    'name' => WeightClass::where('id',$single_price->weight_class_id)->first()->name,
                    'value' =>$single_price->value
                ];
            }
        }
        
        return [
            'service' => $service,
            'prices' =>  $this->prices
        ];
    }

    public function commandBar(): array {
        return [
            Button::make('Kreiraj Servis')
                ->icon('pencil')
                ->method('createOrUpdate')
                ->canSee(!$this->exists),

            Button::make('Sacuvaj Promene')
                ->icon('note')
                ->method('createOrUpdate')
                ->canSee($this->exists),

            Button::make('Izbrisi')
                ->icon('trash')
                ->method('remove')
                ->canSee($this->exists),
        ];
    }

    public function layout(): array {
        return [
            Layout::rows([
                Group::make([
                    Input::make('service.name')
                        ->type('text')
                        ->max(255)
                        ->required()
                        ->title(__('Ime'))
                        ->placeholder(__('Ime')),
                    Input::make('service.description')
                        ->type('text')
                        ->max(255)
                        ->required()
                        ->title(__('Opis'))
                        ->placeholder(__('Opis')),
                ])->fullWidth(),
            ]),
            Layout::rows([
                Group::make([
                    Matrix::make('prices')
                    ->columns([
                        'Kilaza' => 'name', 
                        'Cena' => 'value'
                    ])
                    ->fields([
                        'Kilaza'   => Input::make()->type('number'),
                        'Cena' => TextArea::make(),
                    ])
                    ->maxRows(4),
                ])->fullWidth(),
            ])
        ];
    }

    
    public function createOrUpdate(Service $service, Request $request)    {
        $service->fill($request->get('service'))->save();

        Alert::info('Servis je uspesno sacuvan');

        return redirect()->route('service.list');
    }
    
/*
    public function createOrUpdate(Service $service, Request $request) {
        $request->validate([
            'service.name' => 'required|string',
            'service.description' => 'string',
        ]);

        if(!$service->exists) {
            Service::create([
                'name' => $request['service.name'],
                'description'=> $request['service.description']
            ]);
            
            Toast::info(__('Servis je dodat.'));
            return redirect()->route('service.list');
        }
        else {
            $serviceData = $request->get('service');

            $service->fill($serviceData)->save();

            Toast::info(__('Servis je sacuvan.'));
            return redirect()->route('service.list');
            
        }
    }
*/
    public function remove(Service $service)
    {
        $service->delete();

        Alert::info('You have successfully deleted the service.');

        return redirect()->route('service.list');
    }
}
