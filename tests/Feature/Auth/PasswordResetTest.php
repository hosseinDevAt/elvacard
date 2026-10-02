<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_request_screen_is_no_longer_available(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertNotFound();
    }
}
