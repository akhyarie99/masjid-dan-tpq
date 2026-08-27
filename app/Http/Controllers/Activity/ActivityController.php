<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ActivityController extends Controller
{
    public function calendar(Request $request): Response
    {
        $activities = Activity::where('masjid_id', $request->user()->masjid_id)
            ->get(['id', 'name', 'category', 'location', 'start_at', 'end_at', 'status', 'pic_name', 'pic_phone', 'quota']);

        return Inertia::render('Activity/Calendar', [
            'events' => $activities->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'title' => $activity->name,
                'start' => $activity->start_at,
                'end' => $activity->end_at,
                'category' => $activity->category,
                'location' => $activity->location,
                'pic_name' => $activity->pic_name,
                'pic_phone' => $activity->pic_phone,
                'quota' => $activity->quota,
                'status' => $activity->status,
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Activity/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateActivity($request);

        Activity::create([
            ...$data,
            'masjid_id' => $request->user()->masjid_id,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.activity.calendar')->with('success', 'Kegiatan berhasil dibuat.');
    }

    public function edit(Request $request, Activity $kegiatan): Response
    {
        $this->authorizeSameMasjid($request, $kegiatan);

        return Inertia::render('Activity/Form', ['activity' => $kegiatan]);
    }

    public function update(Request $request, Activity $kegiatan): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $kegiatan);

        $kegiatan->update($this->validateActivity($request));

        return redirect()->route('admin.activity.calendar')->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(Request $request, Activity $kegiatan): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $kegiatan);

        $kegiatan->delete();

        return back()->with('success', 'Kegiatan berhasil dihapus.');
    }

    public function qrCode(Request $request, Activity $kegiatan): Response
    {
        $this->authorizeSameMasjid($request, $kegiatan);

        $token = $this->attendanceToken($kegiatan);
        $url = route('public.activity.checkin', ['activity' => $kegiatan->id, 'token' => $token]);
        $qrSvg = base64_encode(QrCode::size(240)->generate($url));

        return Inertia::render('Activity/QrLabel', [
            'activity' => $kegiatan->only(['id', 'name', 'location', 'start_at']),
            'qrCode' => "data:image/svg+xml;base64,{$qrSvg}",
        ]);
    }

    public function publicRegister(Activity $activity): Response
    {
        return Inertia::render('Public/Activity/Register', [
            'activity' => $activity->only(['id', 'name', 'description', 'location', 'start_at', 'quota']),
            'registeredCount' => ActivityRegistration::where('activity_id', $activity->id)->count(),
        ]);
    }

    public function publicRegisterStore(Request $request, Activity $activity): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        if ($activity->quota && ActivityRegistration::where('activity_id', $activity->id)->count() >= $activity->quota) {
            return back()->with('error', 'Maaf, kuota kegiatan sudah penuh.');
        }

        ActivityRegistration::create([...$data, 'activity_id' => $activity->id]);

        return back()->with('success', 'Pendaftaran berhasil! Sampai jumpa di kegiatan.');
    }

    public function checkinForm(Request $request, Activity $activity, string $token): Response
    {
        abort_unless(hash_equals($this->attendanceToken($activity), $token), 403);

        return Inertia::render('Public/Activity/Checkin', [
            'activity' => $activity->only(['id', 'name', 'location']),
            'token' => $token,
        ]);
    }

    public function checkinStore(Request $request, Activity $activity, string $token): RedirectResponse
    {
        abort_unless(hash_equals($this->attendanceToken($activity), $token), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        ActivityRegistration::updateOrCreate(
            ['activity_id' => $activity->id, 'phone' => $data['phone']],
            ['name' => $data['name'], 'is_attended' => true, 'attended_at' => now()]
        );

        return back()->with('success', 'Presensi berhasil dicatat. Jazakumullahu khairan.');
    }

    /**
     * Activity tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini pengurus tenant A yang tahu/menebak UUID
     * kegiatan tenant B bisa lihat/ubah/hapus, bahkan mencetak QR presensinya,
     * lewat URL langsung. Hanya untuk method admin — publicRegister/checkin*
     * memang dirancang bisa diakses lintas tenant oleh jamaah umum.
     */
    private function authorizeSameMasjid(Request $request, Activity $activity): void
    {
        abort_unless($activity->masjid_id === $request->user()->masjid_id, 404);
    }

    private function attendanceToken(Activity $activity): string
    {
        return substr(hash_hmac('sha256', $activity->id, config('app.key')), 0, 16);
    }

    private function validateActivity(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:kajian_rutin,pengajian_akbar,sosial,phbi,rapat,lainnya'],
            'location' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'pic_phone' => ['nullable', 'string', 'max:30'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,published,ongoing,done,cancelled'],
            'registration_link' => ['nullable', 'url'],
            'streaming_url' => ['nullable', 'url'],
        ]);
    }
}
