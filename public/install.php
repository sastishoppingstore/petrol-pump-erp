<?php
/**
 * Vital Petroleum ERP — One-Click Web Installer
 *
 * Run once: https://yourdomain.com/install.php
 *
 * Flow: Welcome → Requirements → Database → Admin → Done
 *  - Checks PHP version, required extensions and writable directories
 *  - Writes database credentials to .env and generates APP_KEY
 *  - Runs: php artisan migrate --force
 *          php artisan db:seed --class=ProductionSeeder --force
 *  - Creates the first Super Admin (bcrypt hash, super-admin role, first
 *    branch linked) using prepared statements — the password itself is
 *    never echoed, logged or stored in .env
 *  - Writes the lock file storage/app/installed.lock and attempts to
 *    delete itself. Once the lock file exists the installer redirects
 *    to /login and can never run again (delete the lock file manually
 *    if a reinstall is ever required).
 *
 * Security: a session-based CSRF token is required on every POST, the
 * database name is strictly validated before it is used inside
 * CREATE DATABASE, all other values go through prepared statements or
 * the .env writer, and every echoed value is HTML-escaped.
 */

// ---------------------------------------------------------------------------
// Session + CSRF token (standalone file — simple session-based token)
// ---------------------------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['install_csrf']) || !is_string($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
}

// ---------------------------------------------------------------------------
// Paths & request context
// ---------------------------------------------------------------------------
$basePath = dirname(__DIR__);
$envPath  = $basePath . '/.env';
$lockPath = $basePath . '/storage/app/installed.lock';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl  = "{$protocol}://{$host}";
$loginUrl = $baseUrl . '/login';

// ---------------------------------------------------------------------------
// Already installed? The lock file blocks the installer completely.
// ---------------------------------------------------------------------------
if (file_exists($lockPath)) {
    if (!headers_sent()) {
        header('Location: ' . $loginUrl);
    }
    exit;
}

// ---------------------------------------------------------------------------
// .env helpers
// ---------------------------------------------------------------------------
$envContent = file_exists($envPath) ? (string) file_get_contents($envPath) : '';

function getEnvValue($key, $default = '') {
    global $envContent;
    $pattern = "/^" . preg_quote($key, '/') . "=(.*)$/m";
    if (preg_match($pattern, $envContent, $matches)) {
        return trim($matches[1], '"\'');
    }
    return $default;
}

function setEnvValue($key, $value) {
    global $envPath, $envContent;
    // Always write double-quoted with \, " and $ escaped so phpdotenv reads
    // the value back literally (passwords may contain any of those chars).
    $escaped = str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], (string) $value);
    $line = $key . '="' . $escaped . '"';
    $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
    if (preg_match($pattern, $envContent)) {
        $envContent = preg_replace_callback($pattern, function () use ($line) {
            return $line;
        }, $envContent, 1);
    } else {
        if ($envContent !== '' && substr($envContent, -1) !== "\n") {
            $envContent .= "\n";
        }
        $envContent .= $line . "\n";
    }
    file_put_contents($envPath, $envContent);
}

// HTML-escape helper for every echoed value
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------------------
// Requirements check
// ---------------------------------------------------------------------------
function requirementChecks($basePath, $envPath) {
    $checks = [];
    $checks[] = [
        'label' => 'PHP version 8.2 or higher',
        'ok'    => version_compare(PHP_VERSION, '8.2.0', '>='),
        'value' => 'PHP ' . PHP_VERSION,
        'gate'  => true,
    ];
    $extensions = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
    foreach ($extensions as $ext) {
        $loaded = extension_loaded($ext);
        $checks[] = [
            'label' => 'PHP extension: ' . $ext,
            'ok'    => $loaded,
            'value' => $loaded ? 'Enabled' : 'Missing',
            'gate'  => true,
        ];
    }
    $writables = [
        'Writable: storage/ directory'         => $basePath . '/storage',
        'Writable: bootstrap/cache/ directory' => $basePath . '/bootstrap/cache',
    ];
    foreach ($writables as $label => $path) {
        $ok = is_dir($path) && is_writable($path);
        $checks[] = [
            'label' => $label,
            'ok'    => $ok,
            'value' => $ok ? 'Writable' : 'Not writable',
            'gate'  => true,
        ];
    }
    $envOk = file_exists($envPath) ? is_writable($envPath) : is_writable($basePath);
    $checks[] = [
        'label' => 'Writable: .env file',
        'ok'    => $envOk,
        'value' => $envOk ? 'Writable' : 'Not writable',
        'gate'  => true,
    ];
    // Advisory only: needed to run artisan migrate/seed from the web.
    $shellOk = function_exists('exec') || function_exists('shell_exec');
    $checks[] = [
        'label' => 'Shell access (exec) for artisan migrate/seed',
        'ok'    => $shellOk,
        'value' => $shellOk ? 'Available' : 'Disabled — migrate/seed must be run via SSH/terminal',
        'gate'  => false,
    ];
    return $checks;
}

