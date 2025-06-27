<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    header("Location: ../auth/login.php");
    exit();
}

// Handle provider status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $provider_id = intval($_POST['provider_id']);
    $action = $_POST['action'];
    
    if (in_array($action, ['approve', 'reject', 'suspend'])) {
        $status = ($action === 'approve') ? 'approved' : (($action === 'reject') ? 'rejected' : 'suspended');
        
        $update_query = "UPDATE providers SET status = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'si', $status, $provider_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Get provider info for notification
            $provider_query = "SELECT p.*, u.email FROM providers p JOIN users u ON p.user_id = u.id WHERE p.id = ?";
            $provider_stmt = mysqli_prepare($conn, $provider_query);
            mysqli_stmt_bind_param($provider_stmt, 'i', $provider_id);
            mysqli_stmt_execute($provider_stmt);
            $provider_result = mysqli_stmt_get_result($provider_stmt);
            $provider = mysqli_fetch_assoc($provider_result);
            
            // Create notification
            $message = "Your provider account has been " . $status . ".";
            if ($action === 'approve') {
                $message = "Congratulations! Your provider account has been approved. You can now start offering services.";
            } elseif ($action === 'reject') {
                $message = "Your provider account application has been rejected. Please contact support for more information.";
            }
            
            create_notification($provider['user_id'], 'Provider Account Update', $message, 'system', $conn);
            
            redirect_with_message('providers.php', "Provider status updated successfully!", 'success');
        } else {
            redirect_with_message('providers.php', "Failed to update provider status.", 'error');
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["1=1"];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.service_category = ?";
    $params[] = $category_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(p.business_name LIKE ? OR p.location LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM providers p JOIN users u ON p.user_id = u.id WHERE $where_clause";
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

// Get providers
$query = "SELECT p.*, u.username, u.email, u.status as user_status,
          AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count,
          COUNT(DISTINCT b.id) as booking_count
          FROM providers p 
          JOIN users u ON p.user_id = u.id 
          LEFT JOIN reviews r ON p.id = r.provider_id AND r.status = 'active'
          LEFT JOIN bookings b ON p.id = b.provider_id
          WHERE $where_clause 
          GROUP BY p.id 
          ORDER BY p.created_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$categories = get_service_categories();
$status_options = get_provider_status_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Providers - ServiceHub Admin</title>
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
                <h1><i class="fas fa-user-tie"></i> Manage Service Providers</h1>
                <p>Review and manage service provider accounts</p>
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
                    <h3>Filter Providers</h3>
                    <button class="filter-close-btn" id="filter-close" aria-label="Close filters">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="GET" action="" class="filter-form" id="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" placeholder="Business name, location..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <?php foreach ($status_options as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $category_filter === $key ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn-primary">Filter</button>
                            <a href="providers.php" class="btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Providers Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Rating</th>
                            <th>Bookings</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody">
                        <?php while ($provider = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <div class="provider-info-cell">
                                        <?php
                                        $img_path = '../assets/images/default-avatar.png';
                                        if (!empty($provider['profile_image']) && file_exists('../uploads/profiles/' . $provider['profile_image'])) {
                                            $img_path = '../uploads/profiles/' . $provider['profile_image'];
                                        }
                                        ?>
                                        <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($provider['business_name']); ?>" class="provider-thumb">
                                        <div>
                                            <strong><?php echo htmlspecialchars($provider['business_name']); ?></strong>
                                            <br>
                                            <small>@<?php echo htmlspecialchars($provider['username']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($categories[$provider['service_category']] ?? $provider['service_category']); ?></td>
                                <td><?php echo htmlspecialchars($provider['location']); ?></td>
                                <td>
                                    <div>
                                        <div><?php echo htmlspecialchars($provider['email']); ?></div>
                                        <div><?php echo htmlspecialchars($provider['phone']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="rating">

                                        <?php
                                    $rating = round($provider['avg_rating'] ?: 0);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star filled"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                    <hr class="rating-divider" style="margin: 4px 0;">
                                    <small>(<?php echo $provider['review_count'] ?: 0; ?> reviews)</small>
                                </td>
                                <td><?php echo $provider['booking_count'] ?: 0; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $provider['status']; ?>">
                                        <?php echo ucfirst($provider['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons" style="display: flex;">
                                        <a href="provider-details.php?id=<?php echo $provider['id']; ?>" 
                                           class="btn-secondary btn-sm" title="View Details" style="margin-right: 10px;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($provider['status'] === 'pending'): ?>
                                            <form method="POST" action="">
                                                <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                                                <button type="submit" name="action" value="approve" 
                                                        class="btn-success btn-sm" title="Approve"
                                                        onclick="return confirm('Approve this provider?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="submit" name="action" value="reject" 
                                                        class="btn-danger btn-sm" title="Reject"
                                                        onclick="return confirm('Reject this provider?')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($provider['status'] === 'approved'): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                                                <button type="submit" name="action" value="suspend" 
                                                        class="btn-danger btn-sm" title="Suspend"
                                                        onclick="return confirm('Suspend this provider?')">
                                                    <i class="fas fa-ban"></i>
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
                <?php echo generate_pagination($total_records, $per_page, $page, 'providers.php'); ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html> 