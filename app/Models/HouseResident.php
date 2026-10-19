<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDateOnly;
use Illuminate\Database\Eloquent\Model;

class HouseResident extends Model
{
    use NormalizesDateOnly;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_primary_contact' => 'boolean', 'can_manage_payments' => 'boolean'];
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }
}
