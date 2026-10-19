<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\WargaPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    WargaPanelProvider::class,
];
