<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residents', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone', 30)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('resident_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $t->string('username')->nullable()->unique();
            $t->boolean('is_active')->default(true);
        });
        Schema::create('rt_positions', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('committee_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('rt_position_id')->constrained()->restrictOnDelete();
            $t->date('start_date');
            $t->date('end_date')->nullable();
            $t->string('public_phone', 30)->nullable();
            $t->boolean('show_in_contact')->default(false);
            $t->timestamps();
        });
        Schema::create('houses', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('source_label')->nullable();
            $t->string('occupancy_status')->default('occupied');
            $t->boolean('is_ipl_subject')->default(true);
            $t->date('billing_start_period')->nullable();
            $t->date('billing_end_period')->nullable();
            $t->timestamps();
        });
        Schema::create('house_residents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('house_id')->constrained()->restrictOnDelete();
            $t->foreignId('resident_id')->constrained()->restrictOnDelete();
            $t->string('relation_type')->default('occupant');
            $t->date('start_date');
            $t->date('end_date')->nullable();
            $t->boolean('is_primary_contact')->default(true);
            $t->boolean('can_manage_payments')->default(false);
            $t->timestamps();
            $t->index(['resident_id', 'end_date']);
        });
        Schema::create('fund_categories', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('bank_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fund_category_id')->constrained()->restrictOnDelete();
            $t->string('bank_name');
            $t->string('account_number', 60);
            $t->string('account_holder');
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('display_order')->default(0);
            $t->timestamps();
            $t->unique(['bank_name', 'account_number']);
        });
        Schema::create('dues_rates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fund_category_id')->constrained()->restrictOnDelete();
            $t->decimal('amount', 18, 2);
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('app_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['app_settings', 'dues_rates', 'bank_accounts', 'fund_categories', 'house_residents', 'houses', 'committee_members', 'rt_positions'] as $n) {
            Schema::dropIfExists($n);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('resident_id');
            $t->dropColumn(['username', 'is_active']);
        });
        Schema::dropIfExists('residents');
    }
};
