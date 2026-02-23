<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\QuotationItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->latest();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $product = Product::query()->create($validated);

        return response()->json(['message' => 'Product created.', 'data' => $product], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($product->id),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $product->update($validated);

        return response()->json(['message' => 'Product updated.', 'data' => $product]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $isUsedInInvoice = InvoiceItem::query()->where('product_id', $product->id)->exists();
        $isUsedInQuotation = QuotationItem::query()->where('product_id', $product->id)->exists();

        if ($isUsedInInvoice || $isUsedInQuotation) {
            return response()->json([
                'message' => 'Product cannot be deleted because it is used in invoices or quotations.',
            ], 422);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }
}
