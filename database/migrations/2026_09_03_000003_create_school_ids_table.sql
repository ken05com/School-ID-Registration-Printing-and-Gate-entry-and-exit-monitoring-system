-- ============================================================
-- Migration: school_ids (one-to-one with students)
-- ============================================================

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