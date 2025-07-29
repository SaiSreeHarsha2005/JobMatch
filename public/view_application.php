<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$application_id = $_GET['id'] ?? null;

if (!$application_id) {
    echo "Invalid request.";
    exit;
}

// Get user role
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "User not found.";
    exit;
}

$role = $user['role'];

// Get application with related data (fixing the resume issue)
$stmt = $pdo->prepare("
    SELECT a.*, j.title, j.employer_id, u.username AS applicant_name, u.email
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.job_seeker_id = u.id
    WHERE a.id = ?
");
$stmt->execute([$application_id]);
$app = $stmt->fetch();

if (!$app) {
    echo "Application not found.";
    exit;
}

// Authorization: only employer of the job or job seeker who applied can view
if (
    ($role === 'employer' && $user_id != $app['employer_id']) ||
    ($role === 'job_seeker' && $user_id != $app['job_seeker_id'])
) {
    echo "You do not have access to this application.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Application Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2rem;
        }
        .app-box {
            max-width: 600px;
            padding: 2rem;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        h2 {
            color: #004080;
        }
        p {
            margin-bottom: 1rem;
        }
        a.btn {
            display: inline-block;
            background: #004080;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
        }
        a.btn:hover {
            background: #003060;
        }
    </style>
</head>
<body>
    <div class="app-box">
        <h2>Application Details</h2>
        <p><strong>Applicant:</strong> <?= htmlspecialchars($app['applicant_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($app['email']) ?></p>
        <p><strong>Job Title:</strong> <?= htmlspecialchars($app['title']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($app['status']) ?></p>
        <p><strong>Resume:</strong>
            <?php if (!empty($app['resume'])): ?>
                <a href="../uploads/<?= htmlspecialchars($app['resume']) ?>" class="btn" target="_blank">Download Resume</a>

            <?php else: ?>
                Not uploaded.
            <?php endif; ?>
        </p>
        <p><strong>Applied On:</strong> <?= date('M d, Y', strtotime($app['application_date'])) ?></p>
    </div>
</body>
</html>
