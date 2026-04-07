<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('user_dashboard.php');
}

if (is_post() && isset($_POST['signup'])) {
    remember_input($_POST);
    $result = register_student($_POST);

    if ($result['success']) {
        forget_input();
        flash('success', $result['message'] . ' Please log in.');
        redirect('login.php');
    }

    flash('error', $result['message']);
}

$errorMessage = flash('error');
$departments = departments_all();
$borrowLimitInvalidAttempts = (int) ($_SESSION['borrow_limit_invalid_attempts'] ?? 0);
$borrowLimitWarningMessage = borrow_limit_validation_message($borrowLimitInvalidAttempts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/signup.css')) ?>">
</head>
<body>
<div class="overlay"></div>
<div class="signup-container">
    <div class="logo-area">
        <img src="<?= e(url('assets/images/image.png')) ?>" alt="BIPE Library Management System" style="height: 96px; width: auto; display: block; margin: 0 auto 16px; border-radius: 20px; background: #fff; padding: 10px 12px; box-shadow: 0 16px 30px rgba(0, 0, 0, 0.25);">
        <h2>Create your account</h2>
        <p>Fill in your details to start using the BIPE library portal.</p>
    </div>

    <?php if ($errorMessage): ?>
        <p style="color: #f87171; text-align: center; margin-bottom: 15px; font-size: 13px;">
            <?= e($errorMessage) ?>
        </p>
    <?php endif; ?>

    <form id="signup-form" method="POST" action="<?= e(url('signup.php')) ?>">
        <div class="form-grid">
            <div class="input-group">
                <input type="text" name="name" placeholder="Name" value="<?= e(old('name')) ?>" required>
            </div>
            <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" value="<?= e(old('email')) ?>" required>
            </div>
            <div class="input-group">
                <input type="text" name="course" placeholder="Course" value="<?= e(old('course')) ?>" required>
            </div>
            <div class="input-group">
                <input type="text" name="semester" placeholder="Semester" value="<?= e(old('semester')) ?>" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="input-group">
                <input type="number" min="1" max="3" id="borrow-limit" name="no_book_issued" placeholder="Borrow Limit" value="<?= e(old('no_book_issued', '3')) ?>" required>
                <span class="field-hint">Allowed range: 1 to 3</span>
                <p id="borrow-limit-warning" class="field-warning" aria-live="polite"><?= e($borrowLimitWarningMessage) ?></p>
            </div>
            <div class="input-group select-wrapper input-group-wide">
                <?php $currentDepartmentId = old('department_id'); ?>
                <select name="department_id" required>
                    <option value="" disabled <?= $currentDepartmentId === '' ? 'selected' : '' ?>>Select Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= e((string) $department['id']) ?>" <?= selected((string) $department['id'], $currentDepartmentId) ?>>
                            <?= e($department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="select-caption">Choose your department</span>
            </div>
        </div>

        <button type="submit" name="signup">Sign Up</button>
    </form>

    <div class="login-link">
        Already have an account? <a href="<?= e(url('login.php')) ?>">Sign in</a>
    </div>
</div>
<script>
const borrowLimitInput = document.getElementById('borrow-limit');
const borrowLimitWarning = document.getElementById('borrow-limit-warning');
let borrowLimitAttempts = <?= json_encode($borrowLimitInvalidAttempts) ?>;

const getBorrowLimitMessage = (attempts) => {
    if (attempts > 6) {
        return 'Bhai, is field me sirf 1, 2, ya 3 hi chalega.';
    }

    if (attempts >= 6) {
        return 'Bhai, ab bhi galat value aa rahi hai. 1 se 3 ke beech hi rakho.';
    }

    if (attempts >= 3) {
        return 'Bhai, ek baar aur clear kar deta hoon: borrow limit sirf 1, 2, ya 3 hi hai.';
    }

    return 'Borrow limit 4 ya usse zyada allowed nahi hai. Please 1, 2, ya 3 use karein.';
};

if (borrowLimitInput && borrowLimitWarning) {
    const syncBorrowLimitState = (countAttempt = false) => {
        const value = borrowLimitInput.value.trim();
        const isInvalid = value !== '' && Number(value) >= 4;

        if (isInvalid && countAttempt) {
            borrowLimitAttempts += 1;
        }

        borrowLimitWarning.textContent = getBorrowLimitMessage(borrowLimitAttempts);
        borrowLimitWarning.classList.toggle('is-visible', isInvalid);
        borrowLimitInput.classList.toggle('is-invalid', isInvalid);
        borrowLimitInput.setCustomValidity(isInvalid ? getBorrowLimitMessage(borrowLimitAttempts) : '');
    };

    borrowLimitInput.addEventListener('input', () => syncBorrowLimitState(false));
    borrowLimitInput.addEventListener('change', () => syncBorrowLimitState(true));
    syncBorrowLimitState();
}
</script>
</body>
</html>
