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
use Orchid\Screen\Actions\Link;
use Orchid\Screen\TD;


use App\Models\Order;
use App\Models\User;
use App\Models\Service;
use App\Models\WeightClass;
use App\Models\ClothesType;
use App\Models\ServiceType;
use App\Models\SubService;



class OrderShowScreen extends Screen {

    public $name = 'Narudzbina';

    public $order;
    
    public $description = 'Detalji o narudzbini';
    
    public function query(Order $order): array {
        return [
            'order' => $order,
            'subservices' => $order->subservices,
            'status' => $this->returnStatus($order->status),
            'client' => [
                'id' =>$order->client_id,
                'name' => User::where('id',$order->client_id)->first()->fullName
            ],
            'worker' => [
                'id' =>$order->worker_id,
                'name' => User::where('id',$order->worker_id)->first() != null ? User::where('id',$order->worker_id)->first()->fullName : "Nije dodeljen"
            ],
            'driver' => [
                'id' =>$order->driver_id,
                'name' => User::where('id',$order->driver_id)->first() != null ? User::where('id',$order->driver_id)->first()->fullName : "Nije dodeljen"
            ],
            //'takeout_date' => $order->takeout_date['date'] . " od " . $order->takeout_date['start_time'] . " do " . $order->takeout_date['end_time'],
            //'delivery_date' => $order->delivery_date['date'] . " od " . $order->delivery_date['start_time'] . " do " . $order->delivery_date['end_time']
        ];
    }

    public function commandBar(): array
    {
        
        return [
            /*
            Button::make('Izmeni')
                ->icon('trash')
                ->method('remove'),
            */
        ];
        
    }

    public function layout(): array
    {
        return [
            Layout::columns([
                Layout::rows([
                    Label::make('status')
                        ->title('Status')
                        ->horizontal(),
                    Label::make('client.name')
                        ->title('Klijent')
                        ->horizontal(),
                    Label::make('worker.name')
                        ->title('Serviser')
                        ->horizontal(),
                    Label::make('driver.name')
                        ->title('Vozač')
                        ->horizontal(),
                    Label::make('order.created_at')
                        ->title('Datum kreiranja')
                        ->horizontal(),
                    Label::make('order.updated_at')
                        ->title('Datum poslednje promene')
                        ->horizontal(),
                    Label::make('order.price')
                        ->title('Cena')
                        ->horizontal(),
                    Label::make('takeout_data')
                        ->title('Datum Preuzimanja')
                        ->horizontal(),
                    Label::make('delivery_data')
                        ->title('Datum Isporuke')
                        ->horizontal(),
                ]),
                Layout::table('subservices', [
                    TD::make('service_id', 'Servis')
                        ->render(function (SubService $subservice) {
                            return Label::make()
                                ->title(Service::where('id', $subservice->service_id)->first()->name);
                            })
                    ,
                    TD::make('subclass_type_id', 'Tezina/Aritkal')
                        ->render(function (SubService $subservice) {
                            $service = Service::where('id', $subservice->service_id)->first();
                            if ($service->type == 0) {
                                return Label::make()
                                    ->title(WeightClass::where('id', $subservice->subclass_type_id)->first()->name);
                            }
                            else {
                                return Label::make()
                                    ->title(ClothesType::where('id', $subservice->subclass_type_id)->first()->name);
                            }
                        })
                            
                    ,
                    TD::make('amount', 'Kolicina')
                    ,
                ])->title('Spisak svih servisa')
            ])
        ];
    }

    public function returnStatus(int $statusId) {
        switch ($statusId) {
            case 0 :
                return 'Narudzbina se kreira';
                break;
            case 1 :
                return 'Narudzbina kreirana';
                break;
            case 2 :
                return 'Serviser prihvatio';
                break;
            case 3 :
                return 'Vozac preuzeo od klijenta';
                break;
            case 4 :
                return 'Vozac prevozi do servisera';
                break;
            case 5 :
                return 'Servis';
                break;
            case 6 :
                return 'Serviser zavrsio';
                break;
            case 7 :
                return 'Vozac preuzima od servisera';
                break;
            case 8 :
                return 'Vozac dostavlja do klijenta';
                break;
            case 9 :
                return 'Narudzbina zavrsena';
                break;
            default :
                return 'Narudzbina stopirana';
                break;
        }
    }


    public function remove(Order $order)
    {

    }
}
