-- ============================================================
-- Migration: students
-- ============================================================

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