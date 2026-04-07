CREATE DATABASE IF NOT EXISTS `library` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `library`;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS student_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(120) NOT NULL,
    Course VARCHAR(120) NOT NULL,
    Semester VARCHAR(50) NOT NULL,
    Email_Address VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    No_Books_issued INT NOT NULL DEFAULT 3,
    Department VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



CREATE TABLE IF NOT EXISTS books_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(180) NOT NULL,
    Author VARCHAR(160) NOT NULL,
    Department VARCHAR(120) NOT NULL,
    department_id INT NULL,
    Description TEXT NULL,
    Total_copies INT NOT NULL DEFAULT 1,
    Available_copies INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS issued_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    user_email VARCHAR(190) NOT NULL,
    book_title VARCHAR(180) NOT NULL,
    author VARCHAR(160) NOT NULL,
    isbn VARCHAR(40) NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('active', 'returned', 'overdue') NOT NULL DEFAULT 'active',
    renew_count INT NOT NULL DEFAULT 0,
    fine_generated TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS issued_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    user_email VARCHAR(190) NOT NULL,
    book_title VARCHAR(180) NOT NULL,
    author VARCHAR(160) NOT NULL,
    isbn VARCHAR(40) NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('active', 'returned', 'overdue') NOT NULL DEFAULT 'active',
    renew_count INT NOT NULL DEFAULT 0,
    fine_generated TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NULL,
    student_id INT NOT NULL,
    user_email VARCHAR(190) NOT NULL,
    fine_reason VARCHAR(255) NOT NULL,
    fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    fine_date DATE NOT NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('student', 'admin') NOT NULL,
    user_id INT NOT NULL,
    selector CHAR(32) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_remember_user (user_type, user_id),
    INDEX idx_remember_expiry (expires_at)
);

CREATE TABLE departments (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE (name)
);


INSERT INTO admins (name, email, password)
SELECT 'Library Admin', 'admin@lms.com', '$2y$12$mkQ9bGIeavKGbWLs4Q3tJe2hRyCHx3BSnbvlcSdeboLr1V39EyZOG'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE email = 'admin@lms.com');
