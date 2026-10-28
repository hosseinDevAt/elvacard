<?php

namespace App\Http\Middleware;

use App\Services\MaintenanceModeManager;
use Closure;
use Illuminate\Http\Request;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function __construct(
        protected MaintenanceModeManager $manager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->manager->isActive()) {
            return $next($request);
        }

        // 1. Authenticated active staff administrators have unrestricted access
        $user = $request->user();
        if ($user !== null && $user->role === 'admin' && (bool) $user->is_active) {
            return $next($request);
        }

        // 2. Admin dashboard routes (unauthenticated users will be redirected to /login by auth middleware)
        if ($request->is('dashboard') || $request->is('dashboard/*')) {
            return $next($request);
        }

        // 3. Essential authentication endpoints
        if ($request->is('login') || $request->is('logout')) {
            return $next($request);
        }

        // 4. Livewire endpoints (including dynamic livewire-<hash>/update)
        if ($this->isLivewireRequest($request)) {
            if ($request->isMethod('GET')) {
                return $next($request);
            }

            if (app()->environment('testing') && ($request->is('livewire-test/*') || $request->is('_livewire-test/*'))) {
                return $next($request);
            }

            if ($this->isAuthLivewireRequest($request)) {
                return $next($request);
            }

            return $this->maintenanceResponse($request);
        }

        // 5. Static assets and system health check
        if (
            $request->is('up') ||
            $request->is('build/*') ||
            $request->is('storage/*') ||
            $request->is('fonts/*') ||
            $request->is('favicon.*') ||
            $request->is('robots.txt')
        ) {
            return $next($request);
        }

        // 6. Return HTTP 503 Service Unavailable for all other requests
        return $this->maintenanceResponse($request);
    }

    protected function isLivewireRequest(Request $request): bool
    {
        // Named routes registered by Livewire
        if ($request->routeIs('*livewire.update', 'livewire.update', 'default-livewire.update', 'livewire.upload-file', 'livewire.preview-file')) {
            return true;
        }

        // Dynamic prefix resolution from installed Livewire 4 package
        if (class_exists(EndpointResolver::class)) {
            try {
                $prefix = trim(EndpointResolver::prefix(), '/');
                if ($prefix !== '' && ($request->is($prefix) || $request->is($prefix.'/*'))) {
                    return true;
                }
            } catch (\Throwable) {
                // Ignore and fallback to path matching
            }
        }

        $path = trim($request->path(), '/');

        // Standard Livewire prefix without hash
        if ($path === 'livewire' || str_starts_with($path, 'livewire/')) {
            return true;
        }

        // Livewire 4 dynamic hashed prefix (e.g. livewire-6e953cb4/update)
        if (preg_match('#^livewire-[0-9a-zA-Z]+(/.*)?$#', $path)) {
            return true;
        }

        // Testing environment test-runner routes
        if (app()->environment('testing') && ($request->is('livewire-test/*') || $request->is('_livewire-test/*'))) {
            return true;
        }

        return false;
    }

    protected function isAuthLivewireRequest(Request $request): bool
    {
        $components = $request->input('components');

        if (! is_array($components) || empty($components)) {
            return false;
        }

        $validCount = 0;

        foreach ($components as $component) {
            if (! is_array($component)) {
                return false;
            }

            $snapshotRaw = $component['snapshot'] ?? null;
            if (! is_string($snapshotRaw) || trim($snapshotRaw) === '') {
                return false;
            }

            $snapshot = json_decode($snapshotRaw, true);
            if (! is_array($snapshot) || json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            $memo = $snapshot['memo'] ?? null;
            if (! is_array($memo)) {
                return false;
            }

            $name = $memo['name'] ?? null;
            if (! is_string($name) || trim($name) === '') {
                return false;
            }

            if ($name !== 'auth.login' && ! str_starts_with($name, 'auth.')) {
                return false;
            }

            $validCount++;
        }

        return $validCount > 0;
    }

    protected function maintenanceResponse(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'سایت در حال حاضر در حال تعمیر و به‌روزرسانی است.',
            ], 503);
        }

        return response()->view('errors.503', [], 503, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
