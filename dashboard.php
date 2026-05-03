<?php
/**
 * ========================================
 * Dashboard Page
 * ========================================
 */

require_once __DIR__ . '/includes/functions.php';
requireAuth();

define('PAGE_TITLE', $_SESSION['language'] === 'ar' ? 'لوحة التحكم' : 'Dashboard');

// Get dashboard statistics
$stats = getDashboardStats();

// Get recent repairs
$recentRepairs = db()->fetchAll(
    "SELECT * FROM repairs ORDER BY created_at DESC LIMIT 5"
);

// Get low stock items
$lowStockItems = db()->fetchAll(
    "SELECT * FROM inventory WHERE quantity <= min_quantity AND is_active = 1 ORDER BY quantity ASC LIMIT 5"
);

// Get monthly revenue data for chart
$monthlyRevenue = db()->fetchAll(
    "SELECT 
        DATE_FORMAT(invoice_date, '%Y-%m') as month,
        DATE_FORMAT(invoice_date, '%b') as month_name,
        SUM(amount_paid) as revenue
     FROM invoices 
     WHERE invoice_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
     ORDER BY month ASC"
);

// Get repair status distribution
$statusDistribution = db()->fetchAll(
    "SELECT status, COUNT(*) as count FROM repairs GROUP BY status"
);

include 'includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card fade-in">
        <div class="stat-header">
            <div class="stat-icon primary">
                <i class="fas fa-wrench"></i>
            </div>
            <span class="stat-trend up">
                <i class="fas fa-arrow-up"></i>
                12%
            </span>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_repairs']); ?></div>
        <div class="stat-label"><?php echo $_SESSION['language'] === 'ar' ? 'إجمالي الإصلاحات' : 'Total Repairs'; ?></div>
    </div>
    
    <div class="stat-card fade-in" style="animation-delay: 0.1s">
        <div class="stat-header">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['pending_repairs'] + $stats['in_progress_repairs']); ?></div>
        <div class="stat-label"><?php echo $_SESSION['language'] === 'ar' ? 'قيد التنفيذ' : 'Active Repairs'; ?></div>
    </div>
    
    <div class="stat-card fade-in" style="animation-delay: 0.2s">
        <div class="stat-header">
            <div class="stat-icon success">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <span class="stat-trend up">
                <i class="fas fa-arrow-up"></i>
                8%
            </span>
        </div>
        <div class="stat-value"><?php echo formatCurrency($stats['this_month_revenue']); ?></div>
        <div class="stat-label"><?php echo $_SESSION['language'] === 'ar' ? 'إيرادات الشهر' : 'This Month Revenue'; ?></div>
    </div>
    
    <div class="stat-card fade-in" style="animation-delay: 0.3s">
        <div class="stat-header">
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['low_stock_items']); ?></div>
        <div class="stat-label"><?php echo $_SESSION['language'] === 'ar' ? 'مخزون منخفض' : 'Low Stock Items'; ?></div>
    </div>
