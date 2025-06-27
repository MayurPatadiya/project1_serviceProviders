// Main JavaScript file for ServiceHub marketplace

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeModals();
    initializeFileUploads();
    initializeFilters();
    initializeFormValidation();
    initializeNotifications();
    initializeBookingCalendar();
    initializeHamburgerMenu();
    initializeFilterToggle();
});

// Modal functionality
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const closeButtons = document.querySelectorAll('.close, .modal-close');

    // Open modal
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Close modal
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });

    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
}

// File upload functionality
function initializeFileUploads() {
    const fileUploads = document.querySelectorAll('.file-upload');

    fileUploads.forEach(upload => {
        const input = upload.querySelector('input[type="file"]');
        const label = upload.querySelector('.upload-label');

        upload.addEventListener('click', function() {
            input.click();
        });

        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const fileName = file.name;
                
                // Update label
                if (label) {
                    label.textContent = `Selected: ${fileName}`;
                }

                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    if (label) {
                        label.textContent = 'Choose file or drag here';
                    }
                    return;
                }

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid file type (JPG, PNG, or PDF)');
                    this.value = '';
                    if (label) {
                        label.textContent = 'Choose file or drag here';
                    }
                    return;
                }
            }
        });

        // Drag and drop functionality
        upload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#667eea';
            this.style.backgroundColor = '#f8f9ff';
        });

        upload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#e1e5e9';
            this.style.backgroundColor = 'white';
        });

        upload.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#e1e5e9';
            this.style.backgroundColor = 'white';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });
}

// Filter functionality
function initializeFilters() {
    const filterForm = document.getElementById('filter-form');
    if (!filterForm) return;

    const filterInputs = filterForm.querySelectorAll('input, select');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            applyFilters();
        });
    });

    // Price range slider
    const priceSlider = document.getElementById('price-range');
    const priceValue = document.getElementById('price-value');
    
    if (priceSlider && priceValue) {
        priceSlider.addEventListener('input', function() {
            priceValue.textContent = '$' + this.value;
            applyFilters();
        });
    }
}

function applyFilters() {
    const filterForm = document.getElementById('filter-form');
    const formData = new FormData(filterForm);
    const params = new URLSearchParams(formData);
    
    // Update URL without page reload
    const currentUrl = new URL(window.location);
    currentUrl.search = params.toString();
    window.history.pushState({}, '', currentUrl);
    
    // Send AJAX request to get filtered results
    fetch('services.php?' + params.toString())
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newResults = doc.querySelector('.services-grid');
            const currentResults = document.querySelector('.services-grid');
            
            if (newResults && currentResults) {
                currentResults.innerHTML = newResults.innerHTML;
            }
        })
        .catch(error => {
            console.error('Error applying filters:', error);
        });
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

    inputs.forEach(input => {
        if (!input.value.trim()) {
            showFieldError(input, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(input);
        }

        // Email validation
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                showFieldError(input, 'Please enter a valid email address');
                isValid = false;
            }
        }

        // Phone validation
        if (input.name === 'phone' && input.value) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phoneRegex.test(input.value.replace(/\s/g, ''))) {
                showFieldError(input, 'Please enter a valid phone number');
                isValid = false;
            }
        }

        // Password validation
        if (input.type === 'password' && input.value) {
            if (input.value.length < 6) {
                showFieldError(input, 'Password must be at least 6 characters long');
                isValid = false;
            }
        }
    });

    return isValid;
}

function showFieldError(input, message) {
    clearFieldError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.8rem';
    errorDiv.style.marginTop = '0.25rem';
    
    input.parentNode.appendChild(errorDiv);
    input.style.borderColor = '#dc3545';
}

function clearFieldError(input) {
    const existingError = input.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    input.style.borderColor = '#e1e5e9';
}

// Notification system
function initializeNotifications() {
    const notificationToggles = document.querySelectorAll('.notification-toggle');
    
    notificationToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const notificationId = this.getAttribute('data-notification-id');
            markNotificationAsRead(notificationId);
        });
    });
}

