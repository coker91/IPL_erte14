<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $t) {
            $t->id();
            $t->foreignId('house_id')->constrained()->restrictOnDelete();
            $t->foreignId('fund_category_id')->constrained()->restrictOnDelete();
            $t->foreignId('dues_rate_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('responsible_residency_id')->nullable()->constrained('house_residents')->restrictOnDelete();
            $t->date('period');
            $t->date('due_date');
            $t->decimal('amount', 18, 2);
            $t->string('state')->default('issued');
            $t->text('void_reason')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['house_id', 'fund_category_id', 'period']);
            $t->index(['period', 'state']);
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->string('reference_no')->unique();
            $t->foreignId('house_id')->constrained()->restrictOnDelete();
            $t->foreignId('fund_category_id')->constrained()->restrictOnDelete();
            $t->foreignId('bank_account_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $t->date('paid_at');
            $t->decimal('amount', 18, 2);
            $t->string('method')->default('transfer');
            $t->string('transfer_reference')->nullable();
            $t->string('payer_name')->nullable();
            $t->text('notes')->nullable();
            $t->string('status')->default('submitted');
            $t->timestamp('submitted_at')->nullable();
            $t->foreignId('resubmission_of_id')->nullable()->constrained('payments')->restrictOnDelete();
            $t->timestamps();
            $t->index(['status', 'submitted_at']);
        });
        Schema::create('payment_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_id')->constrained()->restrictOnDelete();
            $t->foreignId('bill_id')->constrained()->restrictOnDelete();
            $t->decimal('amount', 18, 2);
            $t->timestamps();
            $t->unique(['payment_id', 'bill_id']);
        });
        Schema::create('payment_proofs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_id')->constrained()->restrictOnDelete();
            $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->string('disk')->default('local');
            $t->string('path');
            $t->string('original_name');
            $t->string('mime_type', 100);
            $t->unsignedBigInteger('size_bytes');
            $t->string('sha256', 64)->index();
            $t->timestamps();
        });
        Schema::create('payment_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_id')->constrained()->restrictOnDelete();
            $t->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $t->string('action');
            $t->text('reason')->nullable();
            $t->timestamps();
        });
        Schema::create('cash_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fund_category_id')->constrained()->restrictOnDelete();
            $t->foreignId('payment_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $t->foreignId('reversal_of_id')->nullable()->unique()->constrained('cash_entries')->restrictOnDelete();
            $t->string('source_key')->nullable()->unique();
            $t->string('entry_type');
            $t->decimal('amount', 18, 2);
            $t->date('entry_date');
            $t->text('description');
            $t->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['fund_category_id', 'entry_date']);
        });
        Schema::create('legacy_recap_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('house_id')->constrained()->restrictOnDelete();
            $t->foreignId('fund_category_id')->constrained()->restrictOnDelete();
            $t->date('period');
            $t->string('status');
            $t->string('source_updated_at');
            $t->date('snapshot_date');
            $t->string('source_name');
            $t->timestamps();
            $t->unique(['house_id', 'fund_category_id', 'period']);
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('action');
            $t->string('entity_type');
            $t->unsignedBigInteger('entity_id');
            $t->json('changes')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'legacy_recap_entries', 'cash_entries', 'payment_reviews', 'payment_proofs', 'payment_allocations', 'payments', 'bills'] as $n) {
            Schema::dropIfExists($n);
        }
    }
};
