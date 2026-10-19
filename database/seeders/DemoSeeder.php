<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\FundCategory;
use App\Models\House;
use App\Models\HouseResident;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('DemoSeeder hanya untuk local/testing.');
        }
        $password = config('ipl.demo_password');
        if (! $password || strlen($password) < 12) {
            throw new \RuntimeException('Isi IPL_DEMO_PASSWORD minimal 12 karakter di .env sebelum menjalankan DemoSeeder.');
        }
        $this->call(DatabaseSeeder::class);
        foreach (['super_admin', 'ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'pengurus'] as $role) {
            $user = User::firstOrCreate(['username' => $role], ['name' => 'Demo '.str_replace('_', ' ', $role), 'email' => $role.'@example.test', 'password' => $password, 'is_active' => true]);
            $user->assignRole($role);
        }
        $cat = FundCategory::where('code', 'IPL')->firstOrFail();
        BankAccount::firstOrCreate(['bank_name' => 'Bank Demo', 'account_number' => '0000000000'], ['fund_category_id' => $cat->id, 'account_holder' => 'Rekening simulasi — jangan transfer']);
        $house = House::firstOrCreate(['code' => 'DEMO/1'], ['is_ipl_subject' => true]);
        $resident = Resident::firstOrCreate(['name' => 'Warga Demo']);
        HouseResident::firstOrCreate(['house_id' => $house->id, 'resident_id' => $resident->id], ['start_date' => today()->startOfYear(), 'can_manage_payments' => true]);
        $user = User::firstOrCreate(['username' => 'warga_demo'], ['name' => 'Warga Demo', 'email' => 'warga@example.test', 'password' => $password, 'resident_id' => $resident->id, 'is_active' => true]);
        $user->assignRole('warga');
        for ($i = 0; $i < 3; $i++) {
            $period = today()->startOfMonth()->addMonths($i);
            Bill::firstOrCreate(['house_id' => $house->id, 'fund_category_id' => $cat->id, 'period' => $period->toDateString()], ['due_date' => $period->copy()->endOfMonth(), 'amount' => '100000.00']);
        }
    }
}
