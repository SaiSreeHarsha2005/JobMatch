<?php
// SESSION SETUP
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

// Allow both admin and employer to access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'employer'])) {
    header("Location: /job_portal/public/login.php");
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/header.php';
require_once '../includes/csrf_token.php';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job_id'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $job_id = intval($_POST['delete_job_id']);
        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
        $stmt->execute([$job_id]);
        echo '<div class="alert-success">Job deleted successfully.</div>';
    } else {
        echo '<div class="alert-danger">Invalid CSRF token.</div>';
    }
}

// Fetch jobs
$stmt = $pdo->prepare("
    SELECT jobs.id, jobs.title, users.username AS employer
    FROM jobs
    JOIN users ON jobs.employer_id = users.id
    WHERE jobs.employer_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .job-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 0 12px rgba(0,0,0,0.05);
    }
    .job-table th, .job-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #eee;
        text-align: left;
    }
    .job-table th {
        background-color: #f7f7f7;
        font-weight: bold;
    }
    .job-table tr:hover {
        background-color: #f0f8ff;
    }
    .btn-danger {
        background: #dc3545;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
    }
    .btn-danger:hover {
        background: #c82333;
    }
    .alert-success {
        color: green;
        margin-top: 10px;
    }
    .alert-danger {
        color: red;
        margin-top: 10px;
    }
    h2 {
        margin-top: 20px;
        font-size: 26px;
    }
</style>

<h2>Manage Jobs</h2>

<?php if (count($jobs) === 0): ?>
    <p>No jobs found.</p>
<?php else: ?>
<table class="job-table">
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
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this job?');" style="display:inline;">
                    <input type="hidden" name="delete_job_id" value="<?= $job['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
