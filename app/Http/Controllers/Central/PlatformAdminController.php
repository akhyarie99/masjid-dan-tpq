<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Masjid;
use App\Models\PlatformAdmin;
use App\Models\PlatformSetting;
use App\Models\TenantPayment;
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
        [$currentMonth, $currentYear] = [now()->month, now()->year];

        $paidTenantIds = TenantPayment::where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->pluck('masjid_id')
            ->all();

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
                'monthly_fee' => $masjid->effectiveMonthlyFee(),
                'has_custom_fee' => $masjid->monthly_fee !== null,
                'paid_this_month' => in_array($masjid->id, $paidTenantIds, true),
                'active_until' => $masjid->active_until?->toDateString(),
                'is_expired' => $masjid->isExpired(),
            ]),
        ]);
    }

    public function toggleActive(Masjid $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);

        return back()->with('success', $tenant->is_active ? 'Tenant diaktifkan.' : 'Tenant dinonaktifkan.');
    }

    public function updateActiveUntil(Request $request, Masjid $tenant): RedirectResponse
    {
        $data = $request->validate([
            'active_until' => ['nullable', 'date'],
        ]);

        $tenant->update(['active_until' => $data['active_until']]);

        return back()->with('success', 'Masa aktif tenant diperbarui.');
    }

    public function showTenant(Masjid $tenant): Response
    {
        $payments = $tenant->payments()
            ->with('recordedBy:id,name')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();

        return Inertia::render('Central/PlatformAdmin/TenantDetail', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'is_active' => $tenant->is_active,
                'subscription_status' => $tenant->subscription_status,
                'monthly_fee' => $tenant->monthly_fee !== null ? (float) $tenant->monthly_fee : null,
                'effective_fee' => $tenant->effectiveMonthlyFee(),
                'active_until' => $tenant->active_until?->toDateString(),
                'is_expired' => $tenant->isExpired(),
            ],
            'payments' => $payments->map(fn (TenantPayment $payment) => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'period_month' => $payment->period_month,
                'period_year' => $payment->period_year,
                'paid_at' => $payment->paid_at->toDateString(),
                'note' => $payment->note,
                'recorded_by' => $payment->recordedBy?->name,
            ]),
            'defaultMonthlyFee' => (float) PlatformSetting::get('default_monthly_fee', '0'),
        ]);
    }

    public function updateFee(Request $request, Masjid $tenant): RedirectResponse
    {
        $data = $request->validate([
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tenant->update(['monthly_fee' => $data['monthly_fee']]);

        return back()->with('success', 'Tarif tenant diperbarui.');
    }

    public function storePayment(Request $request, Masjid $tenant): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'between:2020,2100'],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $exists = $tenant->payments()
            ->where('period_month', $data['period_month'])
            ->where('period_year', $data['period_year'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'period_month' => 'Pembayaran untuk periode ini sudah pernah dicatat.',
            ]);
        }

        $tenant->payments()->create([
            ...$data,
            'recorded_by' => $request->session()->get('platform_admin_id'),
        ]);

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function destroyPayment(Masjid $tenant, TenantPayment $payment): RedirectResponse
    {
        abort_unless($payment->masjid_id === $tenant->id, 404);

        $payment->delete();

        return back()->with('success', 'Catatan pembayaran dihapus.');
    }

    public function revenue(Request $request): Response
    {
        $now = now();

        // (tahun * 12 + bulan) sebagai nilai urut tunggal — jauh lebih sederhana
        // dan tidak salah dibanding kondisi OR manual untuk "12 bulan terakhir"
        // yang melewati batas tahun.
        $monthsAgoBoundary = ($now->year * 12 + $now->month) - 11;

        $monthlyTotals = TenantPayment::selectRaw('period_month, period_year, SUM(amount) as total, COUNT(*) as tenant_count')
            ->whereRaw('(period_year * 12 + period_month) >= ?', [$monthsAgoBoundary])
            ->groupBy('period_year', 'period_month')
            ->orderBy('period_year')
            ->orderBy('period_month')
            ->get()
            ->map(fn ($row) => [
                'month' => (int) $row->period_month,
                'year' => (int) $row->period_year,
                'total' => (float) $row->total,
                'tenant_count' => (int) $row->tenant_count,
            ]);

        $totalThisMonth = (float) TenantPayment::where('period_month', $now->month)
            ->where('period_year', $now->year)
            ->sum('amount');

        $totalThisYear = (float) TenantPayment::where('period_year', $now->year)->sum('amount');

        $totalAllTime = (float) TenantPayment::sum('amount');

        $tenants = Masjid::orderBy('name')->get();
        $paidThisMonthIds = TenantPayment::where('period_month', $now->month)
            ->where('period_year', $now->year)
            ->pluck('masjid_id')
            ->all();

        $tenantBreakdown = $tenants->map(function (Masjid $tenant) use ($paidThisMonthIds) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'is_active' => $tenant->is_active,
                'effective_fee' => $tenant->effectiveMonthlyFee(),
                'paid_this_month' => in_array($tenant->id, $paidThisMonthIds, true),
                'total_paid' => (float) $tenant->payments()->sum('amount'),
            ];
        });

        return Inertia::render('Central/PlatformAdmin/Revenue', [
            'summary' => [
                'this_month' => $totalThisMonth,
                'this_year' => $totalThisYear,
                'all_time' => $totalAllTime,
                'unpaid_count' => $tenants->whereNotIn('id', $paidThisMonthIds)->where('is_active', true)->count(),
            ],
            'monthlyTotals' => $monthlyTotals,
            'tenantBreakdown' => $tenantBreakdown,
            'defaultMonthlyFee' => (float) PlatformSetting::get('default_monthly_fee', '0'),
        ]);
    }

    public function updateDefaultFee(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'default_monthly_fee' => ['required', 'numeric', 'min:0'],
        ]);

        PlatformSetting::set('default_monthly_fee', (string) $data['default_monthly_fee']);

        return back()->with('success', 'Tarif default platform diperbarui.');
    }
}
