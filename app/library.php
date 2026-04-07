<?php

declare(strict_types=1);

function sync_overdue_items(): void
{
    $issues = db_all(
        "SELECT id, student_id, user_email, due_date
         FROM issued_books
         WHERE status = 'active' AND due_date < CURDATE()"
    );

    foreach ($issues as $issue) {
        db_execute("UPDATE issued_books SET status = 'overdue' WHERE id = ?", [(int) $issue['id']], 'i');

        $existingFine = (int) db_value(
            "SELECT COUNT(*) FROM fines WHERE issue_id = ? AND status = 'unpaid'",
            [(int) $issue['id']],
            'i'
        );

        if ($existingFine > 0) {
            continue;
        }

        $daysLate = max(1, (int) db_value('SELECT DATEDIFF(CURDATE(), ?) AS days_late', [$issue['due_date']]));
        $amount = $daysLate * 10;

        db_execute(
            'INSERT INTO fines (issue_id, student_id, user_email, fine_reason, fine_amount, status, fine_date) VALUES (?, ?, ?, ?, ?, ?, CURDATE())',
            [
                (int) $issue['id'],
                (int) $issue['student_id'],
                $issue['user_email'],
                'Late return fine',
                (float) $amount,
                'unpaid',
            ],
            'iissds'
        );

        db_execute('UPDATE issued_books SET fine_generated = 1 WHERE id = ?', [(int) $issue['id']], 'i');
    }
}

function borrow_limit_validation_message(int $attempts = 0): string
{
    if ($attempts > 6) {
        return 'Bhai, is field me sirf 1, 2, ya 3 hi chalega.';
    }

    if ($attempts >= 6) {
        return 'Bhai, ab bhi galat value aa rahi hai. 1 se 3 ke beech hi rakho.';
    }

    if ($attempts >= 3) {
        return 'Bhai, ek baar aur clear kar deta hoon: borrow limit sirf 1, 2, ya 3 hi hai.';
    }

    return 'Borrow limit 4 ya usse zyada allowed nahi hai. Please 1, 2, ya 3 use karein.';
}

function increment_borrow_limit_invalid_attempts(): int
{
    $_SESSION['borrow_limit_invalid_attempts'] = (int) ($_SESSION['borrow_limit_invalid_attempts'] ?? 0) + 1;

    return (int) $_SESSION['borrow_limit_invalid_attempts'];
}

function reset_borrow_limit_invalid_attempts(): void
{
    unset($_SESSION['borrow_limit_invalid_attempts']);
}

