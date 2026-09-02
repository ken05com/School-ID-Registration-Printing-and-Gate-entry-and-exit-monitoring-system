<?php
/**
 * School ID System — One-Command Installer
 * =========================================
 * Sets the project up on any machine with a local MySQL/XAMPP:
 *   1. Detects a reachable MySQL (stock XAMPP 127.0.0.1:3306 root/empty,
 *      this repo's private instance on database/mysql.sock, or any you give it)
 *   2. Creates the `school_id_system` database if missing
 *   3. Writes/updates .env so the app connects
 *   4. Runs the migration runner to build tables + seed data
 *   5. Reports the login URL and demo accounts
 *
 * Usage (CLI):
 *     /opt/lampp/bin/php install.php
 *
 * You can pass values directly to skip prompts:
 *     /opt/lampp/bin/php install.php --host=127.0.0.1 --port=3306
 *         --user=root --pass= --db=school_id_system --yes
 */

// --- helpers -------------------------------------------------------------

function out(string $m): void { echo $m . PHP_EOL; }
function color(string $m): string { return $m; } // keep output plain for the terminal

function ask(string $prompt, string $default = ''): string {
    if (PHP_SAPI !== 'cli') {
        return readline($prompt) ?: $default;
    }
    echo $prompt;
    $v = trim(fgets(STDIN) ?: '');
    return $v === '' ? $default : $v;
}

function parseArgs(): array {
    $opts = [];
    foreach (array_slice($_SERVER['argv'] ?? [], 1) as $a) {
        if (strpos($a, '--') === 0) {
            $p = explode('=', ltrim($a, '--'), 2);
            $opts[$p[0]] = $p[1] ?? true;
        }
    }
    return $opts;
}

function tryConnect(string $host, int $port, string $user, string $pass): ?PDO {
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        return $pdo;
    } catch (Throwable $e) {
        return null;
    }
}

$opts = parseArgs();
$root = __DIR__;
$envFile = $root . '/.env';

// --- 1. Detect / gather MySQL connection ---------------------------------
out('==============================================');
out(' School ID System — Setup');
out('==============================================');

// Candidate connection profiles, in priority order.
// sprintf: [host, port, user, pass]
$candidates = [
    // this repo's private instance (start.sh) via TCP 127.0.0.1:3307
    ['127.0.0.1', 3307, 'school', 'school123'],
    // stock XAMPP default: 127.0.0.1:3306, root with no password
    ['127.0.0.1', 3306, 'root', ''],
    // stock MySQL default: 127.0.0.1:3306, root / root
    ['127.0.0.1', 3306, 'root', 'root'],
    // DNS/localhost variant of XAMPP default
    ['localhost', 3306, 'root', ''],
];

// If the user provided any values, put those first.
if (isset($opts['host']) || isset($opts['port']) || isset($opts['user']) || array_key_exists('pass', $opts)) {
    $custom = [
        $opts['host'] ?? '127.0.0.1',
        (int)($opts['port'] ?? 3306),
        $opts['user'] ?? 'root',
        $opts['pass'] ?? '',
    ];
    array_unshift($candidates, $custom);
}

$pdo = null;
$chosen = null;
foreach ($candidates as $c) {
    list($host, $port, $user, $pass) = $c;
    $p = tryConnect($host, $port, $user, $pass);
    if ($p) {
        $pdo = $p;
        $chosen = ['host' => $host, 'port' => $port, 'user' => $user, 'pass' => $pass];
        break;
    }
}

if (!$pdo && (string)($opts['yes'] ?? '') !== 'true') {
    // Prompt for a manual profile
    out('');
    out('Could not auto-detect a local MySQL. Please enter your connection:');
    $host = ask('  Host [127.0.0.1]: ', '127.0.0.1');
    $port = (int)ask('  Port [3306]: ', '3306');
    $user = ask('  User [root]: ', 'root');
    $pass = ask('  Password: ', '');
    $pdo = tryConnect($host, $port, $user, $pass);
    $chosen = ['host' => $host, 'port' => $port, 'user' => $user, 'pass' => $pass];
}

