CREATE DATABASE IF NOT EXISTS darololom_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE darololom_php;

CREATE TABLE IF NOT EXISTS study_levels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(190) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'teacher', 'student') NOT NULL DEFAULT 'admin',
    permissions JSON NULL,
    can_register_students TINYINT(1) NOT NULL DEFAULT 0,
    can_register_teachers TINYINT(1) NOT NULL DEFAULT 0,
    student_id INT UNSIGNED NULL,
    teacher_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_users_email (email),
    UNIQUE KEY uniq_users_student (student_id),
    UNIQUE KEY uniq_users_teacher (teacher_id),
    CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET @has_permissions_col := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'permissions'
);
SET @alter_users_permissions_sql := IF(
    @has_permissions_col = 0,
    'ALTER TABLE users ADD COLUMN permissions JSON NULL AFTER role',
    'SELECT 1'
);
PREPARE alter_users_permissions_stmt FROM @alter_users_permissions_sql;
EXECUTE alter_users_permissions_stmt;
DEALLOCATE PREPARE alter_users_permissions_stmt;

SET @has_email_col := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'email'
);
SET @alter_users_email_sql := IF(
    @has_email_col = 0,
    'ALTER TABLE users ADD COLUMN email VARCHAR(190) NULL AFTER username',
    'SELECT 1'
);
PREPARE alter_users_email_stmt FROM @alter_users_email_sql;
EXECUTE alter_users_email_stmt;
DEALLOCATE PREPARE alter_users_email_stmt;

SET @has_student_id_col := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'student_id'
);
SET @alter_users_student_id_sql := IF(
    @has_student_id_col = 0,
    'ALTER TABLE users ADD COLUMN student_id INT UNSIGNED NULL AFTER can_register_teachers',
    'SELECT 1'
);
PREPARE alter_users_student_id_stmt FROM @alter_users_student_id_sql;
EXECUTE alter_users_student_id_stmt;
DEALLOCATE PREPARE alter_users_student_id_stmt;

SET @has_teacher_id_col := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'teacher_id'
);
SET @alter_users_teacher_id_sql := IF(
    @has_teacher_id_col = 0,
    'ALTER TABLE users ADD COLUMN teacher_id INT UNSIGNED NULL AFTER student_id',
    'SELECT 1'
);
PREPARE alter_users_teacher_id_stmt FROM @alter_users_teacher_id_sql;
EXECUTE alter_users_teacher_id_stmt;
DEALLOCATE PREPARE alter_users_teacher_id_stmt;

ALTER TABLE users
    MODIFY COLUMN role ENUM('super_admin', 'admin', 'teacher', 'student') NOT NULL DEFAULT 'admin';

SET @has_users_email_index := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'uniq_users_email'
);
SET @create_users_email_index_sql := IF(
    @has_users_email_index = 0,
    'ALTER TABLE users ADD UNIQUE KEY uniq_users_email (email)',
    'SELECT 1'
);
PREPARE create_users_email_index_stmt FROM @create_users_email_index_sql;
EXECUTE create_users_email_index_stmt;
DEALLOCATE PREPARE create_users_email_index_stmt;

SET @has_users_student_index := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'uniq_users_student'
);
SET @create_users_student_index_sql := IF(
    @has_users_student_index = 0,
    'ALTER TABLE users ADD UNIQUE KEY uniq_users_student (student_id)',
    'SELECT 1'
);
PREPARE create_users_student_index_stmt FROM @create_users_student_index_sql;
EXECUTE create_users_student_index_stmt;
DEALLOCATE PREPARE create_users_student_index_stmt;

SET @has_users_teacher_index := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'uniq_users_teacher'
);
SET @create_users_teacher_index_sql := IF(
    @has_users_teacher_index = 0,
    'ALTER TABLE users ADD UNIQUE KEY uniq_users_teacher (teacher_id)',
    'SELECT 1'
);
PREPARE create_users_teacher_index_stmt FROM @create_users_teacher_index_sql;
EXECUTE create_users_teacher_index_stmt;
DEALLOCATE PREPARE create_users_teacher_index_stmt;

