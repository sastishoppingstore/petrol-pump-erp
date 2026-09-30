<?php

namespace App\Support;

use App\Models\Role;

/**
 * Single source of truth for every permission in the system.
 *
 * The seeder, the Gate definitions, the role matrix UI and the route
 * middleware all read from here, so a permission can never exist in one
 * place and be missing from another.
 *
 * Format: "module.action"
 */
class PermissionList
{
    // --- Sales / POS ---
    public const SALES_VIEW = 'sales.view';
    public const SALES_CREATE = 'sales.create';
    public const SALES_EDIT = 'sales.edit';
    public const SALES_DELETE = 'sales.delete';
    public const SALES_VOID = 'sales.void';
    public const SALES_REFUND = 'sales.refund';
    public const SALES_PRINT = 'sales.print';
    public const SALES_EXPORT = 'sales.export';
    public const SALES_APPROVE = 'sales.approve';

    // --- Fuel master ---
    public const FUEL_VIEW = 'fuel.view';
    public const FUEL_CREATE = 'fuel.create';
    public const FUEL_EDIT = 'fuel.edit';
    public const FUEL_DELETE = 'fuel.delete';
    public const FUEL_PRICE_CHANGE = 'fuel.price_change';

    // --- Stock ---
    public const STOCK_VIEW = 'stock.view';
    public const STOCK_ADJUSTMENT = 'stock.stock_adjustment';
    public const STOCK_APPROVE = 'stock.approve';
    public const STOCK_EXPORT = 'stock.export';

    // --- Shifts ---
    public const SHIFT_VIEW = 'shift.view';
    public const SHIFT_CREATE = 'shift.create';
    public const SHIFT_CLOSE = 'shift.close';
    public const SHIFT_PRINT = 'shift.print';

    // --- Purchase ---
    public const PURCHASE_VIEW = 'purchase.view';
    public const PURCHASE_CREATE = 'purchase.create';
    public const PURCHASE_EDIT = 'purchase.edit';
    public const PURCHASE_APPROVE = 'purchase.approve';
    public const PURCHASE_PRINT = 'purchase.print';
    public const PURCHASE_EXPORT = 'purchase.export';

    // --- Customers ---
    public const CUSTOMER_VIEW = 'customer.view';
    public const CUSTOMER_CREATE = 'customer.create';
    public const CUSTOMER_EDIT = 'customer.edit';
    public const CUSTOMER_PAYMENT = 'customer.payment';
    public const CUSTOMER_PRINT = 'customer.print';
    public const CUSTOMER_EXPORT = 'customer.export';

    // --- Suppliers ---
    public const SUPPLIER_VIEW = 'supplier.view';
    public const SUPPLIER_CREATE = 'supplier.create';
    public const SUPPLIER_EDIT = 'supplier.edit';
    public const SUPPLIER_PAYMENT = 'supplier.payment';
    public const SUPPLIER_PRINT = 'supplier.print';
    public const SUPPLIER_EXPORT = 'supplier.export';

    // --- Expenses ---
    public const EXPENSE_VIEW = 'expense.view';
    public const EXPENSE_CREATE = 'expense.create';
    public const EXPENSE_EDIT = 'expense.edit';
    public const EXPENSE_DELETE = 'expense.delete';
    public const EXPENSE_APPROVE = 'expense.approve';
    public const EXPENSE_EXPORT = 'expense.export';

    // --- Employees / HR ---
    public const EMPLOYEE_VIEW = 'employee.view';
    public const EMPLOYEE_CREATE = 'employee.create';
    public const EMPLOYEE_EDIT = 'employee.edit';
    public const EMPLOYEE_DELETE = 'employee.delete';

    // --- Cash ---
    public const CASH_VIEW = 'cash.view';
    public const CASH_CREATE = 'cash.create';
    public const CASH_APPROVE = 'cash.approve';

    // --- Cheques ---
    public const CHEQUE_VIEW = 'cheque.view';
    public const CHEQUE_CREATE = 'cheque.create';
    public const CHEQUE_ACTION = 'cheque.action';

    // --- Payroll ---
    public const PAYROLL_VIEW = 'payroll.view';
    public const PAYROLL_CREATE = 'payroll.create';

    // --- Approvals ---
    public const APPROVAL_VIEW = 'approval.view';
    public const APPROVAL_ACTION = 'approval.action';

    // --- Accounting ---
    public const ACCOUNT_VIEW = 'account.view';
    public const ACCOUNT_CREATE = 'account.create';
    public const ACCOUNT_EDIT = 'account.edit';
    public const JOURNAL_VIEW = 'journal.view';
    public const JOURNAL_CREATE = 'journal.create';

