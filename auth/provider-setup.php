<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is in provider setup process
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: register.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_name = sanitize_input($_POST['business_name']);
    $service_category = sanitize_input($_POST['service_category']);
    $description = sanitize_input($_POST['description']);
    $location = sanitize_input($_POST['location']);
    $phone = sanitize_input($_POST['phone']);
    $hourly_rate = floatval($_POST['hourly_rate']);
    
    // Validation
    if (empty($business_name) || empty($service_category) || empty($location) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } elseif (!validate_phone($phone)) {
        $error = 'Please enter a valid phone number';
    } elseif ($hourly_rate <= 0) {
        $error = 'Please enter a valid hourly rate';
    } else {
        // Handle KYC document upload
        $kyc_document = '';
        if (isset($_FILES['kyc_document']) && $_FILES['kyc_document']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_file($_FILES['kyc_document'], '../uploads/kyc', ['pdf', 'jpg', 'jpeg', 'png']);
            if ($upload_result['success']) {
                $kyc_document = $upload_result['filename'];
            } else {
                $error = $upload_result['message'];
            }
        }
        
        if (empty($error)) {
            // Insert provider record
            $insert_query = "INSERT INTO providers (user_id, business_name, service_category, description, location, phone, hourly_rate, kyc_document, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, 'isssssds', 
                $_SESSION['temp_user_id'], $business_name, $service_category, $description, 
                $location, $phone, $hourly_rate, $kyc_document);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                // Clear temp session and set success message
                unset($_SESSION['temp_user_id']);
                $success = 'Provider profile created successfully! Your account is pending approval. You will be notified once approved.';
            } else {
                $error = 'Failed to create provider profile. Please try again.';
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
    <title>Provider Setup - ServiceHub</title>
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
                <a href="login.php" class="nav-link">Login</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <h2><i class="fas fa-user-cog"></i> Complete Your Provider Profile</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <br><br>
                    <a href="login.php" class="btn-primary">Login Now</a>
                </div>
            <?php else: ?>
                <p style="margin-bottom: 1.5rem; color: #666;">
                    Please complete your provider profile to start offering services on ServiceHub.
                </p>
                
                <form method="POST" action="" enctype="multipart/form-data" data-validate>
                    <div class="form-group">
                        <label for="business_name">Business Name *</label>
                        <input type="text" id="business_name" name="business_name" required 
                               value="<?php echo isset($_POST['business_name']) ? htmlspecialchars($_POST['business_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="service_category">Service Category *</label>
                        <select id="service_category" name="service_category" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $key => $value): ?>
                                <option value="<?php echo $key; ?>" 
                                    <?php echo (isset($_POST['service_category']) && $_POST['service_category'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" 
                                  placeholder="Tell customers about your services, experience, and what makes you unique..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Service Location *</label>
                        <input type="text" id="location" name="location" required 
                               placeholder="e.g., New York, NY" 
                               value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required 
                               placeholder="+1234567890" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="hourly_rate">Hourly Rate (USD) *</label>
                        <input type="number" id="hourly_rate" name="hourly_rate" required 
                               min="1" step="0.01" 
                               value="<?php echo isset($_POST['hourly_rate']) ? htmlspecialchars($_POST['hourly_rate']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="kyc_document">KYC Document (ID, License, or Certificate) *</label>
                        <div class="file-upload">
                            <input type="file" id="kyc_document" name="kyc_document" required 
                                   accept=".pdf,.jpg,.jpeg,.png">
                            <div class="upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Choose file or drag here</p>
                                <small>Accepted formats: PDF, JPG, PNG (Max 5MB)</small>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-check"></i> Complete Setup
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html> 