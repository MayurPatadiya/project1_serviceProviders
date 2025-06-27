<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_provider()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get provider id
$provider_query = "SELECT id FROM providers WHERE user_id = ?";
$provider_stmt = mysqli_prepare($conn, $provider_query);
mysqli_stmt_bind_param($provider_stmt, 'i', $user_id);
mysqli_stmt_execute($provider_stmt);
$provider_result = mysqli_stmt_get_result($provider_stmt);
$provider = mysqli_fetch_assoc($provider_result);
$provider_id = $provider ? $provider['id'] : 0;

$error = '';
$success = '';
$service = null;

// Get service ID from URL
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$service_id) {
    redirect_with_message('services.php', 'Service ID is required.', 'error');
}

// Fetch the service to edit
$service_query = "SELECT * FROM services WHERE id = ? AND provider_id = ?";
$service_stmt = mysqli_prepare($conn, $service_query);
mysqli_stmt_bind_param($service_stmt, 'ii', $service_id, $provider_id);
mysqli_stmt_execute($service_stmt);
$service_result = mysqli_stmt_get_result($service_stmt);
$service = mysqli_fetch_assoc($service_result);

if (!$service) {
    redirect_with_message('services.php', 'Service not found or you do not have permission to edit it.', 'error');
}

$categories = get_service_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $duration = intval($_POST['duration'] ?? 0);
    $status = sanitize_input($_POST['status'] ?? 'active');

    // Validation
    if (!$title || !$price || !$duration) {
        $error = 'Please fill in all required fields.';
    } elseif ($price < 1) {
        $error = 'Price must be at least $1.';
    } elseif ($duration < 1) {
        $error = 'Duration must be at least 1 minute.';
    } elseif (!in_array($status, ['active', 'inactive'])) {
        $error = 'Invalid status selected.';
    }

    if (!$error) {
        // Debug: Log the values being updated
        error_log("Updating service ID: $service_id, Status: $status");
        
        $update_query = "UPDATE services SET title = ?, description = ?, price = ?, duration = ?, status = ? WHERE id = ? AND provider_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, 'ssdiisi', $title, $description, $price, $duration, $status, $service_id, $provider_id);
            if (mysqli_stmt_execute($update_stmt)) {
                redirect_with_message('services.php', 'Service updated successfully!', 'success');
            } else {
                $error = 'Failed to update service. Database error: ' . mysqli_stmt_error($update_stmt);
            }
        } else {
            $error = 'Failed to prepare update statement.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service - ServiceHub</title>
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="services.php" class="nav-link">My Services</a>
                <a href="bookings.php" class="nav-link">Bookings</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="form-container">
            <h2><i class="fas fa-edit"></i> Edit Service</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="" autocomplete="off">
                <div class="form-group">
                    <label for="title">Service Title *</label>
                    <input type="text" id="title" name="title" required maxlength="100" value="<?php echo htmlspecialchars($service['title']); ?>">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" maxlength="1000" placeholder="Describe your service..."><?php echo htmlspecialchars($service['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price (USD) *</label>
                    <input type="number" id="price" name="price" required min="1" step="0.01" value="<?php echo htmlspecialchars($service['price']); ?>">
                </div>
                <div class="form-group">
                    <label for="duration">Duration (minutes) *</label>
                    <input type="number" id="duration" name="duration" required min="1" step="1" value="<?php echo htmlspecialchars($service['duration']); ?>">
                </div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="active" <?php echo $service['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $service['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Update Service</button>
                <a href="services.php" class="btn-secondary" style="margin-left: 1rem;">Cancel</a>
            </form>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 