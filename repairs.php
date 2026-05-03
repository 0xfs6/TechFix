<?php
/**
 * ========================================
 * Repairs Management Page
 * ========================================
 */

require_once __DIR__ . '/includes/functions.php';
requireAuth();

$lang = $_SESSION['language'] ?? 'en';
define('PAGE_TITLE', $lang === 'ar' ? 'إدارة الإصلاحات' : 'Repairs Management');

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = $lang === 'ar' ? 'طلب غير صالح' : 'Invalid request';
    } else {
        $postAction = $_POST['action'] ?? '';
        
        if ($postAction === 'add' || $postAction === 'edit') {
            $data = [
                'customer_name' => sanitize($_POST['customer_name']),
                'phone_number' => sanitize($_POST['phone_number']),
                'email' => sanitize($_POST['email']),
                'device_type' => sanitize($_POST['device_type']),
                'device_name' => sanitize($_POST['device_name']),
                'device_serial' => sanitize($_POST['device_serial']),
                'repair_description' => sanitize($_POST['repair_description']),
                'diagnosis' => sanitize($_POST['diagnosis']),
                'repair_cost' => (float)$_POST['repair_cost'],
                'parts_cost' => (float)$_POST['parts_cost'],
                'status' => sanitize($_POST['status']),
                'priority' => sanitize($_POST['priority']),
                'entry_date' => $_POST['entry_date'],
                'estimated_completion' => $_POST['estimated_completion'] ?: null,
                'notes' => sanitize($_POST['notes']),
                'technician_id' => $_POST['technician_id'] ?: null
            ];
            
            try {
                if ($postAction === 'add') {
                    $data['repair_number'] = generateRepairNumber();
                    $data['created_by'] = $_SESSION['user_id'];
                    $repairId = db()->insert('repairs', $data);
                    
                    // Create invoice automatically
                    $invoiceData = [
                        'invoice_number' => generateInvoiceNumber(),
                        'repair_id' => $repairId,
                        'customer_name' => $data['customer_name'],
                        'customer_email' => $data['email'],
                        'customer_phone' => $data['phone_number'],
                        'subtotal' => $data['repair_cost'] + $data['parts_cost'],
                        'tax_rate' => (float)getSetting('tax_rate', 0),
                        'total_amount' => $data['repair_cost'] + $data['parts_cost'],
                        'invoice_date' => date('Y-m-d'),
                        'created_by' => $_SESSION['user_id']
                    ];
                    $invoiceData['tax_amount'] = $invoiceData['subtotal'] * ($invoiceData['tax_rate'] / 100);
                    $invoiceData['total_amount'] = $invoiceData['subtotal'] + $invoiceData['tax_amount'];
                    
                    db()->insert('invoices', $invoiceData);
                    
                    logActivity('create', 'repair', $repairId, "Created repair: {$data['repair_number']}");
                    setFlashMessage('success', $lang === 'ar' ? 'تم إنشاء الإصلاح بنجاح' : 'Repair created successfully');
                } else {
                    db()->update('repairs', $data, 'id = :id', ['id' => $id]);
                    
                    // Update invoice if exists
                    $totalCost = $data['repair_cost'] + $data['parts_cost'];
                    $taxRate = (float)getSetting('tax_rate', 0);
                    $taxAmount = $totalCost * ($taxRate / 100);
                    
                    db()->update('invoices', [
                        'customer_name' => $data['customer_name'],
                        'customer_email' => $data['email'],
                        'customer_phone' => $data['phone_number'],
                        'subtotal' => $totalCost,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $totalCost + $taxAmount
                    ], 'repair_id = :repair_id', ['repair_id' => $id]);
                    
                    logActivity('update', 'repair', $id, "Updated repair");
                    setFlashMessage('success', $lang === 'ar' ? 'تم تحديث الإصلاح بنجاح' : 'Repair updated successfully');
                }
                redirect('repairs.php');
            } catch (Exception $e) {
                $error = $lang === 'ar' ? 'حدث خطأ. حاول مرة أخرى.' : 'An error occurred. Please try again.';
            }
        }
        
        if ($postAction === 'delete') {
            $deleteId = (int)$_POST['id'];
            try {
                db()->delete('repairs', 'id = ?', [$deleteId]);
                logActivity('delete', 'repair', $deleteId, "Deleted repair");
                setFlashMessage('success', $lang === 'ar' ? 'تم حذف الإصلاح بنجاح' : 'Repair deleted successfully');
            } catch (Exception $e) {
                setFlashMessage('error', $lang === 'ar' ? 'فشل حذف الإصلاح' : 'Failed to delete repair');
            }
            redirect('repairs.php');
        }
        
        if ($postAction === 'update_status') {
            $statusId = (int)$_POST['id'];
            $newStatus = sanitize($_POST['status']);
            
            $updateData = ['status' => $newStatus];
            if ($newStatus === 'completed') {
                $updateData['completion_date'] = date('Y-m-d');
            } elseif ($newStatus === 'delivered') {
                $updateData['delivery_date'] = date('Y-m-d');
            }
            
            db()->update('repairs', $updateData, 'id = :id', ['id' => $statusId]);
            logActivity('update_status', 'repair', $statusId, "Status changed to: $newStatus");
            setFlashMessage('success', $lang === 'ar' ? 'تم تحديث الحالة' : 'Status updated');
            redirect('repairs.php');
        }
    }
}

