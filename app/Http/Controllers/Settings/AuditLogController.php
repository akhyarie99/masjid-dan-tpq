<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;
        $userIds = User::where('masjid_id', $masjidId)->pluck('id');

        $query = Activity::query()->whereIn('causer_id', $userIds)->with('causer:id,name');

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->string('user_id'));
        }
        if ($request->filled('model')) {
            $query->where('subject_type', 'like', "%{$request->string('model')}%");
        }
        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $logs = $query->latest('created_at')->paginate(20)->withQueryString();

        return Inertia::render('Settings/AuditLog', [
            'logs' => $logs->through(fn ($log) => [
                'id' => $log->id,
                'description' => $log->description,
                'event' => $log->event,
                'subject_type' => class_basename($log->subject_type),
                'causer' => $log->causer?->name,
                'changes' => $log->changes(),
                'created_at' => $log->created_at,
            ]),
            'filters' => $request->only(['user_id', 'model', 'event', 'from', 'to']),
            'users' => User::where('masjid_id', $masjidId)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
