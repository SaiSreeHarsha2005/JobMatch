<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['role'] === 'employer') {
    $stmt = $pdo->prepare('
        SELECT a.*, j.title, u.username, u.email
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.job_seeker_id = u.id
        WHERE j.employer_id = ?
        ORDER BY a.created_at DESC
    ');
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare('
        SELECT a.*, j.title, j.company_name
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.job_seeker_id = ?
        ORDER BY a.created_at DESC
    ');
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll();
}

include '../includes/header.php';
?>

<div style="max-width: 1000px; margin: 30px auto; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 0 15px rgba(0,0,0,0.1);">
    <h2 style="margin-bottom: 20px; color: #333;">Your Applications</h2>

    <?php if (count($applications) > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f0f0f0;">
                        <th style="padding: 12px; text-align: left;">Job Title</th>
                        <?php if ($user['role'] === 'employer'): ?>
                            <th style="padding: 12px; text-align: left;">Applicant</th>
                            <th style="padding: 12px; text-align: left;">Email</th>
                        <?php else: ?>
                            <th style="padding: 12px; text-align: left;">Company</th>
                        <?php endif; ?>
                        <th style="padding: 12px; text-align: left;">Status</th>
                        <th style="padding: 12px; text-align: left;">Applied On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 12px;"><?php echo htmlspecialchars($app['title']); ?></td>
                            <?php if ($user['role'] === 'employer'): ?>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($app['username']); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($app['email']); ?></td>
                            <?php else: ?>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($app['company_name']); ?></td>
                            <?php endif; ?>
                            <td style="padding: 12px; color: 
                                <?php 
                                    echo $app['status'] === 'Accepted' ? 'green' : 
                                         ($app['status'] === 'Rejected' ? 'red' : '#555');
                                ?>">
                                <?php echo htmlspecialchars($app['status']); ?>
                            </td>
                            <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: #666;">No applications found.</p>
    <?php endif; ?>

    <div style="margin-top: 30px; display: flex; gap: 10px;">
        <a href="dashboard.php" style="padding: 10px 18px; background: #3498db; color: #fff; text-decoration: none; border-radius: 6px;">Back to Dashboard</a>
        
    </div>
</div>

<?php include '../includes/footer.php'; ?>
