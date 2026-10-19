<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
}
