-- ============================================================
-- School ID Registration, Printing & Gate Monitoring System
-- Database Schema + Seed Data
-- ============================================================

CREATE DATABASE IF NOT EXISTS school_id_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_id_system;

-- ------------------------------------------------------------
-- USERS
-- roles: admin, registrar, id_staff, security_guard, student
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,
  role          ENUM('admin','registrar','id_staff','security_guard','student') NOT NULL DEFAULT 'student',
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- STUDENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_no    VARCHAR(30) NOT NULL UNIQUE,
  full_name     VARCHAR(150) NOT NULL,
  email         VARCHAR(150),
  phone         VARCHAR(30),
  course        VARCHAR(100),
  year_level    VARCHAR(20),
  section       VARCHAR(50),
  address       VARCHAR(255),
  photo_path    VARCHAR(255) DEFAULT NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  registered_by INT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_student_reg FOREIGN KEY (registered_by) REFERENCES users(id)
                    ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- SCHOOL IDS (one-to-one with students)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS school_ids (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_id    INT NOT NULL,
  id_number     VARCHAR(30) NOT NULL UNIQUE,
  qr_code       VARCHAR(64) NOT NULL,
  issue_date    DATE,
  expiry_date   DATE,
  qr_status     ENUM('active','expired','blocked') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_id_student FOREIGN KEY (student_id) REFERENCES students(id)
                    ON DELETE CASCADE,
  CONSTRAINT fk_id_student_unique UNIQUE (student_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ID PRINTING REQUESTS (a student may have many)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS id_requests (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_id    INT NOT NULL,
  request_type  ENUM('new','replacement','renewal') NOT NULL DEFAULT 'new',
  status        ENUM('pending','printed','cancelled') NOT NULL DEFAULT 'pending',
  printed_by    INT,
  request_date  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  printed_date  DATETIME DEFAULT NULL,
  CONSTRAINT fk_req_student FOREIGN KEY (student_id) REFERENCES students(id)
                    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- GATE ENTRY / EXIT LOGS (a student may have many)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gate_logs (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_id    INT NOT NULL,
  id_number     VARCHAR(30) NOT NULL,
  direction     ENUM('entry','exit') NOT NULL,
  scanned_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  guard_id      INT,
  status        ENUM('valid','invalid','expired','blocked','unauthorized') NOT NULL DEFAULT 'valid',
  CONSTRAINT fk_log_student FOREIGN KEY (student_id) REFERENCES students(id)
                    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- NOTIFICATIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  recipient_id  INT,
  message       VARCHAR(255) NOT NULL,
  is_read       TINYINT(1) NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default users (password: password123 hashed with password_hash)
INSERT INTO users (full_name, email, password, role) VALUES
('System Administrator', 'admin@school.edu', '$2y$10$YPfIuDtOiL8gCHxTpY47EOvEzEL5WET1edf4FUfImCwwB8GZblP2q', 'admin'),
('Maria Registrar',       'registrar@school.edu', '$2y$10$YPfIuDtOiL8gCHxTpY47EOvEzEL5WET1edf4FUfImCwwB8GZblP2q', 'registrar'),
('John ID Staff',         'idstaff@school.edu', '$2y$10$YPfIuDtOiL8gCHxTpY47EOvEzEL5WET1edf4FUfImCwwB8GZblP2q', 'id_staff'),
('Guard Carl',            'guard@school.edu', '$2y$10$YPfIuDtOiL8gCHxTpY47EOvEzEL5WET1edf4FUfImCwwB8GZblP2q', 'security_guard');

-- Sample students
INSERT INTO students (student_no, full_name, email, phone, course, year_level, section, address, status, registered_by) VALUES
('2023-0001', 'Juan Dela Cruz',  'juan@student.school.edu', '0917-000-0001', 'BSIT', '3rd Year', 'A', 'Quezon City', 'approved', 2),
('2023-0002', 'Maria Santos',    'maria@student.school.edu', '0917-000-0002', 'BSIT', '2nd Year', 'B', 'Manila', 'approved', 2),
('2024-0003', 'Pedro Garcia',    'pedro@student.school.edu', '0917-000-0003', 'BSECE', '1st Year', 'A', 'Pasig', 'pending', 2),
('2024-0004', 'Ana Reyes',       'ana@student.school.edu', '0917-000-0004', 'BSNUR', '4th Year', 'C', 'Taguig', 'approved', 2),
('2025-0005', 'Luis Mendoza',    'luis@student.school.edu', '0917-000-0005', 'BSBA', '3rd Year', 'B', 'Makati', 'approved', 2);

-- School IDs for approved students (QR = unique token)
INSERT INTO school_ids (student_id, id_number, qr_code, issue_date, expiry_date, qr_status) VALUES
(1, '2023-0001', 'SID-2023-0001-A1B2C3', '2023-06-01', '2026-06-01', 'active'),
(2, '2023-0002', 'SID-2023-0002-D4E5F6', '2023-06-01', '2026-06-01', 'active'),
(4, '2024-0004', 'SID-2024-0004-G7H8I9', '2024-06-01', '2027-06-01', 'active'),
(5, '2025-0005', 'SID-2025-0005-J1K2L3', '2025-06-01', '2028-06-01', 'active');

-- Some sample gate logs
INSERT INTO gate_logs (student_id, id_number, direction, guard_id, status, scanned_at) VALUES
(1, '2023-0001', 'entry', 4, 'valid', NOW() - INTERVAL 2 HOUR),
(1, '2023-0001', 'exit',  4, 'valid', NOW() - INTERVAL 1 HOUR),
(2, '2023-0002', 'entry', 4, 'valid', NOW() - INTERVAL 3 HOUR),
(2, '2023-0002', 'exit',  4, 'valid', NOW() - INTERVAL 30 MINUTE),
(5, '2025-0005', 'entry', 4, 'valid', NOW() - INTERVAL 5 HOUR);
