<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["p.status = 'approved'"];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.business_name LIKE ? OR p.description LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}
if (!empty($category)) {
    $where_conditions[] = "p.service_category = ?";
    $params[] = $category;
    $param_types .= 's';
}
$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT p.id) as total 
                FROM providers p 
                JOIN users u ON p.user_id = u.id
                WHERE $where_clause";
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
$query = "SELECT p.*, u.profile_image, AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count
          FROM providers p
          JOIN users u ON p.user_id = u.id
          LEFT JOIN reviews r ON p.id = r.provider_id AND r.status = 'active'
          WHERE $where_clause
          GROUP BY p.id
          ORDER BY avg_rating DESC, review_count DESC
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Providers - ServiceHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php">
                    <i class="fas fa-tools"></i>
                    <span>ServiceHub</span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-link">Home</a>
                <a href="services.php" class="nav-link">Services</a>
                <a href="providers.php" class="nav-link active">Providers</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="nav-link">Admin Dashboard</a>
                    <?php elseif ($_SESSION['role'] === 'provider'): ?>
                        <a href="provider/dashboard.php" class="nav-link">Provider Dashboard</a>
                    <?php else: ?>
                        <a href="user/dashboard.php" class="nav-link">My Dashboard</a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="auth/login.php" class="nav-link">Login</a>
                    <a href="auth/register.php" class="nav-link">Register</a>
                <?php endif; ?>
            </div>
            <button class="nav-hamburger" aria-label="Toggle navigation">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </nav>
    <div class="container">
        <div class="main-content">
            <!-- Filter Toggle Button (Mobile Only) -->
            <div class="filter-toggle-container">
                <button id="filter-toggle" class="filter-toggle-btn" aria-label="Toggle filters">
                    <i class="fas fa-filter"></i>
                    <span>Filters</span>
                </button>
            </div>

            <!-- Filter Sidebar -->
            <div class="filter-sidebar" id="filter-sidebar">
                <div class="filter-header">
                <h3>Find Providers</h3>
                    <button class="filter-close-btn" id="filter-close" aria-label="Close filters">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="filter-form" method="GET" action="">
                    <div class="filter-group">
                        <h4>Search</h4>
                        <input type="text" name="search" placeholder="Search providers..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <h4>Category</h4>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $category === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" id="search1-btn" style="width:100%;"><i class="fas fa-filter"></i> Filter</button>
                    <a href="providers.php" class="btn-secondary" style="width:100%;margin-top:0.5rem;text-align:center;">Clear</a>
                </form>
            </div>
            <!-- Providers Grid -->
            <div class="services-content">
                <div class="services-header">
                    <h1>Service Providers</h1>
                    <p><?php echo $total_records; ?> providers found</p>
                </div>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="services-grid">
                        <?php while ($provider = mysqli_fetch_assoc($result)): ?>
                            <div class="service-card">
                                <div class="service-image">
                                    <?php
                                    $img_path = 'assets/images/default-avatar.png';
                                    if (!empty($provider['profile_image']) && file_exists('uploads/profiles/' . $provider['profile_image'])) {
                                        $img_path = 'uploads/profiles/' . $provider['profile_image'];
                                    }
                                    ?>
                                    <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($provider['business_name']); ?>" class="avatar">
                                </div>
                                <div class="service-info">
                                    <h3><?php echo htmlspecialchars($provider['business_name']); ?></h3>
                                    <p class="category"><?php echo htmlspecialchars($categories[$provider['service_category']] ?? $provider['service_category']); ?></p>
                                    <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($provider['location']); ?></p>
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
                                        <span>(<?php echo $provider['review_count'] ?: 0; ?> reviews)</span>
                                    </div>
                                    <a href="provider/profile.php?id=<?php echo $provider['id']; ?>" class="btn-secondary">View Profile</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <?php echo generate_pagination($total_records, $per_page, $page, 'providers.php'); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No providers found</h3>
                        <p>Try adjusting your search criteria or browse all categories.</p>
                        <a href="providers.php" class="btn-primary">Browse All Providers</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>ServiceHub</h3>
                    <p>Connecting customers with trusted service providers</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="providers.php">Providers</a></li>
                        <li><a href="auth/register.php">Become a Provider</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 ServiceHub. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="assets/js/main.js"></script>
</body>
</html> 