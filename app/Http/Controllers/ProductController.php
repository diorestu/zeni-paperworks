<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::latest()->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'currency' => Setting::where('key', 'currency')->value('value') ?? 'IDR',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        Product::create($validated);

        return redirect()->back()->with('status', 'Product created');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($product->id),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $product->update($validated);

        return redirect()->back()->with('status', 'Product updated');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->back()->with('status', 'Product deleted');
    }
}
