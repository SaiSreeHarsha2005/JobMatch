<?php
// SESSION SETUP for localhost
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

// ADMIN AUTH CHECK
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once 'header.php';
require_once '../includes/db_connect.php';

// Example: Fetch applications with job title and applicant username
$stmt = $pdo->query("
    SELECT applications.id, jobs.title AS job_title, users.username AS applicant, applications.status
    FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    JOIN users ON applications.user_id = users.id
");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Applications</h2>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Job Title</th>
            <th>Applicant</th>
            <th>Status</th>
            <!-- Add Actions if needed -->
        </tr>
    </thead>
    <tbody>
    <?php foreach ($applications as $app): ?>
        <tr>
            <td><?= htmlspecialchars($app['id']) ?></td>
            <td><?= htmlspecialchars($app['job_title']) ?></td>
            <td><?= htmlspecialchars($app['applicant']) ?></td>
            <td><?= htmlspecialchars($app['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'footer.php'; ?>
