<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_customer()) {
    header("Location: ../auth/login.php");
    exit();
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$booking_id) {
    redirect_with_message('../user/bookings.php', 'Invalid booking.', 'error');
}

// Get booking details
$query = "SELECT b.*, p.user_id as provider_user_id FROM bookings b JOIN providers p ON b.provider_id = p.id WHERE b.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $booking_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    redirect_with_message('../user/bookings.php', 'Booking not found.', 'error');
}

if ($booking['customer_id'] != $_SESSION['user_id']) {
    redirect_with_message('../user/bookings.php', 'You are not allowed to cancel this booking.', 'error');
}

if ($booking['status'] !== 'pending') {
    redirect_with_message('../booking/view.php?id=' . $booking_id, 'Only pending bookings can be cancelled.', 'error');
}

// Cancel the booking
$update_query = "UPDATE bookings SET status = 'cancelled' WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, 'i', $booking_id);
if (mysqli_stmt_execute($update_stmt)) {
    // Notify provider
    create_notification($booking['provider_user_id'], 'Booking Cancelled', 'A booking has been cancelled by the customer.', 'booking', $conn);
    redirect_with_message('../user/bookings.php', 'Booking cancelled successfully.', 'success');
} else {
    redirect_with_message('../booking/view.php?id=' . $booking_id, 'Failed to cancel booking. Please try again.', 'error');
} 