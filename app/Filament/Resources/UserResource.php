<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class UserResource extends ManagedResource
{
    protected static ?string $model = User::class;

    protected static string $managePermission = 'users.manage';

    public static function getModelLabel(): string
    {
        return 'Akun Pengguna';
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('users.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate() && (! $record->hasRole('super_admin') || auth()->user()->can('roles.manage'));
    }

    public static function fields(): array
    {
        return [
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('username')->required()->alphaDash()->unique(ignoreRecord: true),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')->password()->revealable()->minLength(12)->required(fn (string $operation) => $operation === 'create')->dehydrated(fn ($state) => filled($state)),
            Select::make('resident_id')->label('Profil warga')->relationship('resident', 'name')->searchable()->preload()->unique(ignoreRecord: true),
            Toggle::make('is_active')->label('Aktif')->default(true),
            CheckboxList::make('roles')->relationship('roles', 'name')->visible(fn () => auth()->user()->can('roles.manage'))->dehydrated(fn () => auth()->user()->can('roles.manage')),
        ];
    }

    public static function columns(): array
    {
        return [TextColumn::make('name')->searchable(), TextColumn::make('username')->searchable(), TextColumn::make('roles.name')->badge(), TextColumn::make('is_active')];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUserRecords::route('/')];
    }
}
