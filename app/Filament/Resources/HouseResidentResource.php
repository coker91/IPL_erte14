<?php

namespace App\Filament\Resources;

use App\Models\HouseResident;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;

class HouseResidentResource extends ManagedResource
{
    protected static ?string $model = HouseResident::class;

    protected static string $managePermission = 'houses.manage';

    public static function getModelLabel(): string
    {
        return 'Penghuni Rumah';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Penghuni Rumah';
    }

    public static function fields(): array
    {
        return [Select::make('house_id')->label('Rumah')->relationship('house', 'code')->searchable()->preload()->required(), Select::make('resident_id')->label('Warga')->relationship('resident', 'name')->searchable()->preload()->required(), Select::make('relation_type')->label('Hubungan')->options(['owner' => 'Pemilik', 'occupant' => 'Penghuni'])->default('occupant')->required(), DatePicker::make('start_date')->label('Mulai')->required(), DatePicker::make('end_date')->label('Akhir')->afterOrEqual('start_date'), Toggle::make('is_primary_contact')->label('Kontak utama')->default(true), Toggle::make('can_manage_payments')->label('Boleh mengelola pembayaran')->default(false)];
    }

    public static function columns(): array
    {
        return [TextColumn::make('house.code')->label('House / Code')->searchable(), TextColumn::make('resident.name')->label('Resident / Name')->searchable(), TextColumn::make('start_date')->label('Start Date'), TextColumn::make('end_date')->label('End Date'), TextColumn::make('can_manage_payments')->label('Can Manage Payments')];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageHouseResidentRecords::route('/')];
    }
}
