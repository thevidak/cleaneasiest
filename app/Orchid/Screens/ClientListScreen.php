<?php

namespace App\Orchid\Screens;

use Orchid\Screen\Screen;
use App\Models\User;
use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserFiltersLayout;
use App\Orchid\Layouts\User\UserListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

use App\Orchid\Layouts\ClientListLayout;

class ClientListScreen extends Screen
{
    public $name = 'Klijenti';

    public $description = 'Spisak svih registrovanih klijenata';

    public function query(): array
    {
        return [
            'users' => User::where('role_id',5)->paginate()
        ];
    }

    public function commandBar(): array
    {
        return [
            Link::make(__('Dodaj Novog'))
                ->icon('plus')
                ->route('client.edit'),
        ];
    }

    public function layout(): array
    {
        return [
            ClientListLayout::class,
        ];
    }

    public function remove(User $user) {
        $user->delete();

        Toast::info(__('Klijent je izbrisan'));
        return redirect()->route('client.list');
    }
}
