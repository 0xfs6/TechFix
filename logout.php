<?php
/**
 * ========================================
 * Logout Handler
 * ========================================
 */

require_once __DIR__ . '/includes/Auth.php';

$auth = new Auth();
$auth->logout();

setFlashMessage('success', 'You have been logged out successfully.');
redirect('login.php');
