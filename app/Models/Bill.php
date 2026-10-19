<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDateOnly;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use NormalizesDateOnly;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['period' => 'date', 'due_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function category()
    {
        return $this->belongsTo(FundCategory::class, 'fund_category_id');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function paidAmount(): string
    {
        return (string) $this->allocations()->whereHas('payment', fn ($q) => $q->where('status', 'approved'))->sum('amount');
    }

    public function outstandingCents(): int
    {
        return Money::cents($this->amount) - Money::cents($this->paidAmount());
    }
}
