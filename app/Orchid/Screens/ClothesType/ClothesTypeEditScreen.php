<?php

namespace App\Orchid\Screens\ClothesType;

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

class ClothesTypeEditScreen extends Screen
{

    public $name = 'ClothesTypeEditScreen';

    public $exists = false;

    public function query(ClothesType $clothes): array{
        $this->exists = $clothes->exists;

        if($this->exists){
            $this->name = 'Izmeni Servis';
        }
        
        return [
            'clothes' => $clothes,
            'clothes_picture' => $clothes->picture, 
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
                    Cropper::make('clothes_picture')
                    ->title('Picture')
                    ->width(300)
                    ->height(300)
                    ->maxFileSize(8)
                    ->targetUrl()
                    ->horizontal(),
                    
                    Input::make('clothes.name')
                        ->type('text')
                        ->max(255)
                        ->required()
                        ->title(__('Ime'))
                        ->placeholder(__('Ime')),
                ])->fullWidth(),
            ]),
            
        ];
    }

    public function createOrUpdate(ClothesType $clothes, Request $request)  {
        $clothes->fill($request->get('clothes'))->save();

        
        if (!str_contains($request->clothes_picture, 'default'))
            $file_location = explode('storage/', $request->clothes_picture)[1];
            if (isset($file_location)) {
                rename ('storage/' . $file_location, 'storage/images/clothes/' . $clothes->id . '.png');
            }

        Alert::info('Artikal je uspesno sacuvan');
        return redirect()->route('clothes.list');
    }

    public function remove(ClothesType $clothes)
    {
        $clothes->delete();

        Alert::info('You have successfully deleted the clothes.');

        return redirect()->route('clothes.list');
    }
}
