<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'finance.view', 'finance.create', 'finance.approve', 'finance.delete',
            'asset.view', 'asset.create', 'asset.edit', 'asset.delete',
            'activity.view', 'activity.create', 'activity.edit',
            'prayer.view', 'prayer.manage',
            'tpq.view', 'tpq.manage', 'tpq.grade', 'tpq.report',
            'jamaah.view', 'jamaah.manage',
            'report.view', 'report.export',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $viewPermissions = collect($permissions)->filter(fn ($p) => str_ends_with($p, '.view'))->all();

        $rolePermissions = [
            'super_admin' => $permissions,
            'admin' => collect($permissions)->reject(fn ($p) => in_array($p, ['finance.delete', 'asset.delete']))->all(),
            'bendahara' => ['finance.view', 'finance.create', 'finance.approve', 'finance.delete', 'report.view', 'report.export'],
            'sekretaris' => ['activity.view', 'activity.create', 'activity.edit', 'jamaah.view', 'jamaah.manage', 'prayer.view'],
            'ustadz' => ['tpq.view', 'tpq.manage', 'tpq.grade', 'tpq.report'],
            'viewer' => $viewPermissions,
        ];

        foreach ($rolePermissions as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePerms);
        }

        Cache::forget('spatie.permission.cache');
    }
}
