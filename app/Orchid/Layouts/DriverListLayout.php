<?php

declare(strict_types=1);

namespace App\Orchid\Layouts;

use Orchid\Platform\Models\User;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Persona;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class DriverListLayout extends Table {

    public $target = 'users';

    public function columns(): array {
        return [
            TD::make('id', __('ID'))
                ->sort()
                ->cantHide(),

            TD::make('fullName', __('Ime'))
                ->sort()
                ->cantHide()
                //->filter(Input::make())
                ->render(function (User $user) {
                    return Link::make($user->fullName)
                        ->route('driver.edit', $user);
                }),

            TD::make('email', __('Email'))
                ->sort()
                ->cantHide()
                //->filter(Input::make())
            ,
            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (User $user) {
                    return DropDown::make()
                        ->icon('options-vertical')
                        ->list([

                            Link::make(__('Promeni'))
                                ->route('driver.edit', $user->id)
                                ->icon('pencil'),

                            Button::make(__('Obrisi'))
                                ->icon('trash')
                                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                                ->method('remove', [
                                    'id' => $user->id,
                                ]),
                        ]);
                }),
            ];
    }
}
