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

use App\Orchid\Layouts\WorkerListLayout;

class WorkerListScreen extends Screen
{
    public $name = 'Serviseri';

    public $description = 'Spisak svih registrovanih servisera';

    public function query(): array
    {
        return [
            'users' => User::where('role_id',3)->paginate()
        ];
    }

    public function commandBar(): array
    {
        return [
            Link::make(__('Dodaj Novog'))
                ->icon('plus')
                ->route('worker.edit'),
        ];
    }

    public function layout(): array
    {
        return [
            WorkerListLayout::class,
        ];
    }

    public function remove(User $user) {
        $user->delete();

        Toast::info(__('Serviser je obrisan'));
        return redirect()->route('worker.list');
    }
}
