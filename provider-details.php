<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$provider_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$provider_id) {
    header('Location: services.php');
    exit();
}

// Fetch provider info
$provider_query = "SELECT p.*, u.email, u.profile_image, u.username FROM providers p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.status = 'approved'";
$provider_stmt = mysqli_prepare($conn, $provider_query);
mysqli_stmt_bind_param($provider_stmt, 'i', $provider_id);
mysqli_stmt_execute($provider_stmt);
$provider_result = mysqli_stmt_get_result($provider_stmt);
$provider = mysqli_fetch_assoc($provider_result);

if (!$provider) {
    header('Location: services.php');
    exit();
}

// Fetch average rating and review count
$rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE provider_id = ? AND status = 'active'";
$rating_stmt = mysqli_prepare($conn, $rating_query);
mysqli_stmt_bind_param($rating_stmt, 'i', $provider_id);
mysqli_stmt_execute($rating_stmt);
$rating_result = mysqli_stmt_get_result($rating_stmt);
$rating_data = mysqli_fetch_assoc($rating_result);
$avg_rating = round($rating_data['avg_rating'] ?: 0, 1);
$review_count = $rating_data['review_count'] ?: 0;

// Fetch provider's services
$services_query = "SELECT * FROM services WHERE provider_id = ? AND status = 'active' ORDER BY created_at DESC";
$services_stmt = mysqli_prepare($conn, $services_query);
mysqli_stmt_bind_param($services_stmt, 'i', $provider_id);
mysqli_stmt_execute($services_stmt);
$services_result = mysqli_stmt_get_result($services_stmt);

// Fetch reviews
$reviews_query = "SELECT r.*, u.username FROM reviews r JOIN users u ON r.customer_id = u.id WHERE r.provider_id = ? AND r.status = 'active' ORDER BY r.created_at DESC LIMIT 10";
$reviews_stmt = mysqli_prepare($conn, $reviews_query);
mysqli_stmt_bind_param($reviews_stmt, 'i', $provider_id);
mysqli_stmt_execute($reviews_stmt);
$reviews_result = mysqli_stmt_get_result($reviews_stmt);

$categories = get_service_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($provider['business_name']); ?> - Provider Details | ServiceHub</title>
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
                <a href="providers.php" class="nav-link">Providers</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="provider-details-card">
            <div class="provider-header">
                <img src="<?php echo !empty($provider['profile_image']) && file_exists('uploads/profiles/' . $provider['profile_image']) ? 'uploads/profiles/' . $provider['profile_image'] : 'assets/images/default-avatar.png'; ?>" alt="<?php echo htmlspecialchars($provider['business_name']); ?>" class="provider-thumb-large" style="width: 150px; height: 150px;">
                <div class="provider-main-info">
                    <h1><?php echo htmlspecialchars($provider['business_name']); ?></h1>
                    <p class="category">
                        <?php echo htmlspecialchars($categories[$provider['service_category']] ?? $provider['service_category']); ?>
                    </p>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($provider['location']); ?></p>
                    <div class="rating">
                        <?php
                        $rounded_rating = round($avg_rating);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rounded_rating) {
                                echo '<i class="fas fa-star filled"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                        <span>(<?php echo $review_count; ?> reviews)</span>
                    </div>
                    <div class="contact-info">
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($provider['email']); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($provider['phone']); ?></p>
                    </div>
                </div>
            </div>
            <div class="provider-description">
                <h3>About</h3>
                <p><?php echo nl2br(htmlspecialchars($provider['description'])); ?></p>
            </div>
            <div class="provider-services">
                <h3>Services Offered</h3>
                <?php if (mysqli_num_rows($services_result) > 0): ?>
                    <ul class="service-list">
                        <?php while ($service = mysqli_fetch_assoc($services_result)): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($service['title']); ?></strong> - <?php echo format_currency($service['price']); ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No services listed yet.</p>
                <?php endif; ?>
            </div>
            <div class="provider-reviews">
                <h3>Recent Reviews</h3>
                <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                    <ul class="review-list">
                        <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                            <li>
                                <div class="review-header">
                                    <strong>@<?php echo htmlspecialchars($review['username']); ?></strong>
                                    <span class="review-rating">
                                        <?php
                                        $stars = intval($review['rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $stars) {
                                                echo '<i class="fas fa-star filled"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </span>
                                    <span class="review-date">
                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="review-body">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No reviews yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html> 