<?php

namespace App\Filament\Warga\Pages;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\House;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TagihanSaya extends Page
{
    protected string $view = 'filament.warga.pages.tagihan-saya';

    protected static ?string $title = 'Tagihan & Pembayaran Saya';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->is_active && auth()->user()?->hasRole('warga'));
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('house_id')->label('Rumah')->options(fn () => House::whereIn('id', auth()->user()->manageableHouseIds())->pluck('code', 'id'))->required()->live()->afterStateUpdated(fn ($set) => $set('bill_ids', [])),
            Select::make('bank_account_id')->label('Rekening tujuan')->options(fn () => BankAccount::where('is_active', true)->get()->mapWithKeys(fn ($a) => [$a->id => $a->category->name.' — '.$a->bank_name.' '.$a->account_number.' a/n '.$a->account_holder]))->required()->live()->afterStateUpdated(fn ($set) => $set('bill_ids', [])),
            Select::make('bill_ids')->label('Pilih tagihan yang dibayar')->multiple()->options(function (Get $get) {
                if (! in_array((int) $get('house_id'), auth()->user()->manageableHouseIds(), true)) {
                    return [];
                }
                $account = BankAccount::find($get('bank_account_id'));
                if (! $account) {
                    return [];
                }

                return Bill::where('house_id', $get('house_id'))->where('fund_category_id', $account->fund_category_id)->where('state', 'issued')->orderBy('period')->get()->filter(fn ($b) => $b->outstandingCents() > 0)->mapWithKeys(fn ($b) => [$b->id => $b->period->format('m/Y').' — Rp '.number_format($b->outstandingCents() / 100, 0, ',', '.')]);
            })->required()->helperText('Nominal transfer harus sesuai jumlah sisa tagihan terpilih. Pembayaran sebagian belum diaktifkan.'),
            DatePicker::make('paid_at')->label('Tanggal transfer')->required()->maxDate('today'),
            TextInput::make('amount')->label('Nominal transfer')->numeric()->required()->minValue(1),
            FileUpload::make('proofs')->label('Resi transfer')->multiple()->maxFiles(3)->maxSize(5120)->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])->storeFiles(false)->required()->helperText('JPG, PNG, PDF; maksimal 3 berkas, masing-masing 5 MB. Tidak otomatis dianggap lunas.'),
            Textarea::make('notes')->label('Catatan')->maxLength(2000),
        ])->statePath('data');
    }

    public function submit(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();
        $files = array_values($data['proofs'] ?? []);
        unset($data['proofs']);
        app(PaymentService::class)->submit(auth()->user(), $data, $files);
        $this->form->fill();
        Notification::make()->title('Pengajuan terkirim. Menunggu verifikasi bendahara.')->success()->send();
    }

    public function history()
    {
        return Payment::with(['house', 'proofs', 'reviews'])->where('submitted_by', auth()->id())->latest()->limit(30)->get();
    }
}
