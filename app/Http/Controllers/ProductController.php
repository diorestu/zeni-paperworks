<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\QuotationItem;
use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

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

        $product = Product::create($validated);

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'product.created',
            'title' => 'Product created',
            'message' => "Product {$product->name} has been created.",
            'href' => route('products.index'),
            'icon' => 'si:inventory-line',
        ]);

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

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'product.updated',
            'title' => 'Product updated',
            'message' => "Product {$product->name} has been updated.",
            'href' => route('products.index'),
            'icon' => 'si:edit-line',
        ]);

        return redirect()->back()->with('status', 'Product updated');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $isUsedInInvoice = InvoiceItem::where('product_id', $product->id)->exists();
        $isUsedInQuotation = QuotationItem::where('product_id', $product->id)->exists();

        if ($isUsedInInvoice || $isUsedInQuotation) {
            return redirect()
                ->back()
                ->with('error', 'Product cannot be deleted because it is already used in invoice or quotation items.');
        }

        $productName = $product->name;
        $product->delete();

        $this->notificationService->notifyUser($request->user(), [
            'type' => 'product.deleted',
            'title' => 'Product deleted',
            'message' => "Product {$productName} has been deleted.",
            'href' => route('products.index'),
            'icon' => 'si:bin-line',
        ]);

        return redirect()->back()->with('status', 'Product deleted successfully.');
    }
}