// Get repair data for edit
$repair = null;
if ($action === 'edit' && $id) {
    $repair = db()->fetchOne("SELECT * FROM repairs WHERE id = ?", [$id]);
    if (!$repair) {
        setFlashMessage('error', $lang === 'ar' ? 'الإصلاح غير موجود' : 'Repair not found');
        redirect('repairs.php');
    }
}

// Get repairs list
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$where = '1=1';
$params = [];

if ($search) {
    $where .= " AND (customer_name LIKE :search OR phone_number LIKE :search OR repair_number LIKE :search OR device_name LIKE :search)";
    $params['search'] = "%$search%";
}

if ($statusFilter) {
    $where .= " AND status = :status";
    $params['status'] = $statusFilter;
}

$totalItems = db()->count('repairs', $where, $params);
$pagination = paginate($totalItems, $page);

$repairs = db()->fetchAll(
    "SELECT * FROM repairs WHERE $where ORDER BY created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

// Get technicians for dropdown
$technicians = db()->fetchAll("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name");

$csrfToken = generateCSRFToken();

include 'includes/header.php';
?>

<?php if ($action === 'list'): ?>
<!-- Repairs List View -->
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
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'قيد الانتظار' : 'Pending'; ?></option>
                <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'قيد التنفيذ' : 'In Progress'; ?></option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'مكتمل' : 'Completed'; ?></option>
                <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'تم التسليم' : 'Delivered'; ?></option>
            </select>
        </div>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            <?php echo $lang === 'ar' ? 'إضافة إصلاح' : 'Add Repair'; ?>
        </a>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo $lang === 'ar' ? 'رقم الإصلاح' : 'Repair #'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'العميل' : 'Customer'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الجهاز' : 'Device'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'التكلفة' : 'Cost'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'التاريخ' : 'Date'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الإجراءات' : 'Actions'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($repairs)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        <?php echo $lang === 'ar' ? 'لا توجد إصلاحات' : 'No repairs found'; ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($repairs as $r): ?>
                <tr>
                    <td><strong><?php echo sanitize($r['repair_number']); ?></strong></td>
                    <td>
                        <div><?php echo sanitize($r['customer_name']); ?></div>
                        <small class="text-muted"><?php echo sanitize($r['phone_number']); ?></small>
                    </td>
                    <td>
                        <i class="fas <?php echo $r['device_type'] === 'mobile' ? 'fa-mobile-alt' : ($r['device_type'] === 'laptop' ? 'fa-laptop' : 'fa-tablet-alt'); ?> me-1"></i>
                        <?php echo sanitize($r['device_name']); ?>
                    </td>
                    <td>
                        <span title="<?php echo sanitize($r['repair_description']); ?>">
                            <?php echo mb_strlen($r['repair_description']) > 40 ? mb_substr(sanitize($r['repair_description']), 0, 40) . '...' : sanitize($r['repair_description']); ?>
                        </span>
                    </td>
                    <td><?php echo formatCurrency($r['total_cost']); ?></td>
                    <td>
                        <span class="badge <?php echo getStatusBadgeClass($r['status']); ?>">
                            <?php echo getStatusLabel($r['status']); ?>
                        </span>
                    </td>
                    <td><?php echo formatDate($r['entry_date']); ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?action=view&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-secondary" title="<?php echo $lang === 'ar' ? 'عرض' : 'View'; ?>">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="?action=edit&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo $lang === 'ar' ? 'تعديل' : 'Edit'; ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteRepair(<?php echo $r['id']; ?>, '<?php echo sanitize($r['repair_number']); ?>')" title="<?php echo $lang === 'ar' ? 'حذف' : 'Delete'; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
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
        <?php echo renderPagination($pagination, 'repairs.php'); ?>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Form (Hidden) -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDeleteRepair(id, number) {
    if (confirm('<?php echo $lang === 'ar' ? 'هل أنت متأكد من حذف الإصلاح' : 'Are you sure you want to delete repair'; ?> ' + number + '?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Repair Form -->
<div class="card fade-in">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i>
            <?php echo $action === 'add' ? ($lang === 'ar' ? 'إضافة إصلاح جديد' : 'Add New Repair') : ($lang === 'ar' ? 'تعديل الإصلاح' : 'Edit Repair'); ?>
        </h3>
        <a href="repairs.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            <?php echo $lang === 'ar' ? 'رجوع' : 'Back'; ?>
        </a>
    </div>
    
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        
        <div class="card-body">
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <h5 class="mb-3"><?php echo $lang === 'ar' ? 'معلومات العميل' : 'Customer Information'; ?></h5>
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'اسم العميل' : 'Customer Name'; ?> *</label>
                    <input type="text" name="customer_name" class="form-control" required value="<?php echo $repair ? sanitize($repair['customer_name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'رقم الهاتف' : 'Phone Number'; ?> *</label>
                    <input type="tel" name="phone_number" class="form-control" required value="<?php echo $repair ? sanitize($repair['phone_number']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'البريد الإلكتروني' : 'Email'; ?></label>
                    <input type="email" name="email" class="form-control" value="<?php echo $repair ? sanitize($repair['email']) : ''; ?>">
                </div>
            </div>
            
            <h5 class="mb-3 mt-4"><?php echo $lang === 'ar' ? 'معلومات الجهاز' : 'Device Information'; ?></h5>
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'نوع الجهاز' : 'Device Type'; ?> *</label>
                    <select name="device_type" class="form-control form-select" required>
                        <option value="mobile" <?php echo $repair && $repair['device_type'] === 'mobile' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'موبايل' : 'Mobile'; ?></option>
                        <option value="laptop" <?php echo $repair && $repair['device_type'] === 'laptop' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'لابتوب' : 'Laptop'; ?></option>
                        <option value="tablet" <?php echo $repair && $repair['device_type'] === 'tablet' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'تابلت' : 'Tablet'; ?></option>
                        <option value="other" <?php echo $repair && $repair['device_type'] === 'other' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'أخرى' : 'Other'; ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'اسم الجهاز' : 'Device Name'; ?> *</label>
                    <input type="text" name="device_name" class="form-control" required placeholder="<?php echo $lang === 'ar' ? 'مثال: iPhone 14 Pro' : 'e.g. iPhone 14 Pro'; ?>" value="<?php echo $repair ? sanitize($repair['device_name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الرقم التسلسلي' : 'Serial Number'; ?></label>
                    <input type="text" name="device_serial" class="form-control" value="<?php echo $repair ? sanitize($repair['device_serial']) : ''; ?>">
                </div>
            </div>
            
            <h5 class="mb-3 mt-4"><?php echo $lang === 'ar' ? 'تفاصيل الإصلاح' : 'Repair Details'; ?></h5>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'وصف المشكلة' : 'Problem Description'; ?> *</label>
                <textarea name="repair_description" class="form-control" rows="3" required><?php echo $repair ? sanitize($repair['repair_description']) : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'التشخيص' : 'Diagnosis'; ?></label>
                <textarea name="diagnosis" class="form-control" rows="2"><?php echo $repair ? sanitize($repair['diagnosis']) : ''; ?></textarea>
            </div>
            
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'تكلفة الإصلاح' : 'Repair Cost'; ?> (<?php echo CURRENCY_SYMBOL; ?>)</label>
                    <input type="number" name="repair_cost" class="form-control" step="0.01" min="0" value="<?php echo $repair ? $repair['repair_cost'] : '0.00'; ?>" onchange="calculateTotal()">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'تكلفة القطع' : 'Parts Cost'; ?> (<?php echo CURRENCY_SYMBOL; ?>)</label>
                    <input type="number" name="parts_cost" class="form-control" step="0.01" min="0" value="<?php echo $repair ? $repair['parts_cost'] : '0.00'; ?>" onchange="calculateTotal()">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الإجمالي' : 'Total'; ?> (<?php echo CURRENCY_SYMBOL; ?>)</label>
                    <input type="text" id="totalCost" class="form-control" readonly value="<?php echo $repair ? number_format($repair['total_cost'], 2) : '0.00'; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?> *</label>
                    <select name="status" class="form-control form-select" required>
                        <option value="pending" <?php echo $repair && $repair['status'] === 'pending' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'قيد الانتظار' : 'Pending'; ?></option>
                        <option value="in_progress" <?php echo $repair && $repair['status'] === 'in_progress' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'قيد التنفيذ' : 'In Progress'; ?></option>
                        <option value="completed" <?php echo $repair && $repair['status'] === 'completed' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'مكتمل' : 'Completed'; ?></option>
                        <option value="delivered" <?php echo $repair && $repair['status'] === 'delivered' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'تم التسليم' : 'Delivered'; ?></option>
                        <option value="cancelled" <?php echo $repair && $repair['status'] === 'cancelled' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'ملغي' : 'Cancelled'; ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الأولوية' : 'Priority'; ?></label>
                    <select name="priority" class="form-control form-select">
                        <option value="low" <?php echo $repair && $repair['priority'] === 'low' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'منخفضة' : 'Low'; ?></option>
                        <option value="normal" <?php echo (!$repair || $repair['priority'] === 'normal') ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'عادية' : 'Normal'; ?></option>
                        <option value="high" <?php echo $repair && $repair['priority'] === 'high' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'عالية' : 'High'; ?></option>
                        <option value="urgent" <?php echo $repair && $repair['priority'] === 'urgent' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'عاجلة' : 'Urgent'; ?></option>
                    </select>
                </div>
            </div>
            
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'تاريخ الإدخال' : 'Entry Date'; ?> *</label>
                    <input type="date" name="entry_date" class="form-control" required value="<?php echo $repair ? $repair['entry_date'] : date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'تاريخ الانتهاء المتوقع' : 'Estimated Completion'; ?></label>
                    <input type="date" name="estimated_completion" class="form-control" value="<?php echo $repair ? $repair['estimated_completion'] : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $lang === 'ar' ? 'الفني' : 'Technician'; ?></label>
                    <select name="technician_id" class="form-control form-select">
                        <option value=""><?php echo $lang === 'ar' ? 'غير محدد' : 'Not Assigned'; ?></option>
                        <?php foreach ($technicians as $tech): ?>
                        <option value="<?php echo $tech['id']; ?>" <?php echo $repair && $repair['technician_id'] == $tech['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($tech['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label"><?php echo $lang === 'ar' ? 'ملاحظات' : 'Notes'; ?></label>
                <textarea name="notes" class="form-control" rows="2"><?php echo $repair ? sanitize($repair['notes']) : ''; ?></textarea>
            </div>
        </div>
        
        <div class="card-footer d-flex justify-between">
            <a href="repairs.php" class="btn btn-secondary"><?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?></a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>
                <?php echo $action === 'add' ? ($lang === 'ar' ? 'إنشاء الإصلاح' : 'Create Repair') : ($lang === 'ar' ? 'حفظ التغييرات' : 'Save Changes'); ?>
            </button>
        </div>
    </form>
</div>

<script>
function calculateTotal() {
    const repairCost = parseFloat(document.querySelector('input[name="repair_cost"]').value) || 0;
    const partsCost = parseFloat(document.querySelector('input[name="parts_cost"]').value) || 0;
    document.getElementById('totalCost').value = (repairCost + partsCost).toFixed(2);
}
</script>

<?php elseif ($action === 'view' && $id): ?>
<?php
$repair = db()->fetchOne("SELECT r.*, u.full_name as technician_name FROM repairs r LEFT JOIN users u ON r.technician_id = u.id WHERE r.id = ?", [$id]);
if (!$repair) {
    setFlashMessage('error', $lang === 'ar' ? 'الإصلاح غير موجود' : 'Repair not found');
    redirect('repairs.php');
}
$invoice = db()->fetchOne("SELECT * FROM invoices WHERE repair_id = ?", [$id]);
?>
<!-- View Repair Details -->
<div class="card fade-in">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-wrench"></i>
            <?php echo $lang === 'ar' ? 'تفاصيل الإصلاح' : 'Repair Details'; ?> - <?php echo sanitize($repair['repair_number']); ?>
        </h3>
        <div class="d-flex gap-2">
            <a href="?action=edit&id=<?php echo $id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i>
                <?php echo $lang === 'ar' ? 'تعديل' : 'Edit'; ?>
            </a>
            <?php if ($invoice): ?>
            <a href="invoice-print.php?id=<?php echo $invoice['id']; ?>" class="btn btn-secondary" target="_blank">
                <i class="fas fa-print"></i>
                <?php echo $lang === 'ar' ? 'طباعة الفاتورة' : 'Print Invoice'; ?>
            </a>
            <?php endif; ?>
            <a href="repairs.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                <?php echo $lang === 'ar' ? 'رجوع' : 'Back'; ?>
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <div class="form-row">
            <div>
                <h5 class="mb-3"><?php echo $lang === 'ar' ? 'معلومات العميل' : 'Customer Information'; ?></h5>
                <table class="table">
                    <tr>
                        <td class="text-muted" style="width: 40%;"><?php echo $lang === 'ar' ? 'الاسم' : 'Name'; ?></td>
                        <td><strong><?php echo sanitize($repair['customer_name']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'الهاتف' : 'Phone'; ?></td>
                        <td><?php echo sanitize($repair['phone_number']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'البريد' : 'Email'; ?></td>
                        <td><?php echo $repair['email'] ? sanitize($repair['email']) : '-'; ?></td>
                    </tr>
                </table>
            </div>
            <div>
                <h5 class="mb-3"><?php echo $lang === 'ar' ? 'معلومات الجهاز' : 'Device Information'; ?></h5>
                <table class="table">
                    <tr>
                        <td class="text-muted" style="width: 40%;"><?php echo $lang === 'ar' ? 'النوع' : 'Type'; ?></td>
                        <td>
                            <i class="fas <?php echo $repair['device_type'] === 'mobile' ? 'fa-mobile-alt' : 'fa-laptop'; ?> me-1"></i>
                            <?php echo ucfirst($repair['device_type']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'الجهاز' : 'Device'; ?></td>
                        <td><strong><?php echo sanitize($repair['device_name']); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'الرقم التسلسلي' : 'Serial #'; ?></td>
                        <td><?php echo $repair['device_serial'] ? sanitize($repair['device_serial']) : '-'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <hr class="my-4">
        
        <h5 class="mb-3"><?php echo $lang === 'ar' ? 'تفاصيل الإصلاح' : 'Repair Details'; ?></h5>
        <div class="form-row">
            <div>
                <table class="table">
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($repair['status']); ?>">
                                <?php echo getStatusLabel($repair['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'الأولوية' : 'Priority'; ?></td>
                        <td><?php echo ucfirst($repair['priority']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'تاريخ الإدخال' : 'Entry Date'; ?></td>
                        <td><?php echo formatDate($repair['entry_date']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'الفني' : 'Technician'; ?></td>
                        <td><?php echo $repair['technician_name'] ? sanitize($repair['technician_name']) : '-'; ?></td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="table">
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'تكلفة الإصلاح' : 'Repair Cost'; ?></td>
                        <td><?php echo formatCurrency($repair['repair_cost']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo $lang === 'ar' ? 'تكلفة القطع' : 'Parts Cost'; ?></td>
                        <td><?php echo formatCurrency($repair['parts_cost']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong><?php echo $lang === 'ar' ? 'الإجمالي' : 'Total'; ?></strong></td>
                        <td><strong class="text-primary"><?php echo formatCurrency($repair['total_cost']); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="mt-3">
            <h6><?php echo $lang === 'ar' ? 'وصف المشكلة' : 'Problem Description'; ?></h6>
            <p class="bg-secondary p-3 rounded"><?php echo nl2br(sanitize($repair['repair_description'])); ?></p>
        </div>
        
        <?php if ($repair['diagnosis']): ?>
        <div class="mt-3">
            <h6><?php echo $lang === 'ar' ? 'التشخيص' : 'Diagnosis'; ?></h6>
            <p class="bg-secondary p-3 rounded"><?php echo nl2br(sanitize($repair['diagnosis'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($repair['notes']): ?>
        <div class="mt-3">
            <h6><?php echo $lang === 'ar' ? 'ملاحظات' : 'Notes'; ?></h6>
            <p class="bg-secondary p-3 rounded"><?php echo nl2br(sanitize($repair['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="card-footer">
        <h6 class="mb-2"><?php echo $lang === 'ar' ? 'تحديث سريع للحالة' : 'Quick Status Update'; ?></h6>
        <form method="POST" action="" class="d-flex gap-2">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <select name="status" class="form-control form-select" style="width: auto;">
                <option value="pending" <?php echo $repair['status'] === 'pending' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'قيد الانتظار' : 'Pending'; ?></option>
                <option value="in_progress" <?php echo $repair['status'] === 'in_progress' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'قيد التنفيذ' : 'In Progress'; ?></option>
                <option value="completed" <?php echo $repair['status'] === 'completed' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'مكتمل' : 'Completed'; ?></option>
                <option value="delivered" <?php echo $repair['status'] === 'delivered' ? 'selected' : ''; ?>><?php echo $lang === 'ar' ? 'تم التسليم' : 'Delivered'; ?></option>
            </select>
            <button type="submit" class="btn btn-primary"><?php echo $lang === 'ar' ? 'تحديث' : 'Update'; ?></button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
