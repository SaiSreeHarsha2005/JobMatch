<?php
// For localhost, domain should be empty or omitted, and secure false if no HTTPS
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
session_regenerate_id(true);

// Consistent admin check - using role check from session
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

include 'header.php';
require_once '../includes/db_connect.php';

// Fetch statistics
$total_jobs = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$total_applications = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>

<h2>Dashboard</h2>
<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Total Jobs</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $total_jobs; ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Total Applications</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $total_applications; ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info mb-3">
            <div class="card-header">Total Users</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $total_users; ?></h5>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
