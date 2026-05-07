<?php

namespace Tests\Feature\MobileApi;

use App\Models\ApiToken;
use App\Models\Counter;
use App\Models\CounterAssignment;
use App\Models\QueueCall;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_endpoint_returns_canonical_envelope(): void
    {
        $this->getJson('/api/mobile/v1/meta')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.api_version', 'mobile-v1');
    }

    public function test_operator_can_login_and_admin_is_rejected(): void
    {
        $this->makeOperator();
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'operator@example.test',
            'password' => 'password',
            'device' => ['installation_id' => 'test-device', 'platform' => 'android', 'app_version' => '1.0.0'],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'admin@example.test',
            'password' => 'password',
        ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ROLE_NOT_ALLOWED');
    }

    public function test_protected_endpoint_requires_token(): void
    {
        $this->getJson('/api/mobile/v1/operator/state')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'TOKEN_EXPIRED');
    }

    public function test_state_returns_empty_assignment_for_unassigned_operator(): void
    {
        $operator = $this->makeOperator();
        $token = $this->tokenFor($operator);

        $this->withToken($token)
            ->getJson('/api/mobile/v1/operator/state')
            ->assertOk()
            ->assertJsonPath('data.assignment', null)
            ->assertJsonPath('data.summary.waiting_total', 0);
    }

    public function test_call_next_moves_fifo_ticket_to_serving_and_audits(): void
    {
        [$operator] = $this->makeQueueFixture();
        $token = $this->tokenFor($operator);

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'call-1')
            ->postJson('/api/mobile/v1/operator/queue/call-next')
            ->assertOk()
            ->assertJsonPath('data.active_ticket.ticket_no', 'A001')
            ->assertJsonPath('data.active_ticket.status', Ticket::STATUS_SERVING);

        $this->assertDatabaseHas('tickets', ['ticket_no' => 'A001', 'status' => Ticket::STATUS_SERVING]);
        $this->assertDatabaseHas('queue_calls', ['event_type' => QueueCall::EVENT_CALL]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'queue.call']);
    }

    public function test_call_next_is_rejected_when_active_ticket_exists(): void
    {
        [$operator] = $this->makeQueueFixture();
        $token = $this->tokenFor($operator);

        $this->withToken($token)->postJson('/api/mobile/v1/operator/queue/call-next')->assertOk();

        $this->withToken($token)
            ->postJson('/api/mobile/v1/operator/queue/call-next')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'ACTIVE_TICKET_EXISTS');
    }

    public function test_recall_skip_workflow_records_events(): void
    {
        [$operator] = $this->makeQueueFixture();
        $token = $this->tokenFor($operator);

        $response = $this->withToken($token)->postJson('/api/mobile/v1/operator/queue/call-next');
        $ticketId = $response->json('data.active_ticket.id');

        $this->withToken($token)
            ->postJson("/api/mobile/v1/operator/queue/{$ticketId}/recall")
            ->assertOk();

        $this->withToken($token)
            ->postJson("/api/mobile/v1/operator/queue/{$ticketId}/skip", ['reason' => 'Tidak hadir'])
            ->assertOk()
            ->assertJsonPath('data.active_ticket', null);

        $this->assertDatabaseHas('tickets', ['id' => $ticketId, 'status' => Ticket::STATUS_SKIPPED]);
        $this->assertDatabaseHas('queue_calls', ['event_type' => QueueCall::EVENT_RECALL]);
        $this->assertDatabaseHas('queue_calls', ['event_type' => QueueCall::EVENT_SKIP, 'notes' => 'Tidak hadir']);
    }

    public function test_done_workflow_records_event(): void
    {
        [$operator] = $this->makeQueueFixture();
        $token = $this->tokenFor($operator);

        $response = $this->withToken($token)->postJson('/api/mobile/v1/operator/queue/call-next');
        $ticketId = $response->json('data.active_ticket.id');

        $this->withToken($token)
            ->postJson("/api/mobile/v1/operator/queue/{$ticketId}/done", ['notes' => 'Selesai'])
            ->assertOk()
            ->assertJsonPath('data.active_ticket', null);

        $this->assertDatabaseHas('tickets', ['id' => $ticketId, 'status' => Ticket::STATUS_DONE]);
        $this->assertDatabaseHas('queue_calls', ['event_type' => QueueCall::EVENT_DONE, 'notes' => 'Selesai']);
    }

    public function test_history_returns_today_events(): void
    {
        [$operator] = $this->makeQueueFixture();
        $token = $this->tokenFor($operator);

        $this->withToken($token)->postJson('/api/mobile/v1/operator/queue/call-next')->assertOk();

        $this->withToken($token)
            ->getJson('/api/mobile/v1/operator/history')
            ->assertOk()
            ->assertJsonPath('data.events.0.event_type', QueueCall::EVENT_CALL);
    }

    public function test_history_rejects_invalid_filters(): void
    {
        [$operator] = $this->makeQueueFixture();
        $token = $this->tokenFor($operator);

        $this->withToken($token)
            ->getJson('/api/mobile/v1/operator/history?date=invalid&limit=-1')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonValidationErrors(['date', 'limit'], 'error.details');
    }

    private function makeOperator(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Operator',
            'email' => 'operator@example.test',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'is_active' => true,
        ], $overrides));
    }

    private function tokenFor(User $operator): string
    {
        $plain = str()->random(80);
        ApiToken::create([
            'user_id' => $operator->id,
            'name' => 'mobile',
            'token_hash' => hash('sha256', $plain),
        ]);

        return $plain;
    }

    private function makeQueueFixture(): array
    {
        $operator = $this->makeOperator();
        $service = Service::create([
            'code' => 'ADM',
            'name' => 'Administrasi',
            'prefix' => 'A',
            'color' => '#2563eb',
            'is_active' => true,
        ]);
        $counter = Counter::create([
            'code' => 'LK-01',
            'name' => 'Loket 1',
            'location' => 'Ruang Pelayanan',
            'is_active' => true,
        ]);
        $counter->services()->attach($service->id);

        CounterAssignment::create([
            'user_id' => $operator->id,
            'counter_id' => $counter->id,
            'start_at' => now()->startOfDay(),
            'is_active' => true,
        ]);

        foreach (['A001', 'A002'] as $index => $ticketNo) {
            Ticket::create([
                'ticket_no' => $ticketNo,
                'service_id' => $service->id,
                'ticket_date' => now()->toDateString(),
                'status' => Ticket::STATUS_WAITING,
                'created_at' => now()->startOfDay()->addMinutes($index + 1),
            ]);
        }

        return [$operator, $counter, $service];
    }
}
