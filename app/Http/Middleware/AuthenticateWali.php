<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWali
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('wali_account_id')) {
            return redirect()->route('wali.login');
        }

        return $next($request);
    }
}
