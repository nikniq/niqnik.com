<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_server(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $payload = [
            'name' => 'App Server',
            'hostname' => 'app.example.test',
            'status' => Server::STATUS_ONLINE,
            'environment' => 'production',
            'last_seen_at' => '2025-01-01T10:00:00',
            'notes' => 'Primary node',
        ];

        $response = $this->actingAs($admin)->post(route('admin.servers.store'), $payload);

        $response->assertRedirect(route('admin.servers.index'));

        $this->assertDatabaseHas('servers', [
            'name' => 'App Server',
            'hostname' => 'app.example.test',
            'status' => Server::STATUS_ONLINE,
            'environment' => 'production',
        ]);
    }
}
