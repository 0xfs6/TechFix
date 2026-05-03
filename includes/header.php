<?php
/**
 * ========================================
 * Header Component
 * ========================================
 */

if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Dashboard');
}

$currentUser = getCurrentUser();
$userInitials = strtoupper(substr($currentUser['full_name'], 0, 2));
$lowStockCount = db()->count('inventory', 'quantity <= min_quantity AND is_active = 1');
$lang = $_SESSION['language'] ?? 'en';
$theme = $_SESSION['theme'] ?? 'light';
$isRTL = $lang === 'ar';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="sidebar-brand">
                    <h2>TechFix</h2>
                    <span>Repair Management</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span class="nav-section-title"><?php echo t('main_menu'); ?></span>
                    <ul class="nav-menu">
                        <li>
                            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                                <i class="fas fa-home"></i>
                                <span><?php echo t('dashboard'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="repairs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'repairs.php' ? 'active' : ''; ?>">
                                <i class="fas fa-wrench"></i>
                                <span><?php echo t('repairs'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="invoices.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'invoices.php' ? 'active' : ''; ?>">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <span><?php echo t('invoices'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="inventory.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'inventory.php' ? 'active' : ''; ?>">
                                <i class="fas fa-boxes-stacked"></i>
                                <span><?php echo t('inventory'); ?></span>
                                <?php if ($lowStockCount > 0): ?>
                                <span class="nav-badge"><?php echo $lowStockCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="nav-section">
                    <span class="nav-section-title"><?php echo t('administration'); ?></span>
                    <ul class="nav-menu">
                        <li>
                            <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i>
                                <span><?php echo t('users'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                                <i class="fas fa-cog"></i>
                                <span><?php echo t('settings'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-bar"></i>
                                <span><?php echo t('reports'); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="nav-section">
                    <span class="nav-section-title"><?php echo $lang === 'ar' ? 'الحساب' : 'Account'; ?></span>
                    <ul class="nav-menu">
                        <li>
                            <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                                <i class="fas fa-user"></i>
                                <span><?php echo t('profile'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" class="nav-link">
                                <i class="fas fa-sign-out-alt"></i>
                                <span><?php echo t('logout'); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><?php echo PAGE_TITLE; ?></h1>
                </div>
                
                <div class="header-right">
                    <!-- Language Toggle -->
                    <button class="toggle-btn" onclick="toggleLanguage()">
                        <i class="fas fa-globe"></i>
                        <span><?php echo $_SESSION['language'] === 'ar' ? 'English' : 'العربية'; ?></span>
                    </button>
                    
                    <!-- Theme Toggle -->
                    <button class="header-action" onclick="toggleTheme()" title="Toggle Theme">
                        <i class="fas <?php echo $theme === 'dark' ? 'fa-sun' : 'fa-moon'; ?>" id="themeIcon"></i>
                    </button>
                    
                    <!-- Notifications -->
                    <button class="header-action" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($lowStockCount > 0): ?>
                        <span class="notification-dot"></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- User Menu -->
                    <div class="user-menu dropdown">
                        <div class="user-avatar"><?php echo $userInitials; ?></div>
                        <div class="user-info">
                            <h4><?php echo sanitize($currentUser['full_name']); ?></h4>
                            <span><?php echo ucfirst($currentUser['role']); ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="page-content">
                <?php 
                $flash = getFlashMessage();
                if ($flash): 
                ?>
                <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : $flash['type']); ?> fade-in">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo sanitize($flash['message']); ?>
                </div>
                <?php endif; ?>
