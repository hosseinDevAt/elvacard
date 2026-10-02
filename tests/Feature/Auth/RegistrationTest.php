<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_no_longer_available(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }
}
