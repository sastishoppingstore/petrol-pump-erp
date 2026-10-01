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
            self::link('dashboard', __('ui.nav.dashboard'), '📊', null, 'dashboard'),
        ];
    }

    private static function fuelGroup(): array
    {
        return [
            self::section(__('ui.nav.section_fuel')),
            self::link('fuels.index', __('ui.nav.fuel_products'), '🛢️', PermissionList::FUEL_VIEW, 'fuels.*'),
            self::link('fuel-prices.index', __('ui.nav.fuel_prices'), '💰', PermissionList::FUEL_VIEW, 'fuel-prices.*'),
            self::link('forecourt.meters.index', __('ui.nav.forecourt_meters'), '⚡', PermissionList::FUEL_VIEW, 'forecourt.meters.*'),
            self::link('tanks.index', __('ui.nav.tanks'), '🛞', PermissionList::STOCK_VIEW, 'tanks.*'),
            self::link('tank-readings.index', __('ui.nav.tank_readings'), '📐', PermissionList::STOCK_VIEW, 'tank-readings.*'),
            self::link('dispensers.index', __('ui.nav.dispensers'), '⛽', PermissionList::FUEL_VIEW, 'dispensers.*'),
            self::link('nozzles.index', __('ui.nav.nozzles'), '🔧', PermissionList::FUEL_VIEW, 'nozzles.*'),
        ];
    }

    private static function operationsGroup(): array
    {
        return [
            self::section(__('ui.nav.section_operations')),
            self::link('pos.index', __('ui.nav.pos'), '🧾', PermissionList::SALES_CREATE, 'pos.*'),
            self::link('invoices.index', __('ui.nav.invoices'), '📄', PermissionList::SALES_VIEW, 'invoices.*'),
            self::link('sales.index', __('ui.nav.sales_history'), '🧮', PermissionList::SALES_VIEW, 'sales.*'),
            self::link('shifts.index', __('ui.nav.shifts'), '🕐', PermissionList::SHIFT_VIEW, 'shifts.*'),
            self::link('cash.index', __('ui.nav.roznamcha'), '📖', PermissionList::CASH_VIEW, 'cash.*'),
            self::link('purchases.index', __('ui.nav.purchases'), '📥', PermissionList::PURCHASE_VIEW, 'purchases.*'),
            self::link('expenses.index', __('ui.nav.expenses'), '💸', PermissionList::EXPENSE_VIEW, 'expenses.*'),
            self::link('employees.index', __('ui.nav.employees'), '👷', PermissionList::EMPLOYEE_VIEW, 'employees.*'),
        ];
    }

    private static function accountsGroup(): array
    {
        return [
            self::section(__('ui.nav.section_accounts')),
            self::link('customers.index', __('ui.nav.customers'), '🧑', PermissionList::CUSTOMER_VIEW, 'customers.*'),
            self::link('suppliers.index', __('ui.nav.suppliers'), '🏭', PermissionList::SUPPLIER_VIEW, 'suppliers.*'),
            self::link('banks.index', __('ui.nav.banks'), '🏦', PermissionList::CASH_VIEW, 'banks.*'),
            self::link('cash-deposits', __('ui.nav.bank_deposits'), '💵', PermissionList::CASH_VIEW, 'bank-deposits*'),
            self::link('closing.index', __('ui.nav.daily_closing'), '🔒', PermissionList::CLOSING_VIEW, 'closing.*'),
            self::link('journals.index', __('ui.nav.journals'), '📚', PermissionList::JOURNAL_VIEW, 'journals.*'),
        ];
    }

    private static function reportsGroup(): array
    {
        return [
            self::section(__('ui.nav.section_reports')),
            self::link('reports.sales', __('ui.nav.sales_reports'), '📈', PermissionList::REPORTS_VIEW, 'reports.sales*'),
            self::link('reports.stock', __('ui.nav.stock_reports'), '📦', PermissionList::REPORTS_VIEW, 'reports.stock*'),
            self::link('reports.financial', __('ui.nav.financial_reports'), '💹', PermissionList::REPORTS_VIEW, 'reports.financial*'),
        ];
    }

    private static function settingsGroup(): array
    {
        return [
            self::section(__('ui.nav.section_settings')),
            self::link('branches.index', __('ui.nav.branches'), '🏢', PermissionList::BRANCH_VIEW, 'branches.*'),
            self::link('users.index', __('ui.nav.users'), '👤', PermissionList::USER_VIEW, 'users.*'),
            self::link('roles.index', __('ui.nav.roles'), '🔑', PermissionList::ROLE_VIEW, 'roles.*'),
            self::link('permissions.index', __('ui.nav.permissions'), '✅', PermissionList::PERMISSION_VIEW, 'permissions.*'),
            self::link('notifications.index', __('ui.nav.notifications'), '🔔', null, 'notifications.*'),
            self::link('audit-logs.index', __('ui.nav.audit_logs'), '🕵️', PermissionList::AUDIT_VIEW, 'audit-logs.*'),
            self::link('admin.settings.index', __('ui.nav.system_settings'), '⚙️', 'admin', 'admin.settings.*'),
            self::link('settings.index', __('ui.nav.settings'), '⚙️', PermissionList::SETTINGS_VIEW, 'settings.*'),
            self::link('settings.bill-designer', __('ui.nav.bill_designer'), '🎨', PermissionList::SETTINGS_VIEW, 'settings.bill-designer'),
            self::link('backups.index', __('ui.nav.backup_restore'), '💾', PermissionList::BACKUP_VIEW, 'backups.*'),
        ];
    }
}
