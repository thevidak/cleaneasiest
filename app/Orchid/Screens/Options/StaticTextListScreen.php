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

use App\Models\Options;

class StaticTextListScreen extends Screen
{
    public $name = 'Statički tekstovi';
    public $description = 'Tekstovi definisani u bazi, koji se dinamički učitavaju u aplikaciji';

    public function query(): array {

        $support_text = Options::where('name', 'SUPPORT_TEXT')->first()->value;

        return [
            'support_text' => $support_text
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
                Input::make('support_text.title')
                        ->title('Naslov:')
                        ->required(),
                TextArea::make('support_text.text')
                    ->title('Tekst:')
                    ->required()
                    ->rows(20),
            ])
            ->title('Tekst na stranici za podršku:'),
        ];
    }

    public function update(Request $request) {
        $request->validate([
            'support_text' => 'required',
        ]);

        $support_text = Options::where('name', 'SUPPORT_TEXT')->first();
        $support_text->value = $request->support_text;
        $support_text->save();

        Toast::info(__('Promene sacuvane'));
        return redirect()->route('statictext.list');
    }
}