if (!$pdo) {
    out('ERROR: No MySQL server could be reached with the provided settings.');
    out('  - Make sure MySQL/XAMPP is running, and the username/password are correct.');
    out('  - You can pass values directly, e.g.:');
    out('      /opt/lampp/bin/php install.php --host=127.0.0.1 --port=3306 --user=root --pass= --yes');
    exit(1);
}

out('MySQL: connected (' . $chosen['host'] . ':' . $chosen['port'] . ' as ' . $chosen['user'] . ')');

// --- 2. Create the database if missing ------------------------------------
$dbname = $opts['db'] ?? 'school_id_system';
$dbname = preg_replace('/[^A-Za-z0-9_]/', '', $dbname) ?: 'school_id_system';

$exists = false;
try {
    $exists = (bool)$pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($dbname))->fetchColumn();
} catch (Throwable $e) { /* ignore */ }

if (!$exists) {
    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    out("Database: created `$dbname` (utf8mb4_unicode_ci)");
} else {
    out("Database: `$dbname` already exists — leaving data intact");
}
$pdo->exec("USE `$dbname`");

// --- 3. Write .env --------------------------------------------------------
function valueForEnv(string $v): string {
    return '"' . str_replace(['"', "\n", "\r"], ['', '', ''], $v) . '"';
}

$envVars = [
    'APP_NAME'        => 'School ID System',
    'APP_BASE_URL'    => '/',
    'APP_ENV'         => isset($opts['env']) ? $opts['env'] : 'local',
    'APP_DEBUG'       => 'true',
    'DB_CONNECTION'   => 'mysql',
    'DB_HOST'         => $chosen['host'],
    'DB_PORT'         => (string)$chosen['port'],
    'DB_DATABASE'     => $dbname,
    'DB_USERNAME'     => $chosen['user'],
    'DB_PASSWORD'     => $chosen['pass'],
];

$env = "APP_NAME=" . valueForEnv($envVars['APP_NAME']) . PHP_EOL
     . "APP_BASE_URL=" . valueForEnv($envVars['APP_BASE_URL']) . PHP_EOL
     . "APP_ENV=" . $envVars['APP_ENV'] . PHP_EOL
     . "APP_DEBUG=" . $envVars['APP_DEBUG'] . PHP_EOL
     . PHP_EOL
     . "# --- Database (auto-configured by install.php) ---" . PHP_EOL
     . "DB_CONNECTION=mysql" . PHP_EOL
     . "DB_HOST=" . $envVars['DB_HOST'] . PHP_EOL
     . "DB_PORT=" . $envVars['DB_PORT'] . PHP_EOL
     . "DB_DATABASE=" . $envVars['DB_DATABASE'] . PHP_EOL
     . "DB_USERNAME=" . $envVars['DB_USERNAME'] . PHP_EOL
     . "DB_PASSWORD=" . valueForEnv($envVars['DB_PASSWORD']) . PHP_EOL;

file_put_contents($envFile, $env);
out('Config: wrote .env (' . $envFile . ')');

// --- 4. Run the migrations ------------------------------------------------
out('');
out('Running migrations ...');
// Run the migrator as a subprocess so it uses the newly-written .env.
$cmd = PHP_BINARY . ' -f ' . escapeshellarg($root . '/database/migrate.php') . ' 2>&1';
passthru($cmd, $code);

if ($code !== 0) {
    out('WARNING: migrations reported a non-zero exit code (' . $code . ').');
    out('  The app may connect but some tables may not have been created.');
    out('  Re-run: ' . PHP_BINARY . ' database/migrate.php');
} else {
    out('Migrations complete.');
}

// --- 5. Report ------------------------------------------------------------
out('');
out('==============================================');
out(' Setup finished.');
out('==============================================');
out('  DB:    ' . $dbname . ' @ ' . $chosen['host'] . ':' . $chosen['port']);
out('  Web:   Start the app, then open your browser.');
out('         - This repo:  ./start.sh     -> http://localhost:8000');
out('         - XAMPP:      put this app under htdocs and visit /app/');
out('  Demo accounts (password: password123):');
out('         admin@school.edu   (Administrator)');
out('         registrar@school.edu');
out('         idstaff@school.edu');
out('         guard@school.edu   (Security Guard)');
out('');