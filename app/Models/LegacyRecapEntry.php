<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDateOnly;
use Illuminate\Database\Eloquent\Model;

class LegacyRecapEntry extends Model
{
    use NormalizesDateOnly;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['period' => 'date', 'snapshot_date' => 'date'];
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function category()
    {
        return $this->belongsTo(FundCategory::class, 'fund_category_id');
    }
}
