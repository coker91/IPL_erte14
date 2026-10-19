<?php

namespace App\Filament\Resources;

use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class ActivityLogResource extends ManagedResource
{
    protected static ?string $model = ActivityLog::class;

    protected static string $managePermission = 'dashboard.view';

    public static function getModelLabel(): string
    {
        return 'Riwayat Aktivitas';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Riwayat Aktivitas';
    }

    public static function fields(): array
    {
        return [];
    }

    public static function columns(): array
    {
        return [TextColumn::make('actor_id')->label('Actor Id'), TextColumn::make('action')->label('Action'), TextColumn::make('entity_type')->label('Entity Type'), TextColumn::make('entity_id')->label('Entity Id'), TextColumn::make('created_at')->label('Created At')];
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
        return ['index' => Pages\ManageActivityLogRecords::route('/')];
    }
}
