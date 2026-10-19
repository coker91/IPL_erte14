<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\FundCategory;
use App\Models\RtPosition;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        foreach (['IPL' => 'Kas IPL (RT)', 'THR' => 'Kas THR', 'HUT_RI' => 'Kas HUT RI'] as $code => $name) {
            FundCategory::firstOrCreate(['code' => $code], ['name' => $name]);
        }
        foreach (['ketua_rt' => 'Ketua RT', 'wakil_rt' => 'Wakil RT', 'sekretaris' => 'Sekretaris', 'bendahara' => 'Bendahara', 'pengurus' => 'Pengurus Lainnya'] as $code => $name) {
            RtPosition::firstOrCreate(['code' => $code], ['name' => $name]);
        }
        foreach (['community_name' => 'Perumahan Villa Rizky Ilhami', 'rt_name' => 'RT 14', 'tagline' => 'Tangerang • Transparan & Amanah', 'arrears_threshold' => '3'] as $key => $value) {
            AppSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
