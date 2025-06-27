<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../auth/login.php");
    exit();
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$booking_id) {
    header("Location: ../user/bookings.php");
    exit();
}

// Get booking details
$query = "SELECT b.*, 
                 p.business_name, p.service_category, p.location as provider_location, p.phone as provider_phone, u.username as provider_username, u.email as provider_email, u.profile_image as provider_image,
                 s.title as service_title, s.description as service_description, s.price as service_price,
                 c.username as customer_username, c.email as customer_email, c.phone as customer_phone, c.profile_image as customer_image
          FROM bookings b
          JOIN providers p ON b.provider_id = p.id
          JOIN users u ON p.user_id = u.id
          JOIN services s ON b.service_id = s.id
          JOIN users c ON b.customer_id = c.id
          WHERE b.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $booking_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    header("Location: ../user/bookings.php");
    exit();
}

// Only allow access to the booking's customer or provider
if (!(
    (is_customer() && $_SESSION['user_id'] == $booking['customer_id']) ||
    (is_provider() && $_SESSION['user_id'] == $booking['provider_id']) ||
    is_admin()
)) {
    header("Location: ../index.php");
    exit();
}

$categories = get_service_categories();
$status_options = get_booking_status_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - ServiceHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.php">
                    <i class="fas fa-tools"></i>
                    <span>ServiceHub</span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="../index.php" class="nav-link">Home</a>
                <a href="../services.php" class="nav-link">Services</a>
                <?php if (is_customer()): ?>
                    <a href="../user/dashboard.php" class="nav-link">My Dashboard</a>
                    <a href="../user/bookings.php" class="nav-link">My Bookings</a>
                <?php elseif (is_provider()): ?>
                    <a href="../provider/dashboard.php" class="nav-link">Provider Dashboard</a>
                    <a href="../provider/bookings.php" class="nav-link">Bookings</a>
                <?php endif; ?>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <h1><i class="fas fa-calendar-check"></i> Booking Details</h1>
                <p>View all details for this booking</p>
            </div>
            <div class="content-card">
                <h2>Service: <?php echo htmlspecialchars($booking['service_title']); ?></h2>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($booking['service_description']); ?></p>
                <p><strong>Provider:</strong> <?php echo htmlspecialchars($booking['business_name']); ?> (<?php echo htmlspecialchars($booking['provider_email']); ?>)</p>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($booking['customer_username']); ?> (<?php echo htmlspecialchars($booking['customer_email']); ?>)</p>
                <table style="margin: 1.5rem 0; width: 100%;">
                    <tr>
                        <th>Date</th>
                        <td><?php echo format_date($booking['booking_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Time</th>
                        <td><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></td>
                    </tr>
                    <tr>
                        <th>Duration</th>
                        <td><?php echo $booking['duration']; ?> minutes</td>
                    </tr>
                    <tr>
                        <th>Total Amount</th>
                        <td><?php echo format_currency($booking['total_amount']); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                    </tr>
                    <tr>
                        <th>Special Requirements</th>
                        <td><?php echo htmlspecialchars($booking['requirements']); ?></td>
                    </tr>
                </table>
                <?php if (is_customer() && $booking['status'] === 'completed'): ?>
                    <a href="review.php?id=<?php echo $booking['id']; ?>" class="btn-success">Leave a Review</a>
                <?php endif; ?>
                <?php if (is_customer() && $booking['status'] === 'pending'): ?>
                    <a href="cancel.php?id=<?php echo $booking['id']; ?>" class="btn-danger" onclick="return confirm('Cancel this booking?');">Cancel Booking</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 