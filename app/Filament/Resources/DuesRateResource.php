<?php

namespace App\Filament\Resources;

use App\Models\DuesRate;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class DuesRateResource extends ManagedResource
{
    protected static ?string $model = DuesRate::class;

    protected static string $managePermission = 'bills.manage';

    public static function getModelLabel(): string
    {
        return 'Tarif Iuran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tarif Iuran';
    }

    public static function fields(): array
    {
        return [Select::make('fund_category_id')->label('Kategori')->relationship('category', 'name')->required()->disabledOn('edit'), TextInput::make('amount')->label('Nominal')->numeric()->minValue(1)->required()->disabledOn('edit'), DatePicker::make('effective_from')->label('Berlaku dari')->required()->disabledOn('edit'), DatePicker::make('effective_to')->label('Berlaku sampai')->afterOrEqual('effective_from')];
    }

    public static function columns(): array
    {
        return [TextColumn::make('category.name')->label('Category / Name'), TextColumn::make('amount')->label('Amount')->money('IDR'), TextColumn::make('effective_from')->label('Effective From'), TextColumn::make('effective_to')->label('Effective To')];
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageDuesRateRecords::route('/')];
    }
}
