<?php

namespace App\Filament\Resources;

use App\Models\BankAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;

class BankAccountResource extends ManagedResource
{
    protected static ?string $model = BankAccount::class;

    protected static string $managePermission = 'settings.manage';

    public static function getModelLabel(): string
    {
        return 'Rekening Pembayaran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Rekening Pembayaran';
    }

    public static function fields(): array
    {
        return [Select::make('fund_category_id')->label('Kategori')->relationship('category', 'name')->required(), TextInput::make('bank_name')->label('Bank')->required(), TextInput::make('account_number')->label('Nomor rekening')->required()->maxLength(60), TextInput::make('account_holder')->label('Atas nama')->required(), Toggle::make('is_active')->label('Aktif')->default(true)];
    }

    public static function columns(): array
    {
        return [TextColumn::make('category.name')->label('Category / Name'), TextColumn::make('bank_name')->label('Bank Name'), TextColumn::make('account_number')->label('Account Number'), TextColumn::make('account_holder')->label('Account Holder'), TextColumn::make('is_active')->label('Is Active')];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageBankAccountRecords::route('/')];
    }
}
