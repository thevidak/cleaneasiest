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

use App\Orchid\Layouts\DriverListLayout;

class DriverListScreen extends Screen
{
    public $name = 'Vozaci';

    public $description = 'Spisak svih registrovanih vozaca';

    public function query(): array
    {
        return [
            'users' => User::where('role_id',4)->paginate()
        ];
    }

    public function commandBar(): array
    {
        return [
            Link::make(__('Dodaj Novog'))
                ->icon('plus')
                ->route('driver.edit'),
        ];
    }

    public function layout(): array
    {
        return [
            DriverListLayout::class,
        ];
    }

    public function remove(User $user) {
        $user->delete();

        Toast::info(__('User was removed'));
        return redirect()->route('driver.list');
    }
}
