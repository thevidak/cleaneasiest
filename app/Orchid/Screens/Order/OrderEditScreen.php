<?php

namespace App\Orchid\Screens\Order;

use Illuminate\Http\Request;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Screen\Fields\Label;

use App\Models\Order;
use App\Models\User;
use App\Models\Service;
use App\Models\WeightClass;

class OrderEditScreen extends Screen {

    public $name = 'Dodaj Narudzbinu';
    
    public $description = 'Detalji o narudzbini';
    
    public $exists = false;

    public function query(Order $order): array
    {
        $this->exists = $order->exists;

        if($this->exists){
            $this->name = 'Izmeni Narudzbinu';
        }

        return [
            'order' => $order,
            'services' => Service::all(),
            'weightClass' => WeightClass::all()
        ];
    }

    public function commandBar(): array
    {
        return [
            Button::make('Kreiraj Narudzbinu')
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

    public function layout(): array
    {
        return [
            
            Layout::rows([
                Select::make('order.status')
                    ->options([
                        0   => 'Narudzbina se kreira',
                        1 => 'Narudzbina kreirana',
                        2 => 'Serviser prihvatio',
                        3 => 'Vozac preuzeo od klijenta',
                        4 => 'Vozac prevozi do servisera',
                        5 => 'Servis',
                        6 => 'Serviser zavrsio',
                        7 => 'Vozac preuzima od servisera',
                        8 => 'Vozac dostavlja do klijenta',
                        9 => 'Narudzbina zavrsena',
                    ])
                    ->title('Status')
                    ->help('Allow search bots to index')
                ,
                Group::make([
                    Relation::make('order.client_id')
                    ->title('Klijent')
                    ->fromModel(User::class, 'fullName')
                    ,
                    Relation::make('order.worker_id')
                        ->title('Serviser')
                        ->fromModel(User::class, 'fullName')
                    ,
                    Relation::make('order.driver_id')
                        ->title('Vozac')
                        ->fromModel(User::class, 'fullName')
                ])->fullWidth()
                ,
                Group::make([
                    DateTimer::make('order.created_at')
                        ->title('Vreme kreiranja'),
                    DateTimer::make('order.updated_at')
                        ->title('Vreme poslednje promene')
                ])->fullWidth()
                ,
            ])
            ,
            //Layout::view('services'),
        ];
    }

    public function createOrUpdate(Order $order, Request $request)    {
        $order->fill($request->get('order'))->save();

        Alert::info('You have successfully created an order.');

        return redirect()->route('order.list');
    }

    public function remove(Order $order)
    {
        $order->delete();

        Alert::info('You have successfully deleted the order.');

        return redirect()->route('order.list');
    }
}
