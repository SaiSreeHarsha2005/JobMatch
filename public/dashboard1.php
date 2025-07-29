<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$is_employer = $user['role'] === 'employer';

if ($is_employer) {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $jobs = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT a.id AS application_id, a.status, a.created_at AS applied_date, a.resume,
               j.id AS job_id, j.title,
               u.id AS applicant_id, u.username AS applicant_name, u.email AS applicant_email
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.job_seeker_id = u.id
        WHERE j.employer_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll();

} else {
    $stmt = $pdo->prepare("
        SELECT a.id AS application_id, a.status, a.created_at AS applied_date,
               j.id AS job_id, j.title, j.location, j.company_name
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.job_seeker_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT * FROM jobs
        WHERE status = 'active'
        AND id NOT IN (
            SELECT job_id FROM applications WHERE job_seeker_id = ?
        )
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recommended_jobs = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | JobMatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f2f4f8; }
        .header { background: #003366; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: #fff; margin-left: 1rem; text-decoration: none; padding: 6px 12px; background: #0055aa; border-radius: 4px; }
        .container { max-width: 1200px; margin: auto; padding: 2rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn-green { background: #28a745; color: white; }
        .btn-red { background: #dc3545; color: white; }
        .btn-outline { border: 1px solid #003366; color: #003366; background: transparent; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; }
        .badge { padding: 0.3rem 0.6rem; border-radius: 5px; font-size: 0.8rem; text-transform: capitalize; }
        .badge-success { background: #28a745; color: #fff; }
        .badge-danger { background: #dc3545; color: #fff; }
        .badge-warning { background: #ffc107; color: #333; }
        .actions { display: flex; gap: 0.5rem; }
    </style>
</head>
<body>
    <div class="header">
        <strong>JobMatch</strong>
        <div>
            Hello, <?php echo htmlspecialchars($user['username']); ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <div class="container">
        <?php if ($is_employer): ?>
            <div class="card">
                <h2>Your Job Listings <a href="post_job.php" class="btn btn-outline" style="float:right;">Post Job</a></h2>
                <?php if (empty($jobs)): echo "<p>No jobs posted yet.</p>"; else: ?>
                <table>
                    <thead><tr><th>Title</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td><?= htmlspecialchars($job['title']) ?></td>
                            <td><span class="badge badge-<?= $job['status'] === 'active' ? 'success' : 'danger' ?>"><?= $job['status'] ?></span></td>
                            <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                            <td class="actions">
                                <a href="edit_job.php?id=<?= $job['id'] ?>" class="btn-outline">Edit</a>
                                <a href="delete_job.php?id=<?= $job['id'] ?>" onclick="return confirm('Delete this job?')" class="btn-outline">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Applications Received</h2>
                <?php if (empty($applications)): echo "<p>No applications yet.</p>"; else: ?>
                <table>
                    <thead><tr><th>Applicant</th><th>Email</th><th>Job</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= htmlspecialchars($app['applicant_name']) ?></td>
                            <td><?= htmlspecialchars($app['applicant_email']) ?></td>
                            <td><?= htmlspecialchars($app['title']) ?></td>
                            <td><span class="badge badge-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'approved' ? 'success' : 'danger'); ?>"><?= $app['status'] ?></span></td>
                            <td class="actions">
                                <?php if ($app['status'] === 'pending'): ?>
                                    <form action="update_application_status.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                                        <button class="btn btn-green" name="action" value="approved">Accept</button>
                                        <button class="btn btn-red" name="action" value="rejected">Reject</button>
                                    </form>
                                <?php endif; ?>
                                <a href="view_application.php?id=<?= $app['application_id'] ?>" class="btn-outline">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>Your Applications</h2>
                <?php if (empty($applications)): echo "<p>You haven't applied to any jobs yet.</p>"; else: ?>
                <table>
                    <thead><tr><th>Title</th><th>Company</th><th>Location</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= htmlspecialchars($app['title']) ?></td>
                            <td><?= htmlspecialchars($app['company_name']) ?></td>
                            <td><?= htmlspecialchars($app['location']) ?></td>
                            <td><span class="badge badge-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'approved' ? 'success' : 'danger'); ?>"><?= $app['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Recommended Jobs</h2>
                <?php if (empty($recommended_jobs)): echo "<p>No job recommendations right now.</p>"; else: ?>
                <table>
                    <thead><tr><th>Title</th><th>Company</th><th>Location</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($recommended_jobs as $job): ?>
                        <tr>
                            <td><?= htmlspecialchars($job['title']) ?></td>
                            <td><?= htmlspecialchars($job['company_name']) ?></td>
                            <td><?= htmlspecialchars($job['location']) ?></td>
                            <td>
                                <form method="POST" action="apply_job.php">
                                    <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                    <button type="submit" class="btn btn-green">Apply</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
