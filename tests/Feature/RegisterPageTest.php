<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_renders_all_fields(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        foreach (['name', 'email', 'password', 'password_confirmation', 'admin_email', 'captcha'] as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
        $response->assertSee('Create your Nikniq account');
        $response->assertSee('--panel-alt: #0f172a', false);
    }
}
