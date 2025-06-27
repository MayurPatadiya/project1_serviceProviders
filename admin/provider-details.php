<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    header("Location: ../auth/login.php");
    exit();
}

$provider_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$provider_id) {
    header("Location: providers.php");
    exit();
}

// Get provider info and linked user
$provider_query = "SELECT p.*, u.username, u.email, u.status as user_status, u.profile_image FROM providers p JOIN users u ON p.user_id = u.id WHERE p.id = ?";
$provider_stmt = mysqli_prepare($conn, $provider_query);
mysqli_stmt_bind_param($provider_stmt, 'i', $provider_id);
mysqli_stmt_execute($provider_stmt);
$provider_result = mysqli_stmt_get_result($provider_stmt);
$provider = mysqli_fetch_assoc($provider_result);

if (!$provider) {
    header("Location: providers.php");
    exit();
}

$profile_img = $provider['profile_image'] ? '../uploads/profiles/' . $provider['profile_image'] : '../assets/images/default-avatar.png';
$categories = get_service_categories();
$status_options = get_provider_status_options();

// Get recent services
$services = [];
$services_query = "SELECT * FROM services WHERE provider_id = ? ORDER BY created_at DESC LIMIT 5";
$services_stmt = mysqli_prepare($conn, $services_query);
mysqli_stmt_bind_param($services_stmt, 'i', $provider_id);
mysqli_stmt_execute($services_stmt);
$services_result = mysqli_stmt_get_result($services_stmt);
while ($row = mysqli_fetch_assoc($services_result)) {
    $services[] = $row;
}

// Get recent bookings
$bookings = [];
$bookings_query = "SELECT b.*, u.username as customer_name, s.title as service_title FROM bookings b JOIN users u ON b.customer_id = u.id JOIN services s ON b.service_id = s.id WHERE b.provider_id = ? ORDER BY b.created_at DESC LIMIT 5";
$bookings_stmt = mysqli_prepare($conn, $bookings_query);
mysqli_stmt_bind_param($bookings_stmt, 'i', $provider_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);
while ($row = mysqli_fetch_assoc($bookings_result)) {
    $bookings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Details - Admin - ServiceHub</title>
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
                <a href="dashboard.php" class="nav-link">Admin Dashboard</a>
                <a href="providers.php" class="nav-link">Manage Providers</a>
                <a href="users.php" class="nav-link">Manage Users</a>
                <a href="reports.php" class="nav-link">Reports</a>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <h1><i class="fas fa-user-tie"></i> Provider Details</h1>
                <p>View all information for this provider</p>
            </div>
            <div class="content-card">
                <div style="display:flex;align-items:center;gap:2rem;">
                    <img src="<?php echo $profile_img; ?>" alt="Profile Image" class="avatar">
                    <div>
                        <h2><?php echo htmlspecialchars($provider['business_name']); ?></h2>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($provider['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($provider['email']); ?></p>
                        <p><strong>User Status:</strong> <span class="status-badge status-<?php echo $provider['user_status']; ?>"><?php echo ucfirst($provider['user_status']); ?></span></p>
                        <p><strong>Provider Status:</strong> <span class="status-badge status-<?php echo $provider['status']; ?>"><?php echo ucfirst($provider['status']); ?></span></p>
                        <p><strong>Registered:</strong> <?php echo format_date($provider['created_at']); ?></p>
                    </div>
                </div>
                <hr>
                <h3>Business Info</h3>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($categories[$provider['service_category']] ?? $provider['service_category']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($provider['description']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($provider['location']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($provider['phone']); ?></p>
                <p><strong>Hourly Rate:</strong> <?php echo format_currency($provider['hourly_rate']); ?></p>
                <?php if ($provider['kyc_document']): ?>
                    <p><strong>KYC Document:</strong> <a href="../uploads/kyc/<?php echo htmlspecialchars($provider['kyc_document']); ?>" target="_blank">View</a></p>
                <?php endif; ?>
                <hr>
                <h3>Recent Services</h3>
                <?php if ($services): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Price</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['title']); ?></td>
                                        <td><?php echo format_currency($service['price']); ?></td>
                                        <td><?php echo $service['duration']; ?> min</td>
                                        <td><span class="status-badge status-<?php echo $service['status']; ?>"><?php echo ucfirst($service['status']); ?></span></td>
                                        <td><?php echo format_date($service['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No recent services.</p>
                <?php endif; ?>
                <h3>Recent Bookings</h3>
                <?php if ($bookings): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                        <td><?php echo format_date($booking['booking_date']); ?></td>
                                        <td><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No recent bookings.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 