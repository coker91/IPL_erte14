<?php

namespace App\Filament\Resources;

use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

abstract class ManagedResource extends Resource
{
    protected static string $managePermission = 'settings.manage';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('admin.access') && (auth()->user()->can(static::$managePermission) || auth()->user()->can('dashboard.view'));
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can(static::$managePermission);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::fields());
    }

    public static function table(Table $table): Table
    {
        return $table->columns(static::columns())->recordActions([EditAction::make()])->defaultSort('id', 'desc');
    }

    public static function fields(): array
    {
        return [];
    }

    public static function columns(): array
    {
        return [];
    }
}
