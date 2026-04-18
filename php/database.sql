-- ============================================================
-- InternPortal – database.sql
-- Run this in phpMyAdmin or MySQL CLI:
--   mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS internportal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE internportal;

-- ── Users ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    student_id VARCHAR(30)  DEFAULT NULL,
    email      VARCHAR(180) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','mentor','intern') NOT NULL DEFAULT 'intern',
    batch      VARCHAR(20)  DEFAULT NULL,
    section    VARCHAR(20)  DEFAULT NULL,
    status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Projects (tasks assigned by mentors) ─────────────────────
CREATE TABLE IF NOT EXISTS projects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id   INT          NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT         DEFAULT NULL,
    deadline    DATE         NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Project assignments (which interns get which projects) ───
CREATE TABLE IF NOT EXISTS assignments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    intern_id  INT NOT NULL,
    UNIQUE KEY uq_assign (project_id, intern_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (intern_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Submissions ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS submissions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT          NOT NULL,
    intern_id    INT          NOT NULL,
    file_path    VARCHAR(300) NOT NULL,
    file_name    VARCHAR(200) NOT NULL,
    notes        TEXT         DEFAULT NULL,
    grade        TINYINT      DEFAULT NULL,
    feedback     TEXT         DEFAULT NULL,
    submitted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    graded_at    DATETIME     DEFAULT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (intern_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Notifications ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    message    VARCHAR(400) NOT NULL,
    type       ENUM('info','success','warning','danger') DEFAULT 'info',
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Seed: default admin account ───────────────────────────────
-- Password: admin123  (bcrypt hash)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Administrator', 'admin@internportal.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Seed: demo mentor
-- Password: mentor123
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Sadia Sultana', 'sadia@internportal.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mentor');

-- Seed: demo intern
-- Password: intern123
INSERT IGNORE INTO users (name, student_id, email, password, role, batch, section) VALUES
('Dhroubo Jyoti Mondal', '232-35-182', 'dhroubo@internportal.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'intern', '41', 'E1');
