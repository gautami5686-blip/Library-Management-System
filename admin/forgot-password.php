<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (current_admin()) {
    redirect('admin/dashboard.php');
}

$user = current_user();
$admin = current_admin();

if (is_post()) {
    $email = trim((string) ($_POST['email'] ?? ''));
    remember_input(['email' => $email]);

    if ($email === '') {
        flash('error', 'Please enter your admin email.');
    } else {
        forget_input();
        flash('success', 'Reset email service is not configured yet. Please contact BIPE support at bipe@gmail.com for admin password help.');
        redirect('admin/forgot-password.php');
    }
}

$errorMessage = flash('error');
$successMessage = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Admin LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/login.css')) ?>">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="overlay"></div>
<main class="auth-page">
    <div class="login-container">
        <div class="logo-area">
            <img src="<?= e(url('assets/images/image.png')) ?>" alt="BIPE Library Management System" class="auth-brand-mark">
            <h2>Forgot Password</h2>
            <p style="max-width: 320px; margin: 10px auto 0; line-height: 1.6;">Enter your admin email. This project does not have email reset configured yet, so we will guide you to support.</p>
        </div>

        <?php if ($errorMessage || $successMessage): ?>
            <p style="color: <?= $errorMessage ? '#f87171' : '#d4af37' ?>; text-align: center; margin-bottom: 18px; font-size: 14px; line-height: 1.6;">
                <?= e($errorMessage ?: $successMessage) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('admin/forgot-password.php')) ?>">
            <div class="input-group">
                <input type="email" name="email" placeholder="Admin Email" value="<?= e(old('email')) ?>" required>
            </div>
            <button type="submit">Request Help</button>
        </form>

        <div class="signup-link" style="line-height: 1.8;">
            <a href="<?= e(url('admin/login.php')) ?>">Back to Admin Login</a><br>
            <a href="<?= e(url('contact.php')) ?>">Contact Support</a>
        </div>
    </div>
</main>
</body>
</html>
