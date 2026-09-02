<?php
/**
 * School ID System — Database Migrator
 * =====================================
 * A lightweight, Laravel-style migration runner.
 *
 * Usage (from the project root):
 *     /opt/lampp/bin/php database/migrate.php          # run pending migrations
 *     /opt/lampp/bin/php database/migrate.php --status  # show applied/pending
 *     /opt/lampp/bin/php database/migrate.php --seed    # re-run only the seed data
 *
 * It reads DB settings from .env (via includes/config.php), ensures the
 * `migrations` tracking table exists, then applies every migration file in
 * database/migrations/ that has not yet been recorded — exactly like
 * `php artisan migrate`. Use `--fresh` to drop and re-run schemas only on a
 * disposable database (it will NOT re-seed by default).
 */

$root = dirname(__DIR__);
require_once $root . '/includes/config.php';

$migrationsDir = __DIR__ . '/migrations';
$fresh   = in_array('--fresh', $argv, true);
$status  = in_array('--status', $argv, true);
$seedOnly = in_array('--seed', $argv, true);

function out(string $msg): void { echo $msg . PHP_EOL; }

try {
    $pdo = db();
} catch (Throwable $e) {
    out('ERROR: could not connect to database.');
    out('  ' . $e->getMessage());
    out('  Check DB settings in .env (' . realpath($root . '/.env') . ').');
    exit(1);
}

// --- Ensure the migrations tracking table exists ---
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  migration     VARCHAR(255) NOT NULL UNIQUE,
  batch         INT NOT NULL,
  applied_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

// --- Fresh mode: drop every managed table, then re-run schemas ---
if ($fresh) {
    out('Dropping tables (--fresh) ...');
    $stmt = $pdo->query("SHOW TABLES");
    $skip = ['migrations'];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $t = $row[0];
        if (in_array($t, $skip, true)) continue;
        $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '', $t) . '`');
    }
    $pdo->exec("DELETE FROM migrations");
    out('  All tables dropped. Re-running migrations below.');
    sleep(1);
}

// --- Gather migration files ---
$files = glob($migrationsDir . '/*.sql');
natsort($files);
$files = array_values($files);

if (!$files) {
    out('No migration files found in ' . $migrationsDir);
    exit(0);
}

// Applied migrations (name => batch)
$applied = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

if ($status) {
    out(str_pad('Migration', 50) . str_pad('State', 12) . 'File');
    out(str_repeat('-', 100));
    foreach ($files as $f) {
        $name = basename($f);
        $state = in_array($name, $applied, true) ? 'APPLIED' : 'pending';
        out(str_pad($state, 12) . '   ' . $name);
    }
    exit(0);
}

// Determine the next batch number (highest existing batch + 1, or 1)
$lastBatch = (int)$pdo->query("SELECT COALESCE(MAX(batch),0) FROM migrations")->fetchColumn();
$nextBatch = $seedOnly ? $lastBatch : $lastBatch + 1;

// --- Apply pending migrations in order ---
$ran = 0;
foreach ($files as $f) {
    $name = basename($f);

    // A --seed run only runs the file whose name contains "seed"
    if ($seedOnly && stripos($name, 'seed') === false) {
        continue;
    }
    if (!$seedOnly && in_array($name, $applied, true)) {
        continue;
    }
    if (in_array($name, $applied, true)) {
        continue;
    }

    $sql = file_get_contents($f);
    if ($sql === false) {
        out("ERROR: could not read $name");
        exit(1);
    }

    out("Migrating: $name");
    try {
        // pdo::exec() can only run a single statement, so split on ';'.
        // NOTE: we do NOT wrap in a transaction because MySQL implicitly
        // commits on DDL (CREATE/DROP/ALTER), so real rollback is impossible.
        // Our migrations are written to be idempotent (IF NOT EXISTS,
        // INSERT IGNORE / WHERE NOT EXISTS) so a failed one can be re-run
        // after it is fixed.
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
        foreach ($statements as $s) {
            $s = trim($s);
            if ($s !== '') {
                $pdo->exec($s);
            }
        }
    } catch (Throwable $e) {
        out("  FAILED: " . $e->getMessage());
        out("  Fix the migration and re-run — earlier statements are not rolled back.");
        exit(1);
    }

    // Record the migration
    $ins = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)
                          ON DUPLICATE KEY UPDATE batch = VALUES(batch)");
    $ins->execute([$name, $nextBatch]);
    $ran++;
}

out($seedOnly
    ? "Seed complete ($ran file(s) processed)."
    : "Migration complete. $ran new migration(s) applied in batch $nextBatch.");
out('Done.');