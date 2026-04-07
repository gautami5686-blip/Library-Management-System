<?php

declare(strict_types=1);

function ensure_schema(mysqli $db): void
{
    static $ready = false;

    if ($ready) {
        return;
    }

    $statements = [
        "CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS student_table (
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
        )",
        "CREATE TABLE IF NOT EXISTS books_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            Title VARCHAR(180) NOT NULL,
            Author VARCHAR(160) NOT NULL,
            Department VARCHAR(120) NOT NULL,
            Description TEXT NULL,
            Total_copies INT NOT NULL DEFAULT 1,
            Available_copies INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS issued_books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            student_id INT NOT NULL,
            user_email VARCHAR(190) NOT NULL,
            book_title VARCHAR(180) NOT NULL,
            author VARCHAR(160) NOT NULL,
            issue_date DATE NOT NULL,
            due_date DATE NOT NULL,
            return_date DATE NULL,
            status ENUM('active', 'returned', 'overdue') NOT NULL DEFAULT 'active',
            renew_count INT NOT NULL DEFAULT 0,
            fine_generated TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_issued_book FOREIGN KEY (book_id) REFERENCES books_table(id) ON DELETE RESTRICT,
            CONSTRAINT fk_issued_student FOREIGN KEY (student_id) REFERENCES student_table(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS book_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            student_id INT NOT NULL,
            user_email VARCHAR(190) NOT NULL,
            status ENUM('waitlisted', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'waitlisted',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_request_book FOREIGN KEY (book_id) REFERENCES books_table(id) ON DELETE CASCADE,
            CONSTRAINT fk_request_student FOREIGN KEY (student_id) REFERENCES student_table(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS fines (
            id INT AUTO_INCREMENT PRIMARY KEY,
            issue_id INT NULL,
            student_id INT NOT NULL,
            user_email VARCHAR(190) NOT NULL,
            fine_reason VARCHAR(255) NOT NULL,
            fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
            fine_date DATE NOT NULL,
            paid_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_fine_issue FOREIGN KEY (issue_id) REFERENCES issued_books(id) ON DELETE SET NULL,
            CONSTRAINT fk_fine_student FOREIGN KEY (student_id) REFERENCES student_table(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('new', 'read') NOT NULL DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS remember_tokens (
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
        )",
    ];

    foreach ($statements as $statement) {
        $db->query($statement);
    }

    ensure_department_relations($db);
    cleanup_legacy_book_columns($db);
    seed_default_data();
    $ready = true;
}

function default_departments(): array
{
    return [
        'Civil Engineering',
        'Computer Science & Engineering',
        'Electrical Engineering',
        'General',
        'Humanities',
        'Mechanical Engineering',
    ];
}

function schema_column_exists(string $table, string $column): bool
{
    return (int) db_value(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        [$table, $column]
    ) > 0;
}

function schema_index_exists(string $table, string $index): bool
{
    return (int) db_value(
        'SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
        [$table, $index]
    ) > 0;
}

function schema_foreign_key_exists(string $table, string $constraint): bool
{
    return (int) db_value(
        "SELECT COUNT(*) FROM information_schema.table_constraints
         WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY'",
        [$table, $constraint]
    ) > 0;
}

function seed_departments(): void
{
    $departmentCount = (int) db_value('SELECT COUNT(*) FROM departments');

    if ($departmentCount > 0) {
        return;
    }

    foreach (default_departments() as $departmentName) {
        db_execute('INSERT IGNORE INTO departments (name) VALUES (?)', [$departmentName]);
    }
}

function ensure_department_relations(mysqli $db): void
{
    $studentDepartmentIdAdded = false;
    $bookDepartmentIdAdded = false;

    seed_departments();

    if (!schema_column_exists('student_table', 'department_id')) {
        $db->query('ALTER TABLE student_table ADD COLUMN department_id INT NULL AFTER Department');
        $studentDepartmentIdAdded = true;
    }

    if (!schema_column_exists('books_table', 'department_id')) {
        $db->query('ALTER TABLE books_table ADD COLUMN department_id INT NULL AFTER Department');
        $bookDepartmentIdAdded = true;
    }

    if ($studentDepartmentIdAdded || $bookDepartmentIdAdded) {
        $departmentNames = db_all(
            "SELECT department_name FROM (
                SELECT DISTINCT TRIM(Department) AS department_name FROM student_table
                UNION
                SELECT DISTINCT TRIM(Department) AS department_name FROM books_table
            ) AS departments_source
            WHERE department_name <> ''"
        );

        foreach ($departmentNames as $departmentRow) {
            db_execute('INSERT IGNORE INTO departments (name) VALUES (?)', [$departmentRow['department_name']]);
        }
    }

    $db->query(
        "UPDATE student_table s
         INNER JOIN departments d ON d.name = s.Department
         SET s.department_id = d.id
         WHERE s.Department <> '' AND (s.department_id IS NULL OR s.department_id <> d.id)"
    );

    $db->query(
        "UPDATE books_table b
         INNER JOIN departments d ON d.name = b.Department
         SET b.department_id = d.id
         WHERE b.Department <> '' AND (b.department_id IS NULL OR b.department_id <> d.id)"
    );

    $db->query(
        "UPDATE student_table s
         INNER JOIN departments d ON d.id = s.department_id
         SET s.Department = d.name
         WHERE s.department_id IS NOT NULL AND s.Department <> d.name"
    );

    $db->query(
        "UPDATE books_table b
         INNER JOIN departments d ON d.id = b.department_id
         SET b.Department = d.name
         WHERE b.department_id IS NOT NULL AND b.Department <> d.name"
    );

    if (!schema_index_exists('student_table', 'idx_student_department_id')) {
        $db->query('ALTER TABLE student_table ADD INDEX idx_student_department_id (department_id)');
    }

    if (!schema_index_exists('books_table', 'idx_books_department_id')) {
        $db->query('ALTER TABLE books_table ADD INDEX idx_books_department_id (department_id)');
    }

    if (!schema_foreign_key_exists('student_table', 'fk_student_department')) {
        $db->query(
            'ALTER TABLE student_table ADD CONSTRAINT fk_student_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL'
        );
    }

    if (!schema_foreign_key_exists('books_table', 'fk_book_department')) {
        $db->query(
            'ALTER TABLE books_table ADD CONSTRAINT fk_book_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL'
        );
    }
}

function cleanup_legacy_book_columns(mysqli $db): void
{
    if (schema_column_exists('issued_books', 'isbn')) {
        $db->query('ALTER TABLE issued_books DROP COLUMN isbn');
    }

    if (schema_column_exists('books_table', 'Cover_Image')) {
        $db->query('ALTER TABLE books_table DROP COLUMN Cover_Image');
    }

    if (schema_column_exists('books_table', 'Category')) {
        $db->query('ALTER TABLE books_table DROP COLUMN Category');
    }

    if (schema_column_exists('books_table', 'ISBN')) {
        $db->query('ALTER TABLE books_table DROP COLUMN ISBN');
    }
}

function seed_default_data(): void
{
    $adminCount = (int) db_value('SELECT COUNT(*) FROM admins');

    if ($adminCount === 0) {
        db_execute(
            'INSERT INTO admins (name, email, password) VALUES (?, ?, ?)',
            ['Library Admin', 'admin@lms.com', '$2y$12$mkQ9bGIeavKGbWLs4Q3tJe2hRyCHx3BSnbvlcSdeboLr1V39EyZOG']
        );
    }

    $bookCount = (int) db_value('SELECT COUNT(*) FROM books_table');

    if ($bookCount > 0) {
        return;
    }

    $books = [
        ['The Art of Innovation', 'Alexander Pierce', 'Computer Science & Engineering', 'A practical guide to innovation systems and product thinking.', 6, 6],
        ['Modern Architecture', 'Elena Rodriguez', 'Civil Engineering', 'Visual thinking around form, structure, and modern design.', 4, 4],
        ['The Silent Cosmos', 'Dr. Julian Vance', 'Electrical Engineering', 'A readable introduction to astronomy and exploration.', 5, 5],
        ['Thoughts of Antiquity', 'Marcus Aurelius', 'Humanities', 'Classical philosophy for deep reflective reading.', 3, 3],
        ['The Quantum Era', 'Sarah Jenkins', 'Electrical Engineering', 'Emerging technologies, physics, and computing futures.', 4, 4],
        ['Whispers of the Wind', 'Claire Beauchamp', 'General', 'A literary fiction title included to match the current design set.', 5, 5],
    ];

    foreach ($books as $book) {
        $department = department_by_name($book[2]);

        db_execute(
            'INSERT INTO books_table (Title, Author, Department, department_id, Description, Total_copies, Available_copies) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $book[0],
                $book[1],
                $book[2],
                (int) ($department['id'] ?? 0),
                $book[3],
                $book[4],
                $book[5],
            ]
        );
    }
}

