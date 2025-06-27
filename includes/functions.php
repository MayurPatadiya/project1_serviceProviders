<?php
// Utility functions for the ServiceHub marketplace

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random_string;
}

/**
 * Upload file with validation
 */
function upload_file($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_types)];
    }

    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File size too large. Maximum 5MB allowed'];
    }

    // Create target directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Generate unique filename
    $filename = generate_random_string() . '.' . $file_extension;
    $target_path = $target_dir . '/' . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $filename, 'path' => $target_path];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user is admin
 */
function is_admin() {
    return has_role('admin');
}

/**
 * Check if user is provider
 */
function is_provider() {
    return has_role('provider');
}

/**
 * Check if user is customer
 */
function is_customer() {
    return has_role('customer');
}

/**
 * Redirect with message
 */
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit();
}

/**
 * Display flash message
 */
function display_message() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $message = $_SESSION['message'];
        unset($_SESSION['message'], $_SESSION['message_type']);
        
        return "<div class='alert alert-$type'>$message</div>";
    }
    return '';
}

/**
 * Format currency
 */
function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format date
 */
function format_date($date) {
    return date('M j, Y', strtotime($date));
}

/**
 * Format datetime
 */
function format_datetime($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}

/**
 * Get provider rating
 */
function get_provider_rating($provider_id, $conn) {
    $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
              FROM reviews 
              WHERE provider_id = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $provider_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

/**
 * Check booking availability
 */
function check_booking_availability($provider_id, $date, $time, $duration, $conn, $exclude_booking_id = null) {
    $start_time = $time;
    $end_time = date('H:i:s', strtotime($time) + ($duration * 60));
    
    $query = "SELECT id FROM bookings 
              WHERE provider_id = ? 
              AND booking_date = ? 
              AND status IN ('pending', 'confirmed')
              AND (
                  (booking_time <= ? AND DATE_ADD(booking_time, INTERVAL duration MINUTE) > ?)
                  OR (booking_time < ? AND DATE_ADD(booking_time, INTERVAL duration MINUTE) >= ?)
                  OR (booking_time >= ? AND booking_time < ?)
              )";
    
    if ($exclude_booking_id) {
        $query .= " AND id != ?";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    
    if ($exclude_booking_id) {
        mysqli_stmt_bind_param($stmt, 'isssssss', $provider_id, $date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time, $exclude_booking_id);
    } else {
        mysqli_stmt_bind_param($stmt, 'isssssss', $provider_id, $date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) == 0;
}

/**
 * Create notification
 */
function create_notification($user_id, $title, $message, $type, $conn) {
    $query = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'isss', $user_id, $title, $message, $type);
    return mysqli_stmt_execute($stmt);
}

/**
 * Get unread notifications count
 */
function get_unread_notifications_count($user_id, $conn) {
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id, $conn) {
    $query = "UPDATE notifications SET is_read = TRUE WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $notification_id);
    return mysqli_stmt_execute($stmt);
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number
 */
function validate_phone($phone) {
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', $phone);
}

/**
 * Generate pagination links
 */
function generate_pagination($total_records, $records_per_page, $current_page, $base_url) {
    $total_pages = ceil($total_records / $records_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<div class="pagination">';
    
    // Previous button
    if ($current_page > 1) {
        $pagination .= '<a href="' . $base_url . '?page=' . ($current_page - 1) . '" class="page-link">&laquo; Previous</a>';
    }
    
    // Page numbers
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        $active_class = ($i == $current_page) ? 'active' : '';
        $pagination .= '<a href="' . $base_url . '?page=' . $i . '" class="page-link ' . $active_class . '">' . $i . '</a>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $pagination .= '<a href="' . $base_url . '?page=' . ($current_page + 1) . '" class="page-link">Next &raquo;</a>';
    }
    
    $pagination .= '</div>';
    return $pagination;
}

/**
 * Get service categories
 */
function get_service_categories() {
    return [
        'electrician' => 'Electrician',
        'plumber' => 'Plumber',
        'photographer' => 'Photographer',
        'cleaner' => 'Cleaner',
        'gardener' => 'Gardener',
        'painter' => 'Painter',
        'carpenter' => 'Carpenter',
        'mechanic' => 'Mechanic',
        'tutor' => 'Tutor',
        'designer' => 'Designer'
    ];
}

/**
 * Get booking status options
 */
function get_booking_status_options() {
    return [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];
}

/**
 * Get provider status options
 */
function get_provider_status_options() {
    return [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected'
    ];
}

/**
 * Log activity for admin tracking
 */
function log_activity($user_id, $action, $details, $conn) {
    // This could be expanded to include a dedicated activity log table
    // For now, we'll use notifications for admin tracking
    if (is_admin()) {
        create_notification(1, 'Admin Activity', "$action: $details", 'admin', $conn);
    }
}
?> 