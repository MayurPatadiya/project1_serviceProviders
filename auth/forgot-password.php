<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!validate_email($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if user exists
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            // Generate a reset token (not stored, as no column exists yet)
            $token = generate_random_string(32);
            // Compose reset link (not functional yet)
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset-password.php?token=$token";
            // Send email (simulate)
            $subject = "Password Reset Request - ServiceHub";
            $message = "Hello,\n\nWe received a request to reset your password. Please click the link below to reset your password:\n$reset_link\n\nIf you did not request this, please ignore this email.";
            $headers = "From: no-reply@servicehub.com";
            // mail($email, $subject, $message, $headers); // Uncomment when ready
            $success = 'If an account with that email exists, a password reset link has been sent.';
        } else {
            $success = 'If an account with that email exists, a password reset link has been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ServiceHub</title>
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
                <a href="register.php" class="nav-link">Register</a>
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
            <h2><i class="fas fa-unlock-alt"></i> Forgot Password</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (!$success): ?>
            <form method="POST" action="" data-validate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
            <?php endif; ?>
            <div style="text-align: center; margin-top: 1rem;">
                <p><a href="login.php" class="links">Back to Login</a></p>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 