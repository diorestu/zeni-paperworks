<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingDowngradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_schedule_downgrade_to_lower_plan(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'plan_name' => 'Pro',
            'plan_renews_at' => now()->addDays(21)->toDateString(),
        ]);

        $this->actingAs($user);

        $response = $this->post(route('settings.billing.downgrade'), [
            'plan' => 'Basic',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $user->refresh();

        $this->assertSame('Basic', $user->pending_plan_name);
        $this->assertSame(
            now()->addDays(21)->toDateString(),
            optional($user->pending_plan_effective_at)->toDateString()
        );
    }

    public function test_downgrade_is_blocked_when_invoice_usage_exceeds_target_limit(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'plan_name' => 'Pro',
            'plan_renews_at' => now()->addDays(21)->toDateString(),
        ]);

        $this->actingAs($user);

        $client = Client::query()->create([
            'name' => 'Downgrade Load Test',
            'email' => 'downgrade-client@example.com',
        ]);

        foreach (range(1, 11) as $number) {
            Invoice::query()->create([
                'client_id' => $client->id,
                'invoice_number' => sprintf('INV-DOWNGRADE-%03d', $number),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'status' => 'draft',
                'subtotal' => 10000,
                'tax_total' => 0,
                'total' => 10000,
            ]);
        }

        $response = $this->post(route('settings.billing.downgrade'), [
            'plan' => 'Basic',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $user->refresh();

        $this->assertNull($user->pending_plan_name);
        $this->assertNull($user->pending_plan_effective_at);
    }
}

