<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class InvoiceNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_number_generation_default()
    {
        $user = User::factory()->create(['role' => 'super_admin']); 
        // We use super_admin because middleware requires it for some routes or at least invoice creation might need permission
        // Actually InvoiceController middleware is 'role:super_admin,admin,user'
        
        $client = Client::create([
            'name' => 'Test Client',
            'company' => 'Test Company',
            'email' => 'client@example.com',
            'phone' => '1234567890',
            'address' => '123 Test St',
        ]);

        $this->actingAs($user);

        // First invoice
        $response = $this->post(route('invoices.store'), [
            'client_id' => $client->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [['description' => 'Test', 'quantity' => 1, 'unit_price' => 100]],
            'invoice_number' => 'INV/' . now()->format('ymd') . '/001',
        ]);
        
        // Assert redirect to avoid 500 error masking
        $response->assertRedirect(route('invoices.index'));

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV/' . now()->format('ymd') . '/001',
        ]);
        
        // Check Inertia prop instead of raw HTML
        $response = $this->get(route('invoices.create'));
        $response->assertInertia(fn ($page) => $page
            ->component('Invoices/Create')
            ->where('nextInvoiceNumber', 'INV/' . now()->format('ymd') . '/002')
        );
    }

    public function test_invoice_number_custom_prefix()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Setting::create(['key' => 'invoice_prefix', 'value' => 'ABC']);

        $this->actingAs($user);

        $response = $this->get(route('invoices.create'));
        
        $response->assertInertia(fn ($page) => $page
            ->component('Invoices/Create')
            ->where('nextInvoiceNumber', 'ABC/' . now()->format('ymd') . '/001')
        );
    }
    
    public function test_bank_account_fix()
    {
         $user = User::factory()->create(['role' => 'admin']);
         $client = Client::create([
            'name' => 'Test Client 2',
            'email' => 'client2@example.com',
         ]);
         
         $this->actingAs($user);
         
         $response = $this->post(route('invoices.store'), [
            'client_id' => $client->id,
            // 'bank_account_id' => null, // Omitted
            'invoice_number' => 'TEST/001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [['description' => 'Test', 'quantity' => 1, 'unit_price' => 100]],
        ]);
        
        $response->assertRedirect(route('invoices.index'));
        $this->assertDatabaseHas('invoices', ['invoice_number' => 'TEST/001']);
    }
}
