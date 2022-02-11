<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Rows;

class UserEditLayout extends Rows
{
    /**
     * Views.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('user.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Ime'))
                ->placeholder(__('Ime')),

            Input::make('user.surname')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Prezime'))
                ->placeholder(__('Prezime')),

            Input::make('user.email')
                ->type('email')
                ->required()
                ->title(__('Email'))
                ->placeholder(__('Email')),

            Input::make('user.country')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Drzava'))
                ->placeholder(__('Drzava')),

            Input::make('user.address')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Adresa'))
                ->placeholder(__('Adresa')),

            Input::make('user.city')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Grad'))
                ->placeholder(__('Grad')),

            Input::make('user.municipality')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Opstina'))
                ->placeholder(__('Opstina')),

            Input::make('user.zip')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Postanski Broj'))
                ->placeholder(__('Postanski Broj')),

            Input::make('user.phone')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Telefon'))
                ->placeholder(__('Telefon')),
        ];
    }
}
