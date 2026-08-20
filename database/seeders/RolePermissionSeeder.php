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
            'tpq.daily-progress.view', 'tpq.daily-progress.manage',
            'jamaah.view', 'jamaah.manage',
            'report.view', 'report.export',
            'settings.manage',
            'study.view', 'study.manage',
            'ramadhan.view', 'ramadhan.manage',
            'wakaf.view', 'wakaf.manage',
            'library.view', 'library.manage',
            'announcement.view', 'announcement.manage',
            'staff-attendance.view-own', 'staff-attendance.view-all',
            'dashboard.admin',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // "viewer" otomatis dapat semua permission baca (.view / -own) — supaya
        // permission baru yang ditambahkan nanti tidak perlu didaftarkan manual
        // lagi di sini selama namanya mengikuti konvensi yang sama.
        $viewPermissions = collect($permissions)
            ->filter(fn ($p) => str_ends_with($p, '.view') || str_ends_with($p, '-own'))
            ->all();

        $rolePermissions = [
            'super_admin' => $permissions,

            // Admin dapat semua termasuk finance.delete/asset.delete — mempertahankan
            // kemampuan yang selama ini sudah dipakai admin lewat celah tidak ada
            // penegakan backend, keputusan sadar bukan kelalaian (lihat commit).
            'admin' => $permissions,

            'bendahara' => [
                'finance.view', 'finance.create', 'finance.approve', 'finance.delete',
                'report.view', 'report.export',
                'dashboard.admin',
                'staff-attendance.view-own', 'staff-attendance.view-all',
            ],

            // Kajian & Pengumuman WAJIB eksplisit di sini — sebelum penegakan backend
            // ada, sekretaris sudah bisa pakai fitur itu lewat sidebar item yang
            // permission-nya null; kalau tidak ditambahkan di sini sekretaris akan
            // mendadak kehilangan akses begitu middleware permission aktif.
            'sekretaris' => [
                'activity.view', 'activity.create', 'activity.edit',
                'jamaah.view', 'jamaah.manage',
                'prayer.view',
                'study.view', 'study.manage',
                'announcement.view', 'announcement.manage',
                'dashboard.admin',
                'staff-attendance.view-own', 'staff-attendance.view-all',
            ],

            // Ustadz sengaja TIDAK dapat tpq.manage/grade/report, dashboard.admin,
            // staff-attendance.view-all, atau *.manage di 6 modul view-only —
            // lihat docs pembatasan role ustadz.
            'ustadz' => [
                'tpq.view',
                'tpq.daily-progress.view', 'tpq.daily-progress.manage',
                'staff-attendance.view-own',
                'study.view',
                'ramadhan.view',
                'wakaf.view',
                'library.view',
                'announcement.view',
            ],

            'viewer' => $viewPermissions,
        ];

        foreach ($rolePermissions as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePerms);
        }

        Cache::forget('spatie.permission.cache');
    }
}
