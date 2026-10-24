-- ==============================================================
-- ឯកសារ SQL សម្រាប់បង្កើត Database និង តារាងទិន្នន័យសិស្ស
-- Database Schema for Student Management System
-- ==============================================================

-- 1. បង្កើត Database ប្រសិនបើមិនទាន់មាន (Create Database)
CREATE DATABASE IF NOT EXISTS `student_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `student_db`;

-- 2. បង្កើតតារាង students (Create Students Table)
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `studentId` VARCHAR(50) NOT NULL UNIQUE COMMENT 'អត្តលេខសិស្ស',
    `fullname` VARCHAR(100) NOT NULL COMMENT 'គោត្តនាម និងនាម',
    `gender` VARCHAR(10) NOT NULL COMMENT 'ភេទ (ប្រុស / ស្រី)',
    `dob` DATE NULL COMMENT 'ថ្ងៃខែឆ្នាំកំណើត (YYYY-MM-DD)',
    `grade` VARCHAR(50) NOT NULL COMMENT 'ថ្នាក់ទី (ទី១, ទី២, ទី៣, ...)',
    `room` VARCHAR(50) NOT NULL COMMENT 'បន្ទប់ (ក, ខ, គ, ...)',
    
    -- ទីកន្លែងកំណើត (Place of Birth)
    `pob_village` VARCHAR(100) DEFAULT '' COMMENT 'ភូមិកំណើត',
    `pob_commune` VARCHAR(100) DEFAULT '' COMMENT 'ឃុំ/សង្កាត់កំណើត',
    `pob_district` VARCHAR(100) DEFAULT '' COMMENT 'ស្រុក/ខណ្ឌកំណើត',
    `pob_province` VARCHAR(100) DEFAULT '' COMMENT 'ខេត្ត/ក្រុងកំណើត',
    
    -- ព័ត៌មានឪពុក (Father Information)
    `father_name` VARCHAR(100) DEFAULT '' COMMENT 'ឈ្មោះឪពុក',
    `father_status` VARCHAR(50) DEFAULT '' COMMENT 'ស្ថានភាពឪពុក (រស់ / ស្លាប់)',
    `father_job` VARCHAR(100) DEFAULT '' COMMENT 'មុខរបរឪពុក',
    
    -- ព័ត៌មានម្តាយ (Mother Information)
    `mother_name` VARCHAR(100) DEFAULT '' COMMENT 'ឈ្មោះម្តាយ',
    `mother_status` VARCHAR(50) DEFAULT '' COMMENT 'ស្ថានភាពម្តាយ (រស់ / ស្លាប់)',
    `mother_job` VARCHAR(100) DEFAULT '' COMMENT 'មុខរបរម្តាយ',
    
    -- អាសយដ្ឋានបច្ចុប្បន្ន (Current Address)
    `curr_village` VARCHAR(100) DEFAULT '' COMMENT 'ភូមិបច្ចុប្បន្ន',
    `curr_commune` VARCHAR(100) DEFAULT '' COMMENT 'ឃុំ/សង្កាត់បច្ចុប្បន្ន',
    `curr_district` VARCHAR(100) DEFAULT '' COMMENT 'ស្រុក/ខណ្ឌបច្ចុប្បន្ន',
    `curr_province` VARCHAR(100) DEFAULT '' COMMENT 'ខេត្ត/ក្រុងបច្ចុប្បន្ន',
    
    -- ព័ត៌មានផ្សេងៗ (Other Information)
    `ethnicity_religion` VARCHAR(100) DEFAULT 'ខ្មែរ/ព្រះពុទ្ធ' COMMENT 'ជនជាតិ/សាសនា',
    
    -- កាលបរិច្ឆេទបង្កើត និងកែប្រែ (Timestamps)
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. បញ្ចូលទិន្នន័យគំរូ (Insert Sample Data)
INSERT INTO `students` (
    `studentId`, `fullname`, `gender`, `dob`, `grade`, `room`,
    `pob_village`, `pob_commune`, `pob_district`, `pob_province`,
    `father_name`, `father_status`, `father_job`,
    `mother_name`, `mother_status`, `mother_job`,
    `curr_village`, `curr_commune`, `curr_district`, `curr_province`,
    `ethnicity_religion`
) VALUES
('ST1002', 'ចាន់ មករា', 'ប្រុស', '2017-04-19', 'ទី១', 'ក', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'ខ្មែរ/ព្រះពុទ្ធ'),
('ST1000', 'សុខ សាន្ត', 'ប្រុស', '2015-01-12', 'ទី១', 'ក', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'ខ្មែរ/ព្រះពុទ្ធ'),
('ST1001', 'មាស ស្រីរ័ត្ន', 'ស្រី', '2016-04-05', 'ទី២', 'ខ', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'ខ្មែរ/ព្រះពុទ្ធ'),
('ST1003', 'រិទ្ធី ឧត្តម', 'ប្រុស', '2018-05-12', 'ទី៣', 'ក', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'ខ្មែរ/ព្រះពុទ្ធ'),
('ST1004', 'សាន ស្រីពៅ', 'ស្រី', '2017-09-17', 'ទី៦', 'ក', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'ខ្មែរ/ព្រះពុទ្ធ')
ON DUPLICATE KEY UPDATE `fullname` = VALUES(`fullname`);

-- 4. បង្កើតតារាង users សម្រាប់ប្រព័ន្ធ Login / Register (Create Users Table)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fullname` VARCHAR(100) NOT NULL COMMENT 'ឈ្មោះពេញ',
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT 'ឈ្មោះអ្នកប្រើសម្រាប់ Login',
    `phone` VARCHAR(20) NOT NULL UNIQUE COMMENT 'លេខទូរស័ព្ទ',
    `password` VARCHAR(255) NOT NULL COMMENT 'ពាក្យសម្ងាត់ Hashed',
    `plain_password` VARCHAR(255) NULL COMMENT 'ពាក្យសម្ងាត់ដើមសម្រាប់ Admin មើល',
    `role` VARCHAR(20) DEFAULT 'user' COMMENT 'សិទ្ធិ (admin / user)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. បញ្ចូលគណនី Admin លំនាំដើម (Default Admin: username=admin, phone=012345678, password=admin123)
INSERT INTO `users` (`fullname`, `username`, `phone`, `password`, `plain_password`, `role`)
VALUES ('អ្នកគ្រប់គ្រងប្រព័ន្ធ', 'admin', '012345678', '$2y$10$wT8KzNfXF5xR/nO2c0989O4F3z2l1wF7v6E5r4t3y2u1i0o9p8q7r', 'admin123', 'admin')
ON DUPLICATE KEY UPDATE `fullname` = VALUES(`fullname`), `phone` = VALUES(`phone`), `plain_password` = VALUES(`plain_password`);

