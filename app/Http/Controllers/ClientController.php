<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(): Response
    {
        $clients = Client::latest()->get();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'industry_sector' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        $client = Client::create($validated);

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'client.created',
            'title' => 'Client created',
            'message' => "Client {$client->name} has been created.",
            'href' => route('clients.index'),
            'icon' => 'si:user-line',
        ]);

        return redirect()->back()->with('status', 'Client created');
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'industry_sector' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        $client->update($validated);

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'client.updated',
            'title' => 'Client updated',
            'message' => "Client {$client->name} has been updated.",
            'href' => route('clients.index'),
            'icon' => 'si:edit-line',
        ]);

        return redirect()->back()->with('status', 'Client updated');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $hasInvoices = Invoice::where('client_id', $client->id)->exists();
        $hasQuotations = Quotation::where('client_id', $client->id)->exists();

        if ($hasInvoices || $hasQuotations) {
            return redirect()
                ->back()
                ->with('error', 'Client cannot be deleted because it is already used in invoice or quotation data.');
        }

        $clientName = $client->name;
        $client->delete();

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'client.deleted',
            'title' => 'Client deleted',
            'message' => "Client {$clientName} has been deleted.",
            'href' => route('clients.index'),
            'icon' => 'si:bin-line',
        ]);

        return redirect()->back()->with('status', 'Client deleted successfully.');
    }
}
