<?php
// SESSION SETUP for localhost (no domain, no HTTPS)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',      // localhost compatibility
    'secure' => false,   // no HTTPS on localhost
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
require_once '../includes/csrf_token.php'; // Assumes this sets/generates $_SESSION['csrf_token']

// Handle job deletion (POST with CSRF check)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job_id'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $job_id = intval($_POST['delete_job_id']);
        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
        $stmt->execute([$job_id]);
        echo '<div class="alert alert-success">Job deleted successfully.</div>';
    } else {
        echo '<div class="alert alert-danger">Invalid CSRF token.</div>';
    }
}

// Fetch jobs with employer username
$stmt = $pdo->query("
    SELECT jobs.id, jobs.title, users.username AS employer
    FROM jobs
    JOIN users ON jobs.employer_id = users.id
");
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Jobs</h2>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Employer</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($jobs as $job): ?>
        <tr>
            <td><?= htmlspecialchars($job['id']) ?></td>
            <td><?= htmlspecialchars($job['title']) ?></td>
            <td><?= htmlspecialchars($job['employer']) ?></td>
            <td>
                <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="delete_job_id" value="<?= $job['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this job?');">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'footer.php'; ?>
