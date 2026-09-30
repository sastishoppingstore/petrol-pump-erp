<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand settings.value from string(255) to text to support JSON station identity & templates
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->text('value')->nullable()->change();
            });
        }

        // 2. Invoice Templates
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->string('paper_size', 20)->default('A4'); // A4, thermal_80mm, thermal_58mm
            $table->string('primary_color', 10)->default('#D71920'); // Vital Red
            $table->string('dark_red_color', 10)->default('#A30F15'); // Vital Dark Red
            $table->string('accent_color', 10)->default('#1B1B1B'); // Vital Text
            $table->string('background_color', 10)->default('#FFFFFF');
            $table->string('light_grey_color', 10)->default('#F6F6F6');
            $table->string('text_color', 10)->default('#1B1B1B');
            $table->string('header_bg', 10)->default('#D71920');
            $table->string('header_text', 10)->default('#FFFFFF');
            $table->string('footer_bg', 10)->default('#D71920');
            $table->string('footer_text', 10)->default('#FFFFFF');
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_urdu_name')->default(true);
            $table->boolean('show_customer_box')->default(true);
            $table->boolean('show_vehicle')->default(true);
            $table->boolean('show_amount_in_words')->default(true);
            $table->boolean('show_signatures')->default(true);
            $table->boolean('show_qr_code')->default(true);
            $table->boolean('show_udhaar_balance')->default(true);
            $table->boolean('show_fbr_details')->default(true);
            $table->text('custom_css')->nullable();
            $table->json('config')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('invoice_templates')->nullOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->dateTime('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('type', 30)->default('sale'); // sale, credit_bill, bulk, manual
            $table->string('status', 30)->default('issued'); // draft, issued, paid, partially_paid, void, cancelled
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('pos_fee', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->decimal('balance_due', 14, 2)->default(0.00);
            $table->decimal('previous_balance', 14, 2)->default(0.00);
            $table->decimal('closing_balance', 14, 2)->default(0.00);
            $table->string('payment_method', 50)->nullable(); // cash, credit, card, bank_transfer, split
            $table->json('payment_details')->nullable();
            $table->string('currency', 10)->default('PKR');
            $table->text('amount_in_words_ur')->nullable();
            $table->text('amount_in_words_en')->nullable();
            $table->string('hash', 64)->unique();
            $table->json('qr_payload')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('invoice_date');
            $table->index('status');
            $table->index('hash');
        });

        // 4. Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('fuel_product_id')->nullable()->constrained('fuel_products')->nullOnDelete();
            $table->foreignId('nozzle_id')->nullable()->constrained('nozzles')->nullOnDelete();
            $table->string('item_description', 255);
            $table->string('item_type', 30)->default('fuel');
            $table->decimal('quantity', 12, 3); // litres / units
            $table->decimal('unit_price', 14, 2); // rate
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('discount', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2);
            $table->decimal('meter_start', 12, 3)->nullable();
            $table->decimal('meter_end', 12, 3)->nullable();
            $table->timestamps();
        });

        // 5. Invoice Snapshots (Immutable rule)
        Schema::create('invoice_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained('invoices')->cascadeOnDelete();
            $table->json('station_snapshot'); // Station name (en/ur), OMC, Owner, Phone, WhatsApp, Address
            $table->json('customer_snapshot')->nullable(); // Customer details, vehicle, credit limit
            $table->json('items_snapshot'); // Frozen items, litres, rates, amounts
            $table->json('payment_snapshot'); // Paid amount, previous balance, closing balance
            $table->json('theme_snapshot'); // Colors, header/footer styles, toggles
            $table->json('raw_snapshot')->nullable(); // Consolidated immutable payload
            $table->string('snapshot_hash', 64); // SHA-256 integrity hash
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_snapshots');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_templates');
    }
};