    // --- Reports ---
    public const REPORTS_VIEW = 'reports.view';
    public const REPORTS_PRINT = 'reports.print';
    public const REPORTS_EXPORT = 'reports.export';

    // --- Daily closing ---
    public const CLOSING_VIEW = 'closing.view';
    public const CLOSING_CREATE = 'closing.create';
    public const CLOSING_APPROVE = 'closing.approve';
    public const CLOSING_PRINT = 'closing.print';

    // --- Settings / administration ---
    public const SETTINGS_VIEW = 'settings.view';
    public const SETTINGS_EDIT = 'settings.edit';
    public const BRANCH_VIEW = 'branch.view';
    public const BRANCH_CREATE = 'branch.create';
    public const BRANCH_EDIT = 'branch.edit';
    public const BRANCH_DELETE = 'branch.delete';
    public const USER_VIEW = 'user.view';
    public const USER_CREATE = 'user.create';
    public const USER_EDIT = 'user.edit';
    public const USER_DELETE = 'user.delete';
    public const ROLE_VIEW = 'role.view';
    public const ROLE_CREATE = 'role.create';
    public const ROLE_EDIT = 'role.edit';
    public const ROLE_DELETE = 'role.delete';
    public const PERMISSION_VIEW = 'permission.view';
    public const PERMISSION_EDIT = 'permission.edit';
    public const AUDIT_VIEW = 'audit.view';
    public const AUDIT_EXPORT = 'audit.export';
    public const BACKUP_VIEW = 'backup.view';
    public const BACKUP_CREATE = 'backup.create';
    public const BACKUP_RESTORE = 'backup.restore';
    public const NOTIFICATION_VIEW = 'notification.view';