</div>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
    <div class="left-column">
        <!-- Revenue Chart -->
        <div class="card fade-in" style="animation-delay: 0.4s">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line"></i>
                    <?php echo $_SESSION['language'] === 'ar' ? 'الإيرادات الشهرية' : 'Monthly Revenue'; ?>
                </h3>
                <select class="form-control" style="width: auto; padding: 0.5rem 2rem 0.5rem 1rem;">
                    <option><?php echo $_SESSION['language'] === 'ar' ? 'آخر 6 أشهر' : 'Last 6 months'; ?></option>
                </select>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </div>
        
        <!-- Recent Repairs -->
        <div class="card fade-in" style="animation-delay: 0.5s">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history"></i>
                    <?php echo $_SESSION['language'] === 'ar' ? 'الإصلاحات الأخيرة' : 'Recent Repairs'; ?>
                </h3>
                <a href="repairs.php" class="btn btn-sm btn-secondary">
                    <?php echo $_SESSION['language'] === 'ar' ? 'عرض الكل' : 'View All'; ?>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php echo $_SESSION['language'] === 'ar' ? 'رقم الإصلاح' : 'Repair #'; ?></th>
                            <th><?php echo $_SESSION['language'] === 'ar' ? 'العميل' : 'Customer'; ?></th>
                            <th><?php echo $_SESSION['language'] === 'ar' ? 'الجهاز' : 'Device'; ?></th>
                            <th><?php echo $_SESSION['language'] === 'ar' ? 'الحالة' : 'Status'; ?></th>
                            <th><?php echo $_SESSION['language'] === 'ar' ? 'التاريخ' : 'Date'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentRepairs)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <?php echo $_SESSION['language'] === 'ar' ? 'لا توجد إصلاحات' : 'No repairs found'; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentRepairs as $repair): ?>
                        <tr>
                            <td><strong><?php echo sanitize($repair['repair_number']); ?></strong></td>
                            <td><?php echo sanitize($repair['customer_name']); ?></td>
                            <td>
                                <i class="fas <?php echo $repair['device_type'] === 'mobile' ? 'fa-mobile-alt' : 'fa-laptop'; ?> me-1 text-muted"></i>
                                <?php echo sanitize($repair['device_name']); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo getStatusBadgeClass($repair['status']); ?>">
                                    <?php echo getStatusLabel($repair['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($repair['entry_date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="right-column">
        <!-- Repair Status Distribution -->
        <div class="card fade-in" style="animation-delay: 0.5s">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie"></i>
                    <?php echo $_SESSION['language'] === 'ar' ? 'توزيع الحالات' : 'Status Distribution'; ?>
                </h3>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card fade-in" style="animation-delay: 0.6s">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt"></i>
                    <?php echo $_SESSION['language'] === 'ar' ? 'إجراءات سريعة' : 'Quick Actions'; ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="repairs.php?action=add" class="quick-action-item">
                        <div class="quick-action-icon stat-icon primary">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="quick-action-text">
                            <h5><?php echo $_SESSION['language'] === 'ar' ? 'إصلاح جديد' : 'New Repair'; ?></h5>
                            <span><?php echo $_SESSION['language'] === 'ar' ? 'إضافة طلب إصلاح' : 'Create repair request'; ?></span>
                        </div>
                    </a>
                    <a href="inventory.php?action=add" class="quick-action-item">
                        <div class="quick-action-icon stat-icon success">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="quick-action-text">
                            <h5><?php echo $_SESSION['language'] === 'ar' ? 'إضافة قطعة' : 'Add Part'; ?></h5>
                            <span><?php echo $_SESSION['language'] === 'ar' ? 'إضافة للمخزون' : 'Add to inventory'; ?></span>
                        </div>
                    </a>
                    <a href="invoices.php" class="quick-action-item">
                        <div class="quick-action-icon stat-icon warning">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="quick-action-text">
                            <h5><?php echo $_SESSION['language'] === 'ar' ? 'الفواتير' : 'View Invoices'; ?></h5>
                            <span><?php echo $_SESSION['language'] === 'ar' ? 'إدارة الفواتير' : 'Manage invoices'; ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Low Stock Alert -->
        <div class="card fade-in" style="animation-delay: 0.7s">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-circle text-danger"></i>
                    <?php echo $_SESSION['language'] === 'ar' ? 'تنبيه المخزون' : 'Low Stock Alert'; ?>
                </h3>
                <a href="inventory.php?filter=low_stock" class="btn btn-sm btn-secondary">
                    <?php echo $_SESSION['language'] === 'ar' ? 'عرض الكل' : 'View All'; ?>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($lowStockItems)): ?>
                <p class="text-muted text-center mb-0">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <?php echo $_SESSION['language'] === 'ar' ? 'جميع القطع متوفرة' : 'All items are well stocked'; ?>
                </p>
                <?php else: ?>
                <div class="low-stock-list">
                    <?php foreach ($lowStockItems as $item): ?>
                    <div class="low-stock-item">
                        <div class="low-stock-info">
                            <h5><?php echo sanitize($item['part_name']); ?></h5>
                            <span><?php echo $_SESSION['language'] === 'ar' ? 'الحد الأدنى:' : 'Min:'; ?> <?php echo $item['min_quantity']; ?></span>
                        </div>
                        <span class="low-stock-qty">
                            <?php echo $item['quantity']; ?> <?php echo $_SESSION['language'] === 'ar' ? 'متبقي' : 'left'; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueData = <?php echo json_encode($monthlyRevenue); ?>;

new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: revenueData.map(d => d.month_name),
        datasets: [{
            label: '<?php echo $_SESSION['language'] === 'ar' ? 'الإيرادات' : 'Revenue'; ?>',
            data: revenueData.map(d => parseFloat(d.revenue)),
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#4f46e5',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                    callback: function(value) {
                        return '<?php echo CURRENCY_SYMBOL; ?>' + value.toLocaleString();
                    }
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Status Distribution Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusData = <?php echo json_encode($statusDistribution); ?>;

const statusLabels = {
    'pending': '<?php echo $_SESSION['language'] === 'ar' ? 'قيد الانتظار' : 'Pending'; ?>',
    'in_progress': '<?php echo $_SESSION['language'] === 'ar' ? 'قيد التنفيذ' : 'In Progress'; ?>',
    'completed': '<?php echo $_SESSION['language'] === 'ar' ? 'مكتمل' : 'Completed'; ?>',
    'delivered': '<?php echo $_SESSION['language'] === 'ar' ? 'تم التسليم' : 'Delivered'; ?>',
    'cancelled': '<?php echo $_SESSION['language'] === 'ar' ? 'ملغي' : 'Cancelled'; ?>'
};

const statusColors = {
    'pending': '#f59e0b',
    'in_progress': '#3b82f6',
    'completed': '#10b981',
    'delivered': '#4f46e5',
    'cancelled': '#ef4444'
};

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusData.map(d => statusLabels[d.status] || d.status),
        datasets: [{
            data: statusData.map(d => parseInt(d.count)),
            backgroundColor: statusData.map(d => statusColors[d.status] || '#64748b'),
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            }
        },
        cutout: '70%'
    }
});
</script>

<?php include 'includes/footer.php'; ?>
