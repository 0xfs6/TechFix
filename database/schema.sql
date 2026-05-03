-- ========================================
-- Repair Shop Management System
-- Database Schema
-- ========================================

-- Create Database
CREATE DATABASE IF NOT EXISTS repair_shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE repair_shop_db;

-- ========================================
-- Users Table
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT NULL,
    language VARCHAR(5) DEFAULT 'en',
    theme VARCHAR(10) DEFAULT 'light',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Inventory / Parts Table
-- ========================================
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_name VARCHAR(150) NOT NULL,
    part_code VARCHAR(50) UNIQUE,
    description TEXT,
    quantity INT NOT NULL DEFAULT 0,
    min_quantity INT DEFAULT 5,
    unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    cost_price DECIMAL(10, 2) DEFAULT 0.00,
    category VARCHAR(100) DEFAULT NULL,
    supplier VARCHAR(150) DEFAULT NULL,
    location VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_part_name (part_name),
    INDEX idx_part_code (part_code),
    INDEX idx_category (category),
    INDEX idx_quantity (quantity),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Repairs Table
-- ========================================
CREATE TABLE IF NOT EXISTS repairs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_number VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    device_type ENUM('mobile', 'laptop', 'tablet', 'other') NOT NULL,
    device_name VARCHAR(150) NOT NULL,
    device_serial VARCHAR(100) DEFAULT NULL,
    repair_description TEXT NOT NULL,
    diagnosis TEXT,
    repair_cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    parts_cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_cost DECIMAL(10, 2) GENERATED ALWAYS AS (repair_cost + parts_cost) STORED,
    status ENUM('pending', 'in_progress', 'completed', 'delivered', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    entry_date DATE NOT NULL,
    estimated_completion DATE DEFAULT NULL,
    completion_date DATE DEFAULT NULL,
    delivery_date DATE DEFAULT NULL,
    notes TEXT,
    technician_id INT DEFAULT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_repair_number (repair_number),
    INDEX idx_customer_name (customer_name),
    INDEX idx_phone_number (phone_number),
    INDEX idx_device_type (device_type),
    INDEX idx_status (status),
    INDEX idx_entry_date (entry_date),
    FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Repair Parts (Junction Table)
-- ========================================
CREATE TABLE IF NOT EXISTS repair_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE RESTRICT,
    INDEX idx_repair_id (repair_id),
    INDEX idx_inventory_id (inventory_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Invoices Table
-- ========================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) NOT NULL UNIQUE,
    repair_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) DEFAULT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5, 2) DEFAULT 0.00,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    discount DECIMAL(10, 2) DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(10, 2) DEFAULT 0.00,
    balance_due DECIMAL(10, 2) GENERATED ALWAYS AS (total_amount - amount_paid) STORED,
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    payment_method ENUM('cash', 'card', 'bank_transfer', 'other') DEFAULT NULL,
    payment_date DATE DEFAULT NULL,
    notes TEXT,
    invoice_date DATE NOT NULL,
    due_date DATE DEFAULT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_repair_id (repair_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_invoice_date (invoice_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Activity Log Table
-- ========================================
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT DEFAULT NULL,
    description TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Settings Table
-- ========================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    category VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Insert Default Admin User
-- Password: admin123 (hashed with bcrypt)
-- ========================================
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@repairshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- ========================================
-- Insert Default Settings
-- ========================================
INSERT INTO settings (setting_key, setting_value, setting_type, category) VALUES
('shop_name', 'TechFix Repair Shop', 'text', 'general'),
('shop_address', '123 Tech Street, City, Country', 'textarea', 'general'),
('shop_phone', '+1 234 567 8900', 'text', 'general'),
('shop_email', 'contact@techfix.com', 'email', 'general'),
('currency', 'USD', 'text', 'general'),
('currency_symbol', '$', 'text', 'general'),
('tax_rate', '10', 'number', 'invoicing'),
('invoice_prefix', 'INV', 'text', 'invoicing'),
('repair_prefix', 'REP', 'text', 'repairs'),
('low_stock_threshold', '5', 'number', 'inventory');

-- ========================================
-- Insert Sample Inventory Items
-- ========================================
INSERT INTO inventory (part_name, part_code, description, quantity, min_quantity, unit_price, cost_price, category, created_by) VALUES
('iPhone Screen Replacement', 'IPH-SCR-001', 'Original iPhone screen assembly', 15, 5, 89.99, 45.00, 'iPhone Parts', 1),
('Samsung Battery', 'SAM-BAT-001', 'Samsung Galaxy battery replacement', 20, 5, 35.99, 18.00, 'Samsung Parts', 1),
('Laptop Keyboard', 'LAP-KEY-001', 'Universal laptop keyboard', 10, 3, 45.99, 22.00, 'Laptop Parts', 1),
('USB-C Charging Port', 'USB-C-001', 'USB Type-C charging port', 25, 10, 15.99, 8.00, 'Charging Parts', 1),
('Laptop RAM 8GB', 'LAP-RAM-8G', 'DDR4 8GB RAM module', 8, 3, 55.99, 30.00, 'Laptop Parts', 1);
