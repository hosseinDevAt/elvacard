<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutoLoginAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            $user = User::firstOrCreate(
                ['phone' => '09000000000'],
                ['name' => 'ادمین', 'password' => bcrypt('123456')]
            );
            Auth::login($user);
        }

        return $next($request);
    }
}
