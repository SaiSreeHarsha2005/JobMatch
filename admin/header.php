<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <!-- Navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item"><a class="nav-link" href="manage_jobs.php">Jobs</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_applications.php">Applications</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
            </ul>
            <span class="navbar-text">
                Logged in as <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
        </div>
    </nav>
    <div class="container mt-4">
