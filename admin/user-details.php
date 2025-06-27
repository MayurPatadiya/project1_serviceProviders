<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$user_id) {
    header("Location: users.php");
    exit();
}

// Get user info
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    header("Location: users.php");
    exit();
}

$profile_img = $user['profile_image'] ? '../uploads/profiles/' . $user['profile_image'] : '../assets/images/default-avatar.png';

// If provider, get provider info
$provider = null;
if ($user['role'] === 'provider') {
    $provider_query = "SELECT * FROM providers WHERE user_id = ?";
    $provider_stmt = mysqli_prepare($conn, $provider_query);
    mysqli_stmt_bind_param($provider_stmt, 'i', $user_id);
    mysqli_stmt_execute($provider_stmt);
    $provider_result = mysqli_stmt_get_result($provider_stmt);
    $provider = mysqli_fetch_assoc($provider_result);
}

// If customer, get recent bookings and reviews
$bookings = [];
$reviews = [];
if ($user['role'] === 'customer') {
    $bookings_query = "SELECT b.*, p.business_name, s.title as service_title FROM bookings b JOIN providers p ON b.provider_id = p.id JOIN services s ON b.service_id = s.id WHERE b.customer_id = ? ORDER BY b.created_at DESC LIMIT 5";
    $bookings_stmt = mysqli_prepare($conn, $bookings_query);
    mysqli_stmt_bind_param($bookings_stmt, 'i', $user_id);
    mysqli_stmt_execute($bookings_stmt);
    $bookings_result = mysqli_stmt_get_result($bookings_stmt);
    while ($row = mysqli_fetch_assoc($bookings_result)) {
        $bookings[] = $row;
    }
    $reviews_query = "SELECT r.*, p.business_name, s.title as service_title FROM reviews r JOIN providers p ON r.provider_id = p.id JOIN bookings b ON r.booking_id = b.id JOIN services s ON b.service_id = s.id WHERE r.customer_id = ? ORDER BY r.created_at DESC LIMIT 5";
    $reviews_stmt = mysqli_prepare($conn, $reviews_query);
    mysqli_stmt_bind_param($reviews_stmt, 'i', $user_id);
    mysqli_stmt_execute($reviews_stmt);
    $reviews_result = mysqli_stmt_get_result($reviews_stmt);
    while ($row = mysqli_fetch_assoc($reviews_result)) {
        $reviews[] = $row;
    }
}
$categories = get_service_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Admin - ServiceHub</title>
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
                <h1><i class="fas fa-user"></i> User Details</h1>
                <p>View all information for this user</p>
            </div>
            <div class="content-card">
                <div style="display:flex;align-items:center;gap:2rem;">
                    <img src="<?php echo $profile_img; ?>" alt="Profile Image" class="avatar">
                    <div>
                        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></p>
                        <p><strong>Registered:</strong> <?php echo format_date($user['created_at']); ?></p>
                        <?php if ($user['phone']): ?><p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p><?php endif; ?>
                        <?php if ($user['address']): ?><p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p><?php endif; ?>
                    </div>
                </div>
                <?php if ($provider): ?>
                    <hr>
                    <h3>Provider Info</h3>
                    <p><strong>Business Name:</strong> <?php echo htmlspecialchars($provider['business_name']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($categories[$provider['service_category']] ?? $provider['service_category']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($provider['description']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($provider['location']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($provider['phone']); ?></p>
                    <p><strong>Status:</strong> <span class="status-badge status-<?php echo $provider['status']; ?>"><?php echo ucfirst($provider['status']); ?></span></p>
                    <?php if ($provider['kyc_document']): ?>
                        <p><strong>KYC Document:</strong> <a href="../uploads/kyc/<?php echo htmlspecialchars($provider['kyc_document']); ?>" target="_blank">View</a></p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($user['role'] === 'customer'): ?>
                    <hr>
                    <h3>Recent Bookings</h3>
                    <?php if ($bookings): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Provider</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['business_name']); ?></td>
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
                    <h3>Recent Reviews</h3>
                    <?php if ($reviews): ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <strong><?php echo htmlspecialchars($review['business_name']); ?></strong> - <?php echo htmlspecialchars($review['service_title']); ?>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="fas fa-star filled"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <p><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <small><?php echo format_date($review['created_at']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No recent reviews.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 