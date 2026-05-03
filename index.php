<?php
/**
 * RepairShop Pro - Device Repair Shop Management System
 * 
 * Main entry point - redirects to dashboard or login
 */

require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/Auth.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
