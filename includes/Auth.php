<?php
/**
 * ========================================
 * Authentication Class
 * ========================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Register a new user
     */
    public function register($username, $email, $password, $fullName, $role = 'user') {
        // Validate inputs
        if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }
        
        if (!validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }
        
        // Check if username exists
        $existing = $this->db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }
        
        // Check if email exists
        $existing = $this->db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered.'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        // Insert user
        try {
            $userId = $this->db->insert('users', [
                'username' => sanitize($username),
                'email' => sanitize($email),
                'password' => $hashedPassword,
                'full_name' => sanitize($fullName),
                'role' => $role
            ]);
            
            logActivity('register', 'user', $userId, "New user registered: {$username}");
            
            return ['success' => true, 'message' => 'Registration successful!', 'user_id' => $userId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    /**
     * Login user
     */
    public function login($username, $password, $remember = false) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required.'];
        }
        
        // Find user by username or email
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$username, $username]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }
        
        // Set session
        $this->setUserSession($user);
        
        // Update last login
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        
        logActivity('login', 'user', $user['id'], "User logged in: {$user['username']}");
        
        return ['success' => true, 'message' => 'Login successful!', 'user' => $user];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isLoggedIn()) {
            logActivity('logout', 'user', $_SESSION['user_id'], "User logged out");
        }
        
        // Destroy session
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }
    
    /**
     * Set user session data
     */
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['language'] = $user['language'] ?? 'en';
        $_SESSION['theme'] = $user['theme'] ?? 'light';
        $_SESSION['logged_in_at'] = time();
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $allowedFields = ['full_name', 'email', 'language', 'theme'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = sanitize($data[$field]);
            }
        }
        
        if (empty($updateData)) {
            return ['success' => false, 'message' => 'No data to update.'];
        }
        
        // Check email uniqueness if updating email
        if (isset($updateData['email'])) {
            $existing = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$updateData['email'], $userId]
            );
            if ($existing) {
                return ['success' => false, 'message' => 'Email already in use.'];
            }
        }
        
        try {
            $this->db->update('users', $updateData, 'id = :id', ['id' => $userId]);
            
            // Update session if current user
            if ($_SESSION['user_id'] == $userId) {
                foreach ($updateData as $key => $value) {
                    if ($key === 'full_name') {
                        $_SESSION['full_name'] = $value;
                    } elseif (isset($_SESSION[$key])) {
                        $_SESSION[$key] = $value;
                    }
                }
            }
            
            logActivity('update_profile', 'user', $userId, "Profile updated");
            
            return ['success' => true, 'message' => 'Profile updated successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Update failed. Please try again.'];
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters.'];
        }
        
        $user = $this->db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        try {
            $this->db->update('users', ['password' => $hashedPassword], 'id = :id', ['id' => $userId]);
            
            logActivity('change_password', 'user', $userId, "Password changed");
            
            return ['success' => true, 'message' => 'Password changed successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Password change failed. Please try again.'];
        }
    }
}
