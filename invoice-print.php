<?php
/**
 * ========================================
 * Invoice Print Page
 * Professional printable invoice design
 * ========================================
 */

require_once __DIR__ . '/includes/functions.php';
requireAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    die('Invalid invoice');
}

$invoice = db()->fetchOne(
    "SELECT i.*, r.repair_number, r.device_name, r.device_type, r.repair_description, r.repair_cost, r.parts_cost, r.device_serial
     FROM invoices i 
     LEFT JOIN repairs r ON i.repair_id = r.id 
     WHERE i.id = ?",
    [$id]
);

if (!$invoice) {
    die('Invoice not found');
}

$lang = $_SESSION['language'] ?? 'en';
$isRTL = $lang === 'ar';

// Get shop settings
$shopName = getSetting('shop_name', APP_NAME);
$shopAddress = getSetting('shop_address', '');
$shopPhone = getSetting('shop_phone', '');
$shopEmail = getSetting('shop_email', '');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'فاتورة' : 'Invoice'; ?> - <?php echo $invoice['invoice_number']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: #fff;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        
        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid #4f46e5;
        }
        
        .company-info h1 {
            font-size: 28px;
            color: #4f46e5;
            margin-bottom: 10px;
        }
        
        .company-info p {
            color: #666;
            margin: 3px 0;
        }
        
        .invoice-title {
            text-align: <?php echo $isRTL ? 'left' : 'right'; ?>;
        }
        
        .invoice-title h2 {
            font-size: 36px;
            color: #1e293b;
            letter-spacing: -1px;
        }
        
        .invoice-title .invoice-number {
            font-size: 16px;
            color: #4f46e5;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .invoice-title .invoice-date {
            color: #666;
            margin-top: 5px;
        }
        
        /* Info Sections */
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .info-section {
            flex: 1;
        }
        
        .info-section h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .info-section p {
            margin: 5px 0;
        }
        
        .info-section strong {
            color: #1e293b;
        }
        
        /* Device Info */
        .device-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .device-info h3 {
            font-size: 14px;
            color: #4f46e5;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .device-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .device-detail label {
            font-size: 11px;
            text-transform: uppercase;
            color: #999;
            display: block;
        }
        
        .device-detail span {
            font-weight: 600;
            color: #1e293b;
        }
        
        /* Table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .invoice-table th {
            background: #4f46e5;
            color: #fff;
            padding: 15px;
            text-align: <?php echo $isRTL ? 'right' : 'left'; ?>;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .invoice-table th:last-child {
            text-align: <?php echo $isRTL ? 'left' : 'right'; ?>;
        }
        
        .invoice-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .invoice-table td:last-child {
            text-align: <?php echo $isRTL ? 'left' : 'right'; ?>;
            font-weight: 600;
        }
        
        .invoice-table tbody tr:hover {
            background: #f8fafc;
        }
        
        .invoice-table .description {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        /* Totals */
        .invoice-totals {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        
        .totals-table {
            width: 300px;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .totals-row.grand-total {
            background: #4f46e5;
            color: #fff;
            margin: 10px -15px -10px;
            padding: 15px;
            border-radius: 0 0 8px 8px;
            border: none;
        }
        
        .totals-row.balance-due {
            background: #fee2e2;
            color: #dc2626;
            margin: 0 -15px;
            padding: 15px;
        }
        
        .totals-row.paid {
            color: #10b981;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-unpaid {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .status-partial {
            background: #fef3c7;
            color: #b45309;
        }
        
        /* Footer */
        .invoice-footer {
            text-align: center;
            padding-top: 30px;
            border-top: 2px solid #eee;
            color: #999;
        }
        
        .invoice-footer h4 {
            color: #4f46e5;
            margin-bottom: 10px;
        }
        
        /* Print Styles */
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            .invoice-container {
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        /* Print Button */
        .print-btn {
            position: fixed;
            top: 20px;
            <?php echo $isRTL ? 'left' : 'right'; ?>: 20px;
            padding: 15px 30px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }
        
        /* RTL Support */
        [dir="rtl"] .invoice-header,
        [dir="rtl"] .invoice-info,
        [dir="rtl"] .device-details {
            flex-direction: row-reverse;
        }
        
        [dir="rtl"] .invoice-totals {
            justify-content: flex-start;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        <?php echo $lang === 'ar' ? 'طباعة الفاتورة' : 'Print Invoice'; ?>
    </button>
    
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1><?php echo sanitize($shopName); ?></h1>
                <?php if ($shopAddress): ?>
                <p><?php echo sanitize($shopAddress); ?></p>
                <?php endif; ?>
                <?php if ($shopPhone): ?>
                <p><?php echo $lang === 'ar' ? 'هاتف:' : 'Phone:'; ?> <?php echo sanitize($shopPhone); ?></p>
                <?php endif; ?>
                <?php if ($shopEmail): ?>
                <p><?php echo $lang === 'ar' ? 'بريد:' : 'Email:'; ?> <?php echo sanitize($shopEmail); ?></p>
                <?php endif; ?>
            </div>
            <div class="invoice-title">
                <h2><?php echo $lang === 'ar' ? 'فاتورة' : 'INVOICE'; ?></h2>
                <div class="invoice-number"><?php echo $invoice['invoice_number']; ?></div>
                <div class="invoice-date"><?php echo formatDate($invoice['invoice_date'], 'F d, Y'); ?></div>
            </div>
        </div>
        
        <!-- Info Sections -->
        <div class="invoice-info">
            <div class="info-section">
                <h3><?php echo $lang === 'ar' ? 'فاتورة إلى' : 'Bill To'; ?></h3>
                <p><strong><?php echo sanitize($invoice['customer_name']); ?></strong></p>
                <p><?php echo sanitize($invoice['customer_phone']); ?></p>
                <?php if ($invoice['customer_email']): ?>
                <p><?php echo sanitize($invoice['customer_email']); ?></p>
                <?php endif; ?>
            </div>
            <div class="info-section" style="text-align: <?php echo $isRTL ? 'left' : 'right'; ?>;">
                <h3><?php echo $lang === 'ar' ? 'حالة الدفع' : 'Payment Status'; ?></h3>
                <span class="status-badge status-<?php echo $invoice['payment_status']; ?>">
                    <?php echo getStatusLabel($invoice['payment_status']); ?>
                </span>
                <p style="margin-top: 15px;">
                    <?php echo $lang === 'ar' ? 'رقم الإصلاح:' : 'Repair #:'; ?>
                    <strong><?php echo sanitize($invoice['repair_number']); ?></strong>
                </p>
            </div>
        </div>
        
        <!-- Device Info -->
        <div class="device-info">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                    <line x1="12" y1="18" x2="12.01" y2="18"></line>
                </svg>
                <?php echo $lang === 'ar' ? 'معلومات الجهاز' : 'Device Information'; ?>
            </h3>
            <div class="device-details">
                <div class="device-detail">
                    <label><?php echo $lang === 'ar' ? 'نوع الجهاز' : 'Device Type'; ?></label>
                    <span><?php echo ucfirst($invoice['device_type']); ?></span>
                </div>
                <div class="device-detail">
                    <label><?php echo $lang === 'ar' ? 'اسم الجهاز' : 'Device Name'; ?></label>
                    <span><?php echo sanitize($invoice['device_name']); ?></span>
                </div>
                <div class="device-detail">
                    <label><?php echo $lang === 'ar' ? 'الرقم التسلسلي' : 'Serial Number'; ?></label>
                    <span><?php echo $invoice['device_serial'] ? sanitize($invoice['device_serial']) : 'N/A'; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Services Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th><?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'المبلغ' : 'Amount'; ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo $lang === 'ar' ? 'خدمة الإصلاح' : 'Repair Service'; ?></strong>
                        <div class="description"><?php echo sanitize($invoice['repair_description']); ?></div>
                    </td>
                    <td><?php echo formatCurrency($invoice['repair_cost']); ?></td>
                </tr>
                <?php if ($invoice['parts_cost'] > 0): ?>
                <tr>
                    <td>
                        <strong><?php echo $lang === 'ar' ? 'قطع الغيار والمكونات' : 'Parts & Components'; ?></strong>
                        <div class="description"><?php echo $lang === 'ar' ? 'قطع الغيار المستخدمة في الإصلاح' : 'Replacement parts used in repair'; ?></div>
                    </td>
                    <td><?php echo formatCurrency($invoice['parts_cost']); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="invoice-totals">
            <div class="totals-table">
                <div class="totals-row">
                    <span><?php echo $lang === 'ar' ? 'المجموع الفرعي' : 'Subtotal'; ?></span>
                    <strong><?php echo formatCurrency($invoice['subtotal']); ?></strong>
                </div>
                <?php if ($invoice['tax_amount'] > 0): ?>
                <div class="totals-row">
                    <span><?php echo $lang === 'ar' ? 'الضريبة' : 'Tax'; ?> (<?php echo $invoice['tax_rate']; ?>%)</span>
                    <strong><?php echo formatCurrency($invoice['tax_amount']); ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($invoice['discount'] > 0): ?>
                <div class="totals-row">
                    <span><?php echo $lang === 'ar' ? 'الخصم' : 'Discount'; ?></span>
                    <strong style="color: #dc2626;">-<?php echo formatCurrency($invoice['discount']); ?></strong>
                </div>
                <?php endif; ?>
                <div class="totals-row grand-total">
                    <span><?php echo $lang === 'ar' ? 'الإجمالي' : 'Total'; ?></span>
                    <strong><?php echo formatCurrency($invoice['total_amount']); ?></strong>
                </div>
                <?php if ($invoice['amount_paid'] > 0): ?>
                <div class="totals-row paid">
                    <span><?php echo $lang === 'ar' ? 'المدفوع' : 'Amount Paid'; ?></span>
                    <strong><?php echo formatCurrency($invoice['amount_paid']); ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($invoice['balance_due'] > 0): ?>
                <div class="totals-row balance-due">
                    <span><?php echo $lang === 'ar' ? 'المتبقي' : 'Balance Due'; ?></span>
                    <strong><?php echo formatCurrency($invoice['balance_due']); ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="invoice-footer">
            <h4><?php echo $lang === 'ar' ? 'شكراً لتعاملكم معنا!' : 'Thank you for your business!'; ?></h4>
            <p><?php echo $lang === 'ar' ? 'نقدر ثقتكم بنا ونتطلع لخدمتكم مرة أخرى.' : 'We appreciate your trust and look forward to serving you again.'; ?></p>
        </div>
    </div>
</body>
</html>
