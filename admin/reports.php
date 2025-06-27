<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    header("Location: ../auth/login.php");
    exit();
}

// Handle report status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['report_id'])) {
    $report_id = intval($_POST['report_id']);
    $action = $_POST['action'];
    $allowed_status = ['pending', 'investigating', 'resolved', 'dismissed'];
    if (in_array($action, $allowed_status)) {
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_input($_POST['admin_notes']) : null;
        $update_query = "UPDATE reports SET status = ?, admin_notes = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'ssi', $action, $admin_notes, $report_id);
        if (mysqli_stmt_execute($update_stmt)) {
            redirect_with_message('reports.php?id=' . $report_id, "Report status updated!", 'success');
        } else {
            redirect_with_message('reports.php?id=' . $report_id, "Failed to update report status.", 'error');
        }
    }
}

// Filters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["1=1"];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}
if (!empty($type_filter)) {
    $where_conditions[] = "r.report_type = ?";
    $params[] = $type_filter;
    $param_types .= 's';
}
if (!empty($search)) {
    $where_conditions[] = "(u.username LIKE ? OR r.reason LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}
$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM reports r JOIN users u ON r.reporter_id = u.id WHERE $where_clause";
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

// Get reports
$query = "SELECT r.*, u.username as reporter_name FROM reports r JOIN users u ON r.reporter_id = u.id WHERE $where_clause ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';
$stmt = mysqli_prepare($conn, $query);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// For detail view
$detail = null;
if (isset($_GET['id'])) {
    $detail_id = intval($_GET['id']);
    $detail_query = "SELECT r.*, u.username as reporter_name FROM reports r JOIN users u ON r.reporter_id = u.id WHERE r.id = ?";
    $detail_stmt = mysqli_prepare($conn, $detail_query);
    mysqli_stmt_bind_param($detail_stmt, 'i', $detail_id);
    mysqli_stmt_execute($detail_stmt);
    $detail_result = mysqli_stmt_get_result($detail_stmt);
    $detail = mysqli_fetch_assoc($detail_result);
}

$status_options = [
    '' => 'All Status',
    'pending' => 'Pending',
    'investigating' => 'Investigating',
    'resolved' => 'Resolved',
    'dismissed' => 'Dismissed'
];
$type_options = [
    '' => 'All Types',
    'user' => 'User',
    'provider' => 'Provider',
    'booking' => 'Booking',
    'service' => 'Service'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - ServiceHub Admin</title>
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
                <a href="reports.php" class="nav-link active">Reports</a>
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
                <h1><i class="fas fa-flag"></i> Reports</h1>
                <p>View and manage all user reports and disputes</p>
            </div>
            <?php echo display_message(); ?>
            
            <!-- Filter Toggle Button (Mobile Only) -->
            <div class="filter-toggle-container">
                <button id="filter-toggle" class="filter-toggle-btn" aria-label="Toggle filters">
                    <i class="fas fa-filter"></i>
                    <span>Filters</span>
                </button>
            </div>
            
            <!-- Filters -->
            <div class="filter-section" id="filter-section">
                <div class="filter-header">
                    <h3>Filter Reports</h3>
                    <button class="filter-close-btn" id="filter-close" aria-label="Close filters">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="GET" action="" class="filter-form" id="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" placeholder="Reporter, reason..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="type">Type</label>
                            <select id="type" name="type">
                                <?php foreach ($type_options as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $type_filter === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <?php foreach ($status_options as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn-primary">Filter</button>
                            <a href="reports.php" class="btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
            <?php if ($detail): ?>
                <!-- Detail View -->
                <div class="content-card">
                    <h2>Report Details</h2>
                    <p><strong>Reporter:</strong> <a href="user-details.php?id=<?php echo $detail['reporter_id']; ?>"><?php echo htmlspecialchars($detail['reporter_name']); ?></a></p>
                    <p><strong>Type:</strong> <?php echo ucfirst($detail['report_type']); ?></p>
                    <p><strong>Status:</strong> <span class="status-badge status-<?php echo $detail['status']; ?>"><?php echo ucfirst($detail['status']); ?></span></p>
                    <p><strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($detail['reason'])); ?></p>
                    <p><strong>Date:</strong> <?php echo format_datetime($detail['created_at']); ?></p>
                    <?php if ($detail['reported_user_id']): ?>
                        <p><strong>Reported User:</strong> <a href="user-details.php?id=<?php echo $detail['reported_user_id']; ?>">View User</a></p>
                    <?php endif; ?>
                    <?php if ($detail['reported_provider_id']): ?>
                        <p><strong>Reported Provider:</strong> <a href="provider-details.php?id=<?php echo $detail['reported_provider_id']; ?>">View Provider</a></p>
                    <?php endif; ?>
                    <?php if ($detail['booking_id']): ?>
                        <p><strong>Booking ID:</strong> <?php echo $detail['booking_id']; ?></p>
                    <?php endif; ?>
                    <form method="POST" action="" style="margin-top: 20px;">
                        <input type="hidden" name="report_id" value="<?php echo $detail['id']; ?>">
                        <label for="admin_notes"><strong>Admin Notes:</strong></label><br>
                        <textarea name="admin_notes" id="admin_notes" rows="3" style="width:100%;max-width:500px;"><?php echo htmlspecialchars($detail['admin_notes']); ?></textarea><br>
                        <label for="action"><strong>Update Status:</strong></label>
                        <select name="action" id="action">
                            <?php foreach ($status_options as $key => $value): if ($key === '') continue; ?>
                                <option value="<?php echo $key; ?>" <?php echo $detail['status'] === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-primary">Update</button>
                        <a href="reports.php" class="btn-secondary">Back to List</a>
                    </form>
                </div>
            <?php else: ?>
            <!-- Reports Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Reporter</th>
                            <th>Type</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($report = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><a href="user-details.php?id=<?php echo $report['reporter_id']; ?>"><?php echo htmlspecialchars($report['reporter_name']); ?></a></td>
                                <td><span class="status-badge status-<?php echo $report['report_type']; ?>"><?php echo ucfirst($report['report_type']); ?></span></td>
                                <td><?php echo htmlspecialchars(substr($report['reason'], 0, 50)) . (strlen($report['reason']) > 50 ? '...' : ''); ?></td>
                                <td><span class="status-badge status-<?php echo $report['status']; ?>"><?php echo ucfirst($report['status']); ?></span></td>
                                <td><?php echo format_date($report['created_at']); ?></td>
                                <td><a href="reports.php?id=<?php echo $report['id']; ?>" class="btn-secondary btn-sm">View</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php echo generate_pagination($total_records, $per_page, $page, 'reports.php'); ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 