<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_customer()) {
    header("Location: ../auth/login.php");
    exit();
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$booking_id) {
    redirect_with_message('../user/bookings.php', 'Invalid booking.', 'error');
}

// Get booking details
$query = "SELECT b.*, p.user_id as provider_user_id, p.id as provider_id, s.title as service_title, s.id as service_id
          FROM bookings b
          JOIN providers p ON b.provider_id = p.id
          JOIN services s ON b.service_id = s.id
          WHERE b.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $booking_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    redirect_with_message('../user/bookings.php', 'Booking not found.', 'error');
}

if ($booking['customer_id'] != $_SESSION['user_id']) {
    redirect_with_message('../user/bookings.php', 'You are not allowed to review this booking.', 'error');
}

if ($booking['status'] !== 'completed') {
    redirect_with_message('../booking/view.php?id=' . $booking_id, 'You can only review completed bookings.', 'error');
}

// Check if review already exists for this booking
$review_check_query = "SELECT id FROM reviews WHERE booking_id = ? AND customer_id = ?";
$review_check_stmt = mysqli_prepare($conn, $review_check_query);
mysqli_stmt_bind_param($review_check_stmt, 'ii', $booking_id, $_SESSION['user_id']);
mysqli_stmt_execute($review_check_stmt);
$review_check_result = mysqli_stmt_get_result($review_check_stmt);
if (mysqli_fetch_assoc($review_check_result)) {
    redirect_with_message('../booking/view.php?id=' . $booking_id, 'You have already reviewed this booking.', 'info');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $comment = sanitize_input($_POST['comment']);
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a valid rating.';
    } elseif (empty($comment)) {
        $error = 'Please enter a comment.';
    } else {
        $insert_query = "INSERT INTO reviews (customer_id, provider_id, booking_id, rating, comment, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, 'iiiis', $_SESSION['user_id'], $booking['provider_id'], $booking_id, $rating, $comment);
        if (mysqli_stmt_execute($insert_stmt)) {
            redirect_with_message('../booking/view.php?id=' . $booking_id, 'Thank you for your review!', 'success');
        } else {
            $error = 'Failed to submit review. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review - ServiceHub</title>
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
                <a href="../index.php" class="nav-link">Home</a>
                <a href="../services.php" class="nav-link">Services</a>
                <a href="../user/dashboard.php" class="nav-link">My Dashboard</a>
                <a href="../user/bookings.php" class="nav-link">My Bookings</a>
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
                <h1><i class="fas fa-star"></i> Leave a Review</h1>
                <p>Review your experience for <strong><?php echo htmlspecialchars($booking['service_title']); ?></strong> with <strong><?php echo htmlspecialchars($booking['provider_id']); ?></strong></p>
            </div>
            <div class="content-card">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="rating">Rating *</label>
                        <div class="star-rating" id="star-rating" style="font-size: 2rem; direction: rtl; display: inline-flex; gap: 0.2em;">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" style="display:none;" <?php if ((isset($_POST['rating']) && intval($_POST['rating']) == $i) || (!isset($_POST['rating']) && $i == 5)) echo 'checked'; ?>>
                                <label for="star<?php echo $i; ?>" data-value="<?php echo $i; ?>" style="color: #FFD700; cursor:pointer;">
                                    <i class="fa fa-star"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="comment">Comment *</label>
                        <textarea id="comment" name="comment" rows="4" required><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                    </div>
                    <button type="submit" class="btn-primary submit-btn "><i class="fas fa-paper-plane"></i> Submit Review</button>
                    <a href="view.php?id=<?php echo $booking_id; ?>" class="btn-secondary">Back to Booking</a>
                </form>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
    // Star rating interactive logic
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('#star-rating label');
        const radios = document.querySelectorAll('#star-rating input[type="radio"]');
        let selected = document.querySelector('#star-rating input[type="radio"]:checked');
        let currentRating = selected ? parseInt(selected.value) : 5;

        function updateStars(rating) {
            stars.forEach(label => {
                const val = parseInt(label.getAttribute('data-value'));
                if (val <= rating) {
                    label.querySelector('i').classList.add('fas');
                    label.querySelector('i').classList.remove('far');
                } else {
                    label.querySelector('i').classList.remove('fas');
                    label.querySelector('i').classList.add('far');
                }
            });
        }

        // Initialize
        updateStars(currentRating);

        stars.forEach(label => {
            label.addEventListener('mouseenter', function() {
                updateStars(parseInt(this.getAttribute('data-value')));
            });
            label.addEventListener('mouseleave', function() {
                updateStars(currentRating);
            });
            label.addEventListener('click', function() {
                const val = parseInt(this.getAttribute('data-value'));
                currentRating = val;
                document.getElementById('star' + val).checked = true;
                updateStars(currentRating);
            });
        });
    });
    </script>
</body>
</html> 