<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a provider
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

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $service_id = intval($_GET['delete']);
    // Only allow deleting own service
    $del_query = "DELETE FROM services WHERE id = ? AND provider_id = ?";
    $del_stmt = mysqli_prepare($conn, $del_query);
    mysqli_stmt_bind_param($del_stmt, 'ii', $service_id, $provider_id);
    if (mysqli_stmt_execute($del_stmt)) {
        redirect_with_message('services.php', 'Service deleted successfully!', 'success');
    } else {
        redirect_with_message('services.php', 'Failed to delete service.', 'error');
    }
}

// Fetch provider's services with category from providers table
$services_query = "SELECT s.*, p.service_category 
                  FROM services s 
                  JOIN providers p ON s.provider_id = p.id 
                  WHERE s.provider_id = ? 
                  ORDER BY s.created_at DESC";
$services_stmt = mysqli_prepare($conn, $services_query);
mysqli_stmt_bind_param($services_stmt, 'i', $provider_id);
mysqli_stmt_execute($services_stmt);
$services_result = mysqli_stmt_get_result($services_stmt);

$categories = get_service_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Services - ServiceHub</title>
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
                <a href="services.php" class="nav-link active">My Services</a>
                <a href="bookings.php" class="nav-link">Bookings</a>
                <a href="profile.php" class="nav-link">Profile</a>
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
        <div class="dashboard-header">
            <h1><i class="fas fa-cogs"></i> My Services</h1>
            <p>Manage your service listings below.</p>
            <a href="add-service.php" class="btn-primary" style="margin-top:1rem;"><i class="fas fa-plus"></i> Add New Service</a>
        </div>
        <?php echo display_message(); ?>
        <div class="table-container" style="margin-top:2rem;">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($services_result) > 0): ?>
                        <?php while ($service = mysqli_fetch_assoc($services_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['title']); ?></td>
                                <td><?php echo htmlspecialchars($categories[$service['service_category']] ?? ucfirst($service['service_category'])); ?></td>
                                <td><?php echo format_currency($service['price']); ?></td>
                                <td><span class="status-badge status-<?php echo $service['status']; ?>"><?php echo ucfirst($service['status']); ?></span></td>
                                <td><?php echo format_date($service['created_at']); ?></td>
                                <td>
                                    <a href="edit-service.php?id=<?php echo $service['id']; ?>" class="btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="services.php?delete=<?php echo $service['id']; ?>" class="btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this service?');"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No services found. <a href="add-service.php">Add your first service</a>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html> 