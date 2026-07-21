<?php

namespace App\Http\Controllers;

use App\Jobs\BroadcastAnnouncementWA;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $announcements = Announcement::with('user:id,name')
            ->where('masjid_id', $request->user()->masjid_id)
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Announcement/Index', ['announcements' => $announcements]);
    }

    public function create(): Response
    {
        return Inertia::render('Announcement/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAnnouncement($request);

        $announcement = Announcement::create([
            ...$data,
            'masjid_id' => $request->user()->masjid_id,
            'user_id' => $request->user()->id,
            'published_at' => $data['is_published'] ? now() : null,
        ]);

        if ($announcement->is_published && $announcement->send_whatsapp) {
            BroadcastAnnouncementWA::dispatch($announcement);
        }

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil dibuat.');
    }

    public function edit(Announcement $pengumuman): Response
    {
        return Inertia::render('Announcement/Form', ['announcement' => $pengumuman]);
    }

    public function update(Request $request, Announcement $pengumuman): RedirectResponse
    {
        $data = $this->validateAnnouncement($request);
        $wasPublished = $pengumuman->is_published;

        $pengumuman->update([
            ...$data,
            'published_at' => $data['is_published'] && ! $wasPublished ? now() : $pengumuman->published_at,
        ]);

        if (! $wasPublished && $pengumuman->is_published && $pengumuman->send_whatsapp) {
            BroadcastAnnouncementWA::dispatch($pengumuman);
        }

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $pengumuman): RedirectResponse
    {
        $pengumuman->delete();

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }

    private function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:umum,kegiatan,duka,urgent'],
            'is_published' => ['boolean'],
            'expired_at' => ['nullable', 'date'],
            'send_whatsapp' => ['boolean'],
        ]);
    }
}
