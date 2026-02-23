<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function company(): JsonResponse
    {
        return response()->json([
            'data' => [
                'invoice_prefix' => Setting::where('key', 'invoice_prefix')->value('value') ?? 'INV',
                'quotation_prefix' => Setting::where('key', 'quotation_prefix')->value('value') ?? 'QUO',
                'currency' => Setting::where('key', 'currency')->value('value') ?? 'IDR',
                'company_name' => Setting::where('key', 'company_name')->value('value') ?? '',
                'company_address' => Setting::where('key', 'company_address')->value('value') ?? '',
                'company_phone' => Setting::where('key', 'company_phone')->value('value') ?? '',
                'company_email' => Setting::where('key', 'company_email')->value('value') ?? '',
                'company_website' => Setting::where('key', 'company_website')->value('value') ?? '',
                'company_tax_id' => Setting::where('key', 'company_tax_id')->value('value') ?? '',
            ],
        ]);
    }

    public function updateCompany(Request $request): JsonResponse
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
        ]);

        foreach ($validated as $key => $value) {
            if ($value === null) continue;

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => in_array($key, ['invoice_prefix', 'quotation_prefix', 'currency'], true) ? strtoupper($value) : $value]
            );
        }

        return response()->json([
            'message' => 'Company settings updated.',
        ]);
    }
}
