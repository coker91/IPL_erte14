<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDateOnly;
use Illuminate\Database\Eloquent\Model;

class DuesRate extends Model
{
    use NormalizesDateOnly;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function category()
    {
        return $this->belongsTo(FundCategory::class, 'fund_category_id');
    }
}
