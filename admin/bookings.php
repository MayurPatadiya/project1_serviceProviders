<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    header("Location: ../auth/login.php");
    exit();
}

// Filters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$provider_filter = isset($_GET['provider']) ? intval($_GET['provider']) : '';
$customer_filter = isset($_GET['customer']) ? intval($_GET['customer']) : '';
$service_filter = isset($_GET['service']) ? intval($_GET['service']) : '';
$date_filter = isset($_GET['date']) ? sanitize_input($_GET['date']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = ["1=1"];
$params = [];
$param_types = '';

if ($status_filter) {
    $where[] = "b.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}
if ($provider_filter) {
    $where[] = "b.provider_id = ?";
    $params[] = $provider_filter;
    $param_types .= 'i';
}
if ($customer_filter) {
    $where[] = "b.customer_id = ?";
    $params[] = $customer_filter;
    $param_types .= 'i';
}
if ($service_filter) {
    $where[] = "b.service_id = ?";
    $params[] = $service_filter;
    $param_types .= 'i';
}
if ($date_filter) {
    $where[] = "b.booking_date = ?";
    $params[] = $date_filter;
    $param_types .= 's';
}
if ($search) {
    $where[] = "(u.username LIKE ? OR p.business_name LIKE ? OR s.title LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}
$where_clause = implode(' AND ', $where);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM bookings b JOIN users u ON b.customer_id = u.id JOIN providers p ON b.provider_id = p.id JOIN services s ON b.service_id = s.id WHERE $where_clause";
if (!empty($params)) {
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
} else {
    $count_result = mysqli_query($conn, $count_query);
}
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $per_page);

// Get bookings
$query = "SELECT b.*, u.username as customer_name, p.business_name as provider_name, s.title as service_title FROM bookings b JOIN users u ON b.customer_id = u.id JOIN providers p ON b.provider_id = p.id JOIN services s ON b.service_id = s.id WHERE $where_clause ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';
$stmt = mysqli_prepare($conn, $query);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// For filter dropdowns
$providers = mysqli_query($conn, "SELECT id, business_name FROM providers ORDER BY business_name");
$customers = mysqli_query($conn, "SELECT id, username FROM users WHERE role = 'customer' ORDER BY username");
$services = mysqli_query($conn, "SELECT id, title FROM services ORDER BY title");
$status_options = get_booking_status_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - ServiceHub Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        
    </style>
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
                <a href="bookings.php" class="nav-link active">Bookings</a>
                <a href="reports.php" class="nav-link">Reports</a>
                <a href="analytics.php" class="nav-link">Analytics</a>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <h1><i class="fas fa-calendar"></i> Manage Bookings</h1>
                <p>View and manage all bookings on the platform</p>
            </div>
            <?php echo display_message(); ?>
            <div class="filter-section">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" placeholder="Customer, Provider, Service..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <?php foreach ($status_options as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="provider">Provider</label>
                            <select id="provider" name="provider">
                                <option value="">All Providers</option>
                                <?php while ($prov = mysqli_fetch_assoc($providers)): ?>
                                    <option value="<?php echo $prov['id']; ?>" <?php echo $provider_filter == $prov['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($prov['business_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="customer">Customer</label>
                            <select id="customer" name="customer">
                                <option value="">All Customers</option>
                                <?php while ($cust = mysqli_fetch_assoc($customers)): ?>
                                    <option value="<?php echo $cust['id']; ?>" <?php echo $customer_filter == $cust['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cust['username']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="service">Service</label>
                            <select id="service" name="service">
                                <option value="">All Services</option>
                                <?php while ($svc = mysqli_fetch_assoc($services)): ?>
                                    <option value="<?php echo $svc['id']; ?>" <?php echo $service_filter == $svc['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($svc['title']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn-primary">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-container" style="margin-top:2rem;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Provider</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['provider_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['service_title']); ?></td>
                                    <td><?php echo format_date($row['booking_date']); ?></td>
                                    <td><?php echo date('g:i A', strtotime($row['booking_time'])); ?></td>
                                    <td><?php echo format_currency($row['total_amount']); ?></td>
                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td>
                                        <a href="../booking/view.php?id=<?php echo $row['id']; ?>" class="btn-secondary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align:center;">No bookings found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:2rem;">
                <?php
                // Pagination
                if ($total_pages > 1) {
                    echo '<div class="pagination">';
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        $query_params = $_GET;
                        $query_params['page'] = $i;
                        $url = '?' . http_build_query($query_params);
                        echo '<a href="' . $url . '" class="page-link ' . $active . '">' . $i . '</a>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 