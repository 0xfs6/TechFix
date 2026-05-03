<?php
/**
 * ========================================
 * User Profile Page
 * ========================================
 */

require_once __DIR__ . '/includes/functions.php';
requireAuth();

$lang = $_SESSION['language'] ?? 'en';
define('PAGE_TITLE', $lang === 'ar' ? 'الملف الشخصي' : 'Profile');

$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', $lang === 'ar' ? 'طلب غير صالح' : 'Invalid request');
        redirect('profile.php');
    }

    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (empty($fullName) || empty($email) || !validateEmail($email)) {
        setFlashMessage('error', $lang === 'ar' ? 'يرجى إدخال اسم صالح وبريد إلكتروني صالح.' : 'Please enter a valid full name and email address.');
        redirect('profile.php');
    }

    db()->update('users', [
        'full_name' => $fullName,
        'email' => $email
    ], 'id = :id', ['id' => $currentUser['id']]);

    setFlashMessage('success', $lang === 'ar' ? 'تم تحديث الملف الشخصي بنجاح.' : 'Profile updated successfully.');
    redirect('profile.php');
}

$csrfToken = generateCSRFToken();
include 'includes/header.php';
?>

<div class="card fade-in">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user"></i> <?php echo $lang === 'ar' ? 'الملف الشخصي' : 'Profile'; ?></h3>
    </div>
    <div class="card-body">
        <form method="post" action="profile.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="form-group">
                <label><?php echo $lang === 'ar' ? 'الاسم الكامل' : 'Full Name'; ?></label>
                <input type="text" name="full_name" class="form-control" value="<?php echo sanitize($currentUser['full_name']); ?>" required>
            </div>
            <div class="form-group mt-2">
                <label><?php echo $lang === 'ar' ? 'البريد الإلكتروني' : 'Email'; ?></label>
                <input type="email" name="email" class="form-control" value="<?php echo sanitize($currentUser['email']); ?>" required>
            </div>
            <div class="form-group mt-2">
                <label><?php echo $lang === 'ar' ? 'الدور' : 'Role'; ?></label>
                <input type="text" class="form-control" value="<?php echo sanitize(ucfirst($currentUser['role'])); ?>" readonly>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save"></i> <?php echo $lang === 'ar' ? 'حفظ التغييرات' : 'Save Changes'; ?></button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php';
