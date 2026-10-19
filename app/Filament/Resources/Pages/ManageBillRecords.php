<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\BillResource;
use App\Services\BillingService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;

class ManageBillRecords extends ManageMasterRecords
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...parent::getHeaderActions(),
            Action::make('generate')->label('Terbitkan IPL Bulanan')->visible(fn () => auth()->user()->can('bills.manage'))->schema([
                DatePicker::make('period')->label('Periode (tanggal 1)')->required()->rules(['date_format:Y-m-01']),
                DatePicker::make('due_date')->label('Jatuh tempo')->required()->afterOrEqual('period'),
            ])->action(function (array $data) {
                abort_unless(auth()->user()->can('bills.manage'), 403);
                $n = app(BillingService::class)->generate($data['period'], $data['due_date']);
                Notification::make()->title("$n tagihan diterbitkan; periode yang sudah lunas/ada dilewati.")->success()->send();
            }),
        ];
    }
}
