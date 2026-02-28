-- ─── StudyDesk Database Schema ────────────────────────────────────────────────
-- Run once to set up the database.
-- Command: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS study_scheduler
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE study_scheduler;

-- ── SCHEDULES TABLE ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS schedules (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100)  NOT NULL,
    start_time   DATETIME      NOT NULL,
    end_time     DATETIME      NOT NULL,
    pdf_file     VARCHAR(255)  DEFAULT '',
    notes        TEXT          DEFAULT '',
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SAMPLE DATA ───────────────────────────────────────────────────────────────
INSERT INTO schedules (subject_name, start_time, end_time, pdf_file, notes) VALUES
('Mathematics',      NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR),    '', 'Review quadratic equations and derivatives.'),
('English Literature', NOW(), DATE_ADD(NOW(), INTERVAL 2 HOUR),  '', 'Analyze Chapter 3 themes and symbolism.'),
('Science',          NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), '', 'Study periodic table groups 1-18.');