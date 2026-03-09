<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class isDriverlogin
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('driver-web')->check()) {
            return redirect()->route('driver-dashboard');
        }

        return $next($request);
    }
}
