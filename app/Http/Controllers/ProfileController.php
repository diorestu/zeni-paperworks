<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . ($user ? $user->id : 0)],
        ]);

        if ($user) {
            $user->update($validated);
        }

        return redirect()->back()->with('status', 'Profile updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('status', 'Password updated');
    }

    public function billing(): Response
    {
        $user = request()->user();

        return Inertia::render('Profile/Billing', [
            'currentPlan' => $user->plan_name ?? 'Free',
            'paymentHistory' => $user->subscriptionInvoices()
                ->latest('invoice_date')
                ->get()
                ->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'plan_name' => $invoice->plan_name,
                    'amount' => (float) $invoice->amount,
                    'invoice_date' => optional($invoice->invoice_date)->toDateString(),
                    'due_date' => optional($invoice->due_date)->toDateString(),
                    'status' => $invoice->status,
                ]),
        ]);
    }

    public function security(): Response
    {
        return Inertia::render('Profile/Security');
    }
}
