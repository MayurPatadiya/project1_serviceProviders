<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    header("Location: ../auth/login.php");
    exit();
}

// Get dashboard statistics
$stats = [];

// Total users
$user_query = "SELECT COUNT(*) as total FROM users WHERE role != 'admin'";
$user_result = mysqli_query($conn, $user_query);
$stats['users'] = mysqli_fetch_assoc($user_result)['total'];

// Total providers
$provider_query = "SELECT COUNT(*) as total FROM providers";
$provider_result = mysqli_query($conn, $provider_query);
$stats['providers'] = mysqli_fetch_assoc($provider_result)['total'];

// Pending approvals
$pending_query = "SELECT COUNT(*) as total FROM providers WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending'] = mysqli_fetch_assoc($pending_result)['total'];

// Total bookings
$booking_query = "SELECT COUNT(*) as total FROM bookings";
$booking_result = mysqli_query($conn, $booking_query);
$stats['bookings'] = mysqli_fetch_assoc($booking_result)['total'];

// Recent reports
$reports_query = "SELECT r.*, u.username as reporter_name 
                  FROM reports r 
                  JOIN users u ON r.reporter_id = u.id 
                  WHERE r.status = 'pending' 
                  ORDER BY r.created_at DESC 
                  LIMIT 5";
$reports_result = mysqli_query($conn, $reports_query);

// Recent bookings
$recent_bookings_query = "SELECT b.*, u.username as customer_name, p.business_name as provider_name 
                          FROM bookings b 
                          JOIN users u ON b.customer_id = u.id 
                          JOIN providers p ON b.provider_id = p.id 
                          ORDER BY b.created_at DESC 
                          LIMIT 5";
$recent_bookings_result = mysqli_query($conn, $recent_bookings_query);

// Monthly revenue (simplified - assuming 10% commission)
$revenue_query = "SELECT SUM(total_amount) * 0.1 as revenue FROM bookings WHERE status = 'completed'";
$revenue_result = mysqli_query($conn, $revenue_query);
$stats['revenue'] = mysqli_fetch_assoc($revenue_result)['revenue'] ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ServiceHub</title>
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
                <a href="dashboard.php" class="nav-link">Admin Dashboard</a>
                <a href="providers.php" class="nav-link">Manage Providers</a>
                <a href="users.php" class="nav-link">Manage Users</a>
                <a href="reports.php" class="nav-link">Reports</a>
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
                <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            </div>

            <!-- Statistics Cards -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $stats['users']; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-tie"></i>
                    <h3><?php echo $stats['providers']; ?></h3>
                    <p>Service Providers</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Pending Approvals</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?php echo $stats['bookings']; ?></h3>
                    <p>Total Bookings</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3><?php echo format_currency($stats['revenue']); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2 style="margin: 15px 0px;">Quick Actions</h2>
                <div class="action-buttons">
                    <a href="providers.php?status=pending" class="btn-primary" style="margin: 0px 16px;">
                        <i class="fas fa-user-check"></i> Review Pending Providers
                    </a>
                    <a href="reports.php" class="btn-secondary" style="margin: 0px 16px;">
                        <i class="fas fa-flag"></i> View Reports
                    </a>
                    <a href="users.php" class="btn-secondary" style="margin: 0px 16px;">
                        <i class="fas fa-users-cog"></i> Manage Users
                    </a>
                    <a href="analytics.php" class="btn-secondary" style="margin: 0px 16px;">
                        <i class="fas fa-chart-bar"></i> View Analytics
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="dashboard-content">
                <div class="content-grid">
                    <!-- Recent Reports -->
                    <div class="content-card">
                        <h3 style="margin: 25px 0px;"><i class="fas fa-flag"></i> Recent Reports</h3>
                        <?php if (mysqli_num_rows($reports_result) > 0): ?>
                            <div class="table-container" style="margin-bottom: 35px;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Reporter</th>
                                            <th>Type</th>
                                            <th>Reason</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($report = mysqli_fetch_assoc($reports_result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $report['report_type']; ?>">
                                                        <?php echo ucfirst($report['report_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars(substr($report['reason'], 0, 50)) . '...'; ?></td>
                                                <td><?php echo format_date($report['created_at']); ?></td>
                                                <td>
                                                    <a href="reports.php?id=<?php echo $report['id']; ?>" class="btn-secondary btn-sm">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No recent reports</p>
                        <?php endif; ?>
                        <a href="reports.php" class="btn-secondary">View All Reports</a>
                    </div>

                    <!-- Recent Bookings -->
                    <div class="content-card">
                        <h3 style="margin: 25px 0px;"><i class="fas fa-calendar"></i> Recent Bookings</h3>
                        <?php if (mysqli_num_rows($recent_bookings_result) > 0): ?>
                            <div class="table-container" style="margin-bottom: 35px;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Provider</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = mysqli_fetch_assoc($recent_bookings_result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['provider_name']); ?></td>
                                                <td><?php echo format_currency($booking['total_amount']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo format_date($booking['created_at']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No recent bookings</p>
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