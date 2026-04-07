<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = current_user();
$admin = current_admin();
$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(url('assets/css/index.css')) ?>">
</head>
<body>
    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <header class="header">
        <a href="<?= e(url('index.php')) ?>" class="logo">
            <img src="<?= e(url('assets/images/image.png')) ?>" alt="BIPE Library Management System" style="height: 56px; width: auto; display: block; border-radius: 14px; background: #fff; padding: 6px 10px; box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);">
        </a>
        <nav>
            <a href="<?= e(url('index.php')) ?>" class="active">Home</a>
            <a href="<?= e(url('catalog.php')) ?>">Catalog</a>
            <a href="<?= e(url('about.php')) ?>">About</a>
            <a href="<?= e(url('contact.php')) ?>">Contact</a>
            <div class="nav-auth">
                <?php if ($user): ?>
                    <a href="<?= e(url('user_dashboard.php')) ?>" class="btn-nav-login">Dashboard</a>
                    <a href="<?= e(url('logout.php')) ?>" class="btn-nav-signup">Logout</a>
                <?php else: ?>
                    <a href="<?= e(url('login.php')) ?>" class="btn-nav-login">Login</a>
                    <a href="<?= e(url('signup.php')) ?>" class="btn-nav-signup">Sign Up</a>
                <?php endif; ?>

                <?php if ($admin): ?>
                    <a href="<?= e(url('admin/dashboard.php')) ?>" class="btn-nav-admin">Admin Panel</a>
                <?php else: ?>
                    <a href="<?= e(url('admin/login.php')) ?>" class="btn-nav-admin">Admin</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <section class="hero">
        <?php if ($successMessage || $errorMessage): ?>
            <div style="max-width: 680px; margin-bottom: 24px; background: <?= $errorMessage ? 'rgba(230,57,70,0.12)' : 'rgba(212,175,55,0.12)' ?>; border: 1px solid <?= $errorMessage ? 'rgba(230,57,70,0.35)' : 'rgba(212,175,55,0.35)' ?>; color: #fff; padding: 14px 18px; border-radius: 14px; backdrop-filter: blur(10px);">
                <?= e($errorMessage ?: $successMessage) ?>
            </div>
        <?php endif; ?>
        <h1>Elevate Your Reading Experience</h1>
        <p>Your library is not just a place—it’s a gateway to endless possibilities.</p>
        <div class="hero-btns">
            <a href="<?= e(url('catalog.php')) ?>" class="btn-primary">Explore Catalog</a>
        </div>
    </section>

    <section class="section-padding">
        <div class="section-header">
            <p>Unmatched Capabilities</p>
            <h2>Library Management, Perfected.</h2>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-book feature-icon"></i>
                <h3>Book Management</h3>
                <p>Effortlessly add, categorize, and organize your entire library inventory with our intuitive digital cataloging system.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-user-tie feature-icon"></i>
                <h3>Member Records</h3>
                <p>Maintain secure reader profiles, track borrowing activity, and keep academic details up to date.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-exchange-alt feature-icon"></i>
                <h3>Issue & Return</h3>
                <p>Track live issues, renewals, overdue books, and returns with a real MySQL-backed workflow.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chart-pie feature-icon"></i>
                <h3>Smart Reports</h3>
                <p>Give admins a clear view of books, students, fines, requests, and contact messages from one place.</p>
            </div>
        </div>
    </section>

    <section class="section-padding" style="background: rgba(0,0,0,0.2); backdrop-filter: blur(5px);">
        <div class="section-header">
            <p>Featured Selections</p>
            <h2>Curated Collections</h2>
        </div>
        <div class="books-grid">
            <div class="book-card">
                <div class="book-info">
                    <h3>The Art of Innovation</h3>
                    <p>Business & Strategy</p>
                    <a href="<?= e(url('catalog.php?search=Innovation')) ?>" class="book-btn" style="display: inline-block; text-decoration: none;">Borrow Book</a>
                </div>
            </div>
            <div class="book-card">
                <div class="book-info">
                    <h3>Modern Architecture</h3>
                    <p>Art & Design</p>
                    <a href="<?= e(url('catalog.php?search=Architecture')) ?>" class="book-btn" style="display: inline-block; text-decoration: none;">Borrow Book</a>
                </div>
            </div>
            <div class="book-card">
                <div class="book-info">
                    <h3>The Silent Cosmos</h3>
                    <p>Science & Astronomy</p>
                    <a href="<?= e(url('catalog.php?search=Cosmos')) ?>" class="book-btn" style="display: inline-block; text-decoration: none;">Borrow</a>
                </div>
            </div>
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

    <script>
        window.addEventListener('scroll', () => {
            const header = document.querySelector('.header');
            if (!header) {
                return;
            }

            if (window.scrollY > 50) {
                header.style.background = 'rgba(10, 25, 47, 0.9)';
                header.style.boxShadow = '0 5px 20px rgba(0,0,0,0.3)';
            } else {
                header.style.background = 'rgba(10, 25, 47, 0.5)';
                header.style.boxShadow = 'none';
            }
        });
    </script>
</body>
</html>
