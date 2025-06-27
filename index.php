<!-- changes at 26 6 2025 10 37am -->
<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiceHub - Multivendor Service Marketplace</title>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Find the Best Service Providers</h1>
            <p>Connect with trusted professionals for all your service needs</p>
            <div class="search-box">
                <form action="services.php" method="GET">
                    <input type="text" name="search" placeholder="What service do you need?" required>
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="electrician">Electrician</option>
                        <option value="plumber">Plumber</option>
                        <option value="photographer">Photographer</option>
                        <option value="cleaner">Cleaner</option>
                        <option value="gardener">Gardener</option>
                        <option value="painter">Painter</option>
                    </select>
                    <button type="submit" class="btn-primary" id="search1-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Featured Categories -->
    <section class="categories">
        <div class="container">
            <h2>Popular Service Categories</h2>
            <div class="category-grid">
                <div class="category-card">
                    <i class="fas fa-bolt"></i>
                    <h3>Electrician</h3>
                    <p>Electrical repairs and installations</p>
                </div>
                <div class="category-card">
                    <i class="fas fa-faucet"></i>
                    <h3>Plumber</h3>
                    <p>Plumbing services and repairs</p>
                </div>
                <div class="category-card">
                    <i class="fas fa-camera"></i>
                    <h3>Photographer</h3>
                    <p>Professional photography services</p>
                </div>
                <div class="category-card">
                    <i class="fas fa-broom"></i>
                    <h3>Cleaner</h3>
                    <p>House and office cleaning</p>
                </div>
                <div class="category-card">
                    <i class="fas fa-seedling"></i>
                    <h3>Gardener</h3>
                    <p>Landscaping and garden maintenance</p>
                </div>
                <div class="category-card">
                    <i class="fas fa-paint-brush"></i>
                    <h3>Painter</h3>
                    <p>Interior and exterior painting</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Providers -->
    <section class="featured-providers">
        <div class="container">
            <h2>Top Rated Providers</h2>
            <div class="provider-grid">
                <?php
                // Only show top 3 providers with at least 1 review, ordered by highest average rating
                $query = "SELECT p.*, u.profile_image, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count 
                          FROM providers p 
                          JOIN users u ON p.user_id = u.id
                          LEFT JOIN reviews r ON p.id = r.provider_id 
                          WHERE p.status = 'approved' AND r.id IS NOT NULL
                          GROUP BY p.id 
                          HAVING review_count > 0
                          ORDER BY avg_rating DESC, review_count DESC 
                          LIMIT 3";
                $result = mysqli_query($conn, $query);
                
                while ($provider = mysqli_fetch_assoc($result)):
                ?>
                <div class="provider-card">
                    <div class="provider-image">
                        <?php
                        $img_path = 'assets/images/default-avatar.png';
                        if (!empty($provider['profile_image']) && file_exists('uploads/profiles/' . $provider['profile_image'])) {
                            $img_path = 'uploads/profiles/' . $provider['profile_image'];
                        }
                        ?>
                        <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($provider['business_name']); ?>">
                    </div>
                    <div class="provider-info">
                        <h3><?php echo htmlspecialchars($provider['business_name']); ?></h3>
                        <p class="category"><?php echo htmlspecialchars($provider['service_category']); ?></p>
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
        </div>
    </section>

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
    <script>
    document.querySelector('.nav-hamburger').click();
    </script>
</body>
</html> 