$requirements = requirementChecks($basePath, $envPath);
$requirementsPass = true;
foreach ($requirements as $check) {
    if (!empty($check['gate']) && !$check['ok']) {
        $requirementsPass = false;
        break;
    }
}
$shellAvailable = function_exists('exec') || function_exists('shell_exec');

// ---------------------------------------------------------------------------
// Artisan runner (same cd + php artisan pattern as the original installer,
// hardened with escapeshellarg and exit-code based success detection)
// ---------------------------------------------------------------------------
function runArtisan($basePath, $args, &$output) {
    $output = '';
    $cmd = 'cd ' . escapeshellarg($basePath) . ' && php artisan ' . $args . ' 2>&1';
    if (function_exists('exec')) {
        $lines = [];
        $code = 1;
        exec($cmd, $lines, $code);
        $output = implode("\n", $lines);
        return $code === 0;
    }
    if (function_exists('shell_exec')) {
        $output = (string) shell_exec($cmd);
        return $output !== ''
            && stripos($output, 'exception') === false
            && stripos($output, ' error') === false;
    }
    return false;
}

// ---------------------------------------------------------------------------
// Database connector (PDO, prepared statements everywhere below)
// ---------------------------------------------------------------------------
function dbConnect($host, $port, $user, $pass, $dbName = null) {
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    if ($dbName !== null && $dbName !== '') {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
    }
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
}

// ---------------------------------------------------------------------------
// Page state
// ---------------------------------------------------------------------------
$errors  = [];
$success = [];
$step = $_POST['step'] ?? $_GET['step'] ?? 'welcome';
if (!in_array($step, ['welcome', 'requirements', 'database', 'admin', 'complete'], true)) {
    $step = 'welcome';
}
$installerDeleted  = false;
$createdAdminEmail = '';
// Form repopulation values (passwords are NEVER repopulated)
$form = [
    'company_name' => 'Mehar Filling Station',
    'admin_name'   => '',
    'admin_email'  => '',
];

