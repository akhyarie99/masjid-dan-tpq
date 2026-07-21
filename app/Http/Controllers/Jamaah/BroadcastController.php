<?php

namespace App\Http\Controllers\Jamaah;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotification;
use App\Models\JamaahProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BroadcastController extends Controller
{
    public function index(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;

        return Inertia::render('Jamaah/Broadcast', [
            'rtOptions' => JamaahProfile::where('masjid_id', $masjidId)->whereNotNull('rt')->distinct()->pluck('rt'),
            'tagOptions' => JamaahProfile::where('masjid_id', $masjidId)->whereNotNull('tags')->pluck('tags')->flatten()->unique()->values(),
            'totalActive' => JamaahProfile::where('masjid_id', $masjidId)->where('status', 'aktif')->where('receive_notification', true)->count(),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['required', 'in:all,rt,tag,manual'],
            'rt' => ['nullable', 'string'],
            'tag' => ['nullable', 'string'],
            'manual_numbers' => ['nullable', 'string'],
            'message' => ['required', 'string'],
        ]);

        $masjidId = $request->user()->masjid_id;
        $phones = [];

        if ($data['target'] === 'manual') {
            $phones = collect(explode("\n", $data['manual_numbers'] ?? ''))
                ->map(fn ($p) => trim($p))
                ->filter()
                ->values()
                ->all();
        } else {
            $query = JamaahProfile::where('masjid_id', $masjidId)
                ->where('status', 'aktif')
                ->where('receive_notification', true);

            if ($data['target'] === 'rt' && ! empty($data['rt'])) {
                $query->where('rt', $data['rt']);
            } elseif ($data['target'] === 'tag' && ! empty($data['tag'])) {
                $query->whereJsonContains('tags', $data['tag']);
            }

            $phones = $query->pluck('phone')->all();
        }

        foreach ($phones as $index => $phone) {
            SendWhatsAppNotification::dispatch($phone, $data['message'])->delay(now()->addSeconds($index * 2));
        }

        return back()->with('success', 'Broadcast sedang dikirim ke '.count($phones).' nomor.');
    }
}
