<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * The sidebar definition (spec section 1, Phase 1).
 *
 * Modules are declared up front but only rendered when the signed-in user
 * actually holds the permission, so a cashier never sees Reports or
 * Settings links. The `permission` on each item is also enforced by route
 * middleware — this class controls visibility only.
 */
class SidebarNav
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function itemsFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $items = array_merge(
            self::mainGroup(),
            self::fuelGroup(),
            self::operationsGroup(),
            self::accountsGroup(),
            self::reportsGroup(),
            self::settingsGroup(),
        );

        // Only render links whose route is actually registered. The full menu
        // is declared up front and appears automatically as each later phase
        // registers its routes, so the sidebar never links to a dead page.
        return array_values(array_filter(
            $items,
            fn (array $item) => $item['type'] === 'section' || Route::has($item['route'])
        ));
    }

    private static function link(string $route, string $label, string $icon, ?string $permission, ?string $match = null): array
    {
        return [
            'type' => 'link',
            'route' => $route,
            'match' => $match ?? $route,
            'label' => $label,
            'icon' => $icon,
            'permission' => $permission,
        ];
    }

    private static function section(string $label): array
    {
        return ['type' => 'section', 'label' => $label];
    }

    private static function mainGroup(): array
    {
        return [
            self::link('dashboard', 'Dashboard', '📊', null, 'dashboard'),
        ];
    }

    private static function fuelGroup(): array
    {
        return [
            self::section('Fuel'),
            self::link('fuels.index', 'Fuel Products', '🛢️', PermissionList::FUEL_VIEW, 'fuels.*'),
            self::link('fuel-prices.index', 'Fuel Prices', '💰', PermissionList::FUEL_VIEW, 'fuel-prices.*'),
            self::link('forecourt.meters.index', 'Forecourt Meters', '⚡', PermissionList::FUEL_VIEW, 'forecourt.meters.*'),
            self::link('tanks.index', 'Tanks', '🛞', PermissionList::STOCK_VIEW, 'tanks.*'),
            self::link('tank-readings.index', 'Tank Readings', '📐', PermissionList::STOCK_VIEW, 'tank-readings.*'),
            self::link('dispensers.index', 'Dispensers', '⛽', PermissionList::FUEL_VIEW, 'dispensers.*'),
            self::link('nozzles.index', 'Nozzles', '🔧', PermissionList::FUEL_VIEW, 'nozzles.*'),
        ];
    }

    private static function operationsGroup(): array
    {
        return [
            self::section('Operations'),
            self::link('pos.index', 'POS', '🧾', PermissionList::SALES_CREATE, 'pos.*'),
            self::link('invoices.index', 'Invoices', '📄', PermissionList::SALES_VIEW, 'invoices.*'),
            self::link('sales.index', 'Sales History', '🧮', PermissionList::SALES_VIEW, 'sales.*'),
            self::link('shifts.index', 'Shifts', '🕐', PermissionList::SHIFT_VIEW, 'shifts.*'),
            self::link('cash.index', 'Roznamcha (Cash Book)', '📖', PermissionList::CASH_VIEW, 'cash.*'),
            self::link('purchases.index', 'Purchases', '📥', PermissionList::PURCHASE_VIEW, 'purchases.*'),
            self::link('expenses.index', 'Expenses', '💸', PermissionList::EXPENSE_VIEW, 'expenses.*'),
            self::link('employees.index', 'Employees', '👷', PermissionList::EMPLOYEE_VIEW, 'employees.*'),
        ];
    }

    private static function accountsGroup(): array
    {
        return [
            self::section('Accounts'),
            self::link('customers.index', 'Customers', '🧑', PermissionList::CUSTOMER_VIEW, 'customers.*'),
            self::link('suppliers.index', 'Suppliers', '🏭', PermissionList::SUPPLIER_VIEW, 'suppliers.*'),
            self::link('banks.index', 'Banks', '🏦', PermissionList::CASH_VIEW, 'banks.*'),
            self::link('cash-deposits', 'Bank Deposits', '💵', PermissionList::CASH_VIEW, 'bank-deposits*'),
            self::link('closing.index', 'Daily Closing', '🔒', PermissionList::CLOSING_VIEW, 'closing.*'),
            self::link('journals.index', 'Journals', '📚', PermissionList::JOURNAL_VIEW, 'journals.*'),
        ];
    }

    private static function reportsGroup(): array
    {
        return [
            self::section('Reports'),
            self::link('reports.sales', 'Sales Reports', '📈', PermissionList::REPORTS_VIEW, 'reports.sales*'),
            self::link('reports.stock', 'Stock Reports', '📦', PermissionList::REPORTS_VIEW, 'reports.stock*'),
            self::link('reports.financial', 'Financial Reports', '💹', PermissionList::REPORTS_VIEW, 'reports.financial*'),
        ];
    }

    private static function settingsGroup(): array
    {
        return [
            self::section('Settings'),
            self::link('branches.index', 'Branches', '🏢', PermissionList::BRANCH_VIEW, 'branches.*'),
            self::link('users.index', 'Users', '👤', PermissionList::USER_VIEW, 'users.*'),
            self::link('roles.index', 'Roles', '🔑', PermissionList::ROLE_VIEW, 'roles.*'),
            self::link('permissions.index', 'Permissions', '✅', PermissionList::PERMISSION_VIEW, 'permissions.*'),
            self::link('notifications.index', 'Notifications', '🔔', null, 'notifications.*'),
            self::link('audit-logs.index', 'Audit Logs', '🕵️', PermissionList::AUDIT_VIEW, 'audit-logs.*'),
            self::link('settings.index', 'Settings', '⚙️', PermissionList::SETTINGS_VIEW, 'settings.*'),
            self::link('settings.bill-designer', 'Bill Designer', '🎨', PermissionList::SETTINGS_VIEW, 'settings.bill-designer'),
            self::link('backups.index', 'Backup / Restore', '💾', PermissionList::BACKUP_VIEW, 'backups.*'),
        ];
    }
}