// ---------------------------------------------------------------------------
// POST handling (CSRF token required on every submission)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenOk = isset($_POST['_token'])
        && hash_equals($_SESSION['install_csrf'], (string) $_POST['_token']);

    if (!$tokenOk) {
        $errors[] = 'Security token mismatch or session expired. Please start again.';
        $step = 'welcome';
    } elseif ($step === 'database' && isset($_POST['db_host'])) {

        // ---------------- Database step ----------------
        if (!$requirementsPass) {
            $errors[] = 'Server requirements are not met yet. Please fix the items on the Requirements step first.';
            $step = 'requirements';
        } else {
            $db_host = trim((string) ($_POST['db_host'] ?? ''));
            $db_port = (int) ($_POST['db_port'] ?? 3306);
            $db_user = trim((string) ($_POST['db_user'] ?? ''));
            $db_pass = (string) ($_POST['db_pass'] ?? '');
            $db_name = trim((string) ($_POST['db_name'] ?? ''));

            if ($db_host === '' || $db_user === '' || $db_name === '') {
                $errors[] = 'Database host, user and name are all required.';
            } elseif (preg_match('/[\s;]/', $db_host)) {
                $errors[] = 'Invalid database host.';
            } elseif ($db_port < 1 || $db_port > 65535) {
                $errors[] = 'Invalid database port.';
            } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $db_name)) {
                // The name is interpolated into CREATE DATABASE inside
                // backticks, so only a strict whitelist is ever allowed.
                $errors[] = 'Database name may only contain letters, numbers and underscores.';
            } else {
                try {
                    $pdo = dbConnect($db_host, $db_port, $db_user, $db_pass);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                    setEnvValue('DB_CONNECTION', 'mysql');
                    setEnvValue('DB_HOST', $db_host);
                    setEnvValue('DB_PORT', (string) $db_port);
                    setEnvValue('DB_DATABASE', $db_name);
                    setEnvValue('DB_USERNAME', $db_user);
                    setEnvValue('DB_PASSWORD', $db_pass);
                    setEnvValue('APP_URL', $baseUrl);

                    if (getEnvValue('APP_KEY', '') === '') {
                        // Same format `php artisan key:generate` writes for
                        // the default AES-256-CBC cipher (32 random bytes).
                        setEnvValue('APP_KEY', 'base64:' . base64_encode(random_bytes(32)));
                        $success[] = 'Application key (APP_KEY) generated.';
                    }

                    $success[] = 'Database connected, created (if it did not exist) and saved to .env.';
                    $step = 'admin';
                } catch (PDOException $ex) {
                    // Deliberately generic: driver errors can contain the
                    // host/username, which must never reach the browser.
                    $errors[] = 'Database connection failed. Please verify the host, port, username and password, and that MySQL is running.';
                }
            }
        }
    } elseif ($step === 'admin' && isset($_POST['admin_email'])) {

        // ---------------- Admin step ----------------
        $form['company_name'] = trim((string) ($_POST['company_name'] ?? ''));
        $form['admin_name']   = trim((string) ($_POST['admin_name'] ?? ''));
        $form['admin_email']  = strtolower(trim((string) ($_POST['admin_email'] ?? '')));
        $admin_password       = (string) ($_POST['admin_password'] ?? '');
        $admin_confirm        = (string) ($_POST['admin_password_confirmation'] ?? '');

        if ($form['company_name'] === '' || $form['admin_name'] === '' || $form['admin_email'] === '') {
            $errors[] = 'Company name, admin name and admin email are all required.';
        } elseif (!filter_var($form['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid admin email address.';
        } elseif (strlen($admin_password) < 8) {
            $errors[] = 'Admin password must be at least 8 characters.';
        } elseif ($admin_password !== $admin_confirm) {
            $errors[] = 'Admin password and confirmation do not match.';
        } elseif (!$shellAvailable) {
            $errors[] = 'This server has exec/shell_exec disabled, so the installer cannot run migrations. '
                . 'Please run these two commands via SSH/terminal, then contact support: '
                . 'php artisan migrate --force && php artisan db:seed --class=ProductionSeeder --force';
        } else {
            $artisanOutput = '';
            if (!runArtisan($basePath, 'migrate --force', $artisanOutput)) {
                $errors[] = 'Database migration failed. Please check storage/logs/laravel.log on the server, fix the issue and try again.';
            } elseif (!runArtisan($basePath, 'db:seed --class=ProductionSeeder --force', $artisanOutput)) {
                $errors[] = 'Database seeding failed. Please check storage/logs/laravel.log on the server, fix the issue and try again.';
            } else {
                try {
                    $pdo = dbConnect(
                        getEnvValue('DB_HOST', '127.0.0.1'),
                        (int) getEnvValue('DB_PORT', '3306'),
                        getEnvValue('DB_USERNAME', ''),
                        getEnvValue('DB_PASSWORD', ''),
                        getEnvValue('DB_DATABASE', '')
                    );

                    $exists = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $exists->execute([$form['admin_email']]);

                    if ($exists->fetch()) {
                        $errors[] = 'A user with this email already exists. Please use a different email.';
                    } else {
                        $now  = date('Y-m-d H:i:s');
                        $hash = password_hash($admin_password, PASSWORD_BCRYPT);

                        $insertUser = $pdo->prepare(
                            'INSERT INTO users (name, email, email_verified_at, password, status, password_changed_at, created_at, updated_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $insertUser->execute([
                            $form['admin_name'],
                            $form['admin_email'],
                            $now,
                            $hash,
                            'ACTIVE',
                            $now,
                            $now,
                            $now,
                        ]);
                        $adminId = (int) $pdo->lastInsertId();

                        // Super-admin role (seeded by ProductionSeeder)
                        $roleId = null;
                        $roleRow = $pdo->query('SELECT id FROM roles WHERE is_super_admin = 1 ORDER BY id ASC LIMIT 1')->fetch();
                        if ($roleRow) {
                            $roleId = (int) $roleRow['id'];
                        } else {
                            $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'ADMIN' LIMIT 1");
                            $roleStmt->execute();
                            $roleRow = $roleStmt->fetch();
                            if ($roleRow) {
                                $roleId = (int) $roleRow['id'];
                            }
                        }
                        if ($roleId !== null) {
                            $pdo->prepare('INSERT INTO user_roles (user_id, role_id, created_at, updated_at) VALUES (?, ?, ?, ?)')
                                ->execute([$adminId, $roleId, $now, $now]);
                        }

                        // First branch + default branch link (best effort,
                        // mirrors `php artisan erp:install`; never fatal)
                        try {
                            $branchRow = $pdo->query('SELECT id FROM branches ORDER BY id ASC LIMIT 1')->fetch();
                            if ($branchRow) {
                                $branchId = (int) $branchRow['id'];
                            } else {
                                $pdo->prepare('INSERT INTO branches (code, name, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)')
                                    ->execute(['MFS-01', $form['company_name'], 'ACTIVE', $now, $now]);
                                $branchId = (int) $pdo->lastInsertId();
                            }
                            $pdo->prepare('INSERT INTO user_branches (user_id, branch_id, is_default, created_at, updated_at) VALUES (?, ?, 1, ?, ?)')
                                ->execute([$adminId, $branchId, $now, $now]);
                        } catch (Throwable $ignored) {
                            // Branch can be created later from the Branches screen.
                        }

                        // Company name → APP_NAME (same as erp:install does)
                        setEnvValue('APP_NAME', $form['company_name']);
                        setEnvValue('APP_INSTALLED', 'true');

                        // Lock the installer permanently
                        $lockDir = dirname($lockPath);
                        if (!is_dir($lockDir)) {
                            @mkdir($lockDir, 0775, true);
                        }
                        @file_put_contents($lockPath, 'Installed: ' . date('c') . PHP_EOL);

                        // Fresh CSRF token, then attempt self-deletion
                        $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
                        $createdAdminEmail = $form['admin_email'];
                        $installerDeleted = @unlink(__FILE__);

                        $success[] = 'Installation complete — Super Admin account created.';
                        $step = 'complete';
                    }
                } catch (Throwable $ex) {
                    // Generic on purpose: never leak DB details or the password.
                    $errors[] = 'Could not create the administrator account. Please verify the database settings and try again.';
                }
            }
        }
    }
}

