<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    header("Location: ../auth/login.php");
    exit();
}

// Monthly user registrations (last 6 months)
$user_growth = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $label = date('M Y', strtotime($month . '-01'));
    $query = "SELECT COUNT(*) as total FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' AND role != 'admin'";
    $result = mysqli_query($conn, $query);
    $user_growth[] = [
        'label' => $label,
        'total' => mysqli_fetch_assoc($result)['total']
    ];
}

// Monthly bookings and revenue (last 6 months)
$booking_trends = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $label = date('M Y', strtotime($month . '-01'));
    $query = "SELECT COUNT(*) as bookings, SUM(total_amount) as revenue FROM bookings WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' AND status = 'completed'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $booking_trends[] = [
        'label' => $label,
        'bookings' => $row['bookings'] ?: 0,
        'revenue' => $row['revenue'] ?: 0
    ];
}

// Top 5 service categories by bookings
$top_categories = [];
$cat_query = "SELECT p.service_category, COUNT(b.id) as total FROM bookings b JOIN providers p ON b.provider_id = p.id GROUP BY p.service_category ORDER BY total DESC LIMIT 5";
$cat_result = mysqli_query($conn, $cat_query);
$categories = get_service_categories();
while ($row = mysqli_fetch_assoc($cat_result)) {
    $top_categories[] = [
        'category' => $categories[$row['service_category']] ?? ucfirst($row['service_category']),
        'total' => $row['total']
    ];
}

// Top 5 providers by revenue
$top_providers = [];
$prov_query = "SELECT p.business_name, SUM(b.total_amount) as revenue FROM bookings b JOIN providers p ON b.provider_id = p.id WHERE b.status = 'completed' GROUP BY b.provider_id ORDER BY revenue DESC LIMIT 5";
$prov_result = mysqli_query($conn, $prov_query);
while ($row = mysqli_fetch_assoc($prov_result)) {
    $top_providers[] = [
        'business_name' => $row['business_name'],
        'revenue' => $row['revenue'] ?: 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - ServiceHub Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="analytics.php" class="nav-link active">Analytics</a>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <h1><i class="fas fa-chart-bar"></i> Analytics</h1>
                <p>Platform trends and insights</p>
            </div>
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-user-plus"></i>
                    <h3><?php echo array_sum(array_column($user_growth, 'total')); ?></h3>
                    <p>New Users (6 mo)</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?php echo array_sum(array_column($booking_trends, 'bookings')); ?></h3>
                    <p>Bookings (6 mo)</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3><?php echo format_currency(array_sum(array_column($booking_trends, 'revenue'))); ?></h3>
                    <p>Revenue (6 mo)</p>
                </div>
            </div>
            <div class="dashboard-content">
                <div class="content-grid">
                    <div class="content-card">
                        <h3><i class="fas fa-users"></i> User Growth</h3>
                        <canvas id="userGrowthChart" height="100"></canvas>
                    </div>
                    <div class="content-card">
                        <h3><i class="fas fa-chart-line"></i> Bookings & Revenue</h3>
                        <canvas id="bookingRevenueChart" height="100"></canvas>
                    </div>
                </div>
                <div class="content-grid">
                    <div class="content-card">
                        <h3><i class="fas fa-layer-group"></i> Top Service Categories</h3>
                        <table>
                            <thead><tr><th>Category</th><th>Bookings</th></tr></thead>
                            <tbody>
                                <?php foreach ($top_categories as $cat): ?>
                                    <tr><td><?php echo htmlspecialchars($cat['category']); ?></td><td><?php echo $cat['total']; ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="content-card">
                        <h3><i class="fas fa-trophy"></i> Top Providers by Revenue</h3>
                        <table>
                            <thead><tr><th>Provider</th><th>Revenue</th></tr></thead>
                            <tbody>
                                <?php foreach ($top_providers as $prov): ?>
                                    <tr><td><?php echo htmlspecialchars($prov['business_name']); ?></td><td><?php echo format_currency($prov['revenue']); ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    // User Growth Chart
    const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(userGrowthCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($user_growth, 'label')); ?>,
            datasets: [{
                label: 'New Users',
                data: <?php echo json_encode(array_column($user_growth, 'total')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {responsive: true, plugins: {legend: {display: false}}}
    });
    // Bookings & Revenue Chart
    const bookingRevenueCtx = document.getElementById('bookingRevenueChart').getContext('2d');
    new Chart(bookingRevenueCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($booking_trends, 'label')); ?>,
            datasets: [
                {
                    label: 'Bookings',
                    data: <?php echo json_encode(array_column($booking_trends, 'bookings')); ?>,
                    backgroundColor: 'rgba(255, 206, 86, 0.7)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($booking_trends, 'revenue')); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    type: 'line',
                    yAxisID: 'y1',
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {legend: {display: true}},
            scales: {
                y: {beginAtZero: true, title: {display: true, text: 'Bookings'}},
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {drawOnChartArea: false},
                    title: {display: true, text: 'Revenue ($)'}
                }
            }
        }
    });
    </script>
</body>
</html> 