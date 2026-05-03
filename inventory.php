<?php
/**
 * ========================================
 * Inventory Management Page
 * ========================================
 */

require_once __DIR__ . '/includes/functions.php';
requireAuth();

$lang = $_SESSION['language'] ?? 'en';
define('PAGE_TITLE', $lang === 'ar' ? 'إدارة المخزون' : 'Inventory Management');

$action = $_GET['action'] ?? 'list';
$search = sanitize($_GET['search'] ?? '');
$filter = sanitize($_GET['filter'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', $lang === 'ar' ? 'طلب غير صالح' : 'Invalid request');
        redirect('inventory.php?action=add');
    }

    $partName = sanitize($_POST['part_name'] ?? '');
    $sku = sanitize($_POST['sku'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $quantity = max(0, (int)($_POST['quantity'] ?? 0));
    $minQuantity = max(0, (int)($_POST['min_quantity'] ?? 5));
    $unitPrice = max(0, (float)($_POST['unit_price'] ?? 0));
    $costPrice = max(0, (float)($_POST['cost_price'] ?? 0));
    $description = sanitize($_POST['description'] ?? '');

    if (empty($partName)) {
        setFlashMessage('error', $lang === 'ar' ? 'الرجاء إدخال اسم الجزء.' : 'Please enter part name.');
        redirect('inventory.php?action=add');
    }

    db()->insert('inventory', [
        'part_name' => $partName,
        'part_code' => $sku,
        'description' => $description,
        'quantity' => $quantity,
        'min_quantity' => $minQuantity,
        'unit_price' => $unitPrice,
        'cost_price' => $costPrice,
        'category' => $category,
        'is_active' => 1,
        'created_by' => $_SESSION['user_id'] ?? null
    ]);

    setFlashMessage('success', $lang === 'ar' ? 'تم إضافة العنصر بنجاح.' : 'Inventory item added successfully.');
    redirect('inventory.php');
}

if ($action === 'add') {
    include 'includes/header.php';
} else {
    $where = 'is_active = 1';
    $params = [];

    if ($search) {
        $where .= " AND (part_name LIKE :search OR part_code LIKE :search OR category LIKE :search)";
        $params['search'] = "%$search%";
    }

    if ($filter === 'low_stock') {
        $where .= " AND quantity <= min_quantity";
    }

    $totalItems = db()->fetchOne("SELECT COUNT(*) as count FROM inventory WHERE $where", $params)['count'];
    $pagination = paginate($totalItems, $page);

    $inventoryItems = db()->fetchAll(
        "SELECT * FROM inventory WHERE $where ORDER BY updated_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
        $params
    );

    include 'includes/header.php';
}
?>

<?php if ($action === 'add'): ?>
<div class="card fade-in">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus"></i> <?php echo $lang === 'ar' ? 'إضافة عنصر جديد' : 'Add Inventory Item'; ?></h3>
    </div>
    <div class="card-body">
        <form method="post" action="inventory.php?action=add">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label"><?php echo $lang === 'ar' ? 'اسم الجزء' : 'Part Name'; ?></label><input required type="text" name="part_name" class="form-control"></div>
                <div class="col-md-6"><label class="form-label"><?php echo $lang === 'ar' ? 'رمز الجزء' : 'SKU'; ?></label><input type="text" name="sku" class="form-control"></div>
                <div class="col-md-6"><label class="form-label"><?php echo $lang === 'ar' ? 'الفئة' : 'Category'; ?></label><input type="text" name="category" class="form-control"></div>
                <div class="col-md-3"><label class="form-label"><?php echo $lang === 'ar' ? 'الكمية' : 'Quantity'; ?></label><input required type="number" min="0" name="quantity" class="form-control" value="0"></div>
                <div class="col-md-3"><label class="form-label"><?php echo $lang === 'ar' ? 'الحد الأدنى' : 'Min Quantity'; ?></label><input required type="number" min="0" name="min_quantity" class="form-control" value="5"></div>
                <div class="col-md-6"><label class="form-label"><?php echo $lang === 'ar' ? 'سعر الوحدة' : 'Unit Price'; ?></label><input required type="number" step="0.01" min="0" name="unit_price" class="form-control" value="0.00"></div>
                <div class="col-md-6"><label class="form-label"><?php echo $lang === 'ar' ? 'سعر التكلفة' : 'Cost Price'; ?></label><input required type="number" step="0.01" min="0" name="cost_price" class="form-control" value="0.00"></div>
                <div class="col-12"><label class="form-label"><?php echo $lang === 'ar' ? 'الوصف' : 'Description'; ?></label><textarea name="description" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?php echo $lang === 'ar' ? 'حفظ' : 'Save'; ?></button>
                <a href="inventory.php" class="btn btn-secondary"><?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?></a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card fade-in">
    <div class="card-header d-flex justify-between align-center">
        <div>
            <h3 class="card-title"><i class="fas fa-boxes-stacked"></i> <?php echo $lang === 'ar' ? 'إدارة المخزون' : 'Inventory Management'; ?></h3>
            <p class="text-muted mb-0"><?php echo $lang === 'ar' ? 'إدارة قطع الغيار والمخزون.' : 'Manage parts and stock levels.'; ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="inventory.php?filter=low_stock" class="btn btn-warning btn-sm"><i class="fas fa-exclamation-triangle"></i> <?php echo $lang === 'ar' ? 'المخزون المنخفض' : 'Low Stock'; ?></a>
            <a href="inventory.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?php echo $lang === 'ar' ? 'إضافة عنصر' : 'Add Item'; ?></a>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex mb-3 gap-2">
            <input type="text" class="form-control" placeholder="<?php echo $lang === 'ar' ? 'بحث...' : 'Search...'; ?>" value="<?php echo sanitize($search); ?>" onkeyup="if(event.key === 'Enter') window.location='inventory.php?search='+encodeURIComponent(this.value)">
            <button class="btn btn-secondary" onclick="window.location='inventory.php?search=';"><?php echo $lang === 'ar' ? 'مسح' : 'Clear'; ?></button>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo $lang === 'ar' ? 'الجزء' : 'Part'; ?></th>
                        <th><?php echo $lang === 'ar' ? 'SKU' : 'SKU'; ?></th>
                        <th><?php echo $lang === 'ar' ? 'الفئة' : 'Category'; ?></th>
                        <th><?php echo $lang === 'ar' ? 'الكمية' : 'Quantity'; ?></th>
                        <th><?php echo $lang === 'ar' ? 'الحد الأدنى' : 'Min Qty'; ?></th>
                        <th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventoryItems)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted"><?php echo $lang === 'ar' ? 'لا توجد عناصر في المخزون.' : 'No inventory items found.'; ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventoryItems as $item): ?>
                        <tr>
                            <td><?php echo sanitize($item['part_name']); ?></td>
                            <td><?php echo sanitize($item['part_code']); ?></td>
                            <td><?php echo sanitize($item['category']); ?></td>
                            <td><?php echo intval($item['quantity']); ?></td>
                            <td><?php echo intval($item['min_quantity']); ?></td>
                            <td><span class="badge <?php echo $item['quantity'] <= $item['min_quantity'] ? 'badge-danger' : 'badge-success'; ?>"><?php echo $item['quantity'] <= $item['min_quantity'] ? ($lang === 'ar' ? 'منخفض' : 'Low') : ($lang === 'ar' ? 'جيد' : 'Good'); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer">
        <?php echo renderPagination($pagination, 'inventory.php'); ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include 'includes/footer.php';
