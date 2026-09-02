-- ============================================================
-- Migration: Create the migrations tracking table
-- Mirrors Laravel's `migrations` table so `php migrate.php`
-- can record which migrations have already been applied.
-- ============================================================

CREATE TABLE IF NOT EXISTS migrations (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  migration     VARCHAR(255) NOT NULL,
  batch         INT NOT NULL,
  applied_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY migrations_unique (migration)
) ENGINE=InnoDB;