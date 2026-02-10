<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankAccountController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Profile/BankAccounts', [
            'bankAccounts' => $request->user()->bankAccounts()->latest()->get(),
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        $request->user()->bankAccounts()->create($validated);

        return redirect()->back()->with('status', 'Bank account added successfully');
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
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

        return redirect()->back()->with('status', 'Bank account updated successfully');
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        return redirect()->back()->with('status', 'Bank account deleted successfully');
    }
}
