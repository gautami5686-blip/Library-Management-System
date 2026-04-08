<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$user = require_user();
$activePage = 'profile';

if (is_post() && isset($_POST['update_profile'])) {
    $result = update_student_profile((int) $user['id'], $_POST, $_FILES);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect('user_profile.php#profile-edit-form');
}

$user = require_user();
$successMessage = flash('success');
$errorMessage = flash('error');
$displayName = $user['Name'];
$departments = departments_all();
$profileImageUrl = student_profile_image_url($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(url('assets/css/dashboard.css')) ?>">
</head>
<body>
    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

    <div class="main">
        <div class="top-bar">
            <div class="welcome-msg">
                <h2>User Profile</h2>
                <p style="color: var(--text-muted);">Manage your personal and academic information.</p>
            </div>
            <div class="profile-area">
                <a href="<?= e(url('logout.php')) ?>" style="color: #E63946; font-size: 14px; font-weight: 600; margin-right: 20px; text-decoration: none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <span style="color: var(--accent-gold); font-size: 15px; font-weight: 600; font-family: 'Playfair Display', serif;"><?= e($displayName) ?></span>
                <div class="user-avatar" style="background: linear-gradient(135deg, var(--accent-gold), #AA8715); width: 45px; height: 45px; border-radius: 50%; display:flex; align-items:center; justify-content:center; color: #fff; font-weight: bold; margin-left: 12px; font-family: 'Playfair Display', serif; font-size: 20px; overflow:hidden;">
                    <?php if ($profileImageUrl): ?>
                        <img src="<?= e($profileImageUrl) ?>" alt="<?= e($displayName) ?>" class="avatar-image">
                    <?php else: ?>
                        <?= e(strtoupper(substr($displayName, 0, 1))) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="page-header" style="border-bottom: none; padding-bottom: 0; display:flex; justify-content:space-between; align-items:center; gap:20px;">
            <h1 style="font-family: 'Playfair Display', serif; color: #fff; font-size: 32px; margin: 0;">My Profile</h1>
            <a href="<?= e(url('catalog.php?department_id=' . (int) ($user['department_id'] ?? 0))) ?>" class="btn-gold" style="text-decoration: none;">
                <i class="fas fa-book-open"></i> Explore Books
            </a>
        </div>

        <?php if ($successMessage || $errorMessage): ?>
            <div style="background: <?= $errorMessage ? 'rgba(230,57,70,0.08)' : 'rgba(197,160,89,0.1)' ?>; border-left: 4px solid <?= $errorMessage ? '#E63946' : 'var(--accent-gold)' ?>; color: <?= $errorMessage ? '#fca5a5' : '#fef3c7' ?>; padding: 18px 20px; border-radius: 8px; margin: 25px 0;">
                <i class="fas <?= $errorMessage ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i> <?= e($errorMessage ?: $successMessage) ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); align-items:start;">
            <div class="stat-card" style="display:block; padding: 40px 30px; text-align:center;">
                <div class="profile-summary-avatar" style="width: 130px; height: 130px; margin: 0 auto 20px auto; background: linear-gradient(135deg, #ffffff, rgba(255,255,255,0.08)); color: var(--accent-gold); border: 2px solid rgba(212,175,55,0.4);">
                    <?php if ($profileImageUrl): ?>
                        <img src="<?= e($profileImageUrl) ?>" alt="<?= e($displayName) ?>" class="avatar-image">
                    <?php else: ?>
                        <?= e(strtoupper(substr($displayName, 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <h3 style="margin: 0 0 8px 0; font-family: 'Playfair Display', serif; color: #fff; font-size: 28px;"><?= e($user['Name']) ?></h3>
                <p style="margin: 0 0 25px 0; color: var(--text-muted); font-size: 14px;"><i class="fas fa-envelope" style="color: var(--accent-gold); margin-right: 5px;"></i> <?= e($user['Email_Address']) ?></p>
                <div style="text-align: center; margin-bottom: 10px;">
                    <span class="status-badge" style="display:inline-block; padding: 8px 18px; font-size: 13px; font-weight: 600; border-radius: 20px; background: rgba(197,160,89,0.1); color: var(--accent-gold); border: 1px solid rgba(197,160,89,0.2);">Active Member</span>
                </div>
            </div>

            <div class="table-container" style="padding: 30px;">
                <h3 style="margin-top: 0; margin-bottom: 30px; font-family: 'Playfair Display', serif; color: #fff; font-size: 26px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 15px; display:flex; align-items:center; gap:12px;">
                    <i class="fas fa-address-card" style="color: var(--accent-gold);"></i> Academic Details
                </h3>
                <form id="profile-edit-form" method="POST" action="<?= e(url('user_profile.php#profile-edit-form')) ?>" enctype="multipart/form-data">
                    <div class="profile-photo-editor">
                        <div class="profile-photo-frame">
                            <?php if ($profileImageUrl): ?>
                                <img src="<?= e($profileImageUrl) ?>" alt="<?= e($displayName) ?>" class="avatar-image">
                            <?php else: ?>
                                <div class="profile-photo-placeholder"><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-upload-controls">
                            <div class="profile-field">
                                <label for="profile-page-image">Profile Image</label>
                                <input id="profile-page-image" type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            </div>
                            <p class="profile-upload-hint">Upload JPG, PNG, or WEBP image. Maximum file size: 2 MB.</p>
                            <?php if ($profileImageUrl): ?>
                                <label class="profile-checkbox">
                                    <input type="checkbox" name="remove_profile_image" value="1">
                                    Remove current photo
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                        <div>
                            <label style="display:block; font-size: 11.5px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 8px;">Full Name</label>
                            <input type="text" name="name" value="<?= e($user['Name']) ?>" required style="width:100%; padding:14px 18px; background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff;">
                        </div>
                        <div>
                            <label style="display:block; font-size: 11.5px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 8px;">Email Address</label>
                            <input type="email" value="<?= e($user['Email_Address']) ?>" disabled style="width:100%; padding:14px 18px; background: rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; color:var(--text-muted);">
                        </div>
                        <div>
                            <label style="display:block; font-size: 11.5px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 8px;">Department</label>
                            <select name="department_id" required style="width:100%; padding:14px 18px; background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff;">
                                <option value="" disabled>Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= e((string) $department['id']) ?>" <?= selected((string) $department['id'], (string) ($user['department_id'] ?? '')) ?>>
                                        <?= e($department['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size: 11.5px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 8px;">Course</label>
                            <input type="text" name="course" value="<?= e($user['Course']) ?>" required style="width:100%; padding:14px 18px; background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff;">
                        </div>
                        <div>
                            <label style="display:block; font-size: 11.5px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 8px;">Semester</label>
                            <input type="text" name="semester" value="<?= e($user['Semester']) ?>" required style="width:100%; padding:14px 18px; background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff;">
                        </div>
                        <div>
                            <label style="display:block; font-size: 11.5px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 8px;">Maximum Allowed Books</label>
                            <input type="text" value="<?= e((string) $user['No_Books_issued']) ?> Books" disabled style="width:100%; padding:14px 18px; background: rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; color:var(--text-muted);">
                        </div>
                    </div>
                    <div style="display:flex; justify-content:flex-end; margin-top: 24px;">
                        <button type="submit" name="update_profile" class="btn-gold"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