    /**
     * Every permission the system knows about, grouped by module.
     * Used by the seeder and by the role matrix screen.
     *
     * @return array<string, array<string, string>> module => [permissionName => label]
     */
    public static function allGrouped(): array
    {
        return [
            'Sales' => [
                self::SALES_VIEW => 'View sales',
                self::SALES_CREATE => 'Create sale',
                self::SALES_EDIT => 'Edit sale',
                self::SALES_DELETE => 'Delete sale',
                self::SALES_VOID => 'Void sale',
                self::SALES_REFUND => 'Refund sale',
                self::SALES_PRINT => 'Print receipt',
                self::SALES_EXPORT => 'Export sales',
                self::SALES_APPROVE => 'Approve sale',
            ],
            'Fuel' => [
                self::FUEL_VIEW => 'View fuel master',
                self::FUEL_CREATE => 'Add fuel product',
                self::FUEL_EDIT => 'Edit fuel product',
                self::FUEL_DELETE => 'Deactivate fuel product',
                self::FUEL_PRICE_CHANGE => 'Change fuel price',
            ],
            'Stock' => [
                self::STOCK_VIEW => 'View stock',
                self::STOCK_ADJUSTMENT => 'Adjust stock',
                self::STOCK_APPROVE => 'Approve stock adjustment',
                self::STOCK_EXPORT => 'Export stock',
            ],
            'Shifts' => [
                self::SHIFT_VIEW => 'View shifts',
                self::SHIFT_CREATE => 'Open shift',
                self::SHIFT_CLOSE => 'Close shift',
                self::SHIFT_PRINT => 'Print shift report',
            ],
            'Purchase' => [
                self::PURCHASE_VIEW => 'View purchases',
                self::PURCHASE_CREATE => 'Create purchase',
                self::PURCHASE_EDIT => 'Edit purchase draft',
                self::PURCHASE_APPROVE => 'Approve purchase',
                self::PURCHASE_PRINT => 'Print purchase invoice',
                self::PURCHASE_EXPORT => 'Export purchases',
            ],
            'Customers' => [
                self::CUSTOMER_VIEW => 'View customers',
                self::CUSTOMER_CREATE => 'Add customer',
                self::CUSTOMER_EDIT => 'Edit customer',
                self::CUSTOMER_PAYMENT => 'Receive customer payment',
                self::CUSTOMER_PRINT => 'Print statement',
                self::CUSTOMER_EXPORT => 'Export customers',
            ],
            'Suppliers' => [
                self::SUPPLIER_VIEW => 'View suppliers',
                self::SUPPLIER_CREATE => 'Add supplier',
                self::SUPPLIER_EDIT => 'Edit supplier',
                self::SUPPLIER_PAYMENT => 'Pay supplier',
                self::SUPPLIER_PRINT => 'Print supplier ledger',
                self::SUPPLIER_EXPORT => 'Export suppliers',
            ],
            'Expenses' => [
                self::EXPENSE_VIEW => 'View expenses',
                self::EXPENSE_CREATE => 'Add expense',
                self::EXPENSE_EDIT => 'Edit expense',
                self::EXPENSE_DELETE => 'Delete expense',
                self::EXPENSE_APPROVE => 'Approve expense',
                self::EXPENSE_EXPORT => 'Export expenses',
            ],
            'Employees' => [
                self::EMPLOYEE_VIEW => 'View employees',
                self::EMPLOYEE_CREATE => 'Add employee',
                self::EMPLOYEE_EDIT => 'Edit employee',
                self::EMPLOYEE_DELETE => 'Delete employee',
            ],
            'Cash' => [
                self::CASH_VIEW => 'View cash',
                self::CASH_CREATE => 'Record cash movement',
                self::CASH_APPROVE => 'Approve cash variance',
            ],
            'Cheques' => [
                self::CHEQUE_VIEW => 'View cheques',
                self::CHEQUE_CREATE => 'Record cheque',
                self::CHEQUE_ACTION => 'Deposit / clear / bounce cheque',
            ],
            'Payroll' => [
                self::PAYROLL_VIEW => 'View payroll & attendance',
                self::PAYROLL_CREATE => 'Manage payroll & salary sheets',
            ],
            'Approvals' => [
                self::APPROVAL_VIEW => 'View approval requests',
                self::APPROVAL_ACTION => 'Approve or reject requests',
            ],
            'Accounting' => [
                self::ACCOUNT_VIEW => 'View accounts',
                self::ACCOUNT_CREATE => 'Add account',
                self::ACCOUNT_EDIT => 'Edit account',
                self::JOURNAL_VIEW => 'View journals',
                self::JOURNAL_CREATE => 'Create journal',
            ],
            'Reports' => [
                self::REPORTS_VIEW => 'View reports',
                self::REPORTS_PRINT => 'Print reports',
                self::REPORTS_EXPORT => 'Export reports',
            ],
            'Daily Closing' => [
                self::CLOSING_VIEW => 'View daily closing',
                self::CLOSING_CREATE => 'Finalise daily closing',
                self::CLOSING_APPROVE => 'Approve / unlock day',
                self::CLOSING_PRINT => 'Print daily closing',
            ],
            'Users' => [
                self::USER_VIEW => 'View users',
                self::USER_CREATE => 'Add user',
                self::USER_EDIT => 'Edit user',
                self::USER_DELETE => 'Delete user',
            ],
            'Roles' => [
                self::ROLE_VIEW => 'View roles',
                self::ROLE_CREATE => 'Add role',
                self::ROLE_EDIT => 'Edit role',
                self::ROLE_DELETE => 'Delete role',
            ],
            'Permissions' => [
                self::PERMISSION_VIEW => 'View permissions',
                self::PERMISSION_EDIT => 'Edit permissions',
            ],
            'Branches' => [
                self::BRANCH_VIEW => 'View branches',
                self::BRANCH_CREATE => 'Add branch',
                self::BRANCH_EDIT => 'Edit branch',
                self::BRANCH_DELETE => 'Delete branch',
            ],
            'Settings' => [
                self::SETTINGS_VIEW => 'View settings',
                self::SETTINGS_EDIT => 'Edit settings',
            ],
            'Audit' => [
                self::AUDIT_VIEW => 'View audit log',
                self::AUDIT_EXPORT => 'Export audit log',
            ],
            'Backup' => [
                self::BACKUP_VIEW => 'View backups',
                self::BACKUP_CREATE => 'Create backup',
                self::BACKUP_RESTORE => 'Restore backup',
            ],
            'Notifications' => [
                self::NOTIFICATION_VIEW => 'View notifications',
            ],
        ];
    }

