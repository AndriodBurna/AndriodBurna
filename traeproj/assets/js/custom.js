// Custom JavaScript for School Management System

/**
 * School Management System JavaScript Functions
 * Provides enhanced functionality for the application
 */

// Global SMS object to avoid namespace pollution
window.SMS = {
    
    // Initialize the application
    init: function() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupAjax();
        this.setupNotifications();
    },
    
    // Setup event listeners
    setupEventListeners: function() {
        // Mobile navigation toggle
        const mobileToggle = document.querySelector('.mobile-nav-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
        
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 5000);
        });
        
        // Confirm delete buttons
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
        
        // Form validation enhancement
        const forms = document.querySelectorAll('form[data-validate="true"]');
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!SMS.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
        
        // Auto-calculate grades
        const scoreInputs = document.querySelectorAll('input[data-calculate-grade="true"]');
        scoreInputs.forEach(function(input) {
            input.addEventListener('input', function() {
                SMS.calculateGrade(this);
            });
        });
        
        // Photo upload preview
        const photoInputs = document.querySelectorAll('input[type="file"][data-preview]');
        photoInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                SMS.previewPhoto(this);
            });
        });
    },
    
    // Initialize UI components
    initializeComponents: function() {
        // Initialize DataTables (only if jQuery is available)
        if (typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
            const tables = document.querySelectorAll('table[data-table="true"]');
            tables.forEach(function(table) {
                $(table).DataTable({
                    responsive: true,
                    pageLength: 25,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
                });
            });
        }
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Initialize date pickers
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(function(input) {
            input.classList.add('form-control');
        });
        
        // Initialize select2 if available (only if jQuery is available)
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            const selectElements = document.querySelectorAll('select[data-select2="true"]');
            selectElements.forEach(function(select) {
                $(select).select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            });
        }
    },
    
    // Setup AJAX defaults
    setupAjax: function() {
        if (typeof $ !== 'undefined') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                beforeSend: function() {
                    SMS.showLoading();
                },
                complete: function() {
                    SMS.hideLoading();
                },
                error: function(xhr, status, error) {
                    SMS.handleAjaxError(xhr, status, error);
                }
            });
        }
    },
    
    // Initialize components with jQuery dependency check
    initComponents: function() {
        // Check if jQuery is available before initializing jQuery-dependent components
        if (typeof $ !== 'undefined') {
            this.initializeComponents();
        } else {
            // Initialize non-jQuery components only
            this.initializeNonJQueryComponents();
        }
    },
    
    // Initialize components that don't require jQuery
    initializeNonJQueryComponents: function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Initialize date pickers
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(function(input) {
            input.classList.add('form-control');
        });
    },
    
    // Setup notification system
    setupNotifications: function() {
        // Create notification container if it doesn't exist
        if (!document.querySelector('.notification-container')) {
            const container = document.createElement('div');
            container.className = 'notification-container';
            container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1050;';
            document.body.appendChild(container);
        }
    },
    
    // Show loading spinner
    showLoading: function() {
        const loadingHtml = `
            <div id="sms-loading" class="position-fixed top-50 start-50 translate-middle z-index-1050">
                <div class="loading-spinner"></div>
            </div>
            <div id="sms-loading-backdrop" class="position-fixed top-0 start-0 w-100 h-100 bg-black bg-opacity-25 z-index-1040"></div>
        `;
        
        if (!document.querySelector('#sms-loading')) {
            document.body.insertAdjacentHTML('beforeend', loadingHtml);
        }
    },
    
    // Hide loading spinner
    hideLoading: function() {
        const loading = document.querySelector('#sms-loading');
        const backdrop = document.querySelector('#sms-loading-backdrop');
        
        if (loading) loading.remove();
        if (backdrop) backdrop.remove();
    },
    
    // Show notification
    showNotification: function(message, type = 'info', duration = 5000) {
        const container = document.querySelector('.notification-container');
        const notificationId = 'notification-' + Date.now();
        
        const notificationHtml = `
            <div id="${notificationId}" class="notification alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', notificationHtml);
        
        // Auto-hide after duration
        setTimeout(function() {
            const notification = document.querySelector(`#${notificationId}`);
            if (notification) {
                notification.style.transition = 'opacity 0.5s ease';
                notification.style.opacity = '0';
                setTimeout(function() {
                    notification.remove();
                }, 500);
            }
        }, duration);
    },
    
    // Calculate grade from score
    calculateGrade: function(scoreInput) {
        const score = parseFloat(scoreInput.value);
        const gradeInput = document.querySelector(scoreInput.getAttribute('data-grade-target'));
        
        if (!gradeInput) return;
        
        let grade = '';
        if (isNaN(score) || score < 0) {
            grade = 'N/A';
        } else if (score >= 90) {
            grade = 'A+';
        } else if (score >= 80) {
            grade = 'A';
        } else if (score >= 75) {
            grade = 'B+';
        } else if (score >= 70) {
            grade = 'B';
        } else if (score >= 65) {
            grade = 'C+';
        } else if (score >= 60) {
            grade = 'C';
        } else if (score >= 55) {
            grade = 'D+';
        } else if (score >= 50) {
            grade = 'D';
        } else {
            grade = 'F';
        }
        
        gradeInput.value = grade;
    },
    
    // Preview photo upload
    previewPhoto: function(fileInput) {
        const preview = document.querySelector(fileInput.getAttribute('data-preview'));
        
        if (!preview || !fileInput.files || !fileInput.files[0]) return;
        
        const file = fileInput.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        
        reader.readAsDataURL(file);
    },
    
    // Enhanced form validation
    validateForm: function(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });
        
        // Email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(function(field) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (field.value && !emailRegex.test(field.value)) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });
        
        // Phone validation
        const phoneFields = form.querySelectorAll('input[data-type="phone"]');
        phoneFields.forEach(function(field) {
            const phoneRegex = /^[0-9\+\-\(\)\s]+$/;
            if (field.value && !phoneRegex.test(field.value)) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });
        
        return isValid;
    },
    
    // Handle AJAX errors
    handleAjaxError: function(xhr, status, error) {
        let message = 'An error occurred. Please try again.';
        
        if (xhr.status === 401) {
            message = 'You are not authorized to perform this action.';
        } else if (xhr.status === 403) {
            message = 'You do not have permission to perform this action.';
        } else if (xhr.status === 404) {
            message = 'The requested resource was not found.';
        } else if (xhr.status === 422) {
            message = 'Validation error. Please check your input.';
        } else if (xhr.status >= 500) {
            message = 'Server error. Please try again later.';
        }
        
        this.showNotification(message, 'danger');
    },
    
    // Toggle status via AJAX
    toggleStatus: function(element, type, id) {
        const url = `/api/${type}/${id}/toggle-status`;
        const currentStatus = element.getAttribute('data-status');
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.setAttribute('data-status', newStatus);
                element.classList.toggle('btn-success', newStatus === 'active');
                element.classList.toggle('btn-danger', newStatus === 'inactive');
                element.textContent = newStatus === 'active' ? 'Active' : 'Inactive';
                this.showNotification('Status updated successfully', 'success');
            } else {
                this.showNotification('Failed to update status', 'danger');
            }
        })
        .catch(error => {
            this.handleAjaxError({ status: 500 }, 'error', error);
        });
    },
    
    // Export data to CSV
    exportToCSV: function(tableId, filename = 'data.csv') {
        const table = document.querySelector(`#${tableId}`);
        if (!table) return;
        
        let csv = '';
        const rows = table.querySelectorAll('tr');
        
        rows.forEach(function(row, index) {
            const cells = row.querySelectorAll('th, td');
            const rowData = [];
            
            cells.forEach(function(cell, cellIndex) {
                // Skip action columns
                if (cell.querySelector('.btn-group')) return;
                
                let text = cell.textContent.trim().replace(/"/g, '""');
                if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                    text = `"${text}"`;
                }
                rowData.push(text);
            });
            
            if (rowData.length > 0) {
                csv += rowData.join(',') + '\n';
            }
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    },
    
    // Print table
    printTable: function(tableId) {
        const table = document.querySelector(`#${tableId}`);
        if (!table) return;
        
        const printWindow = window.open('', '_blank');
        const printContent = `
            <html>
            <head>
                <title>Print Report</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                ${table.outerHTML}
            </body>
            </html>
        `;
        
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.print();
    },
    
    // Auto-generate code/slug
    autoGenerateCode: function(sourceField, targetField, separator = '_') {
        const source = document.querySelector(sourceField);
        const target = document.querySelector(targetField);
        
        if (!source || !target) return;
        
        source.addEventListener('input', function() {
            const text = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, separator)
                .replace(/-+/g, separator)
                .trim();
            
            target.value = text;
        });
    },
    
    // Keyboard navigation for forms
    setupKeyboardNavigation: function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(function(input, index) {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        const nextInput = inputs[index + 1];
                        if (nextInput) {
                            nextInput.focus();
                        } else {
                            form.submit();
                        }
                    }
                });
            });
        });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    SMS.initComponents();
});

// Additional utility functions
window.SMSUtils = {
    // Format date
    formatDate: function(dateString, format = 'YYYY-MM-DD') {
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day);
    },
    
    // Format currency
    formatCurrency: function(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },
    
    // Validate email
    validateEmail: function(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },
    
    // Validate phone number
    validatePhone: function(phone) {
        const regex = /^[\+]?[1-9][\d]{0,15}$/;
        return regex.test(phone.replace(/\s/g, ''));
    },
    
    // Generate random string
    randomString: function(length = 8) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }
};