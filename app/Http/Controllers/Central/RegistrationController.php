<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Masjid;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function showForm(): Response
    {
        return Inertia::render('Central/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_phone' => ['required', 'string', 'max:30'],
            'admin_email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Honeypot: field ini disembunyikan via CSS di form, bot yang isi semua
            // field otomatis akan mengisinya juga — manusia tidak pernah menyentuhnya.
            'website_hp' => ['prohibited'],
        ]);

        $slug = $this->generateSlug($data['name']);

        $centralDomain = config('tenancy.central_domain');
        $loginUrl = "https://{$slug}.{$centralDomain}/login";

        $user = DB::transaction(function () use ($data, $slug) {
            $masjid = Masjid::create([
                'name' => $data['name'],
                'slug' => $slug,
                'address' => $data['address'],
                'is_active' => true,
                'subscription_status' => 'trial',
            ]);

            $user = User::create([
                'masjid_id' => $masjid->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'] ?? null,
                'phone' => $data['admin_phone'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            $user->assignRole('admin');

            return $user;
        });

        SendWhatsAppNotification::dispatch(
            $user->phone,
            "Assalamu'alaikum {$user->name}, pendaftaran lembaga \"{$data['name']}\" berhasil. Login di sini:\n{$loginUrl}"
        );

        return redirect()->away("{$loginUrl}?registered=1");
    }

    private function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = mb_substr($base, 0, 40);

        if ($base === '') {
            throw ValidationException::withMessages([
                'name' => 'Nama lembaga tidak bisa dipakai untuk membuat alamat portal. Coba nama lain.',
            ]);
        }

        $slug = $base;
        $suffix = 1;

        while (Masjid::where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
