<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'fcm_token' => ['nullable', 'string'],
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! $user->is_active || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Nomor HP atau password salah, atau akun tidak aktif.'], 401);
        }

        if (! $user->hasRole(['ustadz', 'admin', 'super_admin'])) {
            return response()->json(['message' => 'Akses tidak diizinkan untuk role ini.'], 403);
        }

        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('mobile-app', ['mobile'])->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => ['required', 'string']]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['message' => 'Token FCM diperbarui.']);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function webviewToken(Request $request): JsonResponse
    {
        // Token short-lived (15 menit) untuk auto-login WebView di app Flutter.
        $token = $request->user()->createToken('webview', ['webview'], now()->addMinutes(15))->plainTextToken;

        return response()->json(['token' => $token, 'url' => config('app.url')]);
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('masjid:id,name,logo');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'avatar' => $user->avatar_url,
            'role' => $user->getRoleNames()->first(),
            'masjid' => [
                'id' => $user->masjid->id,
                'name' => $user->masjid->name,
                'logo' => $user->masjid->logo_url,
            ],
        ];
    }
}
