<?php
/**
 * ========================================
 * Invoices Management Page
 * ========================================
 */

require_once __DIR__ . '/includes/functions.php';
requireAuth();

$lang = $_SESSION['language'] ?? 'en';
define('PAGE_TITLE', $lang === 'ar' ? 'إدارة الفواتير' : 'Invoices Management');

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', $lang === 'ar' ? 'طلب غير صالح' : 'Invalid request');
    } else {
        $postAction = $_POST['action'] ?? '';
        
        if ($postAction === 'update_payment') {
            $invoiceId = (int)$_POST['id'];
            $amountPaid = (float)$_POST['amount_paid'];
            $paymentMethod = sanitize($_POST['payment_method']);
            
            $invoice = db()->fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
            
            if ($invoice) {
                $newAmountPaid = $amountPaid;
                $paymentStatus = 'unpaid';
                
                if ($newAmountPaid >= $invoice['total_amount']) {
                    $paymentStatus = 'paid';
                    $newAmountPaid = $invoice['total_amount'];
                } elseif ($newAmountPaid > 0) {
                    $paymentStatus = 'partial';
                }
                
                db()->update('invoices', [
                    'amount_paid' => $newAmountPaid,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
                    'payment_date' => date('Y-m-d')
                ], 'id = :id', ['id' => $invoiceId]);
                
                logActivity('update_payment', 'invoice', $invoiceId, "Payment updated: $newAmountPaid");
                setFlashMessage('success', $lang === 'ar' ? 'تم تحديث الدفع بنجاح' : 'Payment updated successfully');
            }
            redirect('invoices.php');
        }
    }
}

// Get invoices list
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$where = '1=1';
$params = [];

if ($search) {
    $where .= " AND (i.invoice_number LIKE :search OR i.customer_name LIKE :search OR i.customer_phone LIKE :search)";
    $params['search'] = "%$search%";
}

if ($statusFilter) {
    $where .= " AND i.payment_status = :status";
    $params['status'] = $statusFilter;
}

$totalItems = db()->fetchOne(
    "SELECT COUNT(*) as count FROM invoices i WHERE $where",
    $params
)['count'];

$pagination = paginate($totalItems, $page);

