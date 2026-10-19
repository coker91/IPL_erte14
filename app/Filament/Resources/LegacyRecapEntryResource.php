<?php

namespace App\Filament\Resources;

use App\Models\LegacyRecapEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class LegacyRecapEntryResource extends ManagedResource
{
    protected static ?string $model = LegacyRecapEntry::class;

    protected static string $managePermission = 'dashboard.view';

    public static function getModelLabel(): string
    {
        return 'Rekap Sumber Lama';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Rekap Sumber Lama';
    }

    public static function fields(): array
    {
        return [];
    }

    public static function columns(): array
    {
        return [TextColumn::make('house.code')->label('House / Code')->searchable(), TextColumn::make('period')->label('Period'), TextColumn::make('status')->label('Status')->badge(), TextColumn::make('source_updated_at')->label('Source Updated At')];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageLegacyRecapEntryRecords::route('/')];
    }
}
