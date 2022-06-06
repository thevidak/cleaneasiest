<?php

namespace App\Orchid\Screens;

use Illuminate\Http\Request;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Facades\Alert;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\TextArea;

use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Price;
use App\Models\WeightClass;
use App\Models\ClothesType;


class ServiceEditScreen extends Screen {

    public $name = 'Dodaj novi servis';
    public $description = 'Detalji o servisu';
    
    public $exists = false;

    private $prices = [];
    private $max_rows =4; 

    public function query(Service $service): array {
        $this->exists = $service->exists;

        if($this->exists){
            $this->name = 'Izmeni Servis';

            $clothes_types = ClothesType::all();
            $weight_classes = WeightClass::all();

            if ($service->type == ServiceType::COUNTABLE) {
                foreach($clothes_types as $clothing_type) {
                    $price = Price::where('service_id', $service->id)->where('weight_class_id',$clothing_type->id)->first();
                    $this->prices[] = [
                        'name' => $clothing_type->name,
                        'value' => isset($price)? $price->value : 0
                    ];
                }
            }
            else if ($service->type == ServiceType::WEIGHTABLE) {
                foreach($weight_classes as $weight_class) {
                    $price = Price::where('service_id', $service->id)->where('weight_class_id',$weight_class->id)->first();
                    $this->prices[] = [
                        'name' => $weight_class->name,
                        'value' => isset($price)? $price->value : 0
                    ];
                }
            }


            /*

            $tmp = Price::where('service_id',$service->id)->get();
            if ($service->type == ServiceType::COUNTABLE) {
                foreach($tmp as $single_price) {
                    $this->prices[] = [
                        'name' => ClothesType::where('id',$single_price->weight_class_id)->first()->name,
                        'value' =>$single_price->value
                    ];
                }
            }
            else if ($service->type == ServiceType::WEIGHTABLE) {
                foreach($tmp as $single_price) {
                    $this->prices[] = [
                        'name' => WeightClass::where('id',$single_price->weight_class_id)->first()->name,
                        'value' =>$single_price->value
                    ];
                }
            }

            */
            
        }
        
        return [
            'service' => $service,
            'service_picture' => $service->picture, 
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
                    /*
                    Picture::make('service_picture')
                        ->title('Picture')
                        ->maxFileSize(8)
                        ->horizontal(),
                    */
                    Cropper::make('service_picture')
                    ->title('Picture')
                    ->width(300)
                    ->height(300)
                    ->maxFileSize(8)
                    ->targetUrl()
                    ->horizontal(),
                    
                    Input::make('service.name')
                        ->type('text')
                        ->max(255)
                        ->required()
                        ->title(__('Ime'))
                        ->placeholder(__('Ime')),
                    Select::make('service.type')
                        ->options([
                            '0'   => 'Po tezini',
                            '1' => 'Po komadu',
                        ])
                        ->title('Tip servisa')
                        ->help('Servisi po komadu ili po tezini'),
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
                        'Tip' => 'name', 
                        'Cena' => 'value'
                    ])
                    ->fields([
                        'Kilaza'   => Input::make()->type('number'),
                        'Cena' => TextArea::make(),
                    ])
                    ->maxRows(5),
                ])->fullWidth(),
            ])->canSee($this->exists)
        ];
    }

    
    public function createOrUpdate(Service $service, Request $request)    {
        $service->fill($request->get('service'))->save();

        if (!str_contains($request->service_picture, 'default'))
            $file_location = explode('storage/', $request->service_picture)[1];
            if (isset($file_location)) {
                rename ('storage/' . $file_location, 'storage/images/services/' . $service->id . '.png');
            }

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
