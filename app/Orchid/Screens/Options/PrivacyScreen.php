<?php

namespace App\Orchid\Screens\Options;

use Illuminate\Http\Request;

use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;

use Orchid\Screen\Actions\Button;

use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

use App\Models\Privacy;

class PrivacyScreen extends Screen
{

    public $name = 'Politika Privatnosti';


    public function query(): array
    {
        $privacy = Privacy::where('name', 'Default')->first();

        return [
            'privacy_text' => $privacy->text
        ];
    }


    public function commandBar(): array  {
        return [
            Button::make('Sacuvaj Promene')
                ->icon('note')
                ->method('update'),
        ];
    }


    public function layout(): array
    {
        return [
            Layout::rows([
                Label::make('title')
                        ->title('Tekst:'),
                TextArea::make('privacy_text')
                    ->rows(40),


            ]),
        ];
    }

    public function update(Request $request) {
        $request->validate([
            'privacy_text' => 'required',
        ]);

        $privacy_text = Privacy::where('name', 'Default')->first();
        $privacy_text->text = $request->privacy_text;
        $privacy_text->save();

        Toast::info(__('Promene sacuvane'));
        return redirect()->route('privacy.list');
    }
}
