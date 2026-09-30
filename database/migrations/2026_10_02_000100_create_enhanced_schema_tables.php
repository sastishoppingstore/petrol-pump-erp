<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V4 SCHEMA EXPANSIONS: Nozzle tests, tank dip, tanker wizard, payroll, banking
     * Non-breaking: Only adds new tables, no alterations to existing ones
     */
    public function up(): void
    {
        // ===== TANK DIP & WATER TESTS =====
        if (! Schema::hasTable('tank_dip_charts')) {
            Schema::create('tank_dip_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
            $table->integer('centimeters');
            $table->decimal('litres', 12, 3);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tank_id', 'centimeters']);
        });
            }

        if (! Schema::hasTable('tank_dip_readings')) {
            Schema::create('tank_dip_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('reading_date');
            $table->integer('dip_centimeters');
            $table->decimal('calculated_litres', 12, 3);
            $table->decimal('temperature_celsius', 5, 2)->nullable();
            $table->decimal('temperature_adjustment_litres', 12, 3)->default(0);
            $table->decimal('final_stock_litres', 12, 3);
            $table->enum('status', ['RECORDED', 'VERIFIED', 'DISPUTED'])->default('RECORDED');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('water_test_logs')) {
            Schema::create('water_test_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('test_date');
            $table->enum('water_status', ['CLEAR', 'SLIGHT_DISCOLORATION', 'CONTAMINATED'])->default('CLEAR');
            $table->decimal('water_percentage', 5, 2)->nullable();
            $table->text('action_taken')->nullable();
            $table->timestamps();
        });
            }

        // ===== NOZZLE TESTS & CALIBRATION =====
        if (! Schema::hasTable('nozzle_test_returns')) {
            Schema::create('nozzle_test_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nozzle_id')->constrained('nozzles')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->decimal('test_litres', 12, 3);
            $table->decimal('meter_before', 14, 3);
            $table->decimal('meter_after', 14, 3);
            $table->text('reason');
            $table->enum('status', ['RECORDED', 'APPROVED', 'REVERSED'])->default('RECORDED');
            $table->timestamps();
        });
            }

        // ===== TANKER UNLOADING & SUPPLIER SHORTAGE =====
        if (! Schema::hasTable('tankers')) {
            Schema::create('tankers', function (Blueprint $table) {
            $table->id();
            $table->string('tanker_number', 30)->unique();
            $table->string('driver_name', 100);
            $table->string('driver_phone', 20)->nullable();
            $table->string('supplier_id')->nullable();
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('fuel_purchases')) {
            Schema::create('fuel_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
            $table->foreignId('tanker_id')->constrained('tankers')->cascadeOnDelete();
            $table->string('supplier_challan_number', 50);
            $table->decimal('challan_litres', 12, 3);
            $table->decimal('dip_before_litres', 12, 3);
            $table->decimal('dip_after_litres', 12, 3);
            $table->decimal('actual_received_litres', 12, 3);
            $table->decimal('temperature_celsius', 5, 2)->nullable();
            $table->decimal('evaporation_loss_litres', 12, 3)->default(0);
            $table->decimal('shortage_litres', 12, 3)->default(0);
            $table->decimal('rate_per_litre', 10, 2);
            $table->decimal('total_amount', 14, 2);
            $table->enum('status', ['DRAFT', 'UNLOADING', 'VERIFIED', 'INVOICED'])->default('DRAFT');
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('supplier_shortage_claims')) {
            Schema::create('supplier_shortage_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_purchase_id')->constrained('fuel_purchases')->cascadeOnDelete();
            $table->decimal('shortage_litres', 12, 3);
            $table->decimal('shortage_amount', 14, 2);
            $table->enum('claim_status', ['PENDING', 'APPROVED', 'REJECTED', 'SETTLED'])->default('PENDING');
            $table->date('claimed_date');
            $table->date('settled_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
            }

        // ===== PAYROLL & STAFF MANAGEMENT =====
        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_code', 30)->unique();
            $table->string('name_en', 100);
            $table->string('name_ur', 100);
            $table->string('cnic', 15)->unique();
            $table->string('phone', 20);
            $table->string('position', 50);
            $table->decimal('base_salary', 14, 2);
            $table->enum('shift_type', ['MORNING', 'EVENING', 'NIGHT', 'MIXED'])->default('MORNING');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->enum('status', ['ACTIVE', 'LEAVE', 'TERMINATED'])->default('ACTIVE');
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['PRESENT', 'ABSENT', 'HALF_DAY', 'LEAVE'])->default('ABSENT');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->decimal('working_hours', 4, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'attendance_date']);
        });
            }

        if (! Schema::hasTable('staff_advances')) {
            Schema::create('staff_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('advance_date');
            $table->date('settlement_date')->nullable();
            $table->enum('status', ['PENDING', 'SETTLED', 'CANCELLED'])->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('staff_shortage_deductions')) {
            Schema::create('staff_shortage_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->decimal('shortage_amount', 14, 2);
            $table->date('deduction_date');
            $table->text('reason');
            $table->enum('status', ['DEDUCTED', 'WAIVED', 'APPEALED'])->default('DEDUCTED');
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('salary_sheets')) {
            Schema::create('salary_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->year('salary_year');
            $table->unsignedTinyInteger('salary_month');
            $table->decimal('base_salary', 14, 2);
            $table->decimal('overtime_amount', 14, 2)->default(0);
            $table->decimal('advance_deduction', 14, 2)->default(0);
            $table->decimal('shortage_deduction', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2);
            $table->enum('payment_status', ['PENDING', 'PAID', 'CANCELLED'])->default('PENDING');
            $table->date('payment_date')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'salary_year', 'salary_month']);
        });
            }

        // ===== INTERNAL FUEL CONSUMPTION =====
        if (! Schema::hasTable('internal_fuel_consumptions')) {
            Schema::create('internal_fuel_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
            $table->enum('consumption_type', ['GENERATOR', 'STATION_VEHICLE', 'TESTING', 'CLEANING'])->default('GENERATOR');
            $table->decimal('litres_consumed', 12, 3);
            $table->date('consumption_date');
            $table->text('description');
            $table->timestamps();
        });
            }

        // ===== INVOICING & BILL DESIGNER =====
        if (! Schema::hasTable('invoice_templates')) {
            Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->enum('layout_type', ['MODERN_RED_BAND', 'CLASSIC', 'MINIMAL'])->unique();
            $table->enum('paper_size', ['A4', 'A5', 'THERMAL_80MM', 'THERMAL_58MM']);
            $table->json('design_config')->nullable();
            $table->boolean('show_tax_lines')->default(true);
            $table->boolean('show_fbr_qr')->default(false);
            $table->boolean('show_petroleum_levy')->default(false);
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('invoice_snapshots')) {
            Schema::create('invoice_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('sales')->cascadeOnDelete();
            $table->json('invoice_data');
            $table->json('design_snapshot');
            $table->timestamps();
        });
            }

        // ===== DIGITAL SIGNATURES =====
        if (! Schema::hasTable('digital_signatures')) {
            Schema::create('digital_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->longText('signature_svg');
            $table->timestamp('signed_at');
            $table->string('signer_name', 100);
            $table->string('signer_phone', 20)->nullable();
            $table->timestamps();
        });
            }

        // ===== BANKING MODULE =====
        if (! Schema::hasTable('banks')) {
            Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code', 10)->unique();
            $table->string('bank_name', 100);
            $table->string('bank_name_ur', 100);
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks')->cascadeOnDelete();
            $table->string('account_number', 30);
            $table->string('account_title', 100);
            $table->enum('account_type', ['CURRENT', 'SAVINGS', 'DEPOSIT'])->default('CURRENT');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'CLOSED'])->default('ACTIVE');
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('cheques')) {
            Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('cheque_number', 20);
            $table->string('issued_to', 100);
            $table->decimal('amount', 14, 2);
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['ISSUED', 'PRESENTED', 'CLEARED', 'BOUNCED', 'CANCELLED'])->default('ISSUED');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['bank_account_id', 'cheque_number']);
        });
            }

        if (! Schema::hasTable('card_wallet_settlements')) {
            Schema::create('card_wallet_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 50)->unique();
            $table->enum('payment_method', ['DEBIT_CARD', 'CREDIT_CARD', 'JAZZ_CASH', 'EASYPAISA', 'BANK_TRANSFER'])->default('DEBIT_CARD');
            $table->decimal('amount', 14, 2);
            $table->date('settlement_date');
            $table->enum('settlement_status', ['PENDING', 'SETTLED', 'FAILED', 'REVERSED'])->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
            }

        // ===== CHART OF ACCOUNTS =====
        if (! Schema::hasTable('chart_of_accounts')) {
            Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 20)->unique();
            $table->string('account_name', 100);
            $table->enum('account_type', ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE', 'CONTRA'])->default('ASSET');
            $table->enum('account_classification', ['BANK', 'CASH', 'INVENTORY', 'RECEIVABLE', 'PAYABLE', 'EQUITY', 'REVENUE', 'COGS', 'OPERATING_EXPENSE', 'OTHER'])->default('CASH');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_reference', 50)->unique();
            $table->date('entry_date');
            $table->enum('entry_type', ['SALE', 'PURCHASE', 'PAYMENT', 'EXPENSE', 'ADJUSTMENT', 'OPENING', 'CLOSING'])->default('SALE');
            $table->string('reference_id', 50)->nullable();
            $table->decimal('total_debit', 14, 2)->default(0);
            $table->decimal('total_credit', 14, 2)->default(0);
            $table->text('description')->nullable();
            $table->enum('status', ['DRAFT', 'POSTED', 'REVERSED'])->default('POSTED');
            $table->timestamps();
        });
            }

        if (! Schema::hasTable('journal_lines')) {
            Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->decimal('debit_amount', 14, 2)->default(0);
            $table->decimal('credit_amount', 14, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
            }

        // ===== AUDIT & NOTIFICATIONS =====
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('module', 50);
            $table->string('reference_type', 50);
            $table->string('reference_id', 50);
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
            }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title', 255);
            $table->text('message');
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id', 50)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
            }

        // ===== VOICE & AUDIO GUIDES =====
        if (! Schema::hasTable('voice_clips')) {
            Schema::create('voice_clips', function (Blueprint $table) {
            $table->id();
            $table->string('clip_code', 50)->unique();
            $table->string('clip_name', 100);
            $table->enum('language', ['UR', 'EN'])->default('UR');
            $table->string('file_path', 255);
            $table->enum('context', ['SALE', 'PAYMENT', 'METER_READING', 'CASH_TALLY', 'SHIFT_CLOSE'])->default('SALE');
            $table->timestamps();
        });
            }

        // ===== USER UI PREFERENCES =====
        if (! Schema::hasTable('user_ui_preferences')) {
            Schema::create('user_ui_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('picture_first_mode')->default(true);
            $table->boolean('audio_cues_enabled')->default(true);
            $table->enum('default_language', ['UR', 'EN'])->default('UR');
            $table->boolean('reduced_motion')->default(false);
            $table->enum('theme', ['LIGHT', 'DARK'])->default('LIGHT');
            $table->timestamps();
            $table->unique('user_id');
        });
            }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ui_preferences');
        Schema::dropIfExists('voice_clips');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('card_wallet_settlements');
        Schema::dropIfExists('cheques');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('banks');
        Schema::dropIfExists('digital_signatures');
        Schema::dropIfExists('invoice_snapshots');
        Schema::dropIfExists('invoice_templates');
        Schema::dropIfExists('internal_fuel_consumptions');
        Schema::dropIfExists('salary_sheets');
        Schema::dropIfExists('staff_shortage_deductions');
        Schema::dropIfExists('staff_advances');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('supplier_shortage_claims');
        Schema::dropIfExists('fuel_purchases');
        Schema::dropIfExists('tankers');
        Schema::dropIfExists('nozzle_test_returns');
        Schema::dropIfExists('water_test_logs');
        Schema::dropIfExists('tank_dip_readings');
        Schema::dropIfExists('tank_dip_charts');
    }
};
