-- ============================================================
-- Migration: gate_logs (a student may have many)
-- ============================================================

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