// Steps indicator state
$stepOrder  = ['welcome' => 0, 'requirements' => 1, 'database' => 2, 'admin' => 3, 'complete' => 4];
$stepLabels = ['Welcome', 'Requirements', 'Database', 'Admin', 'Done'];
$currentIdx = $stepOrder[$step];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vital Petroleum ERP — Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(160deg, #020617 0%, #0f172a 55%, #020617 100%);
            background-color: #020617;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient glow orbs — decorative only, never block clicks */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(70px);
            pointer-events: none;
            z-index: 0;
            opacity: .55;
        }
        .orb-red   { width: 340px; height: 340px; background: #dc2626; top: -120px; left: -100px; animation: orbFloat 14s ease-in-out infinite; }
        .orb-blue  { width: 300px; height: 300px; background: #2563eb; bottom: -110px; right: -80px; animation: orbFloat 18s ease-in-out infinite reverse; }
        .orb-amber { width: 200px; height: 200px; background: #f59e0b; top: 55%; left: -80px; opacity: .35; animation: orbFloat 20s ease-in-out infinite; }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50%      { transform: translate(40px, 30px) scale(1.08); }
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(26px) scale(.985); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes floaty {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-9px); }
        }

        /* Frosted glass card */
        .card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 660px;
            background: linear-gradient(165deg, rgba(255,255,255,.09), rgba(255,255,255,.035));
            -webkit-backdrop-filter: blur(20px) saturate(150%);
            backdrop-filter: blur(20px) saturate(150%);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 26px;
            box-shadow: 0 30px 80px rgba(0,0,0,.6), 0 4px 18px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.16);
            padding: 42px 44px 38px;
            animation: cardIn .7s cubic-bezier(.22,1,.36,1) backwards;
        }

        .header { text-align: center; margin-bottom: 26px; }

        .badge {
            width: 78px;
            height: 78px;
            margin: 0 auto 16px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            background: linear-gradient(145deg, #ef4444, #f97316);
            border: 1px solid rgba(255,255,255,.35);
            box-shadow: 0 18px 38px rgba(220,38,38,.5), 0 6px 14px rgba(0,0,0,.5), inset 0 2px 0 rgba(255,255,255,.45), inset 0 -6px 12px rgba(0,0,0,.28);
            animation: floaty 5s ease-in-out infinite;
        }

        .logo {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: .4px;
            background: linear-gradient(90deg, #f8fafc, #fca5a5 55%, #fdba74);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 6px;
        }

        .tagline { color: #94a3b8; font-size: 13.5px; }

        /* Steps indicator */
        .steps {
            display: flex;
            justify-content: space-between;
            gap: 4px;
            margin: 6px 0 30px;
            position: relative;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 17px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: rgba(255,255,255,.10);
            z-index: 0;
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .step-dot {
            width: 34px;
            height: 34px;
            margin: 0 auto 7px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #94a3b8;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.16);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.12);
        }
        .step-item.active .step-dot {
            color: #fff;
            background: linear-gradient(145deg, #dc2626, #f97316);
            border-color: rgba(255,255,255,.4);
            box-shadow: 0 0 0 5px rgba(249,115,22,.18), 0 10px 22px rgba(220,38,38,.5), inset 0 1px 0 rgba(255,255,255,.4);
        }
        .step-item.done .step-dot {
            color: #052e16;
            background: linear-gradient(145deg, #34d399, #10b981);
            border-color: rgba(255,255,255,.35);
        }
        .step-label { font-size: 11px; letter-spacing: .3px; color: #64748b; text-transform: uppercase; }
        .step-item.active .step-label { color: #fdba74; }
        .step-item.done .step-label { color: #6ee7b7; }

        .step-title {
            font-size: 21px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 18px;
            text-align: center;
        }

        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            margin-bottom: 7px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: .3px;
        }

        input, select {
            width: 100%;
            padding: 13px 15px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(2,6,23,.55);
            color: #f1f5f9;
            font-size: 14.5px;
            font-family: inherit;
            position: relative;
            z-index: 2;
            pointer-events: auto !important;
            user-select: text !important;
            -webkit-user-select: text !important;
            transition: border-color .2s, box-shadow .2s;
        }
        input::placeholder { color: #475569; }
        input:focus, select:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 4px rgba(249,115,22,.18), 0 0 22px rgba(249,115,22,.12);
        }

        .form-row { display: flex; gap: 14px; }
        .form-row .form-group { flex: 1; }
        .form-row .form-group.narrow { flex: 0 0 130px; }

        .alert {
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 14px;
            font-size: 14px;
            line-height: 1.55;
            border: 1px solid;
        }
        .alert-error   { background: rgba(220,38,38,.13); color: #fecaca; border-color: rgba(248,113,113,.45); }
        .alert-success { background: rgba(16,185,129,.13); color: #a7f3d0; border-color: rgba(52,211,153,.45); }
        .alert-warn    { background: rgba(245,158,11,.13); color: #fde68a; border-color: rgba(251,191,36,.45); }

        .button-group { display: flex; gap: 12px; margin-top: 28px; }

        .btn-3d, .btn-ghost {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 22px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
            pointer-events: auto !important;
        }

        /* 3D tactile primary button — red/orange fuel gradient */
        .btn-3d {
            color: #fff;
            border: 1px solid rgba(255,255,255,.28);
            background: linear-gradient(145deg, #dc2626, #f97316);
            box-shadow: 0 14px 32px rgba(220,38,38,.45), 0 4px 10px rgba(0,0,0,.4), inset 0 2px 0 rgba(255,255,255,.35), inset 0 -4px 0 rgba(0,0,0,.25);
            text-shadow: 0 1px 2px rgba(0,0,0,.35);
        }
        .btn-3d:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 42px rgba(249,115,22,.55), 0 0 26px rgba(249,115,22,.35), inset 0 2px 0 rgba(255,255,255,.4), inset 0 -4px 0 rgba(0,0,0,.25);
        }
        .btn-3d:active { transform: translateY(0) scale(.98); }

        .btn-ghost {
            color: #e2e8f0;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.16);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.1);
        }
        .btn-ghost:hover { background: rgba(255,255,255,.11); transform: translateY(-2px); }

        .btn-disabled {
            opacity: .45;
            cursor: not-allowed;
            pointer-events: none !important;
        }

        .info-box {
            background: rgba(59,130,246,.09);
            border: 1px solid rgba(96,165,250,.25);
            border-left: 4px solid #3b82f6;
            padding: 15px 17px;
            margin-bottom: 20px;
            border-radius: 12px;
            font-size: 13.5px;
            color: #bfdbfe;
            line-height: 1.7;
        }
        .info-box strong { color: #e0f2fe; }

        /* Requirements list */
        .req-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 15px;
            margin-bottom: 8px;
            border-radius: 12px;
            background: rgba(255,255,255,.045);
            border: 1px solid rgba(255,255,255,.08);
            font-size: 14px;
        }
        .req-name { color: #e2e8f0; }
        .req-val  { color: #94a3b8; font-size: 12.5px; margin-left: 8px; }
        .pill {
            flex: 0 0 auto;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .pill-ok   { background: rgba(16,185,129,.18); color: #6ee7b7; border: 1px solid rgba(52,211,153,.5); }
        .pill-miss { background: rgba(220,38,38,.16); color: #fca5a5; border: 1px solid rgba(248,113,113,.5); }
        .pill-warn { background: rgba(245,158,11,.16); color: #fcd34d; border: 1px solid rgba(251,191,36,.5); }

        .success-badge {
            width: 92px;
            height: 92px;
            margin: 4px auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 44px;
            color: #052e16;
            background: linear-gradient(145deg, #34d399, #059669);
            border: 1px solid rgba(255,255,255,.4);
            box-shadow: 0 18px 40px rgba(16,185,129,.45), 0 0 34px rgba(16,185,129,.3), inset 0 2px 0 rgba(255,255,255,.5), inset 0 -6px 12px rgba(0,0,0,.25);
            animation: floaty 5s ease-in-out infinite;
        }

        .complete-text {
            text-align: center;
            color: #cbd5e1;
            line-height: 1.85;
            font-size: 14.5px;
        }
        .complete-text strong { color: #f1f5f9; }
        .next-steps {
            text-align: left;
            margin: 18px auto 4px;
            max-width: 430px;
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.9;
        }

        @media (max-width: 560px) {
            body { padding: 18px 10px; }
            .card { padding: 28px 20px 26px; border-radius: 20px; }
            .logo { font-size: 24px; }
            .step-label { font-size: 9px; }
            .step-dot { width: 28px; height: 28px; font-size: 11px; }
            .steps::before { top: 14px; }
            .form-row { flex-direction: column; gap: 0; }
            .form-row .form-group.narrow { flex: 1; }
            .button-group { flex-direction: column; }
        }

        @media (prefers-reduced-motion: reduce) {
            .orb, .badge, .success-badge, .card { animation: none !important; }
            .btn-3d, .btn-ghost, input, select { transition: none !important; }
        }
    </style>
</head>
<body>
    <div class="orb orb-red"></div>
    <div class="orb orb-blue"></div>
    <div class="orb orb-amber"></div>

    <div class="card">
        <div class="header">
            <div class="badge">⛽</div>
            <div class="logo">Vital Petroleum ERP</div>
            <div class="tagline">Installation Wizard — Mehar Filling Station</div>
        </div>

        <div class="steps">
            <?php foreach ($stepLabels as $idx => $label): ?>
                <div class="step-item <?= $idx < $currentIdx ? 'done' : ($idx === $currentIdx ? 'active' : '') ?>">
                    <div class="step-dot"><?= $idx < $currentIdx ? '✓' : ($idx + 1) ?></div>
                    <div class="step-label"><?= e($label) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($errors): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">❌ <?= e($error) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($success): ?>
            <?php foreach ($success as $msg): ?>
                <div class="alert alert-success">✅ <?= e($msg) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Welcome Step -->
        <?php if ($step === 'welcome'): ?>
            <div class="step-title">Welcome to Vital Petroleum ERP</div>
            <div class="info-box">
                <strong>This installer will:</strong><br>
                • Check your server (PHP 8.2+, extensions, writable folders)<br>
                • Create the MySQL database and write the .env configuration<br>
                • Generate the application key (APP_KEY)<br>
                • Run all database migrations and production seeders<br>
                • Create your Super Admin account and first branch<br>
                • Lock itself and delete itself when finished<br><br>
                <strong>Make sure you have:</strong><br>
                • MySQL host, username, password and a database name<br>
                • An admin email and a strong password (min 8 characters) ready
            </div>
            <form method="POST" action="install.php">
                <input type="hidden" name="_token" value="<?= e($_SESSION['install_csrf']) ?>">
                <input type="hidden" name="step" value="requirements">
                <div class="button-group">
                    <button type="submit" class="btn-3d">Start Installation →</button>
                </div>
            </form>
        <?php endif; ?>

        <!-- Requirements Step -->
        <?php if ($step === 'requirements'): ?>
            <div class="step-title">Server Requirements</div>
            <?php foreach ($requirements as $check): ?>
                <div class="req-row">
                    <div>
                        <span class="req-name"><?= e($check['label']) ?></span>
                        <span class="req-val"><?= e($check['value']) ?></span>
                    </div>
                    <?php if ($check['ok']): ?>
                        <span class="pill pill-ok">✓ OK</span>
                    <?php elseif (!empty($check['gate'])): ?>
                        <span class="pill pill-miss">✗ Missing</span>
                    <?php else: ?>
                        <span class="pill pill-warn">⚠ Warning</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (!$requirementsPass): ?>
                <div class="alert alert-error" style="margin-top:16px;">
                    Some requirements are missing. Please fix them on your hosting
                    (StackCP → PHP version / extensions / file permissions), then recheck.
                </div>
            <?php endif; ?>

            <div class="button-group">
                <a class="btn-ghost" href="install.php?step=welcome">← Back</a>
                <a class="btn-ghost" href="install.php?step=requirements">↻ Recheck</a>
                <?php if ($requirementsPass): ?>
                    <a class="btn-3d" href="install.php?step=database">Continue: Database →</a>
                <?php else: ?>
                    <span class="btn-3d btn-disabled">Continue: Database →</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Database Step -->
        <?php if ($step === 'database'): ?>
            <div class="step-title">Database Configuration</div>
            <form method="POST" action="install.php">
                <input type="hidden" name="_token" value="<?= e($_SESSION['install_csrf']) ?>">
                <input type="hidden" name="step" value="database">

                <div class="form-row">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="<?= e(getEnvValue('DB_HOST', 'localhost')) ?>" required autocomplete="off">
                    </div>
                    <div class="form-group narrow">
                        <label>Port</label>
                        <input type="number" name="db_port" value="<?= e(getEnvValue('DB_PORT', '3306')) ?>" min="1" max="65535" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Database User (from StackCP / cPanel)</label>
                    <input type="text" name="db_user" value="<?= e(getEnvValue('DB_USERNAME', '')) ?>" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" value="" required autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label>Database Name (will be created if it does not exist)</label>
                    <input type="text" name="db_name" value="<?= e(getEnvValue('DB_DATABASE', 'vital_petrol_erp')) ?>" required autocomplete="off">
                </div>

                <div class="info-box">
                    <strong>Where to find these values:</strong><br>
                    • StackCP Control Panel → MySQL Databases<br>
                    • Create the database user there first (note the username prefix)<br>
                    • Host is usually <strong>localhost</strong> and port <strong>3306</strong><br>
                    • Letters, numbers and underscores only in the database name
                </div>

                <div class="button-group">
                    <a class="btn-ghost" href="install.php?step=requirements">← Back</a>
                    <button type="submit" class="btn-3d">Test &amp; Save → Admin Setup</button>
                </div>
            </form>
        <?php endif; ?>

        <!-- Admin Step -->
        <?php if ($step === 'admin'): ?>
            <div class="step-title">Create Super Admin</div>
            <div class="info-box">
                On submit, the installer will run all migrations, seed roles,
                permissions and settings, then create this administrator.
                This can take a few seconds — please do not refresh.
            </div>
            <form method="POST" action="install.php">
                <input type="hidden" name="_token" value="<?= e($_SESSION['install_csrf']) ?>">
                <input type="hidden" name="step" value="admin">

                <div class="form-group">
                    <label>Company / Station Name</label>
                    <input type="text" name="company_name" value="<?= e($form['company_name']) ?>" required autocomplete="organization">
                </div>

                <div class="form-group">
                    <label>Admin Full Name</label>
                    <input type="text" name="admin_name" value="<?= e($form['admin_name']) ?>" placeholder="e.g., Muhammad Ali" required autocomplete="name">
                </div>

                <div class="form-group">
                    <label>Admin Email (used to login)</label>
                    <input type="email" name="admin_email" value="<?= e($form['admin_email']) ?>" placeholder="admin@meharfilling.com" required autocomplete="email">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Admin Password (min 8 characters)</label>
                        <input type="password" name="admin_password" value="" placeholder="••••••••" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="admin_password_confirmation" value="" placeholder="••••••••" required autocomplete="new-password">
                    </div>
                </div>

                <div class="info-box">
                    <strong>⚠️ Important:</strong><br>
                    • Save this password in a safe place — it is never stored in plain text<br>
                    • This Super Admin can add branches, users and roles after login
                </div>

                <div class="button-group">
                    <a class="btn-ghost" href="install.php?step=database">← Back</a>
                    <button type="submit" class="btn-3d">Install &amp; Create Admin →</button>
                </div>
            </form>
        <?php endif; ?>

        <!-- Complete Step -->
        <?php if ($step === 'complete'): ?>
            <div class="success-badge">✓</div>
            <div class="step-title" style="color:#6ee7b7;">Installation Complete!</div>

            <div class="complete-text">
                <strong>Your Vital Petroleum ERP is ready.</strong><br>
                <?php if ($createdAdminEmail !== ''): ?>
                    Login with the admin email: <strong><?= e($createdAdminEmail) ?></strong><br>
                <?php endif; ?>
                <div class="next-steps">
                    <strong>Next steps after login:</strong><br>
                    1. Settings → Configure fuel products &amp; prices<br>
                    2. Settings → Set tax rates<br>
                    3. Settings → Configure alerts &amp; notifications<br>
                    4. Add tanks, dispensers, nozzles and staff users<br>
                    5. Open your first shift and start selling
                </div>
            </div>

            <?php if ($installerDeleted): ?>
                <div class="alert alert-success" style="margin-top:18px;">
                    🔒 Installer locked and <strong>install.php has been deleted</strong> automatically. You are safe.
                </div>
            <?php else: ?>
                <div class="alert alert-warn" style="margin-top:18px;">
                    🔒 Installer is locked (storage/app/installed.lock), but the file
                    could not delete itself. <strong>Please delete public/install.php
                    from your server manually right now.</strong>
                </div>
            <?php endif; ?>

            <div class="button-group">
                <a class="btn-3d" href="<?= e($loginUrl) ?>">Go to Login →</a>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
