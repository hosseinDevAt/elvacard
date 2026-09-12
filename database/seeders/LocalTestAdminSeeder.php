<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Local-only test admin.
 *
 * Creates a dedicated admin for the local environment with a cryptographically
 * random, non-guessable password. The password is never stored in source code;
 * it is printed once to the terminal when this seeder runs and must be reported
 * only via the Phase I report.
 */
class LocalTestAdminSeeder extends Seeder
{
    public const PHONE = '09221112220';

    public function run(): void
    {
        $password = Str::random(16);

        $admin = User::updateOrCreate(
            ['phone' => self::PHONE],
            [
                'name' => 'Admin Test (Local)',
                'password' => $password,
            ]
        );

        $admin->forceFill([
            'role' => 'admin',
            'email_verified_at' => now(),
        ])->save();

        echo "\n[LocalTestAdminSeeder] Local test admin ready\n";
        echo "Phone:    {$admin->phone}\n";
        echo "Password: {$password}\n";
        echo "Role:     {$admin->role}\n";
    }
}