<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentReview extends Model
{
    protected $guarded = [];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
