<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductStockMovement;
use App\Services\Product\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $lowStockOnly = $request->boolean('low_stock');

        $query = Product::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($lowStockOnly, fn ($q) => $q->lowStock())
            ->orderBy('category')
            ->orderBy('name');

        $products = $query->paginate(20)->withQueryString();

        $valuation = $this->productService->calculateValuation(session('active_branch_id'));
        $lowStockCount = $this->productService->getLowStockAlerts(session('active_branch_id'))->count();

        $categories = [
            Product::CATEGORY_LUBRICANT => 'ENEOS Lubricants',
            Product::CATEGORY_FILTER => 'Oil & Air Filters',
            Product::CATEGORY_TUCK_SHOP => 'Tuck Shop Items',
            Product::CATEGORY_SERVICE => 'Car Wash & Tyre Services',
        ];

        return view('products.index', compact(
            'products',
            'valuation',
            'lowStockCount',
            'categories',
            'search',
            'category',
            'lowStockOnly'
        ));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['branch_id'] = session('active_branch_id');

        $product = $this->productService->createProduct($data, auth()->id());

        return redirect()
            ->route('products.show', $product)
            ->with('status', "Product {$product->name} ({$product->code}) registered successfully.");
    }

    public function show(Product $product): View
    {
        $movements = $product->movements()
            ->with('creator')
            ->orderByDesc('id')
            ->paginate(25);

        return view('products.show', compact('product', 'movements'));
    }

    public function edit(Product $product): View
    {
        return view('products.edit', compact('product'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('status', "Product {$product->name} updated successfully.");
    }

    public function stockIn(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->productService->recordPurchase(
            product: $product,
            quantity: (string) $validated['quantity'],
            unitCost: (string) $validated['unit_cost'],
            branchId: session('active_branch_id'),
            notes: $validated['notes'] ?? 'Stock in',
            userId: auth()->id(),
        );

        return redirect()
            ->route('products.show', $product)
            ->with('status', "Added {$validated['quantity']} {$product->unit} to stock.");
    }

    public function adjustStock(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:ADJUSTMENT_IN,ADJUSTMENT_OUT'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $this->productService->recordAdjustment(
            product: $product,
            quantity: (string) $validated['quantity'],
            type: $validated['type'],
            notes: $validated['notes'],
            branchId: session('active_branch_id'),
            userId: auth()->id(),
        );

        return redirect()
            ->route('products.show', $product)
            ->with('status', "Stock adjusted by {$validated['quantity']} {$product->unit}.");
    }
}