function register_student(array $input): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $course = trim((string) ($input['course'] ?? ''));
    $semester = trim((string) ($input['semester'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    $department = resolve_department($input);
    $borrowLimit = max(1, (int) ($input['no_book_issued'] ?? 3));

    if ($borrowLimit >= 4) {
        return [
            'success' => false,
            'message' => borrow_limit_validation_message(increment_borrow_limit_invalid_attempts()),
        ];
    }

    reset_borrow_limit_invalid_attempts();

    if ($name === '' || $course === '' || $semester === '' || $email === '' || $password === '' || !$department) {
        return ['success' => false, 'message' => 'Please fill in all required fields.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    $existing = db_one('SELECT id FROM student_table WHERE Email_Address = ?', [$email]);

    if ($existing) {
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }

    db_execute(
        'INSERT INTO student_table (Name, Course, Semester, Email_Address, password, No_Books_issued, Department, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$name, $course, $semester, $email, password_hash($password, PASSWORD_DEFAULT), $borrowLimit, $department['name'], (int) $department['id']]
    );

    return ['success' => true, 'message' => 'Account created successfully.'];
}

function authenticate_student(string $email, string $password): ?array
{
    $student = db_one('SELECT * FROM student_table WHERE Email_Address = ?', [strtolower(trim($email))]);

    if (!$student) {
        return null;
    }

    $storedPassword = (string) $student['password'];
    $isHash = (password_get_info($storedPassword)['algo'] ?? null) !== null;
    $isValid = $isHash ? password_verify($password, $storedPassword) : hash_equals($storedPassword, $password);

    if (!$isValid) {
        return null;
    }

    if (!$isHash || password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        db_execute('UPDATE student_table SET password = ? WHERE id = ?', [$newHash, (int) $student['id']], 'si');
        $student['password'] = $newHash;
    }

    return $student;
}

function authenticate_admin(string $email, string $password): ?array
{
    $admin = db_one('SELECT * FROM admins WHERE email = ?', [strtolower(trim($email))]);

    if (!$admin) {
        return null;
    }

    return password_verify($password, (string) $admin['password']) ? $admin : null;
}

function save_contact_message(string $name, string $email, string $message): array
{
    $name = trim($name);
    $email = strtolower(trim($email));
    $message = trim($message);

    if ($name === '' || $email === '' || $message === '') {
        return ['success' => false, 'message' => 'Please complete the contact form before submitting.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please provide a valid email address.'];
    }

    db_execute(
        'INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)',
        [$name, $email, $message]
    );

    return ['success' => true, 'message' => 'Your message has been sent successfully.'];
}

function get_student_active_issue_count(int $studentId): int
{
    return (int) db_value(
        "SELECT COUNT(*) FROM issued_books WHERE student_id = ? AND status IN ('active', 'overdue')",
        [$studentId],
        'i'
    );
}

function reserve_book(int $bookId, array $student): array
{
    $book = db_one('SELECT * FROM books_table WHERE id = ?', [$bookId], 'i');

    if (!$book) {
        return ['success' => false, 'message' => 'Selected book could not be found.'];
    }

    $existingIssue = (int) db_value(
        "SELECT COUNT(*) FROM issued_books WHERE book_id = ? AND student_id = ? AND status IN ('active', 'overdue')",
        [$bookId, (int) $student['id']],
        'ii'
    );

    if ($existingIssue > 0) {
        return ['success' => false, 'message' => 'You already have this book in your account.'];
    }

    $existingRequest = (int) db_value(
        "SELECT COUNT(*) FROM book_requests WHERE book_id = ? AND student_id = ? AND status = 'waitlisted'",
        [$bookId, (int) $student['id']],
        'ii'
    );

    if ($existingRequest > 0) {
        return ['success' => false, 'message' => 'You are already in the waitlist for this book.'];
    }

    $activeCount = get_student_active_issue_count((int) $student['id']);
    $borrowLimit = max(1, (int) $student['No_Books_issued']);

    if ($activeCount >= $borrowLimit) {
        return ['success' => false, 'message' => 'You have reached your current borrowing limit.'];
    }

    if ((int) $book['Available_copies'] > 0) {
        return create_issue((int) $book['id'], $student);
    }

    db_execute(
        "INSERT INTO book_requests (book_id, student_id, user_email, status) VALUES (?, ?, ?, 'waitlisted')",
        [(int) $book['id'], (int) $student['id'], $student['Email_Address']],
        'iis'
    );

    return ['success' => true, 'message' => 'Book is unavailable right now. You have been added to the waitlist.'];
}

function create_issue(int $bookId, array $student, ?int $requestId = null): array
{
    $db = database_connection();
    $book = db_one('SELECT * FROM books_table WHERE id = ?', [$bookId], 'i');

    if (!$book || (int) $book['Available_copies'] < 1) {
        return ['success' => false, 'message' => 'No copies are currently available for issue.'];
    }

    $existingIssue = (int) db_value(
        "SELECT COUNT(*) FROM issued_books WHERE book_id = ? AND student_id = ? AND status IN ('active', 'overdue')",
        [$bookId, (int) $student['id']],
        'ii'
    );

    if ($existingIssue > 0) {
        return ['success' => false, 'message' => 'This student already has the selected book.'];
    }

    $activeCount = get_student_active_issue_count((int) $student['id']);
    $borrowLimit = max(1, (int) $student['No_Books_issued']);

    if ($activeCount >= $borrowLimit) {
        return ['success' => false, 'message' => 'Borrowing limit has already been reached for this student.'];
    }

    $db->begin_transaction();

    try {
        db_execute(
            "INSERT INTO issued_books (book_id, student_id, user_email, book_title, author, issue_date, due_date, status)
             VALUES (?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'active')",
            [
                (int) $book['id'],
                (int) $student['id'],
                $student['Email_Address'],
                $book['Title'],
                $book['Author'],
            ],
            'iisss'
        );

        db_execute(
            'UPDATE books_table SET Available_copies = Available_copies - 1 WHERE id = ? AND Available_copies > 0',
            [(int) $book['id']],
            'i'
        );

        if ($requestId !== null) {
            db_execute(
                "UPDATE book_requests SET status = 'fulfilled' WHERE id = ?",
                [$requestId],
                'i'
            );
        }

        $db->commit();

        return ['success' => true, 'message' => 'Book issued successfully. Due date is 14 days from today.'];
    } catch (Throwable $exception) {
        $db->rollback();

        return ['success' => false, 'message' => 'Could not issue the book right now.'];
    }
}

function renew_issue(int $issueId, int $studentId): array
{
    $issue = db_one(
        'SELECT * FROM issued_books WHERE id = ? AND student_id = ?',
        [$issueId, $studentId],
        'ii'
    );

    if (!$issue) {
        return ['success' => false, 'message' => 'Book record not found.'];
    }

    if ($issue['status'] === 'returned') {
        return ['success' => false, 'message' => 'Returned books cannot be renewed.'];
    }

    if ($issue['status'] === 'overdue') {
        return ['success' => false, 'message' => 'Please clear the overdue fine before renewing this book.'];
    }

    if ((int) $issue['renew_count'] >= 2) {
        return ['success' => false, 'message' => 'Renewal limit has already been reached for this book.'];
    }

    db_execute(
        'UPDATE issued_books SET due_date = DATE_ADD(due_date, INTERVAL 7 DAY), renew_count = renew_count + 1 WHERE id = ?',
        [$issueId],
        'i'
    );

    return ['success' => true, 'message' => 'Book renewed for another 7 days.'];
}

function mark_issue_returned(int $issueId): array
{
    $issue = db_one('SELECT * FROM issued_books WHERE id = ?', [$issueId], 'i');

    if (!$issue) {
        return ['success' => false, 'message' => 'Issue record not found.'];
    }

    if ($issue['status'] === 'returned') {
        return ['success' => false, 'message' => 'This book has already been returned.'];
    }

    $db = database_connection();
    $db->begin_transaction();

    try {
        db_execute(
            "UPDATE issued_books SET status = 'returned', return_date = CURDATE() WHERE id = ?",
            [$issueId],
            'i'
        );

        db_execute(
            'UPDATE books_table SET Available_copies = Available_copies + 1 WHERE id = ?',
            [(int) $issue['book_id']],
            'i'
        );

        $db->commit();

        return ['success' => true, 'message' => 'Book marked as returned.'];
    } catch (Throwable $exception) {
        $db->rollback();

        return ['success' => false, 'message' => 'Could not update the return right now.'];
    }
}

function pay_fine(int $fineId, ?string $userEmail = null): array
{
    $fine = db_one('SELECT * FROM fines WHERE id = ?', [$fineId], 'i');

    if (!$fine) {
        return ['success' => false, 'message' => 'Fine record not found.'];
    }

    if ($userEmail !== null && $fine['user_email'] !== $userEmail) {
        return ['success' => false, 'message' => 'You are not allowed to pay this fine.'];
    }

    db_execute(
        "UPDATE fines SET status = 'paid', paid_at = NOW() WHERE id = ?",
        [$fineId],
        'i'
    );

    return ['success' => true, 'message' => 'Fine payment has been recorded.'];
}

function pay_all_fines(string $userEmail): array
{
    db_execute(
        "UPDATE fines SET status = 'paid', paid_at = NOW() WHERE user_email = ? AND status = 'unpaid'",
        [$userEmail]
    );

    return ['success' => true, 'message' => 'All pending fines have been marked as paid.'];
}

function update_student_profile(int $studentId, array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $department = resolve_department($data);
    $course = trim((string) ($data['course'] ?? ''));
    $semester = trim((string) ($data['semester'] ?? ''));

    if ($name === '' || !$department || $course === '' || $semester === '') {
        return ['success' => false, 'message' => 'Please complete all profile fields.'];
    }

    db_execute(
        'UPDATE student_table SET Name = ?, Department = ?, department_id = ?, Course = ?, Semester = ? WHERE id = ?',
        [$name, $department['name'], (int) $department['id'], $course, $semester, $studentId],
        'ssissi'
    );

    $_SESSION['user_name'] = $name;

    return ['success' => true, 'message' => 'Your profile has been updated successfully.'];
}

function save_book(array $data, ?int $bookId = null): array
{
    $title = trim((string) ($data['title'] ?? ''));
    $author = trim((string) ($data['author'] ?? ''));
    $department = resolve_department($data);
    $description = trim((string) ($data['description'] ?? ''));
    $totalCopies = max(1, (int) ($data['total_copies'] ?? 1));

    if ($title === '' || $author === '' || !$department) {
        return ['success' => false, 'message' => 'Please fill in all book fields.'];
    }

    if ($bookId === null) {
        db_execute(
            'INSERT INTO books_table (Title, Author, Department, department_id, Description, Total_copies, Available_copies) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$title, $author, $department['name'], (int) $department['id'], $description, $totalCopies, $totalCopies]
        );

        return ['success' => true, 'message' => 'Book added successfully.'];
    }

    $book = db_one('SELECT * FROM books_table WHERE id = ?', [$bookId], 'i');

    if (!$book) {
        return ['success' => false, 'message' => 'Book could not be found.'];
    }

    $borrowedCount = max(0, (int) $book['Total_copies'] - (int) $book['Available_copies']);
    $availableCopies = max(0, $totalCopies - $borrowedCount);

    db_execute(
        'UPDATE books_table SET Title = ?, Author = ?, Department = ?, department_id = ?, Description = ?, Total_copies = ?, Available_copies = ? WHERE id = ?',
        [$title, $author, $department['name'], (int) $department['id'], $description, $totalCopies, $availableCopies, $bookId],
        'sssisiii'
    );

    return ['success' => true, 'message' => 'Book details updated successfully.'];
}

function delete_book(int $bookId): array
{
    $activeIssues = (int) db_value(
        "SELECT COUNT(*) FROM issued_books WHERE book_id = ? AND status IN ('active', 'overdue')",
        [$bookId],
        'i'
    );

    if ($activeIssues > 0) {
        return ['success' => false, 'message' => 'This book cannot be deleted while copies are issued.'];
    }

    db_execute('DELETE FROM book_requests WHERE book_id = ?', [$bookId], 'i');
    db_execute('DELETE FROM books_table WHERE id = ?', [$bookId], 'i');

    return ['success' => true, 'message' => 'Book removed successfully.'];
}

function update_student_limit(int $studentId, int $limit): array
{
    $limit = max(1, $limit);

    db_execute(
        'UPDATE student_table SET No_Books_issued = ? WHERE id = ?',
        [$limit, $studentId],
        'ii'
    );

    return ['success' => true, 'message' => 'Student borrow limit updated successfully.'];
}

function mark_message_read(int $messageId): void
{
    db_execute("UPDATE contact_messages SET status = 'read' WHERE id = ?", [$messageId], 'i');
}

