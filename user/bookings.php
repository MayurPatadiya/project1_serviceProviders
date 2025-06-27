<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_customer()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all bookings for the user
$bookings_query = "SELECT b.*, p.business_name, p.service_category, s.title as service_title
                   FROM bookings b
                   JOIN providers p ON b.provider_id = p.id
                   JOIN services s ON b.service_id = s.id
                   WHERE b.customer_id = ?
                   ORDER BY b.created_at DESC";
$bookings_stmt = mysqli_prepare($conn, $bookings_query);
mysqli_stmt_bind_param($bookings_stmt, 'i', $user_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);

$categories = get_service_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - ServiceHub</title>
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
                <a href="dashboard.php" class="nav-link">My Dashboard</a>
                <a href="bookings.php" class="nav-link active">My Bookings</a>
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
                <h1><i class="fas fa-calendar"></i> My Bookings</h1>
                <p>View and manage all your service bookings</p>
            </div>
            <div class="content-card">
                <?php if (mysqli_num_rows($bookings_result) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Provider</th>
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
                                                <strong><?php echo htmlspecialchars($booking['business_name']); ?></strong>
                                                <br>
                                                <small><?php echo htmlspecialchars($categories[$booking['service_category']] ?? $booking['service_category']); ?></small>
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
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../booking/view.php?id=<?php echo $booking['id']; ?>" class="btn-secondary btn-sm">View</a>
                                            <?php if ($booking['status'] === 'completed'): ?>
                                                <a href="../booking/review.php?id=<?php echo $booking['id']; ?>" class="btn-success btn-sm">Review</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No bookings found. <a href="../services.php">Find services</a> to get started!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 