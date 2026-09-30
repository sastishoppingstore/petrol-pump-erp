<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Customer vehicles: add driver_name
        if (! Schema::hasColumn('customer_vehicles', 'driver_name')) {
            Schema::table('customer_vehicles', function (Blueprint $table) {
                $table->string('driver_name', 100)->nullable()->after('registration_number');
            });
        }

        // 2. Purchases: add fuel decantation & shortage tracking fields
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'challan_number')) {
                $table->string('challan_number', 50)->nullable()->after('invoice_number');
            }
            if (! Schema::hasColumn('purchases', 'dip_before')) {
                $table->decimal('dip_before', 12, 3)->nullable()->after('volume_received');
            }
            if (! Schema::hasColumn('purchases', 'dip_after')) {
                $table->decimal('dip_after', 12, 3)->nullable()->after('dip_before');
            }
            if (! Schema::hasColumn('purchases', 'ifem')) {
                $table->decimal('ifem', 14, 2)->default(0)->after('purchase_rate');
            }
            if (! Schema::hasColumn('purchases', 'petroleum_levy')) {
                $table->decimal('petroleum_levy', 14, 2)->default(0)->after('ifem');
            }
            if (! Schema::hasColumn('purchases', 'bill_photo_path')) {
                $table->string('bill_photo_path', 255)->nullable()->after('driver_name');
            }
            if (! Schema::hasColumn('purchases', 'shortage_litres')) {
                $table->decimal('shortage_litres', 12, 3)->default(0)->after('volume_received');
            }
            if (! Schema::hasColumn('purchases', 'shortage_amount')) {
                $table->decimal('shortage_amount', 14, 2)->default(0)->after('shortage_litres');
            }
            if (! Schema::hasColumn('purchases', 'shortage_claimed')) {
                $table->boolean('shortage_claimed')->default(false)->after('shortage_amount');
            }
            if (! Schema::hasColumn('purchases', 'shortage_claim_status')) {
                $table->string('shortage_claim_status', 30)->default('NONE')->after('shortage_claimed');
            }
            if (! Schema::hasColumn('purchases', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        // 3. Products master (Non-fuel: Lubricants, Filters, Tuck Shop, Services)
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('code', 30)->unique();
                $table->string('name', 150);
                $table->string('category', 50)->default('LUBRICANT'); // LUBRICANT, FILTER, TUCK_SHOP, SERVICE, TYRE, CAR_WASH
                $table->string('unit', 30)->default('CAN'); // CAN, BOTTLE, PIECE, SERVICE, LITRE
                $table->decimal('cost_price', 14, 2)->default(0);
                $table->decimal('selling_price', 14, 2)->default(0);
                $table->decimal('current_stock', 12, 3)->default(0);
                $table->decimal('min_stock_level', 12, 3)->default(0);
                $table->string('barcode', 50)->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('branch_id');
                $table->index('category');
                $table->index('status');
                $table->index('name');
            });
        }

        // 4. Product stock movements (inventory ledger for non-fuel items)
        if (! Schema::hasTable('product_stock_movements')) {
            Schema::create('product_stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('type', 30); // PURCHASE, SALE, ADJUSTMENT_IN, ADJUSTMENT_OUT, RETURN
                $table->decimal('quantity', 12, 3);
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->decimal('before_stock', 12, 3)->default(0);
                $table->decimal('after_stock', 12, 3)->default(0);
                $table->string('reference_type', 80)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['branch_id', 'product_id']);
                $table->index(['reference_type', 'reference_id']);
            });
        }

        // 5. Tank dip calibration chart (cm to litres)
        if (! Schema::hasTable('tank_dip_charts')) {
            Schema::create('tank_dip_charts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
                $table->decimal('dip_cm', 8, 2);
                $table->decimal('litres', 12, 3);
                $table->timestamps();

                $table->unique(['tank_id', 'dip_cm']);
                $table->index(['tank_id', 'dip_cm']);
            });
        }

        // 6. Tank readings additions for cm dip and evaporation allowance
        Schema::table('tank_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('tank_readings', 'dip_cm')) {
                $table->decimal('dip_cm', 8, 2)->nullable()->after('dip_height');
            }
            if (! Schema::hasColumn('tank_readings', 'allowable_loss_litres')) {
                $table->decimal('allowable_loss_litres', 12, 3)->default(0)->after('variance_type');
            }
            if (! Schema::hasColumn('tank_readings', 'is_within_tolerance')) {
                $table->boolean('is_within_tolerance')->default(true)->after('allowable_loss_litres');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tank_readings', function (Blueprint $table) {
            $table->dropColumn(['dip_cm', 'allowable_loss_litres', 'is_within_tolerance']);
        });

        Schema::dropIfExists('tank_dip_charts');
        Schema::dropIfExists('product_stock_movements');
        Schema::dropIfExists('products');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'challan_number',
                'dip_before',
                'dip_after',
                'ifem',
                'petroleum_levy',
                'bill_photo_path',
                'shortage_litres',
                'shortage_amount',
                'shortage_claimed',
                'shortage_claim_status',
                'approved_at',
            ]);
        });

        if (Schema::hasColumn('customer_vehicles', 'driver_name')) {
            Schema::table('customer_vehicles', function (Blueprint $table) {
                $table->dropColumn('driver_name');
            });
        }
    }
};
