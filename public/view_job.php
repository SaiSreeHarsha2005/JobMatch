<?php
// SESSION SETUP
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/header.php';

// Get job ID from query parameter
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($job_id <= 0) {
    echo "<p>Invalid Job ID.</p>";
    require_once '../includes/footer.php';
    exit;
}

// Fetch job details
$stmt = $pdo->prepare("
    SELECT jobs.*, users.username AS employer_name
    FROM jobs
    JOIN users ON jobs.employer_id = users.id
    WHERE jobs.id = ?
");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "<p>Job not found.</p>";
    require_once '../includes/footer.php';
    exit;
}
?>

<style>
    .job-details-container {
        max-width: 800px;
        margin: 30px auto;
        background: #fff;
        padding: 25px 30px;
        border-radius: 12px;
        box-shadow: 0 0 12px rgba(0,0,0,0.05);
    }
    .job-details-container h2 {
        margin-bottom: 15px;
        font-size: 28px;
    }
    .job-info p {
        margin: 8px 0;
        line-height: 1.5;
    }
    .btn-back {
        margin-top: 20px;
        display: inline-block;
        padding: 10px 16px;
        background: #007bff;
        color: white;
        border-radius: 6px;
        text-decoration: none;
    }
    .btn-back:hover {
        background: #0056b3;
    }
</style>

<div class="job-details-container">
    <h2><?= htmlspecialchars($job['title']) ?></h2>
    <div class="job-info">
        <p><strong>Company:</strong> <?= htmlspecialchars($job['company_name']) ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
        <p><strong>Type:</strong> <?= htmlspecialchars($job['job_type']) ?></p>
        <p><strong>Salary:</strong> <?= htmlspecialchars($job['salary']) ?></p>
        <p><strong>Posted by:</strong> <?= htmlspecialchars($job['employer_name']) ?></p>
        <p><strong>Posted on:</strong> <?= date('F j, Y', strtotime($job['created_at'])) ?></p>
        <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($job['description'])) ?></p>
    </div>

    <a href="browse_jobs.php" class="btn-back">← Back to Jobs</a>
</div>

<?php require_once '../includes/footer.php'; ?>
