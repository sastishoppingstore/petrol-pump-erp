<?php

/*
|--------------------------------------------------------------------------
| UI Language Lines — اردو
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
        'dashboard' => 'ڈیش بورڈ',

        'section_fuel' => 'ایندھن',
        'fuel_products' => 'ایندھن کی مصنوعات',
        'fuel_prices' => 'ایندھن کی قیمتیں',
        'forecourt_meters' => 'فورکورٹ میٹرز',
        'tanks' => 'ٹینک',
        'tank_readings' => 'ٹینک ریڈنگز',
        'dispensers' => 'ڈسپنسرز',
        'nozzles' => 'نوزلز',

        'section_operations' => 'آپریشنز',
        'pos' => 'پی او ایس',
        'invoices' => 'انوائسز',
        'sales_history' => 'فروخت کی تاریخ',
        'shifts' => 'شفٹیں',
        'roznamcha' => 'روزنامچہ (کیش بک)',
        'purchases' => 'خریداری',
        'expenses' => 'اخراجات',
        'employees' => 'ملازمین',

        'section_accounts' => 'کھاتے',
        'customers' => 'گاہک',
        'suppliers' => 'سپلائرز',
        'banks' => 'بینک',
        'bank_deposits' => 'بینک ڈپازٹ',
        'daily_closing' => 'روزانہ بندش',
        'journals' => 'جرنلز',

        'section_reports' => 'رپورٹس',
        'sales_reports' => 'فروخت رپورٹس',
        'stock_reports' => 'اسٹاک رپورٹس',
        'financial_reports' => 'مالیاتی رپورٹس',

        'section_settings' => 'ترتیبات',
        'branches' => 'شاخیں',
        'users' => 'صارفین',
        'roles' => 'کردار',
        'permissions' => 'اجازتیں',
        'notifications' => 'اطلاعات',
        'audit_logs' => 'آڈٹ لاگز',
        'system_settings' => 'سسٹم ترتیبات',
        'settings' => 'ترتیبات',
        'bill_designer' => 'بل ڈیزائنر',
        'backup_restore' => 'بیک اپ / بحالی',

        'amanat_deposits' => 'امانت ڈپازٹ',
        'document_vault' => 'دستاویزی والٹ',
    ],

    'actions' => [
        'save' => 'محفوظ کریں',
        'cancel' => 'منسوخ',
        'add_new' => 'نیا شامل کریں',
        'edit' => 'ترمیم',
        'delete' => 'حذف کریں',
        'search' => 'تلاش',
        'print' => 'پرنٹ',
        'back' => 'واپس',
        'view' => 'دیکھیں',
        'close' => 'بند کریں',
        'submit' => 'جمع کرائیں',
        'filter' => 'فلٹر',
        'export' => 'ایکسپورٹ',
    ],

    'topbar' => [
        'app_subtitle' => 'پیٹرول پمپ ای آر پی',
        'home' => 'ہوم',
        'dashboard' => 'ڈیش بورڈ',
        'notifications' => 'اطلاعات',
        'profile' => 'پروفائل',
        'logout' => 'لاگ آؤٹ',
        'search' => 'تلاش کریں...',
        'toggle_navigation' => 'نیویگیشن کھولیں / بند کریں',
        'active_branch' => 'فعال شاخ',
        'all_branches' => 'تمام شاخیں',
        'switch' => 'تبدیل کریں',
        'language' => 'زبان',
    ],

    'tiles' => [
        'heading' => 'شارٹ کٹس',
        'meter' => 'میٹر ریڈنگ',
        'meter_desc' => 'نوزل ریڈنگ اندراج',
        'pos' => 'نیا بل',
        'pos_desc' => 'فوری پی او ایس بلنگ',
        'customers' => 'گاہک / ادھار',
        'customers_desc' => 'کھاتہ اور کریڈٹ لمٹ',
        'cash_in' => 'رقم آئی',
        'cash_in_desc' => 'کیش موصولی اندراج',
        'cash_out' => 'رقم گئی',
        'cash_out_desc' => 'کیش ڈراپ / ادائیگی',
        'bank' => 'بینک',
        'bank_desc' => 'بینک کھاتے و ڈپازٹ',
        'cheques' => 'چیک',
        'cheques_desc' => 'کلیرنگ اور وصولی',
        'purchases' => 'تیل خریداری',
        'purchases_desc' => 'ٹینکر آمد و ڈپ پیمائش',
        'suppliers' => 'سپلائر',
        'suppliers_desc' => 'او ایم سی و دیگر کھاتے',
        'tanks' => 'ٹینک / اسٹاک',
        'tanks_desc' => 'اسٹاک لیول اور ڈپ',
        'shifts' => 'شفٹ',
        'shifts_desc' => 'شفٹ ہینڈ اوور و کلوزنگ',
        'profit_loss' => 'منافع / نقصان',
        'profit_loss_desc' => 'مارجن و خالص بچت',
        'staff' => 'عملہ / تنخواہ',
        'staff_desc' => 'حاضری، ایڈوانس و اجرت',
        'expenses' => 'اخراجات',
        'expenses_desc' => 'روزمرہ اخراجات بل',
        'reports' => 'رپورٹس',
        'reports_desc' => 'سیل، اسٹاک و لیجر',
        'documents' => 'دستاویزات',
        'documents_desc' => 'ایف بی آر انوائسز و رسیدیں',
        'notifications' => 'اطلاعات',
        'notifications_desc' => 'الرٹس اور انتباہات',
        'settings' => 'ترتیبات',
        'settings_desc' => 'نرخ، رول اور اسٹیشن کنفگ',
    ],

    'settings' => [
        'site_language' => 'Site Language / سائٹ کی زبان',
        'site_language_help' => 'یہ زبان تمام صارفین کے لیے پوری سائٹ پر لاگو ہوگی — سائیڈبار، بٹن اور ڈیش بورڈ ٹائلز۔',
        'english' => 'English',
        'urdu' => 'اردو (Urdu)',
    ],

    'auth' => [
        'tagline' => 'اپنے اسٹیشن ERP میں جاری رکھنے کے لیے سائن ان کریں',
        'email' => 'ای میل ایڈریس',
        'password' => 'پاس ورڈ',
        'remember' => 'مجھے یاد رکھیں',
        'forgot' => 'پاس ورڈ بھول گئے؟',
        'sign_in' => 'سائن ان',
    ],

    'palette' => [
        'search' => 'تلاش کریں',
        'placeholder' => 'صفحات، گاہک، بل، گاڑیاں تلاش کریں…',
        'pages' => 'صفحات',
        'customers' => 'گاہک (کھاتا)',
        'invoices' => 'بل / انوائسز',
        'vehicles' => 'گاڑیاں',
        'recents' => 'حالیہ',
        'no_results' => 'کوئی نتیجہ نہیں ملا — نام، فون یا بل نمبر آزمائیں',
        'bill' => 'بل',
        'khata' => 'کھاتا',
        'tanks' => 'ٹینک',
        'more' => 'مزید',
        'install_app' => '📲 ایپ انسٹال کریں',
        'install_ios_hint' => 'آئی فون پر: شیئر دبائیں، پھر "Add to Home Screen" منتخب کریں — ایپ انسٹال ہو جائے گی۔',
        'shortcuts_title' => 'کی بورڈ شارٹ کٹس',
        'sc_palette' => 'تلاش کھولیں (کمانڈ پیلیٹ)',
        'sc_dashboard' => 'ڈیش بورڈ پر جائیں',
        'sc_pos' => 'POS پر جائیں (نیا بل)',
        'sc_customers' => 'گاہکوں (کھاتا) پر جائیں',
        'sc_gauges' => 'ٹینک گیجز پر جائیں',
        'sc_reports' => 'رپورٹس پر جائیں',
        'sc_help' => 'یہ شارٹ کٹ فہرست دکھائیں',
        'sc_close' => 'کھلا ڈائیلاگ بند کریں',
    ],

    // PIN login + optional email OTP (D-017)
    'pinlogin' => [
        'pin_not_set' => 'اس اکاؤنٹ کا پن سیٹ نہیں ہے — ایڈمن سے پن لگوا کر آئیں۔',
        'otp_title' => 'ای میل تصدیق',
        'otp_sent' => '6 ہندسوں کا کوڈ :email پر بھیج دیا گیا ہے۔ لاگ اِن کے لیے نیچے درج کریں۔',
        'otp_invalid' => 'غلط کوڈ — دوبارہ کوشش کریں۔',
        'otp_expired' => 'یہ کوڈ ختم ہو چکا ہے — واپس جا کر دوبارہ لاگ اِن کریں۔',
        'otp_resend' => 'کوڈ دوبارہ بھیجیں',
        'otp_resend_wait' => 'نیا کوڈ مانگنے سے پہلے ایک منٹ انتظار کریں۔',
        'otp_back' => 'پن پر واپس',
        'otp_send_failed' => 'کوڈ والی ای میل نہیں جا سکی — دوبارہ کوشش کریں یا ایڈمن سے رابطہ کریں۔',
    ],

];
