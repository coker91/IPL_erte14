<?php

namespace App\Filament\Resources;

use App\Models\CommitteeMember;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;

class CommitteeMemberResource extends ManagedResource
{
    protected static ?string $model = CommitteeMember::class;

    protected static string $managePermission = 'committee.manage';

    public static function getModelLabel(): string
    {
        return 'Susunan Pengurus';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Susunan Pengurus';
    }

    public static function fields(): array
    {
        return [Select::make('user_id')->label('Akun pengurus')->relationship('user', 'name')->searchable()->preload()->required(), Select::make('rt_position_id')->label('Jabatan')->relationship('position', 'name')->required(), DatePicker::make('start_date')->label('Mulai tugas')->required(), DatePicker::make('end_date')->label('Akhir tugas')->afterOrEqual('start_date'), TextInput::make('public_phone')->label('WhatsApp pengurus')->tel()->maxLength(30), Toggle::make('show_in_contact')->label('Tampilkan kontak')->default(false)];
    }

    public static function columns(): array
    {
        return [TextColumn::make('user.name')->label('User / Name'), TextColumn::make('position.name')->label('Position / Name'), TextColumn::make('start_date')->label('Start Date'), TextColumn::make('end_date')->label('End Date'), TextColumn::make('show_in_contact')->label('Show In Contact')];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCommitteeMemberRecords::route('/')];
    }
}
