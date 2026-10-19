<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, Role $role): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->canManage($user) && ! in_array($role->name, $this->systemRoles(), true);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function canManage(User $user): bool
    {
        return $user->is_active && $user->can('roles.manage');
    }

    /** @return list<string> */
    private function systemRoles(): array
    {
        return ['super_admin', 'ketua_rt', 'wakil_rt', 'sekretaris', 'bendahara', 'pengurus', 'warga'];
    }
}
