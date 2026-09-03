<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect(dashboardUrl());
} else {
    redirect('auth/login.php');
}
