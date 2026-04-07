<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$user = current_user();
$admin = current_admin();
$navLinks = [
    ['label' => 'Home', 'href' => url('index.php')],
    ['label' => 'Catalog', 'href' => url('catalog.php')],
    ['label' => 'Contact', 'href' => url('contact.php')],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(url('assets/css/about.css')) ?>">
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

    <section class="hero">
        <h1>Our Library Management System</h1>
        <p>Empowering readers with technology & efficiency</p>
    </section>

    <section class="about-content">
        <h2>Who We Are</h2>
        <p class="about-text">
            We are a teams of innovators passionate about making libraries smarter.
            Our Library Management System is designed to simplify book management, enhance user experience, and promote a culture of reading in the digital age.
        </p>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-search"></i></div>
                <h3>Smart Search</h3>
                <p>Find books instantly with searchable catalog data and department-specific browsing.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Deep Analytics</h3>
                <p>Track borrowing trends, fines, issued books, and library inventory through real-time admin dashboards.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-lightbulb"></i></div>
                <h3>Innovation</h3>
                <p>We connect elegant design with functional backend workflows so the experience remains beautiful and practical.</p>
            </div>
        </div>

        <div class="vision-wrapper">
            <button id="visionBtn">Reveal Our Vision</button>
            <div id="visionText">
                <i class="fas fa-quote-left" style="color: #D4AF37; margin-right: 10px; font-size: 24px;"></i>
                To create a seamless digital ecosystem where knowledge flows freely, and every reader feels empowered to learn, explore, and grow.
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
        const visionBtn = document.getElementById('visionBtn');
        const visionText = document.getElementById('visionText');

        if (visionBtn && visionText) {
            visionBtn.addEventListener('click', () => {
                visionText.classList.toggle('show');
            });
        }
    </script>
</body>
</html>
