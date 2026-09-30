<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductStockMovement;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    /**
     * Create a new non-fuel retail product.
     */
    public function createProduct(array $data, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($data, $userId) {
            $costPrice = Money::round(Money::n($data['cost_price'] ?? '0.00'));
            $sellingPrice = Money::round(Money::n($data['selling_price'] ?? '0.00'));
            $initialStock = Quantity::round(Quantity::n($data['initial_stock'] ?? '0.000'));
            $minStock = Quantity::round(Quantity::n($data['min_stock_level'] ?? '0.000'));

            $product = Product::create([
                'branch_id' => $data['branch_id'] ?? null,
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'category' => $data['category'] ?? Product::CATEGORY_LUBRICANT,
                'unit' => $data['unit'] ?? 'CAN',
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'current_stock' => $initialStock,
                'min_stock_level' => $minStock,
                'barcode' => $data['barcode'] ?? null,
                'status' => $data['status'] ?? Product::STATUS_ACTIVE,
                'notes' => $data['notes'] ?? null,
            ]);

            if (Quantity::compare($initialStock, '0.000') > 0) {
                ProductStockMovement::create([
                    'branch_id' => $product->branch_id ?? 1,
                    'product_id' => $product->id,
                    'type' => ProductStockMovement::TYPE_PURCHASE,
                    'quantity' => $initialStock,
                    'unit_cost' => $costPrice,
                    'before_stock' => '0.000',
                    'after_stock' => $initialStock,
                    'reference_type' => 'INITIAL_STOCK',
                    'reference_id' => $product->id,
                    'notes' => 'Opening stock initialization',
                    'created_by' => $userId ?? auth()->id(),
                ]);
            }

            return $product;
        });
    }

    /**
     * Record stock purchase for non-fuel product.
     */
    public function recordPurchase(
        Product $product,
        string $quantity,
        string $unitCost,
        ?int $branchId = null,
        ?string $notes = null,
        ?int $userId = null,
        ?string $refType = null,
        ?int $refId = null,
    ): ProductStockMovement {
        $qty = Quantity::round($quantity);
        $cost = Money::round($unitCost);

        if (Quantity::compare($qty, '0.000') <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Purchase quantity must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($product, $qty, $cost, $branchId, $notes, $userId, $refType, $refId) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $before = Quantity::n($locked->current_stock);
            $after = Quantity::add($before, $qty);

            $locked->update([
                'current_stock' => $after,
                'cost_price' => Money::compare($cost, '0.00') > 0 ? $cost : $locked->cost_price,
            ]);

            return ProductStockMovement::create([
                'branch_id' => $branchId ?? $locked->branch_id ?? 1,
                'product_id' => $locked->id,
                'type' => ProductStockMovement::TYPE_PURCHASE,
                'quantity' => $qty,
                'unit_cost' => $cost,
                'before_stock' => $before,
                'after_stock' => $after,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'notes' => $notes ?? 'Product purchase receipt',
                'created_by' => $userId ?? auth()->id(),
            ]);
        });
    }

    /**
     * Record a sale of non-fuel product (deduct inventory).
     */
    public function recordSale(
        Product $product,
        string $quantity,
        ?string $unitPrice = null,
        ?int $branchId = null,
        ?string $refType = null,
        ?int $refId = null,
        ?int $userId = null,
    ): ProductStockMovement {
        $qty = Quantity::round($quantity);

        if (Quantity::compare($qty, '0.000') <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Sale quantity must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($product, $qty, $unitPrice, $branchId, $refType, $refId, $userId) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            // Services (car wash, tyre inflation) don't have finite stock checks
            if ($locked->category !== Product::CATEGORY_SERVICE) {
                if (Quantity::compare($locked->current_stock, $qty) < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => sprintf(
                            'Insufficient stock for product "%s". Available: %s %s, Requested: %s %s',
                            $locked->name,
                            Quantity::format($locked->current_stock),
                            $locked->unit,
                            Quantity::format($qty),
                            $locked->unit
                        ),
                    ]);
                }
            }

            $before = Quantity::n($locked->current_stock);
            $after = $locked->category === Product::CATEGORY_SERVICE
                ? $before
                : Quantity::subtract($before, $qty);

            $locked->update(['current_stock' => $after]);

            return ProductStockMovement::create([
                'branch_id' => $branchId ?? $locked->branch_id ?? 1,
                'product_id' => $locked->id,
                'type' => ProductStockMovement::TYPE_SALE,
                'quantity' => $qty,
                'unit_cost' => $locked->cost_price,
                'before_stock' => $before,
                'after_stock' => $after,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'notes' => 'Product POS sale',
                'created_by' => $userId ?? auth()->id(),
            ]);
        });
    }

    /**
     * Record stock adjustment (ADJUSTMENT_IN or ADJUSTMENT_OUT).
     */
    public function recordAdjustment(
        Product $product,
        string $quantity,
        string $type,
        ?string $notes = null,
        ?int $branchId = null,
        ?int $userId = null,
    ): ProductStockMovement {
        $qty = Quantity::round($quantity);

        if (Quantity::compare($qty, '0.000') <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Adjustment quantity must be greater than zero.',
            ]);
        }

        if (! in_array($type, [ProductStockMovement::TYPE_ADJUSTMENT_IN, ProductStockMovement::TYPE_ADJUSTMENT_OUT])) {
            throw ValidationException::withMessages([
                'type' => 'Invalid adjustment type.',
            ]);
        }

        return DB::transaction(function () use ($product, $qty, $type, $notes, $branchId, $userId) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $before = Quantity::n($locked->current_stock);

            if ($type === ProductStockMovement::TYPE_ADJUSTMENT_OUT && Quantity::compare($before, $qty) < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Cannot adjust out more than current stock.',
                ]);
            }

            $after = $type === ProductStockMovement::TYPE_ADJUSTMENT_IN
                ? Quantity::add($before, $qty)
                : Quantity::subtract($before, $qty);

            $locked->update(['current_stock' => $after]);

            return ProductStockMovement::create([
                'branch_id' => $branchId ?? $locked->branch_id ?? 1,
                'product_id' => $locked->id,
                'type' => $type,
                'quantity' => $qty,
                'unit_cost' => $locked->cost_price,
                'before_stock' => $before,
                'after_stock' => $after,
                'notes' => $notes ?? 'Manual inventory adjustment',
                'created_by' => $userId ?? auth()->id(),
            ]);
        });
    }

    /**
     * Get products running low on stock.
     */
    public function getLowStockAlerts(?int $branchId = null): Collection
    {
        return Product::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', Product::STATUS_ACTIVE)
            ->where('category', '!=', Product::CATEGORY_SERVICE)
            ->whereColumn('current_stock', '<=', 'min_stock_level')
            ->orderBy('name')
            ->get();
    }

    /**
     * Inventory valuation summary.
     */
    public function calculateValuation(?int $branchId = null): array
    {
        $products = Product::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', Product::STATUS_ACTIVE)
            ->where('category', '!=', Product::CATEGORY_SERVICE)
            ->get();

        $totalCostValue = '0.00';
        $totalRetailValue = '0.00';
        $totalItems = 0;

        foreach ($products as $product) {
            $stock = Quantity::n($product->current_stock);
            if (Quantity::compare($stock, '0.000') > 0) {
                $costVal = bcmul($stock, Money::n($product->cost_price), 2);
                $retailVal = bcmul($stock, Money::n($product->selling_price), 2);

                $totalCostValue = Money::add($totalCostValue, $costVal);
                $totalRetailValue = Money::add($totalRetailValue, $retailVal);
                $totalItems++;
            }
        }

        $projectedProfit = Money::subtract($totalRetailValue, $totalCostValue);
        $profitMargin = Money::compare($totalRetailValue, '0.00') > 0
            ? bcdiv(bcmul($projectedProfit, '100', 4), $totalRetailValue, 2)
            : '0.00';

        return [
            'total_items_in_stock' => $totalItems,
            'total_cost_value' => $totalCostValue,
            'total_retail_value' => $totalRetailValue,
            'projected_profit' => $projectedProfit,
            'projected_margin_percent' => $profitMargin,
        ];
    }
}
