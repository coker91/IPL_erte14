<?php

namespace App\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

abstract class ManageMasterRecords extends ManageRecords
{
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
