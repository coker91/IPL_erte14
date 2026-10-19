<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\CashEntry;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PaymentService
{
    public function submit(User $actor, array $data, array $files): Payment
    {
        abort_unless($actor->is_active, 403);
        Validator::make($data, ['house_id' => 'required|integer', 'bank_account_id' => 'required|integer', 'bill_ids' => 'required|array|min:1', 'bill_ids.*' => 'integer|distinct', 'paid_at' => 'required|date_format:Y-m-d|before_or_equal:today', 'amount' => 'required|numeric|gt:0|decimal:0,2', 'notes' => 'nullable|string|max:2000'])->validate();
        abort_unless(in_array((int) $data['house_id'], $actor->manageableHouseIds(), true), 403);
        Validator::make(['proofs' => $files], ['proofs' => 'required|array|min:1|max:3', 'proofs.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120'])->validate();
        $stored = [];
        try {
            foreach ($files as $f) {
                $path = $f->store('payment-proofs', 'local');
                $stored[] = ['disk' => 'local', 'path' => $path, 'original_name' => $f->getClientOriginalName(), 'mime_type' => $f->getMimeType(), 'size_bytes' => $f->getSize(), 'sha256' => hash_file('sha256', $f->getRealPath())];
            }

            return DB::transaction(function () use ($actor, $data, $stored) {
                $account = BankAccount::whereKey($data['bank_account_id'])->where('is_active', true)->firstOrFail();
                $bills = Bill::whereIn('id', $data['bill_ids'])->orderBy('id')->lockForUpdate()->get();
                if ($bills->count() !== count($data['bill_ids'])) {
                    $this->fail('Tagihan tidak ditemukan.');
                }
                $total = 0;
                $allocations = [];
                foreach ($bills as $bill) {
                    if ($bill->house_id != (int) $data['house_id'] || $bill->fund_category_id !== $account->fund_category_id || $bill->state !== 'issued') {
                        $this->fail('Rumah/kategori tagihan tidak sesuai.');
                    }
                    $due = $bill->outstandingCents();
                    if ($due <= 0) {
                        $this->fail('Tagihan sudah lunas.');
                    }
                    $total += $due;
                    $allocations[] = ['bill_id' => $bill->id, 'amount' => Money::decimal($due)];
                }
                if (Money::cents((string) $data['amount']) !== $total) {
                    $this->fail('Nominal harus sama dengan total sisa tagihan terpilih.');
                }
                $opening = CashEntry::where('fund_category_id', $account->fund_category_id)->where('entry_type', 'opening')->max('entry_date');
                if ($opening && $data['paid_at'] <= $opening) {
                    $this->fail('Tanggal transfer harus setelah tanggal saldo awal. Rekonsiliasi pembayaran lama melalui bendahara.');
                }
                $p = Payment::create(['reference_no' => 'IPL-'.Str::ulid(), 'house_id' => $data['house_id'], 'fund_category_id' => $account->fund_category_id, 'bank_account_id' => $account->id, 'submitted_by' => $actor->id, 'paid_at' => $data['paid_at'], 'amount' => Money::decimal($total), 'method' => 'transfer', 'notes' => $data['notes'] ?? null, 'status' => 'submitted', 'submitted_at' => now()]);
                $p->allocations()->createMany($allocations);
                $p->proofs()->createMany(array_map(fn ($x) => $x + ['uploaded_by' => $actor->id], $stored));

                return $p;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $x) {
                Storage::disk($x['disk'])->delete($x['path']);
            } throw $e;
        }
    }

    public function review(User $actor, Payment $payment, string $action, ?string $reason = null): void
    {
        Gate::forUser($actor)->authorize($action === 'reverse' ? 'payments.reverse' : 'payments.review');
        abort_unless($actor->is_active, 403);
        if (! in_array($action, ['approve', 'reject', 'reverse'], true)) {
            $this->fail('Keputusan tidak valid.');
        }
        if ($action !== 'approve' && ! trim((string) $reason)) {
            $this->fail('Alasan wajib diisi.');
        }
        DB::transaction(function () use ($actor, $payment, $action, $reason) {
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($p->submitted_by === $actor->id) {
                $this->fail('Pengajuan sendiri harus diperiksa petugas lain.');
            }
            if ($p->status !== ($action === 'reverse' ? 'approved' : 'submitted')) {
                $this->fail('Status pembayaran telah berubah. Muat ulang halaman.');
            }
            $alloc = $p->allocations()->orderBy('bill_id')->get();
            $bills = Bill::whereIn('id', $alloc->pluck('bill_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($action === 'approve') {
                if (! $p->proofs()->exists()) {
                    $this->fail('Bukti transfer wajib tersedia.');
                }
                $account = BankAccount::findOrFail($p->bank_account_id);
                if ($account->fund_category_id !== $p->fund_category_id) {
                    $this->fail('Kategori rekening tidak sesuai.');
                }
                $total = 0;
                foreach ($alloc as $a) {
                    $b = $bills->get($a->bill_id);
                    $c = Money::cents($a->amount);
                    $total += $c;
                    if (! $b || $b->state !== 'issued' || $b->house_id !== $p->house_id || $b->fund_category_id !== $p->fund_category_id || $c <= 0 || $c > $b->outstandingCents()) {
                        $this->fail('Alokasi melebihi sisa tagihan atau tagihan berubah. Tolak pengajuan lalu minta pengajuan baru.');
                    }
                }
                if (! $alloc->count() || $total !== Money::cents($p->amount)) {
                    $this->fail('Total alokasi tidak sesuai nominal.');
                }
                CashEntry::create(['fund_category_id' => $p->fund_category_id, 'payment_id' => $p->id, 'entry_type' => 'income', 'amount' => $p->amount, 'entry_date' => $p->paid_at, 'description' => 'Pembayaran '.$p->reference_no, 'created_by' => $actor->id]);
            }
            if ($action === 'reverse') {
                $entry = CashEntry::where('payment_id', $p->id)->firstOrFail();
                CashEntry::create(['fund_category_id' => $p->fund_category_id, 'reversal_of_id' => $entry->id, 'entry_type' => 'expense', 'amount' => $p->amount, 'entry_date' => today(), 'description' => 'Pembalikan '.$p->reference_no.': '.$reason, 'created_by' => $actor->id]);
            }
            $p->update(['status' => match ($action) {
                'approve' => 'approved','reject' => 'rejected','reverse' => 'reversed'
            }]);
            $p->reviews()->create(['reviewer_id' => $actor->id, 'action' => $action, 'reason' => $reason]);
            ActivityLog::create(['actor_id' => $actor->id, 'action' => $action, 'entity_type' => Payment::class, 'entity_id' => $p->id, 'changes' => ['status' => $p->status, 'reason' => $reason]]);
        }, 3);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['payment' => $message]);
    }
}
