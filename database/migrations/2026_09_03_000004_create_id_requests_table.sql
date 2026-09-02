-- ============================================================
-- Migration: id_requests (a student may have many)
-- ============================================================

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