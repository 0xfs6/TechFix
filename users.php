<?php
/**
 * ========================================
 * Users Management Page
 * ========================================
 */

require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$lang = $_SESSION['language'] ?? 'en';
define('PAGE_TITLE', $lang === 'ar' ? 'المستخدمين' : 'Users');

// load users
$users = db()->fetchAll("SELECT id, username, full_name, email, role, is_active, created_at FROM users ORDER BY created_at DESC");

include 'includes/header.php';
?>

<div class="card fade-in">
    <div class="card-header d-flex justify-between align-center">
        <div>
            <h3 class="card-title"><i class="fas fa-users"></i> <?php echo $lang === 'ar' ? 'إدارة المستخدمين' : 'Users Management'; ?></h3>
            <p class="text-muted mb-0"><?php echo $lang === 'ar' ? 'تحكم في المستخدمين وتفاصيلهم.' : 'Manage system users and roles.'; ?></p>
        </div>
        <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> <?php echo $lang === 'ar' ? 'إضافة مستخدم' : 'Add User'; ?></a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo $lang === 'ar' ? 'اسم المستخدم' : 'Username'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الاسم الكامل' : 'Full Name'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'البريد الإلكتروني' : 'Email'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الدور' : 'Role'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                    <th><?php echo $lang === 'ar' ? 'تاريخ الإنشاء' : 'Created At'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4"><?php echo $lang === 'ar' ? 'لا يوجد مستخدمون بعد.' : 'No users found yet.'; ?></td>
                </tr>
                <?php else: ?>
                    <?php foreach ($users as $idx => $user): ?>
                    <tr>
                        <td><?php echo $idx + 1; ?></td>
                        <td><?php echo sanitize($user['username']); ?></td>
                        <td><?php echo sanitize($user['full_name']); ?></td>
                        <td><?php echo sanitize($user['email']); ?></td>
                        <td><?php echo sanitize(ucfirst($user['role'])); ?></td>
                        <td>
                            <span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $user['is_active'] ? ($lang === 'ar' ? 'نشط' : 'Active') : ($lang === 'ar' ? 'معطل' : 'Inactive'); ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($user['created_at'], 'Y-m-d'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php';
