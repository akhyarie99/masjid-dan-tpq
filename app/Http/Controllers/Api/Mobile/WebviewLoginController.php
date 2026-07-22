<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Menukar token Sanctum short-lived (ability "webview", lihat
 * MobileAuthController::webviewToken) menjadi sesi web biasa, supaya
 * WebView di app Flutter bisa auto-login ke halaman admin berbasis sesi.
 */
class WebviewLoginController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $accessToken = PersonalAccessToken::findToken($request->string('token'));

        if (! $accessToken
            || ! $accessToken->can('webview')
            || ($accessToken->expires_at && $accessToken->expires_at->isPast())
            || ! $accessToken->tokenable instanceof User
        ) {
            abort(401, 'Token WebView tidak valid atau sudah kedaluwarsa.');
        }

        Auth::login($accessToken->tokenable);
        $request->session()->regenerate();

        // Token webview sekali pakai — hapus setelah dipertukarkan menjadi sesi.
        $accessToken->delete();

        $redirect = $request->string('redirect')->toString();
        $safeRedirect = str_starts_with($redirect, '/') ? $redirect : route('admin.dashboard');

        return redirect($safeRedirect);
    }
}
