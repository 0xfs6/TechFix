<?php
/**
 * ========================================
 * Settings Page
 * ========================================
 */

require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$lang = $_SESSION['language'] ?? 'en';
define('PAGE_TITLE', $lang === 'ar' ? 'الإعدادات' : 'Settings');

$settings = [
    'app_name' => getSetting('app_name', 'TechFix'),
    'currency_symbol' => getSetting('currency_symbol', '$'),
    'default_language' => getSetting('default_language', 'en'),
    'theme' => getSetting('theme', 'light'),
    'low_stock_threshold' => getSetting('low_stock_threshold', '5')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', $lang === 'ar' ? 'طلب غير صالح' : 'Invalid request');
        redirect('settings.php');
    }

    $appName = sanitize($_POST['app_name'] ?? 'TechFix');
    $currency = sanitize($_POST['currency_symbol'] ?? '$');
    $defaultLang = in_array($_POST['default_language'] ?? 'en', ['en', 'ar', 'es', 'fr']) ? $_POST['default_language'] : 'en';
    $theme = in_array($_POST['theme'] ?? 'light', ['light', 'dark']) ? $_POST['theme'] : 'light';
    $lowStock = max(1, (int)($_POST['low_stock_threshold'] ?? 5));

    updateSetting('app_name', $appName);
    updateSetting('currency_symbol', $currency);
    updateSetting('default_language', $defaultLang);
    updateSetting('theme', $theme);
    updateSetting('low_stock_threshold', $lowStock);

    setFlashMessage('success', $lang === 'ar' ? 'تم حفظ الإعدادات بنجاح.' : 'Settings saved successfully.');
    redirect('settings.php');
}

$csrfToken = generateCSRFToken();
include 'includes/header.php';
?>

<div class="card fade-in">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cog"></i> <?php echo $lang === 'ar' ? 'الإعدادات العامة' : 'General Settings'; ?></h3>
    </div>
    <div class="card-body">
        <form method="post" action="settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="form-group mb-2">
                <label><?php echo $lang === 'ar' ? 'اسم التطبيق' : 'App Name'; ?></label>
                <input type="text" name="app_name" class="form-control" value="<?php echo sanitize($settings['app_name']); ?>" required>
            </div>
            <div class="form-group mb-2">
                <label><?php echo $lang === 'ar' ? 'رمز العملة' : 'Currency Symbol'; ?></label>
                <input type="text" name="currency_symbol" class="form-control" value="<?php echo sanitize($settings['currency_symbol']); ?>" required>
            </div>
            <div class="form-group mb-2">
                <label><?php echo $lang === 'ar' ? 'اللغة الافتراضية' : 'Default Language'; ?></label>
                <select name="default_language" class="form-control form-select">
                    <option value="en" <?php echo $settings['default_language'] === 'en' ? 'selected' : ''; ?>>English</option>
                    <option value="ar" <?php echo $settings['default_language'] === 'ar' ? 'selected' : ''; ?>>العربية</option>
                    <option value="es" <?php echo $settings['default_language'] === 'es' ? 'selected' : ''; ?>>Español</option>
                    <option value="fr" <?php echo $settings['default_language'] === 'fr' ? 'selected' : ''; ?>>Français</option>
                </select>
            </div>
            <div class="form-group mb-2">
                <label><?php echo $lang === 'ar' ? 'الوضع' : 'Theme'; ?></label>
                <select name="theme" class="form-control form-select">
                    <option value="light" <?php echo $settings['theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
                    <option value="dark" <?php echo $settings['theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                </select>
            </div>
            <div class="form-group mb-2">
                <label><?php echo $lang === 'ar' ? 'حد التنبيه للمخزون المنخفض' : 'Low stock threshold'; ?></label>
                <input type="number" name="low_stock_threshold" class="form-control" min="1" value="<?php echo intval($settings['low_stock_threshold']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save"></i> <?php echo $lang === 'ar' ? 'حفظ الإعدادات' : 'Save Settings'; ?></button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php';
