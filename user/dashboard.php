<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if user is customer
if (!is_customer()) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's bookings
$bookings_query = "SELECT b.*, p.business_name, p.service_category, s.title as service_title,
                   AVG(r.rating) as provider_rating
                   FROM bookings b 
                   JOIN providers p ON b.provider_id = p.id 
                   JOIN services s ON b.service_id = s.id
                   LEFT JOIN reviews r ON p.id = r.provider_id AND r.status = 'active'
                   WHERE b.customer_id = ? 
                   GROUP BY b.id
                   ORDER BY b.created_at DESC 
                   LIMIT 10";
$bookings_stmt = mysqli_prepare($conn, $bookings_query);
mysqli_stmt_bind_param($bookings_stmt, 'i', $user_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);

// Get user's reviews
$reviews_query = "SELECT r.*, p.business_name, s.title as service_title
FROM reviews r
JOIN providers p ON r.provider_id = p.id
JOIN bookings b ON r.booking_id = b.id
JOIN services s ON b.service_id = s.id
WHERE r.customer_id = ?
ORDER BY r.created_at DESC
LIMIT 5";
$reviews_stmt = mysqli_prepare($conn, $reviews_query);
mysqli_stmt_bind_param($reviews_stmt, 'i', $user_id);
mysqli_stmt_execute($reviews_stmt);
$reviews_result = mysqli_stmt_get_result($reviews_stmt);

// Get user statistics
$stats = [];

// Total bookings
$total_bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE customer_id = ?";
$total_bookings_stmt = mysqli_prepare($conn, $total_bookings_query);
mysqli_stmt_bind_param($total_bookings_stmt, 'i', $user_id);
mysqli_stmt_execute($total_bookings_stmt);
$stats['total_bookings'] = mysqli_fetch_assoc(mysqli_stmt_get_result($total_bookings_stmt))['total'];

// Completed bookings
$completed_bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE customer_id = ? AND status = 'completed'";
$completed_bookings_stmt = mysqli_prepare($conn, $completed_bookings_query);
mysqli_stmt_bind_param($completed_bookings_stmt, 'i', $user_id);
mysqli_stmt_execute($completed_bookings_stmt);
$stats['completed_bookings'] = mysqli_fetch_assoc(mysqli_stmt_get_result($completed_bookings_stmt))['total'];

// Pending bookings
$pending_bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE customer_id = ? AND status IN ('pending', 'confirmed')";
$pending_bookings_stmt = mysqli_prepare($conn, $pending_bookings_query);
mysqli_stmt_bind_param($pending_bookings_stmt, 'i', $user_id);
mysqli_stmt_execute($pending_bookings_stmt);
$stats['pending_bookings'] = mysqli_fetch_assoc(mysqli_stmt_get_result($pending_bookings_stmt))['total'];

// Total spent
$total_spent_query = "SELECT SUM(total_amount) as total FROM bookings WHERE customer_id = ? AND status = 'completed'";
$total_spent_stmt = mysqli_prepare($conn, $total_spent_query);
mysqli_stmt_bind_param($total_spent_stmt, 'i', $user_id);
mysqli_stmt_execute($total_spent_stmt);
$stats['total_spent'] = mysqli_fetch_assoc(mysqli_stmt_get_result($total_spent_stmt))['total'] ?: 0;

// Get unread notifications
$notifications_query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT 5";
$notifications_stmt = mysqli_prepare($conn, $notifications_query);
mysqli_stmt_bind_param($notifications_stmt, 'i', $user_id);
mysqli_stmt_execute($notifications_stmt);
$notifications_result = mysqli_stmt_get_result($notifications_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - ServiceHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
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
                <a href="bookings.php" class="nav-link">My Bookings</a>
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
                <h1><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p>Manage your bookings and account</p>
            </div>

            <?php echo display_message(); ?>

            <!-- Statistics Cards -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?php echo $stats['total_bookings']; ?></h3>
                    <p>Total Bookings</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $stats['completed_bookings']; ?></h3>
                    <p>Completed</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $stats['pending_bookings']; ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3><?php echo format_currency($stats['total_spent']); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 style="margin: 25px 0px;">Quick Actions</h2>
                <div class="action-buttons"">
                    <a href="../services.php" class="btn-primary" style="margin: 0px 16px;">
                        <i class="fas fa-search"></i> Find Services
                    </a>
                    <a href="bookings.php" class="btn-secondary">
                        <i class="fas fa-calendar"></i> View All Bookings
                    </a>
                    <a href="profile.php" class="btn-secondary" style="margin: 0px 16px;>
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="content-grid">
                    <!-- Recent Bookings -->
                    <div class="content-card">
                        <h3 style="margin: 25px 0px;"><i class="fas fa-calendar"></i> Recent Bookings</h3>
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
                                                        <small><?php echo htmlspecialchars($booking['service_category']); ?></small>
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
                                                <td style="display: flex;">
                                                    <a href="../booking/view.php?id=<?php echo $booking['id']; ?>" class="btn-secondary btn-sm">
                                                        View
                                                    </a>
                                                    <?php if ($booking['status'] === 'completed'): ?>
                                                        <a href="../booking/review.php?id=<?php echo $booking['id']; ?>" class="btn-success btn-sm">
                                                            Review
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p style="margin-bottom: 20px;">No bookings yet. <a href="../services.php">Find services</a> to get started!</p>
                        <?php endif; ?>
                        <a href="bookings.php" class="btn-secondary">View All Bookings</a>
                    </div>

                    <!-- Recent Reviews -->
                    <div class="content-card">
                        <h3 style="margin-top: 30px;"><i class="fas fa-star"></i> My Reviews</h3>
                        <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                            <div class="reviews-list">
                                <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                                    <div class="review-item">
                                        <div class="review-header">
                                            <strong><?php echo htmlspecialchars($review['business_name']); ?></strong>
                                            <div class="rating">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $review['rating']) {
                                                        echo '<i class="fas fa-star filled"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                                        <small><?php echo format_date($review['created_at']); ?></small>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p>No reviews yet. Complete a booking to leave a review!</p>
                        <?php endif; ?>
                    </div>

                    <!-- Notifications -->
                    <div class="content-card">
                        <h3 style="margin-top: 15px;"><i class="fas fa-bell"></i> Recent Notifications</h3>
                        <?php if (mysqli_num_rows($notifications_result) > 0): ?>
                            <div class="notifications-list">
                                <?php while ($notification = mysqli_fetch_assoc($notifications_result)): ?>
                                    <div class="notification-item">
                                        <div class="notification-content">
                                            <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small><?php echo format_datetime($notification['created_at']); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p>No new notifications</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html> 