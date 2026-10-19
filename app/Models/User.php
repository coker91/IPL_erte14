<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'username', 'email', 'password', 'resident_id', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'email_verified_at' => 'datetime'];
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && ($panel->getId() === 'admin' ? $this->can('admin.access') : ($this->resident_id !== null && $this->hasRole('warga')));
    }

    public function manageableHouseIds(): array
    {
        if (! $this->is_active || ! $this->resident_id) {
            return [];
        }

        return HouseResident::where('resident_id', $this->resident_id)->where('can_manage_payments', true)->whereDate('start_date', '<=', today())->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', today()))->pluck('house_id')->all();
    }
}
