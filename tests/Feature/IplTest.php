<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Filament\Resources\Pages\ManageResidentRecords;
use App\Filament\Warga\Pages\TagihanSaya;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\DuesRate;
use App\Models\FundCategory;
use App\Models\House;
use App\Models\HouseResident;
use App\Models\Payment;
use App\Models\Resident;
use App\Models\User;
use App\Services\BillingService;
use App\Services\DashboardData;
use App\Services\PaymentService;
use App\Services\SourceSnapshotImporter;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class IplTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
    }

    private function fixture(): array
    {
        $r = Resident::create(['name' => 'Warga Uji']);
        $u = User::create(['name' => 'Warga Uji', 'username' => 'warga', 'email' => 'warga@example.test', 'password' => 'test-password-strong', 'resident_id' => $r->id]);
        $h = House::create(['code' => 'TEST/1']);
        HouseResident::create(['house_id' => $h->id, 'resident_id' => $r->id, 'start_date' => today()->subYear(), 'can_manage_payments' => true]);
        $c = FundCategory::where('code', 'IPL')->firstOrFail();
        $a = BankAccount::create(['fund_category_id' => $c->id, 'bank_name' => 'Uji', 'account_number' => '123', 'account_holder' => 'Uji']);
        $b = Bill::create(['house_id' => $h->id, 'fund_category_id' => $c->id, 'period' => today()->startOfMonth(), 'due_date' => today()->endOfMonth(), 'amount' => '100000.00']);
        $reviewer = User::create(['name' => 'Bendahara Uji', 'username' => 'bendahara', 'email' => 'b@example.test', 'password' => 'test-password-strong']);
        $reviewer->assignRole('bendahara');

        return [$u, $h, $a, $b, $reviewer];
    }

    private function submit(array $f): Payment
    {
        [$u,$h,$a,$b] = $f;

        return app(PaymentService::class)->submit($u, ['house_id' => $h->id, 'bank_account_id' => $a->id, 'bill_ids' => [$b->id], 'paid_at' => today()->toDateString(), 'amount' => '100000.00'], [UploadedFile::fake()->create('resi.pdf', 10, 'application/pdf')]);
    }

    public function test_seed_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('fund_categories', 3);
        $this->assertDatabaseCount('roles', 7);
    }

    public function test_approval_and_reversal_affect_cash_and_bill_once(): void
    {
        $f = $this->fixture();
        $p = $this->submit($f);
        $s = app(PaymentService::class);
        $this->assertSame(10000000, $f[3]->outstandingCents());
        $this->assertDatabaseCount('cash_entries', 0);
        $s->review($f[4], $p, 'approve');
        $this->assertSame(0, $f[3]->outstandingCents());
        $this->assertDatabaseCount('cash_entries', 1);
        try {
            $s->review($f[4], $p, 'approve');
            $this->fail('Persetujuan ganda harus ditolak.');
        } catch (ValidationException) {
        }
        $s->review($f[4], $p, 'reverse', 'Salah alokasi');
        $this->assertSame(10000000, $f[3]->outstandingCents());
        $this->assertDatabaseCount('cash_entries', 2);
        $this->assertDatabaseCount('payment_reviews', 2);
    }

    public function test_self_approval_is_forbidden(): void
    {
        $f = $this->fixture();
        $f[0]->assignRole('bendahara');
        $p = $this->submit($f);
        $this->expectException(ValidationException::class);
        app(PaymentService::class)->review($f[0], $p, 'approve');
    }

    public function test_reject_keeps_bill_and_cash_unchanged(): void
    {
        $f = $this->fixture();
        $p = $this->submit($f);
        app(PaymentService::class)->review($f[4], $p, 'reject', 'Dana belum diterima');
        $this->assertDatabaseCount('cash_entries', 0);
        $this->assertSame(10000000, $f[3]->outstandingCents());
        $this->assertSame('rejected', $p->fresh()->status);
    }

    public function test_competing_submissions_cannot_overpay(): void
    {
        $f = $this->fixture();
        $p = $this->submit($f);
        $other = $this->submit($f);
        app(PaymentService::class)->review($f[4], $p, 'approve');
        $this->expectException(ValidationException::class);
        app(PaymentService::class)->review($f[4], $other, 'approve');
    }

    public function test_one_receipt_can_pay_two_months(): void
    {
        $f = $this->fixture();
        $b = Bill::create(['house_id' => $f[1]->id, 'fund_category_id' => $f[2]->fund_category_id, 'period' => today()->addMonth()->startOfMonth(), 'due_date' => today()->addMonth()->endOfMonth(), 'amount' => '100000.00']);
        $p = app(PaymentService::class)->submit($f[0], ['house_id' => $f[1]->id, 'bank_account_id' => $f[2]->id, 'bill_ids' => [$f[3]->id, $b->id], 'paid_at' => today()->toDateString(), 'amount' => '200000.00'], [UploadedFile::fake()->create('resi.pdf', 10, 'application/pdf')]);
        $this->assertCount(2, $p->allocations);
        $this->assertCount(1, $p->proofs);
    }

    public function test_receipts_are_private(): void
    {
        $f = $this->fixture();
        $p = $this->submit($f);
        $proof = $p->proofs->first();
        $other = User::create(['name' => 'Lain', 'email' => 'other@example.test', 'username' => 'other', 'password' => 'test-password-strong']);
        $this->actingAs($other)->get(route('proofs.show', $proof))->assertForbidden();
        $this->actingAs($f[0])->get(route('proofs.show', $proof))->assertOk();
        $this->actingAs($f[4])->get(route('proofs.show', $proof))->assertOk();
    }

    public function test_warga_cannot_enter_admin_panel_or_other_houses(): void
    {
        $f = $this->fixture();
        $this->actingAs($f[0])->get('/admin/houses')->assertForbidden();
        $f[1]->memberships()->update(['end_date' => today()->subDay()]);
        $this->expectException(HttpException::class);
        $this->submit($f);
    }

    public function test_all_admin_resources_render(): void
    {
        $f = $this->fixture();
        $this->submit($f);
        $admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@example.test', 'password' => 'test-password-strong']);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        foreach (['residents', 'houses', 'house-residents', 'fund-categories', 'bank-accounts', 'dues-rates', 'bills', 'cash-entries', 'rt-positions', 'committee-members', 'app-settings', 'legacy-recap-entries', 'activity-logs', 'users', 'payments'] as $path) {
            $this->get('/admin/'.$path)->assertOk();
        }
        $this->get('/')->assertOk();
        $this->get('/admin/login')->assertRedirect();
    }

    public function test_shield_role_management_renders_and_seed_preserves_changes(): void
    {
        $admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@example.test', 'password' => 'test-password-strong']);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get('/admin/shield/roles')->assertOk();

        $role = Role::findByName('pengurus', 'web');
        $role->givePermissionTo('payments.review');
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue($role->fresh()->hasPermissionTo('payments.review'));
    }

    public function test_warga_form_and_login_render(): void
    {
        $f = $this->fixture();
        $this->get('/warga/login')->assertOk()->assertSee('Username');
        $this->actingAs($f[0])->get('/warga/tagihan-saya')->assertOk();
    }

    public function test_master_resource_create_form(): void
    {
        $admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@example.test', 'password' => 'test-password-strong']);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageResidentRecords::class)->callAction(CreateAction::class, ['name' => 'Nama dari Form', 'is_active' => true])->assertHasNoActionErrors();
        $this->assertDatabaseHas('residents', ['name' => 'Nama dari Form']);
    }

    private function snapshot(): array
    {
        return ['year' => 2026, 'snapshot_date' => '2026-10-15', 'source_updated_at' => '15 Oct 2026', 'balances' => ['IPL' => '1000', 'THR' => '20', 'HUT_RI' => '0'], 'accounts' => [], 'rows' => [['house' => 'SOURCE/1', 'name' => 'Warga snapshot uji', 'months' => array_fill(0, 12, 'paid')]]];
    }

    public function test_snapshot_import_has_no_invented_payments_and_is_idempotent(): void
    {
        $i = app(SourceSnapshotImporter::class);
        $this->assertSame(1, $i->import($this->snapshot()));
        $this->assertSame(0, $i->import($this->snapshot()));
        $this->assertDatabaseCount('legacy_recap_entries', 12);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('cash_entries', 3);
        $this->assertFalse((bool) HouseResident::first()->can_manage_payments);
    }

    public function test_source_paid_month_cannot_be_billed_again(): void
    {
        app(SourceSnapshotImporter::class)->import($this->snapshot());
        $this->expectException(ValidationException::class);
        Bill::create(['house_id' => House::first()->id, 'fund_category_id' => FundCategory::where('code', 'IPL')->first()->id, 'period' => '2026-01-01', 'due_date' => '2026-01-31', 'amount' => '100000']);
    }

    public function test_snapshot_validation_is_atomic(): void
    {
        $s = $this->snapshot();
        $s['rows'][0]['months'][0] = 'invalid';
        try {
            app(SourceSnapshotImporter::class)->import($s);
            $this->fail();
        } catch (ValidationException) {
        }$this->assertDatabaseCount('houses', 0);
        $this->assertDatabaseCount('cash_entries', 0);
    }

    public function test_dashboard_does_not_count_future_month_as_arrears(): void
    {
        $s = $this->snapshot();
        $s['rows'][0]['months'] = array_fill(0, 12, 'unpaid');
        app(SourceSnapshotImporter::class)->import($s);
        $this->travelTo(Carbon::parse('2026-02-10'));
        $data = app(DashboardData::class)->build(2026);
        $this->assertCount(0, $data['warnings']);
        $this->assertSame(2, $data['rows'][0]['arrears']);
        $this->travelBack();
    }

    public function test_warga_can_submit_through_filament_form(): void
    {
        $f = $this->fixture();
        $this->actingAs($f[0]);
        Filament::setCurrentPanel(Filament::getPanel('warga'));
        Livewire::test(TagihanSaya::class)->fillForm([
            'house_id' => $f[1]->id, 'bank_account_id' => $f[2]->id, 'bill_ids' => [$f[3]->id],
            'paid_at' => today()->toDateString(), 'amount' => '100000', 'proofs' => [UploadedFile::fake()->create('transfer.pdf', 10, 'application/pdf')],
        ])->call('submit')->assertHasNoFormErrors();
        $this->assertDatabaseHas('payments', ['submitted_by' => $f[0]->id, 'status' => 'submitted']);
    }

    public function test_username_login_works_and_inactive_account_is_denied(): void
    {
        $f = $this->fixture();
        Filament::setCurrentPanel(Filament::getPanel('warga'));
        Livewire::test(Login::class)->fillForm(['username' => 'warga', 'password' => 'test-password-strong'])->call('authenticate')->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($f[0]);
        auth()->logout();
        $f[0]->update(['is_active' => false]);
        Livewire::test(Login::class)->fillForm(['username' => 'warga', 'password' => 'test-password-strong'])->call('authenticate')->assertHasFormErrors(['username']);
    }

    public function test_billing_generation_is_repeatable_and_respects_legacy_paid_month(): void
    {
        app(SourceSnapshotImporter::class)->import($this->snapshot());
        $cat = FundCategory::where('code', 'IPL')->first();
        DuesRate::create(['fund_category_id' => $cat->id, 'amount' => '150000', 'effective_from' => '2026-01-01']);
        $billing = app(BillingService::class);
        $this->assertSame(0, $billing->generate('2026-10-01', '2026-10-31'));
        $this->assertSame(1, $billing->generate('2027-01-01', '2027-01-31'));
        $this->assertSame(0, $billing->generate('2027-01-01', '2027-01-31'));
        $this->assertDatabaseHas('bills', ['amount' => '150000', 'period' => '2027-01-01']);
    }

    public function test_wrong_amount_or_other_house_cannot_create_payment(): void
    {
        $f = $this->fixture();
        try {
            app(PaymentService::class)->submit($f[0], ['house_id' => $f[1]->id, 'bank_account_id' => $f[2]->id, 'bill_ids' => [$f[3]->id], 'paid_at' => today()->toDateString(), 'amount' => '1'], [UploadedFile::fake()->create('transfer.pdf', 10, 'application/pdf')]);
            $this->fail();
        } catch (ValidationException) {
        }
        $this->assertDatabaseCount('payments', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles('payment-proofs'));
    }

    public function test_demo_seeder_is_repeatable(): void
    {
        config(['ipl.demo_password' => 'a-long-demo-password']);
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);
        $this->assertDatabaseCount('bills', 3);
        $this->assertDatabaseCount('houses', 1);
        $this->assertDatabaseCount('users', 7);
    }
}
