<?php

namespace App\Providers;

use App\Models\Bill;
use App\Models\CashEntry;
use App\Models\CommitteeMember;
use App\Models\DuesRate;
use App\Models\HouseResident;
use App\Models\LegacyRecapEntry;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::before(fn (User $u, string $ability) => ! $u->is_active ? false : ($u->hasRole('super_admin') ? true : null));
        Role::updating(function (Role $role) {
            if ($role->isDirty('name') && in_array($role->getOriginal('name'), $this->systemRoles(), true)) {
                $this->invalid('Nama role bawaan sistem tidak dapat diubah.');
            }
        });
        Role::deleting(function (Role $role) {
            if (in_array($role->name, $this->systemRoles(), true)) {
                $this->invalid('Role bawaan sistem tidak dapat dihapus.');
            }
        });
        User::created(function (User $u) {
            if ($u->resident_id) {
                $u->assignRole('warga');
            }
        });
        Bill::saving(function (Bill $b) {
            if ($b->exists && $b->isDirty()) {
                $this->invalid('Tagihan yang terbit tidak dapat diedit. Rekonsiliasi melalui bendahara.');
            }
            if ($b->period->day !== 1 || $b->due_date->lt($b->period) || (float) $b->amount <= 0) {
                $this->invalid('Periode wajib tanggal 1, nominal positif, jatuh tempo setelah awal periode.');
            }
            if (LegacyRecapEntry::where('house_id', $b->house_id)->where('fund_category_id', $b->fund_category_id)->whereDate('period', $b->period)->whereIn('status', ['paid', 'exempt'])->exists()) {
                $this->invalid('Periode ini lunas/dikecualikan pada rekap lama. Tidak boleh ditagih kembali.');
            }
            if (! $b->house->is_ipl_subject && $b->category->code === 'IPL') {
                $this->invalid('Unit ini tidak dikenai IPL.');
            }
            if (! $b->exists) {
                $b->created_by = Auth::id();
            }
        });
        DuesRate::saving(function (DuesRate $rate) {
            if ($rate->exists && $rate->isDirty(['fund_category_id', 'amount', 'effective_from'])) {
                $this->invalid('Nominal, kategori, dan awal tarif lama tidak dapat diubah. Tutup periode tarif lalu buat tarif baru.');
            }
            if ($rate->effective_to && Bill::where('dues_rate_id', $rate->id)->whereDate('period', '>', $rate->effective_to)->exists()) {
                $this->invalid('Akhir tarif tidak boleh mendahului tagihan yang sudah diterbitkan.');
            }

            if ((float) $rate->amount <= 0 || ($rate->effective_to && $rate->effective_to->lt($rate->effective_from))) {
                $this->invalid('Tarif atau rentang tanggal tidak valid.');
            }
            $q = DuesRate::where('fund_category_id', $rate->fund_category_id)->where('id', '!=', $rate->id ?? 0)->where('effective_from', '<=', $rate->effective_to?->toDateString() ?? '9999-12-31')->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $rate->effective_from));
            if ($q->exists()) {
                $this->invalid('Periode tarif bertumpang tindih.');
            }
            if (! $rate->exists) {
                $rate->created_by = Auth::id();
            }
        });
        HouseResident::saving(function (HouseResident $m) {
            if ($m->end_date && $m->end_date->lt($m->start_date)) {
                $this->invalid('Tanggal akhir sebelum tanggal mulai.');
            }
            $q = HouseResident::where('house_id', $m->house_id)->where('id', '!=', $m->id ?? 0)->whereDate('start_date', '<=', $m->end_date?->toDateString() ?? '9999-12-31')->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $m->start_date));
            if ($q->where(fn ($q) => $q->where('resident_id', $m->resident_id)->when($m->is_primary_contact, fn ($q) => $q->orWhere('is_primary_contact', true)))->exists()) {
                $this->invalid('Penghuni/kontak utama sudah ada pada periode tersebut.');
            }
        });
        CommitteeMember::saving(function (CommitteeMember $m) {
            if ($m->end_date && $m->end_date->lt($m->start_date)) {
                $this->invalid('Tanggal akhir sebelum tanggal mulai.');
            }
            if (CommitteeMember::where('user_id', $m->user_id)->where('rt_position_id', $m->rt_position_id)->where('id', '!=', $m->id ?? 0)->whereDate('start_date', '<=', $m->end_date?->toDateString() ?? '9999-12-31')->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $m->start_date))->exists()) {
                $this->invalid('Penugasan identik sudah ada pada periode tersebut.');
            }
        });
        CashEntry::creating(function (CashEntry $e) {
            if ((float) $e->amount < 0 || ! in_array($e->entry_type, ['opening', 'income', 'expense'])) {
                $this->invalid('Jenis/nominal kas tidak valid.');
            }
            if (! $e->created_by) {
                $e->created_by = Auth::id();
            }
            if (! $e->payment_id && ! $e->reversal_of_id && $e->entry_type !== 'opening') {
                $date = CashEntry::where('fund_category_id', $e->fund_category_id)->where('entry_type', 'opening')->max('entry_date');
                if ($date && $e->entry_date->toDateString() <= $date) {
                    $this->invalid('Kas baru wajib setelah tanggal saldo awal.');
                }
            }
        });
        CashEntry::updating(fn () => $this->invalid('Buku kas tidak boleh ditimpa. Gunakan catatan koreksi.'));
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['data' => $message]);
    }

    /** @return list<string> */
    private function systemRoles(): array
    {
        return ['super_admin', 'ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'pengurus', 'warga'];
    }
}
