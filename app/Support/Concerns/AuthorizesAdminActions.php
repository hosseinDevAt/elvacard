<?php

namespace App\Support\Concerns;

/**
 * Component-level defense-in-depth authorization for admin Livewire
 * components.
 *
 * Page loads are already gated by the `auth` + `admin` route middleware; this
 * trait re-checks the role on every Livewire request (initial mount and each
 * hydrate) so a crafted /livewire/update call can never run an admin component
 * as a non-admin. Livewire automatically invokes the `boot`-suffixed trait
 * hook on every request.
 */
trait AuthorizesAdminActions
{
    public function bootAuthorizesAdminActions(): void
    {
        $user = auth()->user();

        if ($user === null || $user->role !== 'admin') {
            abort(403);
        }
    }
}
