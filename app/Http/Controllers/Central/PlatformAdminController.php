<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Masjid;
use App\Models\PlatformAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlatformAdminController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Central/PlatformAdmin/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = PlatformAdmin::where('email', $credentials['email'])->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        $request->session()->put('platform_admin_id', $admin->id);
        $request->session()->regenerate();

        return redirect()->route('platform-admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('platform_admin_id');
        $request->session()->regenerate();

        return redirect()->route('platform-admin.login');
    }

    public function dashboard(Request $request): Response
    {
        $tenants = Masjid::withCount('users')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $centralDomain = config('tenancy.central_domain');

        return Inertia::render('Central/PlatformAdmin/Dashboard', [
            'tenants' => $tenants->through(fn (Masjid $masjid) => [
                'id' => $masjid->id,
                'name' => $masjid->name,
                'slug' => $masjid->slug,
                'subdomain_url' => "https://{$masjid->slug}.{$centralDomain}",
                'custom_domain' => $masjid->custom_domain,
                'custom_domain_verified' => (bool) $masjid->custom_domain_verified_at,
                'is_active' => $masjid->is_active,
                'subscription_status' => $masjid->subscription_status,
                'users_count' => $masjid->users_count,
                'created_at' => $masjid->created_at->toDateString(),
            ]),
        ]);
    }

    public function toggleActive(Masjid $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);

        return back()->with('success', $tenant->is_active ? 'Tenant diaktifkan.' : 'Tenant dinonaktifkan.');
    }
}
