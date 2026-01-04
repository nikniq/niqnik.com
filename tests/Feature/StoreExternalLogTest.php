<?php

namespace Tests\Feature;

use App\Models\ExternalLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StoreExternalLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_logs_in_external_logs_table(): void
    {
        config(['services.log_receiver.token' => 'secret-token']);

        $user = User::factory()->create();
        $occurredAt = Carbon::parse('2025-01-02 03:04:05');

        $response = $this->withHeader('X-Log-Token', 'secret-token')->postJson('/api/logs', [
            'type' => 'external_event',
            'user_id' => $user->id,
            'source' => 'client-app',
            'occurred_at' => $occurredAt->toIso8601String(),
            'context' => ['foo' => 'bar'],
        ]);

        $response->assertCreated()->assertJsonFragment([
            'type' => 'external_event',
            'user_id' => $user->id,
            'source' => 'client-app',
        ]);

        $this->assertDatabaseHas('external_logs', [
            'type' => 'external_event',
            'user_id' => $user->id,
            'source' => 'client-app',
        ]);

        $log = ExternalLog::first();

        $this->assertNotNull($log);
        $this->assertEquals('external_event', $log->type);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('client-app', $log->source);
        $this->assertTrue($log->occurred_at->equalTo($occurredAt));
        $this->assertSame('127.0.0.1', $log->ip);
        $this->assertSame('bar', $log->context['foo'] ?? null);
        $this->assertSame('client-app', $log->context['_source'] ?? null);
        $this->assertSame($occurredAt->toIso8601String(), $log->context['_occurred_at'] ?? null);
        $this->assertSame('127.0.0.1', $log->context['_ip'] ?? null);
    }
}
