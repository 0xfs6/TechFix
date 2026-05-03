<?php
/**
 * ========================================
 * Repair Shop Management System
 * Configuration File
 * ========================================
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// ========================================
// Database Configuration
// ========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'repair_shop_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ========================================
// Application Configuration
// ========================================
define('APP_NAME', 'TechFix Repair Shop');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/repair-shop');
define('APP_PATH', dirname(__DIR__));

// ========================================
// Security Configuration
// ========================================
define('HASH_COST', 10);
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'csrf_token');

// ========================================
// Upload Configuration
// ========================================
define('UPLOAD_PATH', APP_PATH . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// ========================================
// Pagination
// ========================================
define('ITEMS_PER_PAGE', 10);

// ========================================
// Currency Configuration
// ========================================
define('CURRENCY', 'USD');
define('CURRENCY_SYMBOL', '$');

// ========================================
// Invoice/Repair Number Prefixes
// ========================================
define('INVOICE_PREFIX', 'INV');
define('REPAIR_PREFIX', 'REP');
