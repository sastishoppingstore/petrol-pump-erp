<?php

/*
|--------------------------------------------------------------------------
| UI Language Lines — English
|--------------------------------------------------------------------------
|
| Site-wide chrome strings: sidebar navigation, common action buttons,
| top bar and dashboard launcher tiles. The active language is chosen by
| the admin in Settings (setting key: ui_language) and applied to every
| request by App\Http\Middleware\SetLocale.
|
| IMPORTANT: lang/en/ui.php and lang/ur/ui.php must always carry the
| exact same key set — a missing key renders the raw key name on screen.
|
*/

return [

    'nav' => [
        'dashboard' => 'Dashboard',

        'section_fuel' => 'Fuel',
        'fuel_products' => 'Fuel Products',
        'fuel_prices' => 'Fuel Prices',
        'forecourt_meters' => 'Forecourt Meters',
        'tanks' => 'Tanks',
        'tank_readings' => 'Tank Readings',
        'dispensers' => 'Dispensers',
        'nozzles' => 'Nozzles',

        'section_operations' => 'Operations',
        'pos' => 'POS',
        'invoices' => 'Invoices',
        'sales_history' => 'Sales History',
        'shifts' => 'Shifts',
        'roznamcha' => 'Roznamcha (Cash Book)',
        'purchases' => 'Purchases',
        'expenses' => 'Expenses',
        'employees' => 'Employees',

        'section_accounts' => 'Accounts',
        'customers' => 'Customers',
        'suppliers' => 'Suppliers',
        'banks' => 'Banks',
        'bank_deposits' => 'Bank Deposits',
        'daily_closing' => 'Daily Closing',
        'journals' => 'Journals',

        'section_reports' => 'Reports',
        'sales_reports' => 'Sales Reports',
        'stock_reports' => 'Stock Reports',
        'financial_reports' => 'Financial Reports',

        'section_settings' => 'Settings',
        'branches' => 'Branches',
        'users' => 'Users',
        'roles' => 'Roles',
        'permissions' => 'Permissions',
        'notifications' => 'Notifications',
        'audit_logs' => 'Audit Logs',
        'system_settings' => 'System Settings',
        'settings' => 'Settings',
        'bill_designer' => 'Bill Designer',
        'backup_restore' => 'Backup / Restore',

        'amanat_deposits' => 'Amanat Deposits',
        'document_vault' => 'Document Vault',
    ],

    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'add_new' => 'Add New',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'search' => 'Search',
        'print' => 'Print',
        'back' => 'Back',
        'view' => 'View',
        'close' => 'Close',
        'submit' => 'Submit',
        'filter' => 'Filter',
        'export' => 'Export',
    ],

    'topbar' => [
        'app_subtitle' => 'Petrol Pump ERP',
        'home' => 'Home',
        'dashboard' => 'Dashboard',
        'notifications' => 'Notifications',
        'profile' => 'Profile',
        'logout' => 'Sign out',
        'search' => 'Search...',
        'toggle_navigation' => 'Toggle navigation',
        'active_branch' => 'Active branch',
        'all_branches' => 'All branches',
        'switch' => 'Switch',
        'language' => 'Language',
    ],

    'tiles' => [
        'heading' => 'Tiles',
        'meter' => 'Meter Reading',
        'meter_desc' => 'Forecourt meters',
        'pos' => 'New Bill / Sale',
        'pos_desc' => 'Forecourt POS',
        'customers' => 'Customers / Udhaar',
        'customers_desc' => 'Credit ledgers',
        'cash_in' => 'Cash In',
        'cash_in_desc' => 'Forecourt cash received',
        'cash_out' => 'Cash Out',
        'cash_out_desc' => 'Cash drops & payouts',
        'bank' => 'Bank',
        'bank_desc' => 'Bank deposits & accounts',
        'cheques' => 'Cheques',
        'cheques_desc' => 'Cheque clearing khata',
        'purchases' => 'Fuel Purchase',
        'purchases_desc' => 'OMC tanker supply',
        'suppliers' => 'Suppliers',
        'suppliers_desc' => 'OMC Vital khata',
        'tanks' => 'Tanks & Stock',
        'tanks_desc' => 'Live fuel stock levels',
        'shifts' => 'Shift',
        'shifts_desc' => 'Shift closing & handover',
        'profit_loss' => 'Profit / Loss',
        'profit_loss_desc' => 'Gross & net forecourt P&L',
        'staff' => 'Staff / Salary',
        'staff_desc' => 'Forecourt attendants',
        'expenses' => 'Expenses',
        'expenses_desc' => 'Pump daily expenses',
        'reports' => 'Reports',
        'reports_desc' => 'Station analytics',
        'documents' => 'Documents',
        'documents_desc' => 'FBR receipts & archives',
        'notifications' => 'Notifications',
        'notifications_desc' => 'Forecourt alert centre',
        'settings' => 'Settings',
        'settings_desc' => 'System configuration',
    ],

    'settings' => [
        'site_language' => 'Site Language / سائٹ کی زبان',
        'site_language_help' => 'This language applies to the whole site for every user — sidebar, buttons and dashboard tiles.',
        'english' => 'English',
        'urdu' => 'اردو (Urdu)',
    ],

];
