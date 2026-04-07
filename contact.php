<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = current_user();
$admin = current_admin();
$navLinks = [
    ['label' => 'Home', 'href' => url('index.php')],
    ['label' => 'About', 'href' => url('about.php')],
    ['label' => 'Catalog', 'href' => url('catalog.php')],
];

if (is_post()) {
    remember_input($_POST);
    $result = save_contact_message(
        (string) ($_POST['name'] ?? ''),
        (string) ($_POST['email'] ?? ''),
        (string) ($_POST['message'] ?? '')
    );

    if ($result['success']) {
        forget_input();
        flash('success', $result['message']);
        redirect('contact.php');
    }

    flash('error', $result['message']);
}

$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(url('assets/css/contact.css')) ?>">
</head>
<body>
    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <header class="header">
        <a href="<?= e(url('index.php')) ?>" class="logo">
            <img src="<?= e(url('assets/images/image.png')) ?>" alt="BIPE Library Management System" style="height: 56px; width: auto; display: block; border-radius: 14px; background: #fff; padding: 6px 10px; box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);">
        </a>
        <nav>
            <?php foreach ($navLinks as $link): ?>
                <a href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a>
            <?php endforeach; ?>
            <?php if ($user): ?>
                <div class="nav-auth">
                    <a href="<?= e(url('user_dashboard.php')) ?>" class="btn-nav-login">Dashboard</a>
                    <?php if ($admin): ?>
                        <a href="<?= e(url('admin/dashboard.php')) ?>" class="btn-nav-admin">Admin Panel</a>
                    <?php else: ?>
                        <a href="<?= e(url('admin/login.php')) ?>" class="btn-nav-admin">Admin</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="nav-auth">
                    <a href="<?= e(url('login.php')) ?>" class="btn-nav-login">Login</a>
                    <a href="<?= e(url('signup.php')) ?>" class="btn-nav-signup">Sign Up</a>
                    <?php if ($admin): ?>
                        <a href="<?= e(url('admin/dashboard.php')) ?>" class="btn-nav-admin">Admin Panel</a>
                    <?php else: ?>
                        <a href="<?= e(url('admin/login.php')) ?>" class="btn-nav-admin">Admin</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </nav>
    </header>

    <section class="hero contact-hero">
        <h1>Get in Touch</h1>
        <p>We're here to help you elevate your library experience</p>
    </section>

    <section class="contact-section">
        <div class="contact-info glass-card">
            <h2>Contact Details</h2>
            <div class="info-item">
                <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="info-content">
                    <h3>Address</h3>
                    <p>Gajokhar, Pindra, Varanasi, Uttar Pradesh, India</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                <div class="info-content">
                    <h3>Phone</h3>
                    <p>+91 2234567654<br><span style="color: var(--text-muted); font-size: 14px;">Mon - Fri, 9:00 AM - 6:00 PM</span></p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <div class="info-content">
                    <h3>Email</h3>
                    <p>bipe@gmail.com<br>info@library.com</p>
                </div>
            </div>
        </div>

        <div class="contact-form glass-card">
            <h2>Send Us a Message</h2>
            <form id="contactForm" method="POST" action="<?= e(url('contact.php')) ?>">
                <div class="form-group">
                    <input type="text" name="name" class="form-control" placeholder="Your Full Name" value="<?= e(old('name')) ?>" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Your Email Address" value="<?= e(old('email')) ?>" required>
                </div>
                <div class="form-group">
                    <textarea name="message" class="form-control" placeholder="How can we assist you today?" required><?= e(old('message')) ?></textarea>
                </div>
                <button type="submit" class="btn-submit">Send Message <i class="fas fa-paper-plane" style="margin-left: 8px;"></i></button>
            </form>

            <?php if ($successMessage || $errorMessage): ?>
                <div id="formResponse" style="display:flex; margin-top: 25px;">
                    <i class="fas <?= $successMessage ? 'fa-check-circle' : 'fa-exclamation-circle' ?>" style="font-size: 24px; color: <?= $successMessage ? 'var(--accent-gold)' : '#f87171' ?>;"></i>
                    <div>
                        <strong><?= $successMessage ? 'Thank you!' : 'Submission failed' ?></strong><br>
                        <?= e($successMessage ?: $errorMessage) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-about">
                <p>Manage books, empower members, and streamline records efficiently with our state-of-the-art digital library management system.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h3>Quick Links</h3>
                <a href="<?= e(url('index.php')) ?>">Home</a>
                <a href="<?= e(url('catalog.php')) ?>">Catalog Search</a>
                <a href="<?= e(url('about.php')) ?>">About the System</a>
                <a href="<?= e(url('contact.php')) ?>">Contact Support</a>
            </div>
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <p><i class="fas fa-envelope"></i> bipe@gmail.com</p>
                <p><i class="fas fa-phone-alt"></i> +91 2234567654</p>
                <p><i class="fas fa-map-marker-alt"></i> Gajokhar, Pindra, Varanasi, Uttar Pradesh, India</p>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2026 Library Management System | All Rights Reserved.
        </div>
    </footer>
</body>
</html>
