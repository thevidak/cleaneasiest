<?php

declare(strict_types=1);

use App\Orchid\Screens\Examples\ExampleCardsScreen;
use App\Orchid\Screens\Examples\ExampleChartsScreen;
use App\Orchid\Screens\Examples\ExampleFieldsAdvancedScreen;
use App\Orchid\Screens\Examples\ExampleFieldsScreen;
use App\Orchid\Screens\Examples\ExampleLayoutsScreen;
use App\Orchid\Screens\Examples\ExampleScreen;
use App\Orchid\Screens\Examples\ExampleTextEditorsScreen;
use App\Orchid\Screens\PlatformScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;


use App\Orchid\Screens\Order\OrderNewListScreen;
use App\Orchid\Screens\Order\OrderStalledListScreen;
use App\Orchid\Screens\Order\OrderFinishedListScreen;
use App\Orchid\Screens\Order\OrderListScreen;
use App\Orchid\Screens\Order\OrderShowScreen;
use App\Orchid\Screens\Order\OrderEditScreen;

use App\Orchid\Screens\DriverListScreen;
use App\Orchid\Screens\DriverEditScreen;

use App\Orchid\Screens\WorkerListScreen;
use App\Orchid\Screens\WorkerEditScreen;

use App\Orchid\Screens\ClientListScreen;
use App\Orchid\Screens\ClientEditScreen;

use App\Orchid\Screens\ServiceListScreen;
use App\Orchid\Screens\ServiceEditScreen;

use App\Orchid\Screens\ClothesType\ClothesTypeListScreen;
use App\Orchid\Screens\ClothesType\ClothesTypeEditScreen;

use App\Orchid\Screens\WeightClass\WeightClassListScreen;

use App\Orchid\Screens\Options\RejectReasonListScreen;
use App\Orchid\Screens\Options\PrivacyScreen;
use App\Orchid\Screens\Options\StaticTextListScreen;
use App\Orchid\Screens\Options\FaqScreen;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/

// Main
Route::screen('/main', OrderNewListScreen::class)->name('platform.main');

// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push(__('Profile'), route('platform.profile'));
    });

// Platform > System > Users
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(function (Trail $trail, $user) {
        return $trail
            ->parent('platform.systems.users')
            ->push(__('User'), route('platform.systems.users.edit', $user));
    });

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.systems.users')
            ->push(__('Create'), route('platform.systems.users.create'));
    });

// Platform > System > Users > User
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push(__('Users'), route('platform.systems.users'));
    });

// Platform > System > Roles > Role
Route::screen('roles/{roles}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(function (Trail $trail, $role) {
        return $trail
            ->parent('platform.systems.roles')
            ->push(__('Role'), route('platform.systems.roles.edit', $role));
    });

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.systems.roles')
            ->push(__('Create'), route('platform.systems.roles.create'));
    });

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push(__('Roles'), route('platform.systems.roles'));
    });

// Example...
Route::screen('example', ExampleScreen::class)
    ->name('platform.example')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Example screen');
    });

Route::screen('example-fields', ExampleFieldsScreen::class)->name('platform.example.fields');
Route::screen('example-layouts', ExampleLayoutsScreen::class)->name('platform.example.layouts');
Route::screen('example-charts', ExampleChartsScreen::class)->name('platform.example.charts');
Route::screen('example-editors', ExampleTextEditorsScreen::class)->name('platform.example.editors');
Route::screen('example-cards', ExampleCardsScreen::class)->name('platform.example.cards');
Route::screen('example-advanced', ExampleFieldsAdvancedScreen::class)->name('platform.example.advanced');

Route::screen('orders', OrderListScreen::class)->name('order.list');
Route::screen('orders-new', OrderNewListScreen::class)->name('order.newlist');
Route::screen('orders-finished', OrderFinishedListScreen::class)->name('order.finishedlist');
Route::screen('orders-stalled', OrderStalledListScreen::class)->name('order.stalledlist');
Route::screen('order/{order}', OrderShowScreen::class)->name('order.show');
Route::screen('order/edit/{order?}', OrderEditScreen::class)->name('order.edit');

Route::screen('driver/{driver?}', DriverEditScreen::class)->name('driver.edit');
Route::screen('drivers', DriverListScreen::class)->name('driver.list');

Route::screen('worker/{worker?}', WorkerEditScreen::class)->name('worker.edit');
Route::screen('workers', WorkerListScreen::class)->name('worker.list');

Route::screen('client/{client?}', ClientEditScreen::class)->name('client.edit');
Route::screen('clients', ClientListScreen::class)->name('client.list');

Route::screen('services', ServiceListScreen::class)->name('service.list');
Route::screen('service/{service?}', ServiceEditScreen::class)->name('service.edit');

Route::screen('clothes', ClothesTypeListScreen::class)->name('clothes.list');
Route::screen('cloth/{clothes?}', ClothesTypeEditScreen::class)->name('clothes.edit');

Route::screen('weights', WeightClassListScreen::class)->name('weight.list');


Route::screen('reasons', RejectReasonListScreen::class)->name('reason.list');
Route::screen('privacy', PrivacyScreen::class)->name('privacy.list');
Route::screen('statictext', StaticTextListScreen::class)->name('statictext.list');
Route::screen('faq', FaqScreen::class)->name('faq.list');

