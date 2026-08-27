<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function show(Request $request, Activity $activity): Response
    {
        $this->authorizeSameMasjid($request, $activity);

        $registrations = ActivityRegistration::where('activity_id', $activity->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Activity/Attendance', [
            'activity' => $activity->only(['id', 'name', 'location', 'start_at', 'quota']),
            'registrations' => $registrations,
        ]);
    }

    public function store(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $activity);

        $data = $request->validate([
            'registration_id' => ['required', 'uuid', 'exists:activity_registrations,id'],
            'is_attended' => ['required', 'boolean'],
        ]);

        $registration = ActivityRegistration::where('activity_id', $activity->id)->findOrFail($data['registration_id']);
        $registration->update([
            'is_attended' => $data['is_attended'],
            'attended_at' => $data['is_attended'] ? now() : null,
        ]);

        return back()->with('success', 'Status presensi diperbarui.');
    }

    public function scanQr(Request $request, Activity $activity): JsonResponse
    {
        $this->authorizeSameMasjid($request, $activity);

        $data = $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $registration = ActivityRegistration::where('activity_id', $activity->id)
            ->where('phone', $data['phone'])
            ->first();

        if (! $registration) {
            return response()->json(['message' => 'Nomor HP tidak terdaftar untuk kegiatan ini.'], 404);
        }

        $registration->update(['is_attended' => true, 'attended_at' => now()]);

        return response()->json(['message' => "{$registration->name} berhasil dicatat hadir.", 'registration' => $registration]);
    }

    public function exportPdf(Request $request, Activity $activity): \Illuminate\Http\Response
    {
        $this->authorizeSameMasjid($request, $activity);

        $registrations = ActivityRegistration::where('activity_id', $activity->id)->orderBy('name')->get();

        $pdf = Pdf::loadView('pdf.attendance-list', [
            'activity' => $activity,
            'masjid' => $activity->masjid,
            'registrations' => $registrations,
            'printedAt' => now(),
        ]);

        return $pdf->download("daftar-hadir-{$activity->id}.pdf");
    }

    /**
     * Activity tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini pengurus tenant A yang tahu/menebak UUID
     * kegiatan tenant B bisa melihat/mengubah/mengekspor daftar hadirnya
     * (lengkap dengan nama & nomor HP peserta) lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, Activity $activity): void
    {
        abort_unless($activity->masjid_id === $request->user()->masjid_id, 404);
    }
}
