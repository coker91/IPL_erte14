<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $all = ['admin.access', 'dashboard.view', 'residents.manage', 'houses.manage', 'bills.manage', 'payments.view', 'payments.review', 'payments.reverse', 'cash.manage', 'committee.manage', 'settings.manage', 'users.manage', 'roles.manage'];
        foreach ($all as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $matrix = [
            'super_admin' => $all,
            'ketua_rt' => ['admin.access', 'dashboard.view', 'payments.view', 'committee.manage', 'users.manage'],
            'wakil_rt' => ['admin.access', 'dashboard.view', 'payments.view'],
            'sekretaris' => ['admin.access', 'dashboard.view', 'residents.manage', 'houses.manage', 'committee.manage'],
            'bendahara' => ['admin.access', 'dashboard.view', 'bills.manage', 'payments.view', 'payments.review', 'payments.reverse', 'cash.manage'],
            'pengurus' => ['admin.access', 'dashboard.view'],
            'warga' => [],
        ];
        foreach ($matrix as $r => $permissions) {
            $role = Role::findOrCreate($r, 'web');

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions($permissions);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
