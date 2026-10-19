<?php

namespace App\Filament\Resources;

use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentResource extends ManagedResource
{
    protected static ?string $model = Payment::class;

    public static function getModelLabel(): string
    {
        return 'Verifikasi Pembayaran';
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('payments.view');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('reference_no')->searchable(), TextColumn::make('house.code')->label('Rumah'), TextColumn::make('submitter.name')->label('Pengaju'), TextColumn::make('paid_at')->date(), TextColumn::make('amount')->money('IDR'), TextColumn::make('status')->badge(),
            TextColumn::make('proofs.id')->label('Bukti')->formatStateUsing(fn ($state) => 'Buka resi #'.$state)->url(fn ($state) => route('proofs.show', $state))->openUrlInNewTab(),
            TextColumn::make('reviews.reason')->label('Catatan verifikasi')->wrap(),
        ])->recordActions([
            Action::make('approve')->label('Setujui')->color('success')->requiresConfirmation()->visible(fn (Payment $record) => $record->status === 'submitted' && auth()->user()->can('payments.review') && $record->submitted_by !== auth()->id())->action(fn (Payment $record) => app(PaymentService::class)->review(auth()->user(), $record, 'approve')),
            Action::make('reject')->label('Tolak')->color('danger')->schema([Textarea::make('reason')->label('Alasan')->required()->maxLength(2000)])->visible(fn (Payment $record) => $record->status === 'submitted' && auth()->user()->can('payments.review') && $record->submitted_by !== auth()->id())->action(fn (Payment $record, array $data) => app(PaymentService::class)->review(auth()->user(), $record, 'reject', $data['reason'])),
            Action::make('reverse')->label('Balikkan')->color('warning')->requiresConfirmation()->schema([Textarea::make('reason')->label('Alasan koreksi')->required()->maxLength(2000)])->visible(fn (Payment $record) => $record->status === 'approved' && auth()->user()->can('payments.reverse') && $record->submitted_by !== auth()->id())->action(fn (Payment $record, array $data) => app(PaymentService::class)->review(auth()->user(), $record, 'reverse', $data['reason'])),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePaymentRecords::route('/')];
    }
}
