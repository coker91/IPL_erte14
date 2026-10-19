<?php

namespace App\Filament\Resources;

use App\Models\Resident;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;

class ResidentResource extends ManagedResource
{
    protected static ?string $model = Resident::class;

    protected static string $managePermission = 'residents.manage';

    public static function getModelLabel(): string
    {
        return 'Warga';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Warga';
    }

    public static function fields(): array
    {
        return [TextInput::make('name')->label('Nama')->required()->maxLength(255), TextInput::make('phone')->label('Telepon')->tel()->maxLength(30), Toggle::make('is_active')->label('Aktif')->default(true)];
    }

    public static function columns(): array
    {
        return [TextColumn::make('name')->label('Name')->searchable(), TextColumn::make('phone')->label('Phone'), TextColumn::make('is_active')->label('Is Active')];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageResidentRecords::route('/')];
    }
}
