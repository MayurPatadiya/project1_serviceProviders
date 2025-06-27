<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get search parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$location = isset($_GET['location']) ? sanitize_input($_GET['location']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 1000;
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["p.status = 'approved'"];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.business_name LIKE ? OR p.description LIKE ? OR s.title LIKE ?)";
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

if (!empty($location)) {
    $where_conditions[] = "p.location LIKE ?";
    $params[] = "%$location%";
    $param_types .= 's';
}

if ($min_price > 0) {
    $where_conditions[] = "s.price >= ?";
    $params[] = $min_price;
    $param_types .= 'd';
}

if ($max_price < 1000) {
    $where_conditions[] = "s.price <= ?";
    $params[] = $max_price;
    $param_types .= 'd';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT p.id) as total 
                FROM providers p 
                LEFT JOIN services s ON p.id = s.provider_id 
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

// Get providers with services
$query = "SELECT DISTINCT p.*, u.profile_image,
          AVG(r.rating) as avg_rating, 
          COUNT(DISTINCT r.id) as review_count,
          MIN(s.price) as min_price,
          MAX(s.price) as max_price
          FROM providers p 
          JOIN users u ON p.user_id = u.id
          LEFT JOIN services s ON p.id = s.provider_id 
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
    <title>Services - ServiceHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
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
                <a href="providers.php" class="nav-link">Providers</a>
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
                <h3>Filters</h3>
                    <button class="filter-close-btn" id="filter-close" aria-label="Close filters">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="filter-form" method="GET" action="">
                    <div class="filter-group">
                        <h4>Search</h4>
                        <input type="text" name="search" placeholder="Search services..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="filter-group">
                        <h4>Category</h4>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $key => $value): ?>
                                <option value="<?php echo $key; ?>" 
                                    <?php echo $category === $key ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <h4>Location</h4>
                        <input type="text" name="location" placeholder="Enter location..." 
                               value="<?php echo htmlspecialchars($location); ?>">
                    </div>

                    <div class="filter-group">
                        <h4>Price Range</h4>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="number" name="min_price" placeholder="Min" 
                                   value="<?php echo $min_price; ?>" min="0" step="0.01">
                            <span>-</span>
                            <input type="number" name="max_price" placeholder="Max" 
                                   value="<?php echo $max_price; ?>" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="filter-group">
                        <h4>Minimum Rating</h4>
                        <label><input type="radio" name="rating" value="0" <?php echo $rating == 0 ? 'checked' : ''; ?>> Any</label>
                        <label><input type="radio" name="rating" value="4" <?php echo $rating == 4 ? 'checked' : ''; ?>> 4+ Stars</label>
                        <label><input type="radio" name="rating" value="3" <?php echo $rating == 3 ? 'checked' : ''; ?>> 3+ Stars</label>
                    </div>

                    <div class="filter-buttons">
                        <button type="submit" class="btn-primary" id="btn-filter">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="services.php" class="btn-secondary" id="btn-clear">
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Main Content -->
            <div class="services-content">
                <div class="services-header">
                    <h1>Find Services</h1>
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
                                    
                                    <div class="price-range">
                                        <i class="fas fa-dollar-sign"></i>
                                        <?php if ($provider['min_price'] == $provider['max_price']): ?>
                                            <?php echo format_currency($provider['min_price']); ?>
                                        <?php else: ?>
                                            <?php echo format_currency($provider['min_price']); ?> - <?php echo format_currency($provider['max_price']); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="service-actions">
                                        <a href="provider-details.php?id=<?php echo $provider['id']; ?>" class="btn-secondary">
                                            View Profile
                                        </a>
                                        <a href="booking/create.php?provider_id=<?php echo $provider['id']; ?>" class="btn-primary">
                                            Book Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <?php echo generate_pagination($total_records, $per_page, $page, 'services.php'); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No services found</h3>
                        <p>Try adjusting your search criteria or browse all categories.</p>
                        <a href="services.php" class="btn-primary">Browse All Services</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
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