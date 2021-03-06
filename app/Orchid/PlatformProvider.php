<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;
use Orchid\Support\Color;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * @param Dashboard $dashboard
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * @return Menu[]
     */
    public function registerMainMenu(): array
    {
        return [
            Menu::make('Nove')
                ->icon('note')
                ->route('order.newlist')
                ->title('Narudžbine'),
            Menu::make('Stopirane')
                ->icon('note')
                ->route('order.stalledlist'),
            Menu::make('Završene')
                ->icon('note')
                ->route('order.finishedlist'),
            Menu::make('Sve')
                ->icon('note')
                ->route('order.list'),
            
            
            Menu::make(__('Vozači'))
            ->icon('user')
            ->route('driver.list')
            ->permission('platform.systems.users')
            ->title('Korisnici'),


            Menu::make(__('Serviseri'))
            ->icon('user')
            ->route('worker.list')
            ->permission('platform.systems.users'),


            Menu::make(__('Klijenti'))
            ->icon('user')
            ->route('client.list')
            ->permission('platform.systems.users'),


            Menu::make(__('Servisi'))
            ->icon('book-open')
            ->route('service.list')
            ->permission('platform.systems.users')
            ->title('Podešavanja'),

            Menu::make(__('Tipovi Odeće'))
            ->icon('handbag')
            ->route('clothes.list')
            ->permission('platform.systems.users'),
            
            Menu::make(__('Težine'))
            ->icon('basket-loaded')
            ->route('weight.list')
            ->permission('platform.systems.users'),

            Menu::make(__('Razlozi odbijanja'))
            ->icon('close')
            ->route('reason.list')
            ->permission('platform.systems.users'),

            Menu::make(__('Statični tekst'))
            ->icon('text-left')
            ->route('statictext.list')
            ->permission('platform.systems.users'),

            Menu::make(__('Politika privatnosti'))
            ->icon('lock')
            ->route('privacy.list')
            ->permission('platform.systems.users'),

            Menu::make(__('FAQ'))
            ->icon('question')
            ->route('faq.list')
            ->permission('platform.systems.users'),
          
           
/*
            Menu::make('Example screen')
                ->icon('monitor')
                ->route('platform.example')
                ->title('Navigation')
                ->badge(function () {
                    return 6;
                }),

            Menu::make('Dropdown menu')
                ->icon('code')
                ->list([
                    Menu::make('Sub element item 1')->icon('bag'),
                    Menu::make('Sub element item 2')->icon('heart'),
                ]),

            Menu::make('Basic Elements')
                ->title('Form controls')
                ->icon('note')
                ->route('platform.example.fields'),

            Menu::make('Advanced Elements')
                ->icon('briefcase')
                ->route('platform.example.advanced'),

            Menu::make('Text Editors')
                ->icon('list')
                ->route('platform.example.editors'),

            Menu::make('Overview layouts')
                ->title('Layouts')
                ->icon('layers')
                ->route('platform.example.layouts'),

            Menu::make('Chart tools')
                ->icon('bar-chart')
                ->route('platform.example.charts'),

            Menu::make('Cards')
                ->icon('grid')
                ->route('platform.example.cards')
                ->divider(),

            Menu::make('Documentation')
                ->title('Docs')
                ->icon('docs')
                ->url('https://orchid.software/en/docs'),

            Menu::make('Changelog')
                ->icon('shuffle')
                ->url('https://github.com/orchidsoftware/platform/blob/master/CHANGELOG.md')
                ->target('_blank')
                ->badge(function () {
                    return Dashboard::version();
                }, Color::DARK()),

            Menu::make(__('Users'))
                ->icon('user')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access rights')),

            Menu::make(__('Roles'))
                ->icon('lock')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),
*/
        ];
    }

    /**
     * @return Menu[]
     */
    public function registerProfileMenu(): array
    {
        return [
            Menu::make('Profile')
                ->route('platform.profile')
                ->icon('user'),
        ];
    }

    /**
     * @return ItemPermission[]
     */
    public function registerPermissions(): array
    {
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users')),
        ];
    }
}
