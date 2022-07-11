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

use Orchid\Screen\Fields\Map;
use Orchid\Screen\Fields\Input;

use App\Models\Role;
use App\Models\User;

class WorkerEditScreen extends Screen {
    public $name = 'Izmeni informacije o serviseru';

    public $description = 'Svi detalji o serviseru, ime, prezime, telefon ...';

    public $permission = 'platform.systems.users';

    private $user;

    public function query(User $user): array {
        $this->user = $user;

        if (! $user->exists) {
            $this->name = 'Dodaj Novog Servisera';
        }

        $user->load(['roles']);

        return [
            'user'       => $user,
            'permission' => $user->getStatusPermission(),
            'place' => [
                'lat' => $user->location['latitude'],
                'lng' => $user->location['longitude'],
            ],
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
            Layout::block(
                Layout::rows(
                    [
                        Input::make('user.name')
                            ->type('text')
                            ->max(255)
                            ->required()
                            ->title(__('Ime'))
                            ->placeholder(__('Ime'))
                        ,
                        Input::make('user.surname')
                            ->type('text')
                            ->max(255)
                            ->required()
                            ->title(__('Prezime'))
                            ->placeholder(__('Prezime'))
                        ,
                        Input::make('user.email')
                            ->type('email')
                            ->required()
                            ->title(__('Email'))
                            ->placeholder(__('Email'))
                        ,
                        Map::make('place')
                            ->title('Lokacija Servisa')
                        ,
                        Input::make('user.country')
                            ->type('text')
                            ->max(255)
                            ->required()
                            ->title(__('Drzava'))
                            ->placeholder(__('Drzava'))
                        ,
                        Input::make('user.address')
                            ->type('text')
                            ->max(255)
                            ->required()
                            ->title(__('Adresa'))
                            ->placeholder(__('Adresa'))
                        ,
                        Input::make('user.city')
                            ->type('text')
                            ->max(255)
                            ->required()
                            ->title(__('Grad'))
                            ->placeholder(__('Grad'))
                        ,
                        Input::make('user.municipality')
                            ->type('text')
                            ->max(255)
                            ->required()
                            ->title(__('Opstina'))
                            ->placeholder(__('Opstina'))
                        ,
                        Input::make('user.zip')
                            ->type('text')
                            ->max(255)
                            ->required()
                            ->title(__('Postanski Broj'))
                            ->placeholder(__('Postanski Broj'))
                        ,
                        Input::make('user.phone')
                            ->type('text')
                            ->max(255)
                            ->required()
                            ->title(__('Telefon'))
                            ->placeholder(__('Telefon'))
                        ,
                    ]
                )
            )
            ->title(__('Opste informacije'))
            ->description(__('Opste informacije o serviseru, ove informacije vozac ne moze da menja samostalno'))
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
                'role_id' => Role::WORKER,
                'location' => [
                    'latitude' => $request['place.lat'],
                    'longitude' =>$request['place.lng']
                ]
            ]);

            
            Toast::info(__('Serviser je dodat.'));
            return redirect()->route('worker.list');
        }

        else {
            
            $userData = $request->get('user');
            if ($user->exists && (string)$userData['password'] === '') {
                // When updating existing user null password means "do not change current password"
                //unset($userData['password']);
            } else {
                $user->password = bcrypt($request['user.password']);
                //$userData['password'] = Hash::make($userData['password']);
            }

            $user->name = $request['user.name'];
            $user->email = $request['user.email'];

            $user->surname = $request['user.surname'];
            $user->country = $request['user.country'];
            $user->address = $request['user.address'];
            $user->city = $request['user.city'];
            $user->municipality = $request['user.municipality'];
            $user->zip = $request['user.zip'];
            $user->phone = $request['user.phone'];
            $user->role_id = Role::WORKER;
            $user->location = [
                'latitude' => $request['place.lat'],
                'longitude' =>$request['place.lng']
            ];
            $user->save();
            //$user->location = googleAPIGetGeoLocationFromAddress($request['user.address'] . ", " . $request['user.city'])


            Toast::info(__('Serviser je sacuvan.'));
            return redirect()->route('worker.list');
        }
    }

    public function remove(User $user) {
        $user->delete();

        Toast::info(__('Serviser je izbrisan'));
        return redirect()->route('worker.list');
    }
}
