<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if user is provider
if (!is_provider()) {
    header("Location: ../index.php");
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

// Stats
$stats = [];
// Total services
$total_services_query = "SELECT COUNT(*) as total FROM services WHERE provider_id = ?";
$total_services_stmt = mysqli_prepare($conn, $total_services_query);
mysqli_stmt_bind_param($total_services_stmt, 'i', $provider_id);
mysqli_stmt_execute($total_services_stmt);
$stats['total_services'] = mysqli_fetch_assoc(mysqli_stmt_get_result($total_services_stmt))['total'];
// Total bookings
$total_bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE provider_id = ?";
$total_bookings_stmt = mysqli_prepare($conn, $total_bookings_query);
mysqli_stmt_bind_param($total_bookings_stmt, 'i', $provider_id);
mysqli_stmt_execute($total_bookings_stmt);
$stats['total_bookings'] = mysqli_fetch_assoc(mysqli_stmt_get_result($total_bookings_stmt))['total'];
// Completed bookings
$completed_bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE provider_id = ? AND status = 'completed'";
$completed_bookings_stmt = mysqli_prepare($conn, $completed_bookings_query);
mysqli_stmt_bind_param($completed_bookings_stmt, 'i', $provider_id);
mysqli_stmt_execute($completed_bookings_stmt);
$stats['completed_bookings'] = mysqli_fetch_assoc(mysqli_stmt_get_result($completed_bookings_stmt))['total'];
// Total earnings
$total_earnings_query = "SELECT SUM(total_amount) as total FROM bookings WHERE provider_id = ? AND status = 'completed'";
$total_earnings_stmt = mysqli_prepare($conn, $total_earnings_query);
mysqli_stmt_bind_param($total_earnings_stmt, 'i', $provider_id);
mysqli_stmt_execute($total_earnings_stmt);
$stats['total_earnings'] = mysqli_fetch_assoc(mysqli_stmt_get_result($total_earnings_stmt))['total'] ?: 0;

// Recent bookings
$recent_bookings_query = "SELECT b.*, u.username as customer_name, s.title as service_title FROM bookings b JOIN users u ON b.customer_id = u.id JOIN services s ON b.service_id = s.id WHERE b.provider_id = ? ORDER BY b.created_at DESC LIMIT 10";
$recent_bookings_stmt = mysqli_prepare($conn, $recent_bookings_query);
mysqli_stmt_bind_param($recent_bookings_stmt, 'i', $provider_id);
mysqli_stmt_execute($recent_bookings_stmt);
$recent_bookings_result = mysqli_stmt_get_result($recent_bookings_stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard - ServiceHub</title>
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
                <a href="dashboard.php" class="nav-link">Provider Dashboard</a>
                <a href="services.php" class="nav-link">My Services</a>
                <a href="bookings.php" class="nav-link">Bookings</a>
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
                <h1><i class="fas fa-briefcase"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p>Manage your services, bookings, and earnings</p>
            </div>

            <!-- Statistics Cards -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-cogs"></i>
                    <h3><?php echo $stats['total_services']; ?></h3>
                    <p>Total Services</p>
                </div>
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
                    <i class="fas fa-dollar-sign"></i>
                    <h3><?php echo format_currency($stats['total_earnings']); ?></h3>
                    <p>Total Earnings</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 style="margin: 15px 0px;">Quick Actions</h2>
                <div class="action-buttons">
                    <a href="services.php" class="btn-primary" style="margin: 0px 16px;">
                        <i class="fas fa-plus"></i> Add Service
                    </a>
                    <a href="bookings.php" class="btn-secondary" ">
                        <i class="fas fa-calendar"></i> View Bookings
                    </a>
                    <a href="profile.php" class="btn-secondary" style="margin: 0px 16px;">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="content-grid">
                    <!-- Recent Bookings -->
                    <div class="content-card ">
                        <h3 style="margin-top: 30px;"><i class="fas fa-calendar"></i> Recent Bookings</h3>
                        <?php if (mysqli_num_rows($recent_bookings_result) > 0): ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Service</th>
                                            <th>Date & Time</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = mysqli_fetch_assoc($recent_bookings_result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
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
                                                    <a href="../booking/view.php?id=<?php echo $booking['id']; ?>" class="btn-secondary btn-sm">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p style="margin-bottom: 30px;">No bookings yet. <a href="services.php">Add a service</a> to get started!</p>
                        <?php endif; ?>
                        <a href="bookings.php" class="btn-secondary">View All Bookings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html> 