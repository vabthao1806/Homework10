-- ============================================================
-- setup.sql - Contact Manager database setup
-- INS3064 - Homework 10
-- Student: Nguyễn Thị Thảo Vân (24070557)
--
-- Run this file once in phpMyAdmin (SQL tab) or with the mysql command line.
-- Re-running it resets the table and sample data.
-- ============================================================

CREATE DATABASE IF NOT EXISTS contact_manager
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE contact_manager;

DROP TABLE IF EXISTS contacts;

CREATE TABLE contacts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) UNIQUE,
    phone       VARCHAR(20),
    group_name  VARCHAR(50) DEFAULT 'General',
    notes       TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sample data
INSERT INTO contacts (name, email, phone, group_name, notes) VALUES
('Nguyễn Văn An',   'an.nguyen@example.com',  '0901234567', 'Family',  'Older brother'),
('Trần Thị Bình',   'binh.tran@example.com',  '0912345678', 'Friends', 'Met at university'),
('Lê Minh Châu',    'chau.le@example.com',    '0987654321', 'Work',    'Project manager, Hanoi office'),
('Phạm Quốc Dũng',  'dung.pham@example.com',  '0978123456', 'Work',    NULL),
('Hoàng Thu Hà',    'ha.hoang@example.com',   NULL,         'Friends', 'Prefers email'),
('Vũ Đức Huy',      'huy.vu@example.com',     '0935551234', 'General', 'Dentist appointment every 6 months');
