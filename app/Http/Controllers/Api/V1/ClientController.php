<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Client::query()->latest();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'industry_sector' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        $client = Client::query()->create($validated);

        return response()->json(['message' => 'Client created.', 'data' => $client], 201);
    }

    public function update(Request $request, Client $client): JsonResponse
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

        return response()->json(['message' => 'Client updated.', 'data' => $client]);
    }

    public function destroy(Client $client): JsonResponse
    {
        $hasInvoices = Invoice::query()->where('client_id', $client->id)->exists();
        $hasQuotations = Quotation::query()->where('client_id', $client->id)->exists();

        if ($hasInvoices || $hasQuotations) {
            return response()->json([
                'message' => 'Client cannot be deleted because it is already used in invoices or quotations.',
            ], 422);
        }

        $client->delete();

        return response()->json(['message' => 'Client deleted.']);
    }
}
