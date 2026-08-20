<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('platform_admin_id')) {
            return redirect()->route('platform-admin.login');
        }

        return $next($request);
    }
}
