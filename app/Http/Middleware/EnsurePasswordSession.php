<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invalidates a session once the account's password changes, mirroring
 * Laravel's AuthenticateSession password hashing but keyed per account.
 *
 * Each session stores which account id its password hash belongs to, so a
 * stored hash from a previous account (for example after an acting-as switch
 * in tests, or after logging out and logging back in) is never mistaken for a
 * changed password. Any other session still holding the pre-change hash of the
 * same account is logged out on its next request.
 */
class EnsurePasswordSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || ! $request->user() || ! $request->user()->getAuthPassword()) {
            return $next($request);
        }

        $user = $request->user();
        $guardKey = Auth::getDefaultDriver();
        $hashKey = 'password_hash_'.$guardKey;
        $ownerKey = 'password_owner_'.$guardKey;

        if (Auth::guard('web')->viaRemember()) {
            $cookieHash = explode('|', (string) $request->cookies->get(Auth::guard('web')->getRecallerName()))[2] ?? null;

            if (! $cookieHash || ! hash_equals($this->passwordHash($user), $cookieHash)) {
                $this->logout($request);
            }
        }

        if ($request->session()->get($ownerKey) !== (string) $user->getAuthIdentifier()) {
            $this->storePasswordHashInSession($request);
        } elseif (! $this->validatesAgainst($request, $user, $hashKey)) {
            $this->logout($request);
        }

        return tap($next($request), function () use ($request) {
            if ($request->user() !== null) {
                $this->storePasswordHashInSession($request);
            }
        });
    }

    protected function passwordHash($user): string
    {
        return Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword());
    }

    protected function validatesAgainst(Request $request, $user, string $hashKey): bool
    {
        $stored = $request->session()->get($hashKey);

        if (! is_string($stored)) {
            return false;
        }

        $current = $this->passwordHash($user);

        return hash_equals($current, $stored)
            || hash_equals($user->getAuthPassword(), $stored);
    }

    protected function storePasswordHashInSession(Request $request): void
    {
        $user = $request->user();

        if ($user === null) {
            return;
        }

        $guardKey = Auth::getDefaultDriver();

        $request->session()->put([
            'password_hash_'.$guardKey => $this->passwordHash($user),
            'password_owner_'.$guardKey => (string) $user->getAuthIdentifier(),
        ]);
    }

    protected function logout(Request $request): void
    {
        Auth::guard('web')->logoutCurrentDevice();

        $request->session()->flush();

        throw new AuthenticationException('Unauthenticated.', [Auth::getDefaultDriver()], route('login'));
    }
}
