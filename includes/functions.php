<?php
/**
 * ========================================
 * Helper Functions
 * ========================================
 */

require_once __DIR__ . '/../config/database.php';

// Load current language from session (default en)
if (!isset($_SESSION['language']) || !in_array($_SESSION['language'], ['en', 'ar', 'es', 'fr'])) {
    $_SESSION['language'] = 'en';
}

$__translations = [];
function loadTranslations($lang = 'en') {
    global $__translations;
    $langFile = __DIR__ . '/../languages/' . $lang . '.php';
    if (file_exists($langFile)) {
        $__translations = include $langFile;
    } else {
        $__translations = include __DIR__ . '/../languages/en.php';
    }
}

function t($key, $replacements = []) {
    global $__translations;
    if (empty($__translations)) {
        loadTranslations($_SESSION['language'] ?? 'en');
    }
    $text = $__translations[$key] ?? $key;
    foreach ($replacements as $search => $replace) {
        $text = str_replace('{'.$search.'}', $replace, $text);
    }
    return $text;
}

loadTranslations($_SESSION['language']);

/**
 * Sanitize user input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Generate unique repair number
 */
function generateRepairNumber() {
    $prefix = REPAIR_PREFIX;
    $year = date('Y');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return "{$prefix}-{$year}-{$random}";
}

/**
 * Generate unique invoice number
 */
function generateInvoiceNumber() {
    $prefix = INVOICE_PREFIX;
    $year = date('Y');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return "{$prefix}-{$year}-{$random}";
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'badge-warning',
        'in_progress' => 'badge-info',
        'completed' => 'badge-success',
        'delivered' => 'badge-primary',
        'cancelled' => 'badge-danger',
        'unpaid' => 'badge-danger',
        'partial' => 'badge-warning',
        'paid' => 'badge-success'
    ];
    return $classes[$status] ?? 'badge-secondary';
}

/**
 * Get status label
 */
function getStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'unpaid' => 'Unpaid',
        'partial' => 'Partial',
        'paid' => 'Paid'
    ];
    return $labels[$status] ?? ucfirst($status);
}

/**
 * Flash message system
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: " . APP_URL . "/" . ltrim($url, '/'));
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $userId = $_SESSION['user_id'];
    return db()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page.');
        redirect('login.php');
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        setFlashMessage('error', 'Access denied. Admin privileges required.');
        redirect('dashboard.php');
    }
}

/**
 * Log activity
 */
function logActivity($action, $entityType, $entityId = null, $description = '') {
    if (!isLoggedIn()) return;
    
    $data = [
        'user_id' => $_SESSION['user_id'],
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    db()->insert('activity_log', $data);
}

/**
 * Get setting value
 */
function getSetting($key, $default = null) {
    $result = db()->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $result ? $result['setting_value'] : $default;
}

/**
 * Update setting value
 */
function updateSetting($key, $value) {
    return db()->update('settings', ['setting_value' => $value], 'setting_key = :key', ['key' => $key]);
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    $db = db();
    
    return [
        'total_repairs' => $db->count('repairs'),
        'pending_repairs' => $db->count('repairs', "status = 'pending'"),
        'in_progress_repairs' => $db->count('repairs', "status = 'in_progress'"),
        'completed_repairs' => $db->count('repairs', "status IN ('completed', 'delivered')"),
        'total_invoices' => $db->count('invoices'),
        'unpaid_invoices' => $db->count('invoices', "payment_status = 'unpaid'"),
        'total_inventory' => $db->count('inventory'),
        'low_stock_items' => $db->count('inventory', 'quantity <= min_quantity AND is_active = 1'),
        'total_revenue' => $db->fetchOne("SELECT COALESCE(SUM(amount_paid), 0) as total FROM invoices")['total'],
        'this_month_revenue' => $db->fetchOne(
            "SELECT COALESCE(SUM(amount_paid), 0) as total FROM invoices WHERE MONTH(invoice_date) = MONTH(CURRENT_DATE()) AND YEAR(invoice_date) = YEAR(CURRENT_DATE())"
        )['total']
    ];
}

/**
 * Pagination helper
 */
function paginate($totalItems, $currentPage = 1, $perPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($totalItems / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Render pagination HTML
 */
function renderPagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    $prevDisabled = !$pagination['has_prev'] ? 'disabled' : '';
    $prevPage = $pagination['current_page'] - 1;
    $html .= "<li class='page-item {$prevDisabled}'><a class='page-link' href='{$baseUrl}?page={$prevPage}'>&laquo;</a></li>";
    
    // Page numbers
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        $active = $i === $pagination['current_page'] ? 'active' : '';
        $html .= "<li class='page-item {$active}'><a class='page-link' href='{$baseUrl}?page={$i}'>{$i}</a></li>";
    }
    
    // Next button
    $nextDisabled = !$pagination['has_next'] ? 'disabled' : '';
    $nextPage = $pagination['current_page'] + 1;
    $html .= "<li class='page-item {$nextDisabled}'><a class='page-link' href='{$baseUrl}?page={$nextPage}'>&raquo;</a></li>";
    
    $html .= '</ul></nav>';
    
    return $html;
}
