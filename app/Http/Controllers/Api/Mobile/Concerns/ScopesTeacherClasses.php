<?php

namespace App\Http\Controllers\Api\Mobile\Concerns;

use App\Models\TpqClass;
use App\Models\TpqClassTeacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

trait ScopesTeacherClasses
{
    /**
     * Ustadz hanya melihat kelas yang diampu; admin/super_admin melihat semua kelas masjid.
     */
    private function scopedClasses(User $user, string $masjidId, ?string $activeYearId, array $columns = ['id', 'name']): Collection
    {
        $query = TpqClass::where('masjid_id', $masjidId)->where('is_active', true);

        if ($user->hasRole('ustadz') && ! $user->hasRole(['admin', 'super_admin'])) {
            $teacherClassIds = TpqClassTeacher::where('teacher_id', $user->id)
                ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
                ->pluck('class_id');

            $query->whereIn('id', $teacherClassIds);
        }

        return $query->orderBy('order')->get($columns);
    }

    private function assertClassAccessible(User $user, TpqClass $class, ?string $activeYearId): bool
    {
        if (! $user->hasRole('ustadz') || $user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        return TpqClassTeacher::where('class_id', $class->id)
            ->where('teacher_id', $user->id)
            ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
            ->exists();
    }
}