$invoices = db()->fetchAll(
    "SELECT i.*, r.repair_number, r.device_name 
     FROM invoices i 
     LEFT JOIN repairs r ON i.repair_id = r.id 
     WHERE $where 
     ORDER BY i.created_at DESC 
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

$csrfToken = generateCSRFToken();

include 'includes/header.php';
?>

<?php if ($action === 'list'): ?>
<!-- Invoices List View -->
<div class="card fade-in">
    <div class="card-header">
        <div class="d-flex align-center gap-2">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input 
                    type="text" 
                    class="form-control" 
                    placeholder="<?php echo $lang === 'ar' ? 'بحث...' : 'Search...'; ?>"
                    value="<?php echo sanitize($search); ?>"
                    onkeyup="if(event.key==='Enter') window.location='?search='+this.value"
                >
            </div>
            <select class="form-control form-select" style="width: auto;" onchange="window.location='?status='+this.value">
                <option value=""><?php echo $lang === 'ar' ? 'جميع الحالات' : 'All Status'; ?></option>
                <option value="unpaid" <?php echo $statusFilter === 'unpaid' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'غير مدفوع' : 'Unpaid'; ?></option>
                <option value="partial" <?php echo $statusFilter === 'partial' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'جزئي' : 'Partial'; ?></option>
                <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'مدفوع' : 'Paid'; ?></option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <span class="text-muted">
                <?php echo $lang === 'ar' ? 'الإجمالي:' : 'Total:'; ?>
                <strong><?php echo formatCurrency(array_sum(array_column($invoices, 'total_amount'))); ?></strong>
            </span>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo $lang === 'ar' ? 'رقم الفاتورة' : 'Invoice #'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'رقم الإصلاح' : 'Repair #'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'العميل' : 'Customer'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'المبلغ' : 'Amount'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'المدفوع' : 'Paid'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'المتبقي' : 'Balance'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'التاريخ' : 'Date'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الإجراءات' : 'Actions'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-file-invoice fa-2x mb-2 d-block"></i>
                        <?php echo $lang === 'ar' ? 'لا توجد فواتير' : 'No invoices found'; ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><strong><?php echo sanitize($invoice['invoice_number']); ?></strong></td>
                    <td>
                        <a href="repairs.php?action=view&id=<?php echo $invoice['repair_id']; ?>" class="text-primary">
                            <?php echo sanitize($invoice['repair_number']); ?>
                        </a>
                    </td>
                    <td>
                        <div><?php echo sanitize($invoice['customer_name']); ?></div>
                        <small class="text-muted"><?php echo sanitize($invoice['customer_phone']); ?></small>
                    </td>
                    <td><strong><?php echo formatCurrency($invoice['total_amount']); ?></strong></td>
                    <td class="text-success"><?php echo formatCurrency($invoice['amount_paid']); ?></td>
                    <td class="<?php echo $invoice['balance_due'] > 0 ? 'text-danger' : ''; ?>">
                        <?php echo formatCurrency($invoice['balance_due']); ?>
                    </td>
                    <td>
                        <span class="badge <?php echo getStatusBadgeClass($invoice['payment_status']); ?>">
                            <?php echo getStatusLabel($invoice['payment_status']); ?>
                        </span>
                    </td>
                    <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?action=view&id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-secondary" title="<?php echo $lang === 'ar' ? 'عرض' : 'View'; ?>">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="invoice-print.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-primary" target="_blank" title="<?php echo $lang === 'ar' ? 'طباعة' : 'Print'; ?>">
                                <i class="fas fa-print"></i>
                            </a>
                            <?php if ($invoice['payment_status'] !== 'paid'): ?>
                            <button type="button" class="btn btn-sm btn-success" onclick="openPaymentModal(<?php echo $invoice['id']; ?>, <?php echo $invoice['total_amount']; ?>, <?php echo $invoice['amount_paid']; ?>)" title="<?php echo $lang === 'ar' ? 'دفع' : 'Pay'; ?>">
                                <i class="fas fa-dollar-sign"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer">
        <?php echo renderPagination($pagination, 'invoices.php'); ?>
    </div>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div class="modal-backdrop" id="paymentModal">
    <div class="modal">
        <div class="modal-header">
            <h4 class="modal-title"><?php echo $lang === 'ar' ? 'تسجيل دفعة' : 'Record Payment'; ?></h4>
            <button type="button" class="modal-close" onclick="closeModal('paymentModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="update_payment">
            <input type="hidden" name="id" id="paymentInvoiceId">
            
            <div class="modal-body">
                <div class="alert alert-info">
                    <div class="d-flex justify-between">
                        <span><?php echo $lang === 'ar' ? 'المبلغ الإجمالي:' : 'Total Amount:'; ?></span>
                        <strong id="paymentTotal"></strong>
                    </div>
                    <div class="d-flex justify-between mt-2">
                        <span><?php echo $lang === 'ar' ? 'المدفوع سابقاً:' : 'Previously Paid:'; ?></span>
                        <strong id="paymentPrevious"></strong>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'المبلغ المدفوع' : 'Amount Paid'; ?></label>
                    <input type="number" name="amount_paid" id="paymentAmount" class="form-control" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'طريقة الدفع' : 'Payment Method'; ?></label>
                    <select name="payment_method" class="form-control form-select" required>
                        <option value="cash"><?php echo $lang === 'ar' ? 'نقداً' : 'Cash'; ?></option>
                        <option value="card"><?php echo $lang === 'ar' ? 'بطاقة' : 'Card'; ?></option>
                        <option value="bank_transfer"><?php echo $lang === 'ar' ? 'تحويل بنكي' : 'Bank Transfer'; ?></option>
                        <option value="other"><?php echo $lang === 'ar' ? 'أخرى' : 'Other'; ?></option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')"><?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?></button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-1"></i>
                    <?php echo $lang === 'ar' ? 'تأكيد الدفع' : 'Confirm Payment'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(id, total, paid) {
    const modal = document.getElementById('paymentModal');
    const idEl = document.getElementById('paymentInvoiceId');
    const totalEl = document.getElementById('paymentTotal');
    const previousEl = document.getElementById('paymentPrevious');
    const amountEl = document.getElementById('paymentAmount');

    if (!modal || !idEl || !totalEl || !previousEl || !amountEl) {
        console.error('Payment modal elements missing');
        return;
    }

    idEl.value = id;
    totalEl.textContent = '<?php echo CURRENCY_SYMBOL; ?>' + parseFloat(total).toFixed(2);
    previousEl.textContent = '<?php echo CURRENCY_SYMBOL; ?>' + parseFloat(paid).toFixed(2);
    amountEl.value = (total - paid).toFixed(2);
    amountEl.max = total;

    if (typeof openModal === 'function') {
        openModal('paymentModal');
    } else {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}
</script>

<?php elseif ($action === 'view' && $id): ?>
<?php
$invoice = db()->fetchOne(
    "SELECT i.*, r.repair_number, r.device_name, r.device_type, r.repair_description, r.repair_cost, r.parts_cost 
     FROM invoices i 
     LEFT JOIN repairs r ON i.repair_id = r.id 
     WHERE i.id = ?",
    [$id]
);

if (!$invoice) {
    setFlashMessage('error', $lang === 'ar' ? 'الفاتورة غير موجودة' : 'Invoice not found');
    redirect('invoices.php');
}
?>
<!-- View Invoice Details -->
<div class="card fade-in">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-file-invoice-dollar"></i>
            <?php echo $lang === 'ar' ? 'تفاصيل الفاتورة' : 'Invoice Details'; ?> - <?php echo sanitize($invoice['invoice_number']); ?>
        </h3>
        <div class="d-flex gap-2">
            <a href="invoice-print.php?id=<?php echo $id; ?>" class="btn btn-primary" target="_blank">
                <i class="fas fa-print"></i>
                <?php echo $lang === 'ar' ? 'طباعة' : 'Print'; ?>
            </a>
            <a href="invoices.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                <?php echo $lang === 'ar' ? 'رجوع' : 'Back'; ?>
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <div class="form-row">
            <div>
                <h5 class="mb-3"><?php echo $lang === 'ar' ? 'معلومات الفاتورة' : 'Invoice Information'; ?></h5>
                <table class="table">
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'رقم الفاتورة' : 'Invoice #'; ?></td>
                        <td><strong><?php echo sanitize($invoice['invoice_number']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'رقم الإصلاح' : 'Repair #'; ?></td>
                        <td>
                            <a href="repairs.php?action=view&id=<?php echo $invoice['repair_id']; ?>" class="text-primary">
                                <?php echo sanitize($invoice['repair_number']); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'التاريخ' : 'Date'; ?></td>
                        <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($invoice['payment_status']); ?>">
                                <?php echo getStatusLabel($invoice['payment_status']); ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div>
                <h5 class="mb-3"><?php echo $lang === 'ar' ? 'معلومات العميل' : 'Customer Information'; ?></h5>
                <table class="table">
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'الاسم' : 'Name'; ?></td>
                        <td><strong><?php echo sanitize($invoice['customer_name']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'الهاتف' : 'Phone'; ?></td>
                        <td><?php echo sanitize($invoice['customer_phone']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'البريد' : 'Email'; ?></td>
                        <td><?php echo $invoice['customer_email'] ? sanitize($invoice['customer_email']) : '-'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <hr class="my-4">
        
        <h5 class="mb-3"><?php echo $lang === 'ar' ? 'تفاصيل الخدمة' : 'Service Details'; ?></h5>
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?></th>
                    <th class="text-end"><?php echo $lang === 'ar' ? 'المبلغ' : 'Amount'; ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo sanitize($invoice['device_name']); ?></strong>
                        <br>
                        <small class="text-muted"><?php echo sanitize($invoice['repair_description']); ?></small>
                    </td>
                    <td class="text-end"><?php echo formatCurrency($invoice['repair_cost']); ?></td>
                </tr>
                <?php if ($invoice['parts_cost'] > 0): ?>
                <tr>
                    <td><?php echo $lang === 'ar' ? 'قطع الغيار' : 'Parts & Components'; ?></td>
                    <td class="text-end"><?php echo formatCurrency($invoice['parts_cost']); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="text-end"><strong><?php echo $lang === 'ar' ? 'المجموع الفرعي' : 'Subtotal'; ?></strong></td>
                    <td class="text-end"><?php echo formatCurrency($invoice['subtotal']); ?></td>
                </tr>
                <?php if ($invoice['tax_amount'] > 0): ?>
                <tr>
                    <td class="text-end"><?php echo $lang === 'ar' ? 'الضريبة' : 'Tax'; ?> (<?php echo $invoice['tax_rate']; ?>%)</td>
                    <td class="text-end"><?php echo formatCurrency($invoice['tax_amount']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($invoice['discount'] > 0): ?>
                <tr>
                    <td class="text-end"><?php echo $lang === 'ar' ? 'الخصم' : 'Discount'; ?></td>
                    <td class="text-end text-danger">-<?php echo formatCurrency($invoice['discount']); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="table-primary">
                    <td class="text-end"><strong><?php echo $lang === 'ar' ? 'الإجمالي' : 'Total'; ?></strong></td>
                    <td class="text-end"><strong><?php echo formatCurrency($invoice['total_amount']); ?></strong></td>
                </tr>
                <tr>
                    <td class="text-end"><?php echo $lang === 'ar' ? 'المدفوع' : 'Amount Paid'; ?></td>
                    <td class="text-end text-success"><?php echo formatCurrency($invoice['amount_paid']); ?></td>
                </tr>
                <tr class="<?php echo $invoice['balance_due'] > 0 ? 'table-danger' : 'table-success'; ?>">
                    <td class="text-end"><strong><?php echo $lang === 'ar' ? 'المتبقي' : 'Balance Due'; ?></strong></td>
                    <td class="text-end"><strong><?php echo formatCurrency($invoice['balance_due']); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        
        <?php if ($invoice['payment_method']): ?>
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <?php echo $lang === 'ar' ? 'طريقة الدفع:' : 'Payment Method:'; ?> 
            <strong><?php echo ucfirst(str_replace('_', ' ', $invoice['payment_method'])); ?></strong>
            <?php if ($invoice['payment_date']): ?>
            | <?php echo $lang === 'ar' ? 'تاريخ الدفع:' : 'Payment Date:'; ?> 
            <strong><?php echo formatDate($invoice['payment_date']); ?></strong>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($invoice['payment_status'] !== 'paid'): ?>
    <div class="card-footer">
        <button type="button" class="btn btn-success" onclick="openPaymentModal(<?php echo $invoice['id']; ?>, <?php echo $invoice['total_amount']; ?>, <?php echo $invoice['amount_paid']; ?>)">
            <i class="fas fa-dollar-sign me-1"></i>
            <?php echo $lang === 'ar' ? 'تسجيل دفعة' : 'Record Payment'; ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Payment Modal (same as list view) -->
<div class="modal-backdrop" id="paymentModal">
    <div class="modal">
        <div class="modal-header">
            <h4 class="modal-title"><?php echo $lang === 'ar' ? 'تسجيل دفعة' : 'Record Payment'; ?></h4>
            <button type="button" class="modal-close" onclick="closeModal('paymentModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="update_payment">
            <input type="hidden" name="id" id="paymentInvoiceId">
            
            <div class="modal-body">
                <div class="alert alert-info">
                    <div class="d-flex justify-between">
                        <span><?php echo $lang === 'ar' ? 'المبلغ الإجمالي:' : 'Total Amount:'; ?></span>
                        <strong id="paymentTotal"></strong>
                    </div>
                    <div class="d-flex justify-between mt-2">
                        <span><?php echo $lang === 'ar' ? 'المدفوع سابقاً:' : 'Previously Paid:'; ?></span>
                        <strong id="paymentPrevious"></strong>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'المبلغ المدفوع' : 'Amount Paid'; ?></label>
                    <input type="number" name="amount_paid" id="paymentAmount" class="form-control" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'طريقة الدفع' : 'Payment Method'; ?></label>
                    <select name="payment_method" class="form-control form-select" required>
                        <option value="cash"><?php echo $lang === 'ar' ? 'نقداً' : 'Cash'; ?></option>
                        <option value="card"><?php echo $lang === 'ar' ? 'بطاقة' : 'Card'; ?></option>
                        <option value="bank_transfer"><?php echo $lang === 'ar' ? 'تحويل بنكي' : 'Bank Transfer'; ?></option>
                        <option value="other"><?php echo $lang === 'ar' ? 'أخرى' : 'Other'; ?></option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')"><?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?></button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-1"></i>
                    <?php echo $lang === 'ar' ? 'تأكيد الدفع' : 'Confirm Payment'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(id, total, paid) {
    const modal = document.getElementById('paymentModal');
    const idEl = document.getElementById('paymentInvoiceId');
    const totalEl = document.getElementById('paymentTotal');
    const previousEl = document.getElementById('paymentPrevious');
    const amountEl = document.getElementById('paymentAmount');

    if (!modal || !idEl || !totalEl || !previousEl || !amountEl) {
        console.error('Payment modal elements missing');
        return;
    }

    idEl.value = id;
    totalEl.textContent = '<?php echo CURRENCY_SYMBOL; ?>' + parseFloat(total).toFixed(2);
    previousEl.textContent = '<?php echo CURRENCY_SYMBOL; ?>' + parseFloat(paid).toFixed(2);
    amountEl.value = (total - paid).toFixed(2);
    amountEl.max = total;

    if (typeof openModal === 'function') {
        openModal('paymentModal');
    } else {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
