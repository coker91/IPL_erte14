<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resident extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function memberships()
    {
        return $this->hasMany(HouseResident::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
