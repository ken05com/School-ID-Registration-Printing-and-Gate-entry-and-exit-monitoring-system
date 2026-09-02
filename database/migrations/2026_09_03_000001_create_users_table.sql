-- ============================================================
-- Migration: users
-- roles: admin, registrar, id_staff, security_guard, student
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,
  role          ENUM('admin','registrar','id_staff','security_guard','student') NOT NULL DEFAULT 'student',
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;