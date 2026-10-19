<?php

namespace App\Filament\Resources;

use App\Models\FundCategory;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;

class FundCategoryResource extends ManagedResource
{
    protected static ?string $model = FundCategory::class;

    protected static string $managePermission = 'settings.manage';

    public static function getModelLabel(): string
    {
        return 'Kategori Dana';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kategori Dana';
    }

    public static function fields(): array
    {
        return [TextInput::make('code')->label('Kode')->required()->unique(ignoreRecord: true), TextInput::make('name')->label('Nama')->required(), Toggle::make('is_active')->label('Aktif')->default(true)];
    }

    public static function columns(): array
    {
        return [TextColumn::make('code')->label('Code')->searchable(), TextColumn::make('name')->label('Name')->searchable(), TextColumn::make('is_active')->label('Is Active')];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageFundCategoryRecords::route('/')];
    }
}
