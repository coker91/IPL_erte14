<?php

namespace App\Filament\Resources;

use App\Models\House;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;

class HouseResource extends ManagedResource
{
    protected static ?string $model = House::class;

    protected static string $managePermission = 'houses.manage';

    public static function getModelLabel(): string
    {
        return 'Rumah';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Rumah';
    }

    public static function fields(): array
    {
        return [TextInput::make('code')->label('Blok / Nomor Rumah')->required()->unique(ignoreRecord: true)->maxLength(40), TextInput::make('source_label')->label('Label Sumber')->maxLength(255), Select::make('occupancy_status')->label('Status')->options(['occupied' => 'Dihuni', 'vacant' => 'Kosong', 'facility' => 'Fasilitas'])->required()->default('occupied'), Toggle::make('is_ipl_subject')->label('Wajib IPL')->default(true), DatePicker::make('billing_start_period')->label('Mulai Penagihan'), DatePicker::make('billing_end_period')->label('Akhir Penagihan')->afterOrEqual('billing_start_period')];
    }

    public static function columns(): array
    {
        return [TextColumn::make('code')->label('Code')->searchable(), TextColumn::make('source_label')->label('Source Label')->searchable(), TextColumn::make('occupancy_status')->label('Occupancy Status'), TextColumn::make('is_ipl_subject')->label('Is Ipl Subject')];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageHouseRecords::route('/')];
    }
}
