<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Orchid\Layouts\Role\RolePermissionLayout;
use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserPasswordLayout;
use App\Orchid\Layouts\User\UserRoleLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Orchid\Access\UserSwitch;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

use App\Models\Role;
use App\Models\User;

class ClientEditScreen extends Screen {
    public $name = 'Izmeni informacije o klijentu';

    public $description = 'Svi detalji o klijentu, ime, prezime, telefon ...';

    public $permission = 'platform.systems.users';

    private $user;

    public function query(User $user): array {
        $this->user = $user;

        if (! $user->exists) {
            $this->name = 'Dodaj Novog Klijenta';
        }

        $user->load(['roles']);

        return [
            'user'       => $user,
            'permission' => $user->getStatusPermission(),
        ];
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('Obrisi'))
                ->icon('trash')
                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                ->method('remove')
                ->canSee($this->user->exists),

            Button::make(__('Sacuvaj'))
                ->icon('check')
                ->method('save'),
        ];
    }

    public function layout(): array
    {
        return [

            Layout::block(UserEditLayout::class)
                ->title(__('Opste informacije'))
                ->description(__('Opste informacije o klijentu, ove informacije vozac ne moze da menja samostalno'))
                ->commands(
                    Button::make(__('Sacuvaj'))
                        ->type(Color::DEFAULT())
                        ->icon('check')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

            Layout::block(UserPasswordLayout::class)
                ->title(__('Sifra'))
                ->description(__('Uverite se da vaš nalog koristi dugu, nasumičnu lozinku da biste ostali sigurni.'))
                ->commands(
                    Button::make(__('Sacuvaj'))
                        ->type(Color::DEFAULT())
                        ->icon('check')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

        ];
    }

    public function save(User $user, Request $request) {
        $request->validate([
            'user.name' => 'required|string',
            'user.surname' => 'required|string',
            'user.country' => 'required|string',
            'user.address' => 'required|string',
            'user.city' => 'required|string',
            'user.municipality' => 'required|string',
            'user.zip' => 'required|string',
            'user.phone' => 'required|string',
            'user.email' => ['required', Rule::unique(User::class, 'email')->ignore($user)],
            //'user.password' => 'required|string'
        ]);

        if(!$user->exists) {
            User::create([
                'name' => $request['user.name'],
                'email'=> $request['user.email'],
                'password' => bcrypt($request['user.password']),
                'surname' => $request['user.surname'],
                'country' => $request['user.country'],
                'address' => $request['user.address'],
                'city' => $request['user.city'],
                'municipality' => $request['user.municipality'],
                'zip' => $request['user.zip'],
                'phone' => $request['user.phone'],
                'role_id' => Role::CLIENT,
                'location' => googleAPIGetGeoLocationFromAddress($request['user.address'] . ", " . $request['user.city'])
            ]);
            
            Toast::info(__('Klijent je dodat.'));

            return redirect()->route('client.list');
        }

        else {
            
            $userData = $request->get('user');
            if ($user->exists && (string)$userData['password'] === '') {
                // When updating existing user null password means "do not change current password"
                unset($userData['password']);
            } else {
                $userData['password'] = Hash::make($userData['password']);
            }

            $user->fill($userData)->save();

            Toast::info(__('Klijent je sacuvan.'));
            
            return redirect()->route('client.list');
            
        }
    }

    public function remove(User $user) {
        $user->delete();

        Toast::info(__('Klijent je izbrisan'));
        return redirect()->route('client.list');
    }
}
