<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->bankAccounts()->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'is_default' => ['boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $request->user()->bankAccounts()->update(['is_default' => false]);
        }

        $account = $request->user()->bankAccounts()->create($validated);

        return response()->json(['message' => 'Bank account created.', 'data' => $account], 201);
    }

    public function update(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'is_default' => ['boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $request->user()->bankAccounts()->where('id', '!=', $bankAccount->id)->update(['is_default' => false]);
        }

        $bankAccount->update($validated);

        return response()->json(['message' => 'Bank account updated.', 'data' => $bankAccount]);
    }

    public function destroy(BankAccount $bankAccount): JsonResponse
    {
        $bankAccount->delete();

        return response()->json(['message' => 'Bank account deleted.']);
    }
}
