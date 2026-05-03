<?php
/**
 * ========================================
 * Registration Page
 * ========================================
 */

require_once __DIR__ . '/includes/Auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$formData = [];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $formData = [
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'full_name' => $_POST['full_name'] ?? '',
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];
        
        // Validate password confirmation
        if ($formData['password'] !== $formData['confirm_password']) {
            $error = 'Passwords do not match.';
        } else {
            $auth = new Auth();
            $result = $auth->register(
                $formData['username'],
                $formData['email'],
                $formData['password'],
                $formData['full_name']
            );
            
            if ($result['success']) {
                setFlashMessage('success', 'Registration successful! Please login.');
                redirect('login.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h1>Create Account</h1>
                    <p>Join us and start managing your repair shop.</p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo sanitize($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">
                                <i class="fas fa-id-card"></i>
                                Full Name
                            </label>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                class="form-control" 
                                placeholder="Enter your full name"
                                value="<?php echo isset($formData['full_name']) ? sanitize($formData['full_name']) : ''; ?>"
                                required 
                                autofocus
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="username">
                                <i class="fas fa-user"></i>
                                Username
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-control" 
                                placeholder="Choose a username"
                                value="<?php echo isset($formData['username']) ? sanitize($formData['username']) : ''; ?>"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control" 
                            placeholder="Enter your email"
                            value="<?php echo isset($formData['email']) ? sanitize($formData['email']) : ''; ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i>
                                Password
                            </label>
                            <div class="password-input-wrapper">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-control" 
                                    placeholder="Create a password"
                                    minlength="6"
                                    required
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                    <i class="fas fa-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i>
                                Confirm Password
                            </label>
                            <div class="password-input-wrapper">
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-control" 
                                    placeholder="Confirm your password"
                                    minlength="6"
                                    required
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                    <i class="fas fa-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="terms" id="terms" required>
                            <span class="checkmark"></span>
                            I agree to the <a href="#">Terms & Conditions</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-user-plus me-2"></i>
                        Create Account
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                </div>
            </div>
            
            <div class="auth-decoration">
                <div class="decoration-content">
                    <h2>Device Repair Management</h2>
                    <p>Streamline your repair shop operations with our comprehensive management system.</p>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i> Track repairs efficiently</li>
                        <li><i class="fas fa-check"></i> Manage inventory</li>
                        <li><i class="fas fa-check"></i> Generate invoices</li>
                        <li><i class="fas fa-check"></i> Multi-language support</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Apply saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>
