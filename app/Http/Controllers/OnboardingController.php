<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }

        if ($user?->wizard_completed) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/Wizard', [
            'userName' => $user?->name,
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            $user->update([
                'wizard_completed' => true,
            ]);
        }

        return redirect()->route('dashboard')->with('status', 'Onboarding selesai. Selamat datang di Paperwork!');
    }
}
