<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\DuesRate;
use App\Models\FundCategory;
use App\Models\House;
use App\Models\LegacyRecapEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingService
{
    public function generate(string $period, string $dueDate): int
    {
        $p = CarbonImmutable::createFromFormat('!Y-m-d', $period);
        $due = CarbonImmutable::createFromFormat('!Y-m-d', $dueDate);
        if (! $p || $p->day !== 1 || ! $due || $due->lt($p)) {
            throw ValidationException::withMessages(['period' => 'Periode harus tanggal 1 dan jatuh tempo tidak lebih awal.']);
        }

        return DB::transaction(function () use ($p, $due) {
            $cat = FundCategory::where('code', 'IPL')->firstOrFail();
            $rates = DuesRate::where('fund_category_id', $cat->id)->whereDate('effective_from', '<=', $p)->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $p))->get();
            if ($rates->count() !== 1) {
                throw ValidationException::withMessages(['period' => 'Isi satu tarif IPL yang berlaku pada periode ini dahulu.']);
            }
            $n = 0;
            foreach (House::where('is_ipl_subject', true)->where(fn ($q) => $q->whereNull('billing_start_period')->orWhereDate('billing_start_period', '<=', $p))->where(fn ($q) => $q->whereNull('billing_end_period')->orWhereDate('billing_end_period', '>=', $p))->orderBy('id')->lockForUpdate()->get() as $h) {
                if (Bill::where('house_id', $h->id)->where('fund_category_id', $cat->id)->whereDate('period', $p)->exists()) {
                    continue;
                }
                if (LegacyRecapEntry::where('house_id', $h->id)->where('fund_category_id', $cat->id)->whereDate('period', $p)->whereIn('status', ['paid', 'exempt'])->exists()) {
                    continue;
                }
                $member = $h->memberships()->where('is_primary_contact', true)->whereDate('start_date', '<=', $p)->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $p))->first();
                Bill::create(['house_id' => $h->id, 'fund_category_id' => $cat->id, 'dues_rate_id' => $rates[0]->id, 'responsible_residency_id' => $member?->id, 'period' => $p, 'due_date' => $due, 'amount' => $rates[0]->amount]);
                $n++;
            }

            return $n;
        });
    }
}
