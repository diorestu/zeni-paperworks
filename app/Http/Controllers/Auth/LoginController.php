<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Login', [
            'status' => session('status'),
        ]);
    }

    public function createRegister(): Response
    {
        return Inertia::render('Register');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = $request->user();
            if ($user?->role === 'user' && ! $user->approved_at) {
                $owner = User::query()->find($user->company_id);
                $ownerPlan = $owner?->plan_name ?? 'Free';
                $isProActive = $ownerPlan === 'Pro'
                    && (! $owner?->plan_renews_at || ! Carbon::parse($owner->plan_renews_at)->isPast());

                if ($isProActive) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    throw ValidationException::withMessages([
                        'email' => 'Your account is pending approval from your company admin.',
                    ]);
                }
            }

            return redirect()->intended('/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'wizard_completed' => false,
        ]);

        // New account becomes root company for its own tenant.
        $user->update(['company_id' => $user->id]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return redirect()->intended('/dashboard');
    }

    public function sendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'Email kamu sudah terverifikasi.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Link verifikasi telah dikirim ulang ke email kamu.');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
