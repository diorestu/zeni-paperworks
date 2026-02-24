<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()->route('login')->with('error', 'Google OAuth is not configured yet.');
        }

        return Socialite::driver('google')
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            return redirect()->route('login')->with('error', 'Google sign-in failed. Please try again.');
        }

        $user = User::query()
            ->where('google_id', $googleUser->id)
            ->orWhere('email', $googleUser->email)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->name ?: 'Google User',
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'google_avatar' => $googleUser->avatar,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'role' => 'admin',
                'wizard_completed' => false,
            ]);

            $user->update(['company_id' => $user->id]);
        } else {
            $user->update([
                'google_id' => $user->google_id ?: $googleUser->id,
                'google_avatar' => $googleUser->avatar ?: $user->google_avatar,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended('/dashboard');
    }
}
