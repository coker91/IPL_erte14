<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDateOnly;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use NormalizesDateOnly;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['paid_at' => 'date', 'amount' => 'decimal:2', 'submitted_at' => 'datetime'];
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function category()
    {
        return $this->belongsTo(FundCategory::class, 'fund_category_id');
    }

    public function account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function proofs()
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function reviews()
    {
        return $this->hasMany(PaymentReview::class);
    }
}