function markNotificationAsRead(notificationId) {
    fetch('includes/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to show notification as read
            const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notification) {
                notification.classList.add('read');
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

// Booking calendar functionality
function initializeBookingCalendar() {
    const bookingDateInput = document.getElementById('booking-date');
    if (!bookingDateInput) return;

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    bookingDateInput.setAttribute('min', today);

    // Check availability when date changes
    bookingDateInput.addEventListener('change', function() {
        checkAvailability();
    });
}

function checkAvailability() {
    const date = document.getElementById('booking-date').value;
    const providerId = document.getElementById('provider-id').value;
    const time = document.getElementById('booking-time').value;
    const duration = document.getElementById('duration').value;

    if (!date || !providerId) return;

    fetch('includes/check_availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            provider_id: providerId,
            date: date,
            time: time,
            duration: duration
        })
    })
    .then(response => response.json())
    .then(data => {
        const availabilityMessage = document.getElementById('availability-message');
        if (availabilityMessage) {
            if (data.available) {
                availabilityMessage.textContent = 'Time slot is available!';
                availabilityMessage.style.color = '#28a745';
                availabilityMessage.style.display = 'block';
            } else {
                availabilityMessage.textContent = 'Time slot is not available. Please choose another time.';
                availabilityMessage.style.color = '#dc3545';
                availabilityMessage.style.display = 'block';
            }
        }
    })
    .catch(error => {
        console.error('Error checking availability:', error);
    });
}

// Rating system
function initializeRatingSystem() {
    const ratingStars = document.querySelectorAll('.rating-stars .star');
    
    ratingStars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = index + 1;
            setRating(rating);
        });

        star.addEventListener('mouseenter', function() {
            const rating = index + 1;
            highlightStars(rating);
        });

        star.addEventListener('mouseleave', function() {
            const currentRating = document.querySelector('.rating-stars').getAttribute('data-rating') || 0;
            highlightStars(currentRating);
        });
    });
}

function setRating(rating) {
    const ratingContainer = document.querySelector('.rating-stars');
    const hiddenInput = document.getElementById('rating-input');
    
    ratingContainer.setAttribute('data-rating', rating);
    if (hiddenInput) {
        hiddenInput.value = rating;
    }
    
    highlightStars(rating);
}

function highlightStars(rating) {
    const stars = document.querySelectorAll('.rating-stars .star');
    
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('filled');
        } else {
            star.classList.remove('filled');
        }
    });
}

// AJAX helper functions
function makeRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };

    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }

    return fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        });
}

// Utility functions
function showLoading(element) {
    element.innerHTML = '<div class="loading"></div>';
}

function hideLoading(element, originalContent) {
    element.innerHTML = originalContent;
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    // Insert at the top of the page
    document.body.insertBefore(alertDiv, document.body.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Initialize rating system if present
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.rating-stars')) {
        initializeRatingSystem();
    }
});

// Export functions for use in other scripts
window.ServiceHub = {
    showAlert,
    formatCurrency,
    formatDate,
    makeRequest,
    validateForm
};

// Hamburger menu functionality
function initializeHamburgerMenu() {
    const hamburger = document.querySelector('.nav-hamburger');
    const navMenu = document.querySelector('.nav-menu');
    if (!hamburger || !navMenu) return;
    hamburger.addEventListener('click', function() {
        navMenu.classList.toggle('show');
    });
    // Hide menu on link click (mobile only)
    navMenu.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 900) {
                navMenu.classList.remove('show');
            }
        });
    });
}

// Filter toggle functionality
function initializeFilterToggle() {
    const filterToggle = document.getElementById('filter-toggle');
    const filterSidebar = document.getElementById('filter-sidebar');
    const filterSection = document.getElementById('filter-section');
    const filterClose = document.getElementById('filter-close');
    
    // Determine which filter element to use (sidebar for main pages, section for admin)
    const filterElement = filterSidebar || filterSection;
    
    if (!filterToggle || !filterElement) return;

    // Toggle button functionality
    filterToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleFilterElement();
    });
    
    // Close button functionality
    if (filterClose) {
        filterClose.addEventListener('click', function(e) {
            e.preventDefault();
            closeFilterElement();
        });
    }
    
    // Close filter element when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!filterToggle.contains(e.target) && !filterElement.contains(e.target)) {
                closeFilterElement();
            }
        }
    });
    
    function toggleFilterElement() {
        filterElement.classList.toggle('show');
        filterToggle.classList.toggle('active');
        
        // Update button text and icon
        const buttonText = filterToggle.querySelector('span');
        const buttonIcon = filterToggle.querySelector('i');
        
        if (filterElement.classList.contains('show')) {
            buttonText.textContent = 'Hide Filters';
            buttonIcon.className = 'fas fa-times';
        } else {
            buttonText.textContent = 'Filters';
            buttonIcon.className = 'fas fa-filter';
        }
    }
    
    function closeFilterElement() {
        filterElement.classList.remove('show');
        filterToggle.classList.remove('active');
        
        const buttonText = filterToggle.querySelector('span');
        const buttonIcon = filterToggle.querySelector('i');
        buttonText.textContent = 'Filters';
        buttonIcon.className = 'fas fa-filter';
    }
} 