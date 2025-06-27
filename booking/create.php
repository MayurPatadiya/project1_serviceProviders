<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect_with_message('../auth/login.php', 'Please login to book services.', 'warning');
}

// Check if user is customer
if (!is_customer()) {
    redirect_with_message('../index.php', 'Only customers can book services.', 'error');
}

$error = '';
$success = '';

// Get provider ID
$provider_id = isset($_GET['provider_id']) ? intval($_GET['provider_id']) : 0;

if (!$provider_id) {
    redirect_with_message('../services.php', 'Invalid provider selected.', 'error');
}

// Get provider details
$provider_query = "SELECT p.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count 
                   FROM providers p 
                   LEFT JOIN reviews r ON p.id = r.provider_id AND r.status = 'active'
                   WHERE p.id = ? AND p.status = 'approved'
                   GROUP BY p.id";
$provider_stmt = mysqli_prepare($conn, $provider_query);
mysqli_stmt_bind_param($provider_stmt, 'i', $provider_id);
mysqli_stmt_execute($provider_stmt);
$provider_result = mysqli_stmt_get_result($provider_stmt);

if (!$provider = mysqli_fetch_assoc($provider_result)) {
    redirect_with_message('../services.php', 'Provider not found or not approved.', 'error');
}

// Get provider's services
$services_query = "SELECT * FROM services WHERE provider_id = ? AND status = 'active'";
$services_stmt = mysqli_prepare($conn, $services_query);
mysqli_stmt_bind_param($services_stmt, 'i', $provider_id);
mysqli_stmt_execute($services_stmt);
$services_result = mysqli_stmt_get_result($services_stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id']);
    $booking_date = sanitize_input($_POST['booking_date']);
    $booking_time = sanitize_input($_POST['booking_time']);
    $duration = intval($_POST['duration']);
    $requirements = sanitize_input($_POST['requirements']);
    
    // Validation
    if (empty($service_id) || empty($booking_date) || empty($booking_time)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if service exists and belongs to provider
        $service_query = "SELECT * FROM services WHERE id = ? AND provider_id = ? AND status = 'active'";
        $service_stmt = mysqli_prepare($conn, $service_query);
        mysqli_stmt_bind_param($service_stmt, 'ii', $service_id, $provider_id);
        mysqli_stmt_execute($service_stmt);
        $service_result = mysqli_stmt_get_result($service_stmt);
        
        if (!$service = mysqli_fetch_assoc($service_result)) {
            $error = 'Invalid service selected';
        } else {
            // Check if booking date is in the future
            $booking_datetime = $booking_date . ' ' . $booking_time;
            if (strtotime($booking_datetime) <= time()) {
                $error = 'Booking date and time must be in the future';
            } else {
                // Check availability
                if (check_booking_availability($provider_id, $booking_date, $booking_time, $duration, $conn)) {
                    // Calculate total amount
                    $total_amount = $service['price'] * ($duration / 60);
                    
                    // Create booking
                    $booking_query = "INSERT INTO bookings (customer_id, provider_id, service_id, booking_date, booking_time, duration, total_amount, requirements, status) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                    $booking_stmt = mysqli_prepare($conn, $booking_query);
                    mysqli_stmt_bind_param($booking_stmt, 'iiissids', 
                        $_SESSION['user_id'], $provider_id, $service_id, $booking_date, 
                        $booking_time, $duration, $total_amount, $requirements);
                    
                    if (mysqli_stmt_execute($booking_stmt)) {
                        $booking_id = mysqli_insert_id($conn);
                        
                        // Create notification for provider
                        create_notification($provider['user_id'], 'New Booking Request', 
                            "You have a new booking request from " . $_SESSION['username'], 'booking', $conn);
                        
                        redirect_with_message('view.php?id=' . $booking_id, 'Booking created successfully!', 'success');
                    } else {
                        $error = 'Failed to create booking. Please try again.';
                    }
                } else {
                    $error = 'Selected time slot is not available. Please choose another time.';
                }
            }
        }
    }
}

