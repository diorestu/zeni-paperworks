<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
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
}
