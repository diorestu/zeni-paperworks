<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $activePlan = $this->resolveActivePlanName($user);
        $subUsers = User::query()
            ->where('company_id', $user->company_id)
            ->where('role', 'user')
            ->latest('created_at')
            ->get();

        $subUserLimit = $activePlan === 'Pro' ? 5 : null;
        $canManageSubUsers = in_array($activePlan, ['Pro', 'Enterprise'], true);
        $approvalRequired = $activePlan === 'Pro';

        return Inertia::render('Settings/Index', [
            'invoice_prefix' => Setting::where('key', 'invoice_prefix')->value('value') ?? 'INV',
            'quotation_prefix' => Setting::where('key', 'quotation_prefix')->value('value') ?? 'QUO',
            'currency' => Setting::where('key', 'currency')->value('value') ?? 'IDR',
            'company_name' => Setting::where('key', 'company_name')->value('value') ?? '',
            'company_address' => Setting::where('key', 'company_address')->value('value') ?? '',
            'company_phone' => Setting::where('key', 'company_phone')->value('value') ?? '',
            'company_email' => Setting::where('key', 'company_email')->value('value') ?? '',
            'company_website' => Setting::where('key', 'company_website')->value('value') ?? '',
            'company_tax_id' => Setting::where('key', 'company_tax_id')->value('value') ?? '',
            'company_logo_url' => $this->resolveCompanyLogoUrl(),
            'taxes' => Tax::all(),
            'active_plan' => $activePlan,
            'can_manage_sub_users' => $canManageSubUsers,
            'sub_user_limit' => $subUserLimit,
            'sub_user_count' => $subUsers->count(),
            'requires_sub_user_approval' => $approvalRequired,
            'sub_users' => $subUsers->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'email_verified_at' => optional($member->email_verified_at)->toDateTimeString(),
                'approved_at' => optional($member->approved_at)->toDateTimeString(),
                'created_at' => optional($member->created_at)->toDateTimeString(),
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_prefix' => 'nullable|string|max:10|alpha_dash',
            'quotation_prefix' => 'nullable|string|max:10|alpha_dash',
            'currency' => ['nullable', 'string', Rule::in(['IDR', 'USD', 'EUR', 'SGD'])],
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_tax_id' => 'nullable|string|max:100',
            'company_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
        ]);

        if ($request->hasFile('company_logo')) {
            $oldLogoPath = Setting::where('key', 'company_logo')->value('value');
            $newLogoPath = $request->file('company_logo')->store('company-logos', 'public');

            Setting::updateOrCreate(
                ['key' => 'company_logo'],
                ['value' => $newLogoPath]
            );

            if (! empty($oldLogoPath) && $oldLogoPath !== $newLogoPath) {
                Storage::disk('public')->delete($oldLogoPath);
            }
        }

        unset($validated['company_logo']);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => in_array($key, ['invoice_prefix', 'quotation_prefix', 'currency']) ? strtoupper($value) : $value]
                );
            }
        }

        return redirect()->back()->with('status', 'Settings updated successfully.');
    }

    private function resolveCompanyLogoUrl(): ?string
    {
        $logoPath = Setting::where('key', 'company_logo')->value('value');

        if (empty($logoPath)) {
            return null;
        }

        return Storage::disk('public')->url($logoPath);
    }

    public function storeSubUser(Request $request): RedirectResponse
    {
        $activePlan = $this->resolveActivePlanName($request->user());
        if (! in_array($activePlan, ['Pro', 'Enterprise'], true)) {
            return redirect()->back()->with('error', 'Sub-user feature is available for Pro and Enterprise plans only.');
        }

        $existingSubUserCount = User::query()
            ->where('company_id', $request->user()->company_id)
            ->where('role', 'user')
            ->count();

        if ($activePlan === 'Pro' && $existingSubUserCount >= 5) {
            return redirect()->back()->with('error', 'Pro plan can only have up to 5 sub-users.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::query()->create([
            'company_id' => $request->user()->company_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user',
            'wizard_completed' => true,
            'email_verified_at' => $activePlan === 'Enterprise' ? now() : null,
            'approved_at' => $activePlan === 'Enterprise' ? now() : null,
        ]);

        if ($activePlan === 'Pro') {
            return redirect()->back()->with('status', 'Sub-user created and waiting for approval.');
        }

        return redirect()->back()->with('status', 'Sub-user created successfully.');
    }

    public function approveSubUser(Request $request, User $user): RedirectResponse
    {
        abort_unless(
            $user->company_id === $request->user()->company_id && $user->role === 'user',
            403
        );

        $activePlan = $this->resolveActivePlanName($request->user());
        if ($activePlan !== 'Pro') {
            return redirect()->back()->with('error', 'Approval workflow is available on Pro plan only.');
        }

        if (! $user->approved_at) {
            $user->update([
                'approved_at' => now(),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ]);
        }

        return redirect()->back()->with('status', 'Sub-user approved successfully.');
    }

    public function destroySubUser(Request $request, User $user): RedirectResponse
    {
        abort_unless(
            $user->company_id === $request->user()->company_id && $user->role === 'user',
            403
        );

        $user->delete();

        return redirect()->back()->with('status', 'Sub-user deleted successfully.');
    }

    private function resolveActivePlanName(User $user): string
    {
        $planName = $user->plan_name ?? 'Free';

        if ($planName !== 'Free' && $user->plan_renews_at && Carbon::parse($user->plan_renews_at)->isPast()) {
            return 'Free';
        }

        return $planName;
    }
}
