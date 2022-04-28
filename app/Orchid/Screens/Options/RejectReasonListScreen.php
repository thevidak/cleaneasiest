<?php

namespace App\Orchid\Screens\Options;

use Illuminate\Http\Request;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Matrix;

use App\Models\Options;

class RejectReasonListScreen extends Screen {

    public $name = 'RejectReasonListScreen';

    
    public $description = 'Detalji o servisu';


    public function query(): array {

        $worker_reject_array = Options::where('name', 'WORKER_REJECT_REASONS')->first()->value;
        $worker_reject= [];

        foreach ($worker_reject_array as $single) {
            $worker_reject[] = ['reason' => $single];
        }

        $driver_reject_array = Options::where('name', 'DRIVER_REJECT_REASONS')->first()->value;
        $driver_reject= [];

        foreach ($driver_reject_array as $single) {
            $driver_reject[] = ['reason' => $single];
        }

        $driver_cant_load_array = Options::where('name', 'DRIVER_CANT_LOAD_FROM_CLIENT_REASONS')->first()->value;
        $driver_cant_load= [];

        foreach ($driver_cant_load_array as $single) {
            $driver_cant_load[] = ['reason' => $single];
        }

        return [
            'worker_reject' => $worker_reject,
            'driver_reject' => $driver_reject,
            'driver_cant_load' => $driver_cant_load
        ];
    }

    public function commandBar(): array  {
        return [
            Button::make('Sacuvaj Promene')
                ->icon('note')
                ->method('update'),
        ];
    }

    public function layout(): array {
        return [
            Layout::rows([
                Matrix::make('worker_reject')
                    ->columns([
                        'Razlozi odbijanja za Servisere' => 'reason'
                    ]),
                Matrix::make('driver_reject')
                    ->columns([
                        'Razlozi odbijanja za Vozace' => 'reason'
                    ]),
                Matrix::make('driver_cant_load')
                    ->columns([
                        'Razlozi zbog koji vozac ne moze da preuzme posiljku' => 'reason'
                    ]),
            ]),
        ];
    }

    public function update(Request $request) {
        $request->validate([
            'worker_reject' => 'required',
            'driver_reject' => 'required',
            'driver_cant_load' => 'required'
        ]);

        $worker_reject_reasons = [];
        foreach ($request->worker_reject as $worker_reject_reason) {
            $worker_reject_reasons[] = $worker_reject_reason['reason'];
        }
        $worker_reject_reasons_option = Options::where('name', 'WORKER_REJECT_REASONS')->first();
        $worker_reject_reasons_option->value = $worker_reject_reasons;
        $worker_reject_reasons_option->save();

        Toast::info(__('Promene sacuvane'));
        return redirect()->route('reason.list');
    }
}
