/**
 * ========================================
 * Main JavaScript
 * Repair Shop Management System
 * ========================================
 */

// ========================================
// Theme Toggle
// ========================================
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Update icon
    const themeIcon = document.getElementById('themeIcon');
    if (themeIcon) {
        themeIcon.classList.remove('fa-sun', 'fa-moon');
        themeIcon.classList.add(newTheme === 'dark' ? 'fa-sun' : 'fa-moon');
    }
    
    // Save preference to server
    saveUserPreference('theme', newTheme);
}

// Initialize theme from localStorage
(function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
})();

// ========================================
// Language Toggle
// ========================================
function toggleLanguage() {
    const currentLang = document.documentElement.getAttribute('lang');
    const newLang = currentLang === 'ar' ? 'en' : 'ar';
    
    // Save preference and reload
    saveUserPreference('language', newLang, true);
}

// ========================================
// Save User Preference
// ========================================
function saveUserPreference(key, value, reload = false) {
    fetch('ajax/save-preference.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ key, value })
    })
    .then(response => response.json())
    .then(data => {
        if (reload && data.success) {
            window.location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

// ========================================
// Sidebar Toggle
// ========================================
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.querySelector('.sidebar-toggle');
    
    if (window.innerWidth <= 992 && 
        sidebar.classList.contains('active') && 
        !sidebar.contains(e.target) && 
        !toggle.contains(e.target)) {
        toggleSidebar();
    }
});

// ========================================
// Modal Functions
// ========================================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on backdrop click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal-backdrop.active');
        if (activeModal) {
            activeModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});

// ========================================
// Form Validation
// ========================================
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// ========================================
// Confirmation Dialog
// ========================================
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Delete confirmation
function confirmDelete(url, itemName = 'this item') {
    if (confirm(`Are you sure you want to delete ${itemName}? This action cannot be undone.`)) {
        window.location.href = url;
    }
}

// ========================================
// Toast Notifications
// ========================================
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ========================================
// Search & Filter
// ========================================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Live search
const searchInputs = document.querySelectorAll('.search-input');
searchInputs.forEach(input => {
    input.addEventListener('input', debounce(function() {
        const searchTerm = this.value.toLowerCase();
        const table = document.querySelector(this.dataset.target);
        
        if (table) {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }
    }, 300));
});

// ========================================
// Data Table Sorting
// ========================================
function sortTable(table, column, type = 'string') {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const header = table.querySelectorAll('th')[column];
    const isAsc = header.classList.contains('sort-asc');
    
    // Remove sort classes from all headers
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    rows.sort((a, b) => {
        let aVal = a.cells[column].textContent.trim();
        let bVal = b.cells[column].textContent.trim();
        
        if (type === 'number') {
            aVal = parseFloat(aVal.replace(/[^0-9.-]/g, '')) || 0;
            bVal = parseFloat(bVal.replace(/[^0-9.-]/g, '')) || 0;
        } else if (type === 'date') {
            aVal = new Date(aVal);
            bVal = new Date(bVal);
        }
        
        if (isAsc) {
            return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
        } else {
            return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
        }
    });
    
    header.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
    
    rows.forEach(row => tbody.appendChild(row));
}

// ========================================
// Format Currency
// ========================================
function formatCurrency(amount, currency = '$') {
    return currency + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// ========================================
// Print Functions
// ========================================
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="assets/css/print.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                @media print {
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            ${element.innerHTML}
            <script>
                window.onload = function() {
                    window.print();
                    window.close();
                };
            </script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// ========================================
// Date Picker Initialization
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Auto-set today's date for date inputs without value
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value && input.dataset.default === 'today') {
            input.value = new Date().toISOString().split('T')[0];
        }
    });
});

// ========================================
// Dynamic Form Fields
// ========================================
function addFormRow(containerId, template) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const div = document.createElement('div');
    div.className = 'form-row dynamic-row fade-in';
    div.innerHTML = template;
    container.appendChild(div);
}

function removeFormRow(button) {
    const row = button.closest('.dynamic-row');
    if (row) {
        row.remove();
    }
}

// ========================================
// Calculate Totals
// ========================================
function calculateTotal(repairCostId, partsCostId, totalId) {
    const repairCost = parseFloat(document.getElementById(repairCostId).value) || 0;
    const partsCost = parseFloat(document.getElementById(partsCostId).value) || 0;
    const total = repairCost + partsCost;
    
    document.getElementById(totalId).value = total.toFixed(2);
}

// ========================================
// Auto-resize Textarea
// ========================================
document.querySelectorAll('textarea.auto-resize').forEach(textarea => {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});

// ========================================
// Loading State
// ========================================
function setLoading(element, isLoading) {
    if (isLoading) {
        element.disabled = true;
        element.dataset.originalText = element.innerHTML;
        element.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
    } else {
        element.disabled = false;
        element.innerHTML = element.dataset.originalText;
    }
}

// ========================================
// AJAX Form Submit
// ========================================
function submitFormAjax(form, callback) {
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: form.method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (callback) callback(data);
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    });
}

// ========================================
// Copy to Clipboard
// ========================================
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

// ========================================
// Number Input Validation
// ========================================
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', function() {
        if (this.min && parseFloat(this.value) < parseFloat(this.min)) {
            this.value = this.min;
        }
        if (this.max && parseFloat(this.value) > parseFloat(this.max)) {
            this.value = this.max;
        }
    });
});

console.log('Repair Shop Management System initialized.');
