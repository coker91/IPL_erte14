<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDateOnly;
use Illuminate\Database\Eloquent\Model;

class House extends Model
{
    use NormalizesDateOnly;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_ipl_subject' => 'boolean', 'billing_start_period' => 'date', 'billing_end_period' => 'date'];
    }

    public function memberships()
    {
        return $this->hasMany(HouseResident::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function legacyEntries()
    {
        return $this->hasMany(LegacyRecapEntry::class);
    }
}
