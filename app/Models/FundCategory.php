<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundCategory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function accounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function entries()
    {
        return $this->hasMany(CashEntry::class);
    }
}
