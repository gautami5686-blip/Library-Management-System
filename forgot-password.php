<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('user_dashboard.php');
}

if (current_admin()) {
    redirect('admin/dashboard.php');
}

if (is_post()) {
    $email = (string) ($_POST['email'] ?? '');
    remember_input(['email' => $email]);

    $result = reset_student_password(
        $email,
        (string) ($_POST['password'] ?? ''),
        (string) ($_POST['confirm_password'] ?? '')
    );

    if ($result['success']) {
        forget_input();
        flash('success', $result['message']);
        redirect('login.php');
    }

    flash('error', $result['message']);
}

$errorMessage = flash('error');
$successMessage = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/login.css')) ?>">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="overlay"></div>
<main class="auth-page">
    <div class="login-container">
        <div class="logo-area">
            <img src="<?= e(url('assets/images/image.png')) ?>" alt="BIPE Library Management System" class="auth-brand-mark">
            <h2>Forgot Password</h2>
            <p style="max-width: 320px; margin: 10px auto 0; line-height: 1.6;">
                Enter your registered student email and set a new password. Your password will be updated directly in the database.
            </p>
        </div>

        <?php if ($errorMessage || $successMessage): ?>
            <p style="color: <?= $errorMessage ? '#f87171' : '#d4af37' ?>; text-align: center; margin-bottom: 18px; font-size: 14px; line-height: 1.6;">
                <?= e($errorMessage ?: $successMessage) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('forgot-password.php')) ?>">
            <div class="input-group">
                <input type="email" name="email" placeholder="Registered Email Address" value="<?= e(old('email')) ?>" required>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="New Password" required>
            </div>

            <div class="input-group">
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            </div>

            <button type="submit">Update Password</button>
            <a href="<?= e(url('login.php')) ?>" class="secondary-action-link">Back to Login</a>
        </form>

        <div class="signup-link" style="line-height: 1.8;">
            Need a new account? <a href="<?= e(url('signup.php')) ?>">Create one</a>
        </div>
    </div>
</main>
</body>
</html>
