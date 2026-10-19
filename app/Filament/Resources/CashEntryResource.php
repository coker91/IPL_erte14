<?php

namespace App\Filament\Resources;

use App\Models\CashEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class CashEntryResource extends ManagedResource
{
    protected static ?string $model = CashEntry::class;

    protected static string $managePermission = 'cash.manage';

    public static function getModelLabel(): string
    {
        return 'Buku Kas';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Buku Kas';
    }

    public static function fields(): array
    {
        return [Select::make('fund_category_id')->label('Kategori')->relationship('category', 'name')->required(), Select::make('entry_type')->label('Jenis')->options(['income' => 'Pemasukan lain', 'expense' => 'Pengeluaran'])->required(), TextInput::make('amount')->label('Nominal')->numeric()->minValue(1)->required(), DatePicker::make('entry_date')->label('Tanggal')->required()->maxDate('today'), Textarea::make('description')->label('Keterangan')->required()->maxLength(2000)];
    }

    public static function columns(): array
    {
        return [TextColumn::make('category.name')->label('Category / Name'), TextColumn::make('entry_type')->label('Entry Type'), TextColumn::make('amount')->label('Amount')->money('IDR'), TextColumn::make('entry_date')->label('Entry Date'), TextColumn::make('description')->label('Description')];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCashEntryRecords::route('/')];
    }
}
