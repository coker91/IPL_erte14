<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\BankAccount;
use App\Models\CashEntry;
use App\Models\FundCategory;
use App\Models\House;
use App\Models\HouseResident;
use App\Models\LegacyRecapEntry;
use App\Models\Resident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SourceSnapshotImporter
{
    public function import(array $data): int
    {
        Validator::make($data, [
            'year' => 'required|integer|between:2000,2100', 'snapshot_date' => 'required|date_format:Y-m-d',
            'source_updated_at' => 'required|string', 'rows' => 'required|array|min:1',
            'rows.*.house' => 'required|string|distinct', 'rows.*.name' => 'required|string',
            'rows.*.months' => 'required|array|size:12', 'rows.*.months.*' => 'required|in:paid,unpaid,exempt',
            'balances' => 'required|array', 'balances.IPL' => 'required|numeric|min:0', 'balances.THR' => 'required|numeric|min:0', 'balances.HUT_RI' => 'required|numeric|min:0',
            'accounts' => 'present|array', 'accounts.*.category' => 'required|in:IPL,THR,HUT_RI', 'accounts.*.bank' => 'required|string', 'accounts.*.number' => 'required|string', 'accounts.*.holder' => 'required|string',
        ])->validate();

        return DB::transaction(function () use ($data) {
            $hash = hash('sha256', json_encode($data));
            $marker = AppSetting::where('key', 'source_snapshot_hash')->first();
            if ($marker) {
                if ($marker->value === $hash) {
                    return 0;
                } throw new \RuntimeException('Snapshot berbeda sudah diimpor. Rekonsiliasi dahulu; importer tidak menimpa data.');
            }
            if (House::exists() || CashEntry::exists() || LegacyRecapEntry::exists()) {
                throw new \RuntimeException('Impor sumber hanya pada database yang belum memiliki rumah/keuangan. Gunakan database baru, bukan menimpa data.');
            }
            $cat = FundCategory::where('code', 'IPL')->firstOrFail();
            foreach ($data['rows'] as $row) {
                $vacant = in_array(strtoupper($row['name']), ['KOSONG', 'RUMAH KOSONG', 'WARGA']);
                $facility = count(array_unique($row['months'])) === 1 && $row['months'][0] === 'exempt';
                $h = House::create(['code' => $row['house'], 'source_label' => $row['name'], 'occupancy_status' => $facility ? 'facility' : ($vacant ? 'vacant' : 'occupied'), 'is_ipl_subject' => ! $facility]);
                if (! $facility && ! $vacant) {
                    $r = Resident::create(['name' => $row['name']]);
                    HouseResident::create(['house_id' => $h->id, 'resident_id' => $r->id, 'start_date' => $data['snapshot_date'], 'can_manage_payments' => false]);
                }
                foreach ($row['months'] as $index => $status) {
                    LegacyRecapEntry::create(['house_id' => $h->id, 'fund_category_id' => $cat->id, 'period' => sprintf('%d-%02d-01', $data['year'], $index + 1), 'status' => $status, 'snapshot_date' => $data['snapshot_date'], 'source_updated_at' => $data['source_updated_at'], 'source_name' => 'Google Apps Script — snapshot rekap; nominal/tanggal transaksi tidak tersedia']);
                }
            }
            foreach ($data['accounts'] as $a) {
                BankAccount::firstOrCreate(['bank_name' => $a['bank'], 'account_number' => $a['number']], ['fund_category_id' => FundCategory::where('code', $a['category'])->firstOrFail()->id, 'account_holder' => $a['holder']]);
            }
            foreach ($data['balances'] as $code => $amount) {
                CashEntry::create(['fund_category_id' => FundCategory::where('code', $code)->firstOrFail()->id, 'entry_type' => 'opening', 'amount' => $amount, 'entry_date' => $data['snapshot_date'], 'source_key' => 'google-opening-'.$code, 'description' => 'Saldo awal snapshot, bukan akumulasi ulang pembayaran lama']);
            }
            AppSetting::create(['key' => 'source_snapshot_hash', 'value' => $hash]);
            AppSetting::updateOrCreate(['key' => 'source_updated_at'], ['value' => $data['source_updated_at']]);

            return count($data['rows']);
        });
    }
}
