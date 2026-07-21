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
    public function show(Activity $activity): Response
    {
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

    public function exportPdf(Activity $activity): \Illuminate\Http\Response
    {
        $registrations = ActivityRegistration::where('activity_id', $activity->id)->orderBy('name')->get();

        $pdf = Pdf::loadView('pdf.attendance-list', [
            'activity' => $activity,
            'masjid' => $activity->masjid,
            'registrations' => $registrations,
            'printedAt' => now(),
        ]);

        return $pdf->download("daftar-hadir-{$activity->id}.pdf");
    }
}