$categories = get_service_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - ServiceHub</title>
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
                <a href="../services.php" class="nav-link">Services</a>
                <a href="../user/dashboard.php" class="nav-link">My Dashboard</a>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="booking-container">
            <div class="booking-header">
                <h1><i class="fas fa-calendar-plus"></i> Book Service</h1>
                <p>Schedule your service with <?php echo htmlspecialchars($provider['business_name']); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="booking-content">
                <!-- Provider Info -->
                <div class="provider-info-card">
                    <div class="provider-header">
                        <?php
                        $img_path = '../assets/images/default-avatar.png';
                        if (!empty($provider['profile_image']) && file_exists('../uploads/profiles/' . $provider['profile_image'])) {
                            $img_path = '../uploads/profiles/' . $provider['profile_image'];
                        }
                        ?>
                        <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($provider['business_name']); ?>" class="provider-avatar">
                        <div class="provider-details">
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
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="booking-form">
                    <form method="POST" action="" data-validate>
                        <input type="hidden" id="provider-id" value="<?php echo $provider_id; ?>">
                        
                        <div class="form-group">
                            <label for="service_id">Select Service *</label>
                            <select id="service_id" name="service_id" required>
                                <option value="">Choose a service</option>
                                <?php while ($service = mysqli_fetch_assoc($services_result)): ?>
                                    <option value="<?php echo $service['id']; ?>" 
                                            data-price="<?php echo $service['price']; ?>"
                                            data-duration="<?php echo $service['duration']; ?>">
                                        <?php echo htmlspecialchars($service['title']); ?> - 
                                        <?php echo format_currency($service['price']); ?> per hour
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="booking_date">Booking Date *</label>
                            <input type="date" id="booking_date" name="booking_date" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="booking_time">Booking Time *</label>
                            <input type="time" id="booking_time" name="booking_time" required>
                        </div>

                        <div class="form-group">
                            <label for="duration">Duration (minutes) *</label>
                            <select id="duration" name="duration" required>
                                <option value="60">1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                                <option value="180">3 hours</option>
                                <option value="240">4 hours</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="requirements">Special Requirements</label>
                            <textarea id="requirements" name="requirements" rows="4" 
                                      placeholder="Any special requirements or notes for the provider..."></textarea>
                        </div>

                        <div class="booking-summary">
                            <h4>Booking Summary</h4>
                            <div class="summary-item">
                                <span>Service:</span>
                                <span id="summary-service">-</span>
                            </div>
                            <div class="summary-item">
                                <span>Date & Time:</span>
                                <span id="summary-datetime">-</span>
                            </div>
                            <div class="summary-item">
                                <span>Duration:</span>
                                <span id="summary-duration">-</span>
                            </div>
                            <div class="summary-item total">
                                <span>Total Amount:</span>
                                <span id="summary-total">-</span>
                            </div>
                        </div>

                        <div id="availability-message" style="display: none; margin: 1rem 0; padding: 0.5rem; border-radius: 5px;"></div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-calendar-check"></i> Confirm Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Update booking summary
        function updateSummary() {
            const serviceSelect = document.getElementById('service_id');
            const dateInput = document.getElementById('booking_date');
            const timeInput = document.getElementById('booking_time');
            const durationSelect = document.getElementById('duration');
            
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const serviceName = selectedOption.text || '-';
            const servicePrice = parseFloat(selectedOption.dataset.price) || 0;
            const duration = parseInt(durationSelect.value) || 60;
            const date = dateInput.value || '-';
            const time = timeInput.value || '-';
            
            const totalAmount = servicePrice * (duration / 60);
            
            document.getElementById('summary-service').textContent = serviceName;
            document.getElementById('summary-datetime').textContent = date !== '-' && time !== '-' ? `${date} at ${time}` : '-';
            document.getElementById('summary-duration').textContent = `${duration} minutes`;
            document.getElementById('summary-total').textContent = totalAmount > 0 ? `$${totalAmount.toFixed(2)}` : '-';
        }
        
        // Add event listeners
        document.getElementById('service_id').addEventListener('change', updateSummary);
        document.getElementById('booking_date').addEventListener('change', updateSummary);
        document.getElementById('booking_time').addEventListener('change', updateSummary);
        document.getElementById('duration').addEventListener('change', updateSummary);
        
        // Initialize summary
        updateSummary();
    </script>
</body>
</html> 