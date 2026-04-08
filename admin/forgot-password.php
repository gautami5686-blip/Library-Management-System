<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

flash('error', 'Admin forgot password option has been removed. Please contact support for admin access help.');
redirect('admin/login.php');