    /**
     * @return array<int, string> every permission name
     */
    public static function allNames(): array
    {
        $names = [];

        foreach (self::allGrouped() as $permissions) {
            foreach (array_keys($permissions) as $name) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Default role -> permission matrix (spec section 5).
     *
     * @return array<int, string>
     */
    public static function forRole(string $roleName): array
    {
        return match ($roleName) {
            Role::ADMIN => self::allNames(),

            // Everything except settings / roles / backup restore, and scoped
            // to assigned branches. Backup *create* and *view* are allowed.
            Role::MANAGER => array_values(array_diff(
                self::allNames(),
                [
                    self::SETTINGS_EDIT,
                    self::ROLE_CREATE,
                    self::ROLE_EDIT,
                    self::ROLE_DELETE,
                    self::PERMISSION_EDIT,
                    self::BACKUP_RESTORE,
                ]
            )),

            // POS, own sales history, customer payments, own shift, print.
            Role::CASHIER => [
                self::SALES_VIEW,
                self::SALES_CREATE,
                self::SALES_VOID,
                self::SALES_PRINT,
                self::CUSTOMER_VIEW,
                self::CUSTOMER_PAYMENT,
                self::SHIFT_VIEW,
                self::SHIFT_CREATE,
                self::SHIFT_CLOSE,
                self::FUEL_VIEW,
                self::STOCK_VIEW,
                self::CASH_VIEW,
                self::NOTIFICATION_VIEW,
            ],

            // POS on own nozzles only, own shift view.
            Role::ATTENDANT => [
                self::SALES_VIEW,
                self::SALES_CREATE,
                self::SALES_PRINT,
                self::SHIFT_VIEW,
                self::SHIFT_CREATE,
                self::FUEL_VIEW,
                self::NOTIFICATION_VIEW,
            ],

            // View all, purchases, suppliers, expenses, customers, ledgers,
            // reports, export, journals. No POS.
            Role::ACCOUNTANT => [
                self::SALES_VIEW,
                self::FUEL_VIEW,
                self::STOCK_VIEW,
                self::STOCK_EXPORT,
                self::SHIFT_VIEW,
                self::PURCHASE_VIEW,
                self::PURCHASE_CREATE,
                self::PURCHASE_EDIT,
                self::PURCHASE_APPROVE,
                self::PURCHASE_PRINT,
                self::PURCHASE_EXPORT,
                self::CUSTOMER_VIEW,
                self::CUSTOMER_CREATE,
                self::CUSTOMER_EDIT,
                self::CUSTOMER_PAYMENT,
                self::CUSTOMER_PRINT,
                self::CUSTOMER_EXPORT,
                self::SUPPLIER_VIEW,
                self::SUPPLIER_CREATE,
                self::SUPPLIER_EDIT,
                self::SUPPLIER_PAYMENT,
                self::SUPPLIER_PRINT,
                self::SUPPLIER_EXPORT,
                self::EXPENSE_VIEW,
                self::EXPENSE_CREATE,
                self::EXPENSE_EDIT,
                self::EXPENSE_APPROVE,
                self::EXPENSE_EXPORT,
                self::EMPLOYEE_VIEW,
                self::CASH_VIEW,
                self::CASH_CREATE,
                self::CHEQUE_VIEW,
                self::CHEQUE_CREATE,
                self::CHEQUE_ACTION,
                self::PAYROLL_VIEW,
                self::PAYROLL_CREATE,
                self::APPROVAL_VIEW,
                self::ACCOUNT_VIEW,
                self::JOURNAL_VIEW,
                self::JOURNAL_CREATE,
                self::REPORTS_VIEW,
                self::REPORTS_PRINT,
                self::REPORTS_EXPORT,
                self::CLOSING_VIEW,
                self::CLOSING_PRINT,
                self::BRANCH_VIEW,
                self::USER_VIEW,
                self::AUDIT_VIEW,
                self::NOTIFICATION_VIEW,
            ],

            // View + print only.
            Role::VIEWER => [
                self::SALES_VIEW,
                self::SALES_PRINT,
                self::FUEL_VIEW,
                self::STOCK_VIEW,
                self::SHIFT_VIEW,
                self::PURCHASE_VIEW,
                self::PURCHASE_PRINT,
                self::CUSTOMER_VIEW,
                self::CUSTOMER_PRINT,
                self::SUPPLIER_VIEW,
                self::EXPENSE_VIEW,
                self::EMPLOYEE_VIEW,
                self::CASH_VIEW,
                self::ACCOUNT_VIEW,
                self::JOURNAL_VIEW,
                self::REPORTS_VIEW,
                self::REPORTS_PRINT,
                self::CLOSING_VIEW,
                self::CLOSING_PRINT,
                self::BRANCH_VIEW,
                self::NOTIFICATION_VIEW,
            ],

            default => [],
        };
    }

    /**
     * The six built-in roles and their display labels.
     *
     * @return array<string, array{label: string, description: string}>
     */
    public static function builtInRoles(): array
    {
        return [
            Role::ADMIN => [
                'label' => 'Administrator',
                'description' => 'Full access to every module and every branch.',
            ],
            Role::MANAGER => [
                'label' => 'Manager',
                'description' => 'All operations for assigned branches, including approvals. Cannot change settings, roles or restore backups.',
            ],
            Role::CASHIER => [
                'label' => 'Cashier',
                'description' => 'Runs the POS, takes customer payments, opens and closes own shift.',
            ],
            Role::ATTENDANT => [
                'label' => 'Attendant',
                'description' => 'Sells fuel on assigned nozzles and views own shift.',
            ],
            Role::ACCOUNTANT => [
                'label' => 'Accountant',
                'description' => 'Purchases, suppliers, expenses, ledgers, reports and journals. No POS.',
            ],
            Role::VIEWER => [
                'label' => 'Viewer',
                'description' => 'Read-only access with printing.',
            ],
        ];
    }
}
