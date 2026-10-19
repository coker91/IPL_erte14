<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\CommitteeMember;
use App\Models\FundCategory;
use App\Models\House;

class DashboardData
{
    public function build(int $year, string $search = ''): array
    {
        $houses = House::with(['memberships.resident', 'legacyEntries' => fn ($q) => $q->whereYear('period', $year), 'bills' => fn ($q) => $q->with('allocations.payment')->whereYear('period', $year)->where('state', 'issued')])->when($search, fn ($q) => $q->where(fn ($q) => $q->where('code', 'like', '%'.$search.'%')->orWhere('source_label', 'like', '%'.$search.'%')->orWhereHas('memberships.resident', fn ($r) => $r->where('name', 'like', '%'.$search.'%'))))->orderBy('id')->get();
        $ipl = FundCategory::where('code', 'IPL')->value('id');
        $rows = [];
        $warnings = [];
        foreach ($houses as $h) {
            $name = $h->memberships->filter(fn ($m) => $m->start_date->lte(today()) && (! $m->end_date || $m->end_date->gte(today())))->first()?->resident?->name ?? $h->source_label ?? 'Belum didata';
            $months = [];
            $due = 0;
            for ($m = 1; $m <= 12; $m++) {
                $bill = $h->bills->first(fn ($b) => $b->fund_category_id === $ipl && $b->period->month === $m);
                $legacy = $h->legacyEntries->first(fn ($l) => $l->fund_category_id === $ipl && $l->period->month === $m);
                if ($bill) {
                    $paid = $bill->allocations->filter(fn ($a) => $a->payment->status === 'approved')->sum('amount');
                    $left = (float) $bill->amount - $paid;
                    $status = $left <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
                    if ($left > 0 && $bill->due_date->lte(today())) {
                        $due++;
                    }
                } elseif ($legacy) {
                    $status = $legacy->status;
                    if ($status === 'unpaid' && $year * 100 + $m <= today()->year * 100 + today()->month) {
                        $due++;
                    }
                } else {
                    $status = 'unknown';
                }
                if ($status === 'unpaid' && $year * 100 + $m > today()->year * 100 + today()->month) {
                    $status = 'future';
                }
                $months[] = $status;
            }
            $row = ['name' => $name, 'house' => $h->code, 'months' => $months, 'arrears' => $due];
            $rows[] = $row;
            if ($due >= 3) {
                $warnings[] = $row;
            }
        }
        $funds = FundCategory::with('accounts')->get()->map(function ($f) {
            $f->balance = $f->entries()->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'expense' THEN -amount ELSE amount END),0) as total")->value('total');

            return $f;
        });

        return ['rows' => $rows, 'warnings' => $warnings, 'funds' => $funds, 'settings' => AppSetting::pluck('value', 'key'), 'contacts' => CommitteeMember::with(['user', 'position'])->where('show_in_contact', true)->whereDate('start_date', '<=', today())->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', today()))->get(), 'year' => $year];
    }
}