CREATE TABLE IF NOT EXISTS semesters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    number TINYINT UNSIGNED NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS course_periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    number TINYINT UNSIGNED NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS school_classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    level_id INT UNSIGNED NULL,
    semester_id INT UNSIGNED NULL,
    period_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_school_classes_level FOREIGN KEY (level_id) REFERENCES study_levels(id) ON DELETE SET NULL,
    CONSTRAINT fk_school_classes_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL,
    CONSTRAINT fk_school_classes_period FOREIGN KEY (period_id) REFERENCES course_periods(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    level_id INT UNSIGNED NULL,
    semester TINYINT UNSIGNED NOT NULL DEFAULT 1,
    period_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subjects_level FOREIGN KEY (level_id) REFERENCES study_levels(id) ON DELETE SET NULL,
    CONSTRAINT fk_subjects_period FOREIGN KEY (period_id) REFERENCES course_periods(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    father_name VARCHAR(255) NULL,
    birth_date DATE NULL,
    permanent_address TEXT NULL,
    current_address TEXT NULL,
    village VARCHAR(150) NULL,
    district VARCHAR(150) NULL,
    area VARCHAR(150) NULL,
    current_street VARCHAR(150) NULL,
    gender ENUM('male', 'female') NOT NULL DEFAULT 'male',
    education_level ENUM('p', 'b', 'm', 'd') NOT NULL DEFAULT 'p',
    id_number VARCHAR(100) NULL,
    image_path VARCHAR(255) NULL,
    plan_file VARCHAR(255) NULL,
    education_document VARCHAR(255) NULL,
    experience_document VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id INT UNSIGNED NOT NULL,
    publication_year SMALLINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_articles_author (author_id),
    INDEX idx_articles_year (publication_year),
    CONSTRAINT fk_articles_author FOREIGN KEY (author_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_articles_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    publication_year SMALLINT UNSIGNED NOT NULL,
    cover_image_path VARCHAR(255) NOT NULL,
    pdf_file_path VARCHAR(255) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_books_title (title),
    INDEX idx_books_author (author),
    INDEX idx_books_year (publication_year),
    CONSTRAINT fk_books_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET @has_teachers_current_street := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'teachers'
      AND column_name = 'current_street'
);
SET @add_teachers_current_street_sql := IF(
    @has_teachers_current_street = 0,
    'ALTER TABLE teachers ADD COLUMN current_street VARCHAR(150) NULL AFTER area',
    'SELECT 1'
);
PREPARE add_teachers_current_street_stmt FROM @add_teachers_current_street_sql;
EXECUTE add_teachers_current_street_stmt;
DEALLOCATE PREPARE add_teachers_current_street_stmt;

CREATE TABLE IF NOT EXISTS teacher_class (
    teacher_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (teacher_id, class_id),
    CONSTRAINT fk_teacher_class_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_teacher_class_class FOREIGN KEY (class_id) REFERENCES school_classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_subject (
    teacher_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (teacher_id, subject_id),
    CONSTRAINT fk_teacher_subject_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_teacher_subject_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_level (
    teacher_id INT UNSIGNED NOT NULL,
    level_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (teacher_id, level_id),
    CONSTRAINT fk_teacher_level_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_teacher_level_level FOREIGN KEY (level_id) REFERENCES study_levels(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_semester (
    teacher_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (teacher_id, semester_id),
    CONSTRAINT fk_teacher_semester_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_teacher_semester_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_period (
    teacher_id INT UNSIGNED NOT NULL,
    period_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (teacher_id, period_id),
    CONSTRAINT fk_teacher_period_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_teacher_period_period FOREIGN KEY (period_id) REFERENCES course_periods(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL UNIQUE,
    contract_number VARCHAR(100) NOT NULL UNIQUE,
    contract_date DATE NULL,
    monthly_salary VARCHAR(100) NULL,
    position VARCHAR(150) NULL,
    notes LONGTEXT NULL,
    signed_file VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_teacher_contract_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    father_name VARCHAR(255) NULL,
    grandfather_name VARCHAR(255) NULL,
    birth_date DATE NULL,
    id_number VARCHAR(100) NULL,
    exam_number VARCHAR(100) NULL,
    gender ENUM('male', 'female') NOT NULL DEFAULT 'male',
    current_address TEXT NULL,
    village VARCHAR(150) NULL,
    district VARCHAR(150) NULL,
    area VARCHAR(150) NULL,
    current_street VARCHAR(150) NULL,
    time_start TIME NULL,
    time_end TIME NULL,
    permanent_address TEXT NULL,
    school_class_id INT UNSIGNED NULL,
    mobile_number VARCHAR(20) NULL,
    enrollment_year SMALLINT UNSIGNED NULL,
    image_path VARCHAR(255) NULL,
    certificate_file VARCHAR(255) NULL,
    level_id INT UNSIGNED NULL,
    is_grade12_graduate TINYINT(1) NOT NULL DEFAULT 0,
    is_graduated TINYINT(1) NOT NULL DEFAULT 0,
    certificate_number VARCHAR(50) NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_class FOREIGN KEY (school_class_id) REFERENCES school_classes(id) ON DELETE SET NULL,
    CONSTRAINT fk_students_level FOREIGN KEY (level_id) REFERENCES study_levels(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET @has_students_current_street := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'students'
      AND column_name = 'current_street'
);
SET @add_students_current_street_sql := IF(
    @has_students_current_street = 0,
    'ALTER TABLE students ADD COLUMN current_street VARCHAR(150) NULL AFTER area',
    'SELECT 1'
);
PREPARE add_students_current_street_stmt FROM @add_students_current_street_sql;
EXECUTE add_students_current_street_stmt;
DEALLOCATE PREPARE add_students_current_street_stmt;

SET @has_students_enrollment_year := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'students'
      AND column_name = 'enrollment_year'
);
SET @add_students_enrollment_year_sql := IF(
    @has_students_enrollment_year = 0,
    'ALTER TABLE students ADD COLUMN enrollment_year SMALLINT UNSIGNED NULL AFTER mobile_number',
    'SELECT 1'
);
PREPARE add_students_enrollment_year_stmt FROM @add_students_enrollment_year_sql;
EXECUTE add_students_enrollment_year_stmt;
DEALLOCATE PREPARE add_students_enrollment_year_stmt;

SET @has_fk_users_student := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND constraint_name = 'fk_users_student'
);
SET @add_fk_users_student_sql := IF(
    @has_fk_users_student = 0,
    'ALTER TABLE users ADD CONSTRAINT fk_users_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE add_fk_users_student_stmt FROM @add_fk_users_student_sql;
EXECUTE add_fk_users_student_stmt;
DEALLOCATE PREPARE add_fk_users_student_stmt;

SET @has_fk_users_teacher := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND constraint_name = 'fk_users_teacher'
);
SET @add_fk_users_teacher_sql := IF(
    @has_fk_users_teacher = 0,
    'ALTER TABLE users ADD CONSTRAINT fk_users_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE add_fk_users_teacher_stmt FROM @add_fk_users_teacher_sql;
EXECUTE add_fk_users_teacher_stmt;
DEALLOCATE PREPARE add_fk_users_teacher_stmt;

CREATE TABLE IF NOT EXISTS student_semester (
    student_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (student_id, semester_id),
    CONSTRAINT fk_student_semester_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_semester_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_period (
    student_id INT UNSIGNED NOT NULL,
    period_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (student_id, period_id),
    CONSTRAINT fk_student_period_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_period_period FOREIGN KEY (period_id) REFERENCES course_periods(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_scores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    recorded_by_teacher_id INT UNSIGNED NULL,
    score TINYINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_subject (student_id, subject_id),
    CONSTRAINT fk_student_scores_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_scores_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_scores_recorded_by_teacher FOREIGN KEY (recorded_by_teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET @has_recorded_by_col := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'student_scores'
      AND column_name = 'recorded_by_teacher_id'
);
SET @add_recorded_by_col_sql := IF(
    @has_recorded_by_col = 0,
    'ALTER TABLE student_scores ADD COLUMN recorded_by_teacher_id INT UNSIGNED NULL AFTER subject_id',
    'SELECT 1'
);
PREPARE add_recorded_by_col_stmt FROM @add_recorded_by_col_sql;
EXECUTE add_recorded_by_col_stmt;
DEALLOCATE PREPARE add_recorded_by_col_stmt;

SET @has_fk_student_scores_recorded_by_teacher := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'student_scores'
      AND constraint_name = 'fk_student_scores_recorded_by_teacher'
);
SET @add_fk_student_scores_recorded_by_teacher_sql := IF(
    @has_fk_student_scores_recorded_by_teacher = 0,
    'ALTER TABLE student_scores ADD CONSTRAINT fk_student_scores_recorded_by_teacher FOREIGN KEY (recorded_by_teacher_id) REFERENCES teachers(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE add_fk_student_scores_recorded_by_teacher_stmt FROM @add_fk_student_scores_recorded_by_teacher_sql;
EXECUTE add_fk_student_scores_recorded_by_teacher_stmt;
DEALLOCATE PREPARE add_fk_student_scores_recorded_by_teacher_stmt;

CREATE TABLE IF NOT EXISTS student_behaviors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    entry_type ENUM('violation', 'merit') NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_behaviors_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_behaviors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    entry_type ENUM('violation', 'merit') NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_teacher_behaviors_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO study_levels (code, name) VALUES
    ('aali', 'عالی'),
    ('moteseta', 'متوسطه'),
    ('ebtedai', 'ابتداییه')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO semesters (number) VALUES (1), (2), (3), (4), (13), (14)
ON DUPLICATE KEY UPDATE number = VALUES(number);

INSERT INTO course_periods (number) VALUES (1), (2), (3), (4), (5), (6)
ON DUPLICATE KEY UPDATE number = VALUES(number);
