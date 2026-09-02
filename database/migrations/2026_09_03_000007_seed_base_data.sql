-- ============================================================
-- Migration: seed base users and sample students
-- Runs as part of the migration batch. Uses INSERT IGNORE so
-- re-applying on an existing database does not duplicate rows.
-- Default passwords are 'password123' (bcrypt, verified with
-- password_verify). Do not change these hashes or login breaks.
-- ============================================================

-- Base users (roles)
INSERT IGNORE INTO users (full_name, email, password, role) VALUES
('System Administrator', 'admin@school.edu',      '$2y$10$YPfIuDtOiL8gCHxTpY47EOvEzEL5WET1edf4FUfImCwwB8GZblP2q', 'admin'),
('Maria Registrar',       'registrar@school.edu', '$2y$10$YPfIuDtOiL8gCHxTpY47EOvEzEL5WET1edf4FUfImCwwB8GZblP2q', 'registrar'),
('John ID Staff',         'idstaff@school.edu',   '$2y$10$YPfIuDtOiL8gCHxTpY47EOvEzEL5WET1edf4FUfImCwwB8GZblP2q', 'id_staff'),
('Guard Carl',            'guard@school.edu',     '$2y$10$YPfIuDtOiL8gCHxTpY47EOvEzEL5WET1edf4FUfImCwwB8GZblP2q', 'security_guard');

-- Sample students (only inserted if student_no not already present)
INSERT IGNORE INTO students (student_no, full_name, email, phone, course, year_level, section, address, status, registered_by) VALUES
('2023-0001', 'Juan Dela Cruz',  'juan@student.school.edu',    '0917-000-0001', 'BSIT',  '3rd Year', 'A', 'Quezon City', 'approved', 2),
('2023-0002', 'Maria Santos',    'maria@student.school.edu',   '0917-000-0002', 'BSIT',  '2nd Year', 'B', 'Manila',      'approved', 2),
('2024-0003', 'Pedro Garcia',    'pedro@student.school.edu',   '0917-000-0003', 'BSECE', '1st Year', 'A', 'Pasig',       'pending',  2),
('2024-0004', 'Ana Reyes',       'ana@student.school.edu',     '0917-000-0004', 'BSNUR', '4th Year', 'C', 'Taguig',      'approved', 2),
('2025-0005', 'Luis Mendoza',    'luis@student.school.edu',    '0917-000-0005', 'BSBA',  '3rd Year', 'B', 'Makati',      'approved', 2);

-- School IDs for the (known) approved students above.
-- Uses INSERT ... SELECT ... WHERE NOT EXISTS to stay idempotent.
-- (Concat literals are collated to utf8mb4_unicode_ci to avoid
--  "Illegal mix of collations" when the DB default collation differs.)
INSERT INTO school_ids (student_id, id_number, qr_code, issue_date, expiry_date, qr_status)
SELECT s.id, s.student_no,
       CONCAT('SID-', s.student_no, '-', SUBSTRING(MD5(s.full_name), 1, 6)) COLLATE utf8mb4_unicode_ci,
       CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 YEAR), 'active'
FROM students s
WHERE s.student_no IN ('2023-0001','2023-0002','2024-0004','2025-0005')
  AND NOT EXISTS (SELECT 1 FROM school_ids si WHERE si.student_id = s.id);