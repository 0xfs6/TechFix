<?php
/**
 * ========================================
 * Reports Page
 * ========================================
 */

require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$lang = $_SESSION['language'] ?? 'en';
define('PAGE_TITLE', $lang === 'ar' ? 'التقارير' : 'Reports');

// Summary metrics
$stats = getDashboardStats();

// Monthly revenue last 6 months
$monthlyRevenue = db()->fetchAll(
    "SELECT DATE_FORMAT(invoice_date, '%Y-%m') as month, DATE_FORMAT(invoice_date, '%b') as month_name, COALESCE(SUM(amount_paid), 0) as revenue
     FROM invoices
     WHERE invoice_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
     ORDER BY month ASC"
);

// Repair status breakdown
$statusDistribution = db()->fetchAll("SELECT status, COUNT(*) as count FROM repairs GROUP BY status");

include 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card fade-in">
        <div class="stat-header"><div class="stat-icon primary"><i class="fas fa-wrench"></i></div></div>
        <div class="stat-value"><?php echo number_format($stats['total_repairs']); ?></div>
        <div class="stat-label"><?php echo $lang === 'ar' ? 'إجمالي الإصلاحات' : 'Total Repairs'; ?></div>
    </div>
    <div class="stat-card fade-in" style="animation-delay: 0.1s">
        <div class="stat-header"><div class="stat-icon success"><i class="fas fa-dollar-sign"></i></div></div>
        <div class="stat-value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
        <div class="stat-label"><?php echo $lang === 'ar' ? 'إجمالي الإيرادات' : 'Total Revenue'; ?></div>
    </div>
    <div class="stat-card fade-in" style="animation-delay: 0.2s">
        <div class="stat-header"><div class="stat-icon warning"><i class="fas fa-clock"></i></div></div>
        <div class="stat-value"><?php echo number_format($stats['pending_repairs'] + $stats['in_progress_repairs']); ?></div>
        <div class="stat-label"><?php echo $lang === 'ar' ? 'الإصلاحات النشطة' : 'Active Repairs'; ?></div>
    </div>
    <div class="stat-card fade-in" style="animation-delay: 0.3s">
        <div class="stat-header"><div class="stat-icon danger"><i class="fas fa-boxes-stacked"></i></div></div>
        <div class="stat-value"><?php echo number_format($stats['total_inventory']); ?></div>
        <div class="stat-label"><?php echo $lang === 'ar' ? 'عناصر المخزون' : 'Inventory Items'; ?></div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="left-column">
        <div class="card fade-in">
            <div class="card-header d-flex justify-between align-center">
                <h3 class="card-title"><i class="fas fa-chart-line"></i> <?php echo $lang === 'ar' ? 'إيرادات آخر 6 أشهر' : 'Revenue Last 6 Months'; ?></h3>
            </div>
            <div class="card-body">
                <canvas id="reportsRevenueChart" height="240"></canvas>
            </div>
        </div>

        <div class="card fade-in" style="animation-delay: 0.1s">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history"></i> <?php echo $lang === 'ar' ? 'أحدث الفواتير' : 'Recent Invoices'; ?></h3>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo $lang === 'ar' ? 'رقم الفاتورة' : 'Invoice #'; ?></th>
                            <th><?php echo $lang === 'ar' ? 'العميل' : 'Customer'; ?></th>
                            <th><?php echo $lang === 'ar' ? 'المبلغ' : 'Amount'; ?></th>
                            <th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recentInvoices = db()->fetchAll("SELECT invoice_number, customer_name, total_amount, payment_status FROM invoices ORDER BY created_at DESC LIMIT 5");
                        if (empty($recentInvoices)):
                        ?>
                        <tr><td colspan="5" class="text-center text-muted"><?php echo $lang === 'ar' ? 'لا توجد فواتير' : 'No invoices found'; ?></td></tr>
                        <?php else: foreach ($recentInvoices as $idx => $invoice): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo sanitize($invoice['invoice_number']); ?></td>
                            <td><?php echo sanitize($invoice['customer_name']); ?></td>
                            <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                            <td><span class="badge <?php echo getStatusBadgeClass($invoice['payment_status']); ?>"><?php echo getStatusLabel($invoice['payment_status']); ?></span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="right-column">
        <div class="card fade-in">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> <?php echo $lang === 'ar' ? 'توزيع الحالات' : 'Status Distribution'; ?></h3>
            </div>
            <div class="card-body">
                <canvas id="reportsStatusChart" height="240"></canvas>
            </div>
        </div>

        <div class="card fade-in" style="animation-delay: 0.1s">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell"></i> <?php echo $lang === 'ar' ? 'التنبيهات' : 'Alerts'; ?></h3>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <?php echo $lang === 'ar' ? 'الأصناف منخفضة المخزون:' : 'Low stock items:'; ?>
                        <strong><?php echo number_format($stats['low_stock_items']); ?></strong>
                    </li>
                    <li class="list-group-item">
                        <?php echo $lang === 'ar' ? 'الفواتير غير المدفوعة:' : 'Unpaid invoices:'; ?>
                        <strong><?php echo number_format(db()->count('invoices', "payment_status = 'unpaid'")); ?></strong>
                    </li>
                    <li class="list-group-item">
                        <?php echo $lang === 'ar' ? 'إصلاحات قيد التنفيذ:' : 'In-progress repairs:'; ?>
                        <strong><?php echo number_format($stats['in_progress_repairs']); ?></strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const revenueLabels = <?php echo json_encode(array_column($monthlyRevenue, 'month_name')); ?>;
const revenueData = <?php echo json_encode(array_map(function($row){ return (float)$row['revenue']; }, $monthlyRevenue)); ?>;
const statusLabels = <?php echo json_encode(array_map(function($row){ return ucwords(str_replace('_', ' ', $row['status'])); }, $statusDistribution)); ?>;
const statusData = <?php echo json_encode(array_map(function($row){ return (int)$row['count']; }, $statusDistribution)); ?>;

const revenueCtx = document.getElementById('reportsRevenueChart');
if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: '<?php echo $lang === 'ar' ? 'الإيرادات' : 'Revenue'; ?>',
                data: revenueData,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

const statusCtx = document.getElementById('reportsStatusChart');
if (statusCtx) {
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#6610f2', '#dc3545'],
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}
</script>

<?php include 'includes/footer.php';
