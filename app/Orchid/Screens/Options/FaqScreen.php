<?php

namespace App\Orchid\Screens\Options;

use Illuminate\Http\Request;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;

use App\Models\Faq;

class FaqScreen extends Screen {

    public $name = 'FAQ';

    
    public $description = 'Često postavljana pitanja';


    public function query(): array {

        $faqs = Faq::all();
        $faq_list= [];

        foreach ($faqs as $faq) {
            $faq_list[] = [
                'question' => $faq->question,
                'answer' => $faq->answer
            ];
        }

        return [
            'faq' => $faq_list
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
                Matrix::make('faq')
                    ->title('Spisak pitanja i odgovora:')
                    ->columns([
                        'Pitanje' => 'question',
                        'Odgovor' => 'answer'
                    ])
              

            ]),
        ];
    }

    public function update(Request $request) {
        $request->validate([
            'faq' => 'required',
        ]);

        Faq::truncate();
        foreach ($request->faq as $faq) {
            Faq::create([
                'question' => $faq['question'],
                'answer' => $faq['answer']
            ]);
        }
        
        Toast::info(__('Promene sacuvane'));
        return redirect()->route('faq.list');
    }
}
