<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_provider()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get provider id
$provider_query = "SELECT id FROM providers WHERE user_id = ?";
$provider_stmt = mysqli_prepare($conn, $provider_query);
mysqli_stmt_bind_param($provider_stmt, 'i', $user_id);
mysqli_stmt_execute($provider_stmt);
$provider_result = mysqli_stmt_get_result($provider_stmt);
$provider = mysqli_fetch_assoc($provider_result);
$provider_id = $provider ? $provider['id'] : 0;

// Handle mark as completed
if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $booking_id = intval($_GET['complete']);
    $update_query = "UPDATE bookings SET status = 'completed' WHERE id = ? AND provider_id = ? AND status IN ('pending','confirmed')";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, 'ii', $booking_id, $provider_id);
    if (mysqli_stmt_execute($update_stmt)) {
        redirect_with_message('bookings.php', 'Booking marked as completed!', 'success');
    } else {
        redirect_with_message('bookings.php', 'Failed to update booking.', 'error');
    }
}

// Get all bookings for the provider
$bookings_query = "SELECT b.*, u.username as customer_name, u.email as customer_email, s.title as service_title
                   FROM bookings b
                   JOIN users u ON b.customer_id = u.id
                   JOIN services s ON b.service_id = s.id
                   WHERE b.provider_id = ?
                   ORDER BY b.created_at DESC";
$bookings_stmt = mysqli_prepare($conn, $bookings_query);
mysqli_stmt_bind_param($bookings_stmt, 'i', $provider_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);

$status_options = get_booking_status_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Bookings - ServiceHub</title>
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="services.php" class="nav-link">My Services</a>
                <a href="bookings.php" class="nav-link active">Bookings</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
            <button class="nav-hamburger" aria-label="Toggle navigation">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </nav>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <h1><i class="fas fa-calendar"></i> Bookings</h1>
                <p>View and manage all your service bookings</p>
            </div>
            <div class="content-card">
                <?php echo display_message(); ?>
                <?php if (mysqli_num_rows($bookings_result) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Customer</th>
                                    <th>Date & Time</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong>
                                                <br>
                                                <small><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo format_date($booking['booking_date']); ?>
                                            <br>
                                            <small><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></small>
                                        </td>
                                        <td><?php echo format_currency($booking['total_amount']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo $status_options[$booking['status']] ?? ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../booking/view.php?id=<?php echo $booking['id']; ?>" class="btn-secondary btn-sm">View</a>
                                            <?php if (in_array($booking['status'], ['pending','confirmed'])): ?>
                                                <a href="bookings.php?complete=<?php echo $booking['id']; ?>" class="btn-success btn-sm" onclick="return confirm('Mark this booking as completed?');">Mark Completed</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No bookings found. <a href="services.php">Add a service</a> to get started!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 