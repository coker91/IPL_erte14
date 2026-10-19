<?php

namespace App\Filament\Resources;

use App\Models\Bill;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class BillResource extends ManagedResource
{
    protected static ?string $model = Bill::class;

    protected static string $managePermission = 'bills.manage';

    public static function getModelLabel(): string
    {
        return 'Tagihan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tagihan';
    }

    public static function fields(): array
    {
        return [Select::make('house_id')->label('Rumah')->relationship('house', 'code')->searchable()->preload()->required(), Select::make('fund_category_id')->label('Kategori')->relationship('category', 'name')->required(), DatePicker::make('period')->label('Periode (tanggal 1)')->required()->rules(['date_format:Y-m-01']), DatePicker::make('due_date')->label('Jatuh tempo')->required()->afterOrEqual('period'), TextInput::make('amount')->label('Nominal tarif disepakati')->numeric()->minValue(1)->required()];
    }

    public static function columns(): array
    {
        return [TextColumn::make('house.code')->label('House / Code')->searchable(), TextColumn::make('category.name')->label('Category / Name'), TextColumn::make('period')->label('Period'), TextColumn::make('due_date')->label('Due Date'), TextColumn::make('amount')->label('Amount')->money('IDR'), TextColumn::make('state')->label('State')->badge()];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageBillRecords::route('/')];
    }
}
