<?php

namespace App\Filament\Resources;

use App\Models\AppSetting;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class AppSettingResource extends ManagedResource
{
    protected static ?string $model = AppSetting::class;

    protected static string $managePermission = 'settings.manage';

    public static function getModelLabel(): string
    {
        return 'Pengaturan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pengaturan';
    }

    public static function fields(): array
    {
        return [TextInput::make('key')->label('Kunci')->required()->unique(ignoreRecord: true), Textarea::make('value')->label('Nilai')->required()->maxLength(5000)];
    }

    public static function columns(): array
    {
        return [TextColumn::make('key')->label('Key'), TextColumn::make('value')->label('Value')];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAppSettingRecords::route('/')];
    }
}
