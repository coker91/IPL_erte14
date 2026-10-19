<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDateOnly;
use Illuminate\Database\Eloquent\Model;

class CommitteeMember extends Model
{
    use NormalizesDateOnly;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'show_in_contact' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(RtPosition::class, 'rt_position_id');
    }
}
