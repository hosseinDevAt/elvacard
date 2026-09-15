<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Terminates requests made by authenticated customers whose account has been
 * deactivated by an administrator.
 *
 * Guests and staff accounts (role === 'admin') are never affected. Admin
 * deactivation is not offered by the customer block feature, and the literal
 * admin role mechanism from the core auth migration is preserved.
 *
 * The account status is re-read from the database because the in-memory model
 * may not carry the column default (e.g. freshly created or acting-as users).
 */
class EnsureUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->role === 'admin') {
            return $next($request);
        }

        $status = User::query()
            ->whereKey($user->getAuthIdentifier())
            ->first(['role', 'is_active']);

        if ($status !== null && (bool) $status->is_active) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        session()->flash('error', 'حساب شما غیرفعال است. برای اطلاعات بیشتر با پشتیبانی تماس بگیرید.');

        return redirect()->route('login');
    }
}
