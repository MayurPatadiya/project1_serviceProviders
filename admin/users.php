<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    header("Location: ../auth/login.php");
    exit();
}

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    if (in_array($action, ['suspend', 'activate'])) {
        $status = ($action === 'suspend') ? 'suspended' : 'active';
        $update_query = "UPDATE users SET status = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'si', $status, $user_id);
        if (mysqli_stmt_execute($update_stmt)) {
            redirect_with_message('users.php', "User status updated successfully!", 'success');
        } else {
            redirect_with_message('users.php', "Failed to update user status.", 'error');
        }
    }
}

// Get filter parameters
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["1=1"];
$params = [];
$param_types = '';

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $param_types .= 's';
}
if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}
if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}
$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
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

// Get users
$query = "SELECT * FROM users WHERE $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';
$stmt = mysqli_prepare($conn, $query);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$role_options = [
    '' => 'All Roles',
    'admin' => 'Admin',
    'provider' => 'Provider',
    'customer' => 'Customer'
];
$status_options = [
    '' => 'All Status',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'suspended' => 'Suspended'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - ServiceHub Admin</title>
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
                <a href="users.php" class="nav-link active">Manage Users</a>
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
                <h1><i class="fas fa-users"></i> Manage Users</h1>
                <p>View and manage all platform users</p>
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
                    <h3>Filter Users</h3>
                    <button class="filter-close-btn" id="filter-close" aria-label="Close filters">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="GET" action="" class="filter-form" id="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" placeholder="Username, email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="role">Role</label>
                            <select id="role" name="role">
                                <?php foreach ($role_options as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $role_filter === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
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
                            <a href="users.php" class="btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
            <!-- Users Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo format_date($user['created_at']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="user-details.php?id=<?php echo $user['id'];  ?>" class="btn-secondary btn-sm" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="action" value="suspend" class="btn-danger btn-sm" title="Suspend" onclick="return confirm('Suspend this user?')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($user['status'] === 'suspended'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="action" value="activate" class="btn-success btn-sm" title="Activate" onclick="return confirm('Activate this user?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php echo generate_pagination($total_records, $per_page, $page, 'users.php'); ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 