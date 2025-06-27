<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_provider()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get provider info
$provider_query = "SELECT p.*, u.email, u.profile_image FROM providers p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?";
$provider_stmt = mysqli_prepare($conn, $provider_query);
mysqli_stmt_bind_param($provider_stmt, 'i', $user_id);
mysqli_stmt_execute($provider_stmt);
$provider_result = mysqli_stmt_get_result($provider_stmt);
$provider = mysqli_fetch_assoc($provider_result);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_name = sanitize_input($_POST['business_name']);
    $service_category = sanitize_input($_POST['service_category']);
    $description = sanitize_input($_POST['description']);
    $location = sanitize_input($_POST['location']);
    $phone = sanitize_input($_POST['phone']);
    $profile_image = $provider['profile_image'];

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_file($_FILES['profile_image'], '../uploads/profiles', ['jpg', 'jpeg', 'png']);
        if ($upload_result['success']) {
            $profile_image = $upload_result['filename'];
            // Update user table
            $update_user_query = "UPDATE users SET profile_image = ? WHERE id = ?";
            $update_user_stmt = mysqli_prepare($conn, $update_user_query);
            mysqli_stmt_bind_param($update_user_stmt, 'si', $profile_image, $user_id);
            mysqli_stmt_execute($update_user_stmt);
        } else {
            $error = $upload_result['message'];
        }
    }

    if (!$error) {
        $update_query = "UPDATE providers SET business_name = ?, service_category = ?, description = ?, location = ?, phone = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'sssssi', $business_name, $service_category, $description, $location, $phone, $user_id);
        if (mysqli_stmt_execute($update_stmt)) {
            $success = 'Profile updated successfully!';
            // Refresh provider info
            mysqli_stmt_execute($provider_stmt);
            $provider_result = mysqli_stmt_get_result($provider_stmt);
            $provider = mysqli_fetch_assoc($provider_result);
        } else {
            $error = 'Failed to update profile. Please try again.';
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
    <title>Provider Profile - ServiceHub</title>
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
                <a href="profile.php" class="nav-link active">Profile</a>
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
        <div class="form-container">
            <h2><i class="fas fa-user-cog"></i> Provider Profile</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group" style="text-align:center;">
                    <?php
                    $img = $provider['profile_image'] ? '../uploads/profiles/' . $provider['profile_image'] : '../assets/images/default-avatar.png';
                    ?>
                    <img src="<?php echo $img; ?>" alt="Profile Image" class="avatar" style="margin-bottom:1rem;">
                    <input type="file" name="profile_image" accept=".jpg,.jpeg,.png">
                </div>
                <div class="form-group">
                    <label for="business_name">Business Name</label>
                    <input type="text" id="business_name" name="business_name" required value="<?php echo htmlspecialchars($provider['business_name']); ?>">
                </div>
                <div class="form-group">
                    <label for="service_category">Service Category</label>
                    <select id="service_category" name="service_category" required>
                        <?php foreach ($categories as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($provider['service_category'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($provider['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" required value="<?php echo htmlspecialchars($provider['location']); ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" required value="<?php echo htmlspecialchars($provider['phone']); ?>">
                </div>
                <?php if ($provider['kyc_document']): ?>
                <div class="form-group">
                    <label>KYC Document:</label>
                    <a href="../uploads/kyc/<?php echo htmlspecialchars($provider['kyc_document']); ?>" target="_blank">View Document</a>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 