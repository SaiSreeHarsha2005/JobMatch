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
    // Get job statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_jobs, 
                          SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_jobs,
                          SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_jobs
                          FROM jobs WHERE employer_id = ?");
    $stmt->execute([$user_id]);
    $job_stats = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $jobs = $stmt->fetchAll();

    // Get application statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_applications,
               SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
               SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications,
               SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE j.employer_id = ?
    ");
    $stmt->execute([$user_id]);
    $app_stats = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT a.id AS application_id, a.status, a.created_at AS applied_date, a.resume,
               j.id AS job_id, j.title, j.company_name,
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
    // Get application statistics for job seeker
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_applications,
               SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
               SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications,
               SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications
        FROM applications WHERE job_seeker_id = ?
    ");
    $stmt->execute([$user_id]);
    $app_stats = $stmt->fetch();

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
        SELECT j.*
        FROM jobs j
        WHERE j.status = 'active'
        AND j.id NOT IN (
            SELECT job_id FROM applications WHERE job_seeker_id = ?
        )
        ORDER BY j.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$user_id]);
    $recommended_jobs = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | JobMatch Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2d3748;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #1a202c 0%, #2d3748 100%);
            padding: 2rem 0;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }

        .sidebar-logo {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid #4a5568;
            margin-bottom: 2rem;
        }

        .sidebar-logo h1 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .sidebar-logo span {
            color: #4299e1;
            font-size: 0.9rem;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: #cbd5e0;
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(90deg, #4299e1, #667eea);
            color: #fff;
        }

        .sidebar-menu i {
            margin-right: 1rem;
            width: 20px;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .top-bar {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .welcome-text h2 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            color: #718096;
            font-size: 0.9rem;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification-btn {
            position: relative;
            background: #f7fafc;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .notification-btn:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e53e3e;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 0.7rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1rem;
            background: #f7fafc;
            border-radius: 12px;
        }

        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #4299e1, #667eea); color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #48bb78, #38b2ac); color: white; }
        .stat-icon.orange { background: linear-gradient(135deg, #ed8936, #f6ad55); color: white; }
        .stat-icon.red { background: linear-gradient(135deg, #e53e3e, #fc8181); color: white; }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
        }

        .content-grid {
            display: grid;
            gap: 2rem;
            grid-template-columns: 1fr;
        }

        .section-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4299e1, #667eea);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 153, 225, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38b2ac);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e53e3e, #fc8181);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #4a5568;
        }

        .btn-outline:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .data-table th {
            background: #f7fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .data-table tr:hover {
            background: #f7fafc;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .badge-pending {
            background: #fed7d7;
            color: #c53030;
        }

        .badge-accepted {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-rejected {
            background: #fed7d7;
            color: #c53030;
        }

        .badge-active {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-closed {
            background: #e2e8f0;
            color: #4a5568;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .job-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .job-card {
            background: #fff;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid #4299e1;
        }

        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .job-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .company-name {
            color: #718096;
            font-size: 0.9rem;
        }

        .job-meta {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            font-size: 0.85rem;
            color: #718096;
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            border: none;
            background: #e2e8f0;
            color: #4a5568;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #4299e1, #667eea);
            color: white;
        }

        .status-update-form {
            display: inline-flex;
            gap: 0.5rem;
        }

        .quick-actions {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .fab {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4299e1, #667eea);
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(66, 153, 225, 0.3);
            transition: all 0.3s;
        }

        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(66, 153, 225, 0.4);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-bar {
                flex-direction: column;
                gap: 1rem;
            }

            .job-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <h1>JobMatch <span>Pro</span></h1>
        </div>
        <ul class="sidebar-menu">
            <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <?php if ($is_employer): ?>
                <li><a href="post_job.php"><i class="fas fa-plus-circle"></i> Post Job</a></li>
              <li><a href="/job_portal/public/manage_jobs.php"><i class="fas fa-briefcase"></i> Manage Jobs</a></li>


                <li><a href="applications.php"><i class="fas fa-file-alt"></i> Applications</a></li>
            <?php else: ?>
                <li><a href="browse_jobs.php"><i class="fas fa-search"></i> Browse Jobs</a></li>
                <li><a href="my_applications.php"><i class="fas fa-file-alt"></i> My Applications</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <?php endif; ?>
            
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h2>
                <p><?php echo $is_employer ? 'Manage your job listings and review applications' : 'Discover your next career opportunity'; ?></p>
            </div>
            <div class="user-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <?php if ($is_employer && $app_stats['pending_applications'] > 0): ?>
                        <span class="notification-badge"><?= $app_stats['pending_applications'] ?></span>
                    <?php endif; ?>
                </button>
                <div class="user-profile">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    </div>
                    <span><?= ucfirst($user['role']) ?></span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <?php if ($is_employer): ?>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-briefcase"></i></div>
                    <div class="stat-number"><?= $job_stats['total_jobs'] ?? 0 ?></div>
                    <div class="stat-label">Total Jobs Posted</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number"><?= $job_stats['active_jobs'] ?? 0 ?></div>
                    <div class="stat-label">Active Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?= $app_stats['pending_applications'] ?? 0 ?></div>
                    <div class="stat-label">Pending Applications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-users"></i></div>
                    <div class="stat-number"><?= $app_stats['total_applications'] ?? 0 ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
            <?php else: ?>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-number"><?= $app_stats['total_applications'] ?? 0 ?></div>
                    <div class="stat-label">Applications Sent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?= $app_stats['pending_applications'] ?? 0 ?></div>
                    <div class="stat-label">Pending Reviews</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number"><?= $app_stats['accepted_applications'] ?? 0 ?></div>
                    <div class="stat-label">Accepted</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-number"><?= $app_stats['rejected_applications'] ?? 0 ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Content Sections -->
        <div class="content-grid">
            <?php if ($is_employer): ?>
                <!-- Job Listings Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title"><i class="fas fa-briefcase"></i> Your Job Listings</h3>
                        <a href="post_job.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Post New Job
                        </a>
                    </div>
                    
                    <?php if (empty($jobs)): ?>
                        <div class="empty-state">
                            <i class="fas fa-briefcase"></i>
                            <h4>No jobs posted yet</h4>
                            <p>Start by posting your first job to attract talented candidates</p>
                            <a href="post_job.php" class="btn btn-primary">Post Your First Job</a>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Status</th>
                                    <th>Applications</th>
                                    <th>Posted Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jobs as $job): 
                                    $app_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = ?");
                                    $app_count_stmt->execute([$job['id']]);
                                    $app_count = $app_count_stmt->fetch()['count'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                                        <div class="company-name"><?= htmlspecialchars($job['company_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $job['status'] ?>">
                                            <?= ucfirst($job['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="stat-number" style="font-size: 1rem;"><?= $app_count ?></span>
                                        <small style="color: #718096;">applications</small>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                                    <td class="actions">
                                        <a href="view_job.php?id=<?= $job['id'] ?>" class="btn btn-outline" style="padding: 0.5rem;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_job.php?id=<?= $job['id'] ?>" class="btn btn-outline" style="padding: 0.5rem;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_job.php?id=<?= $job['id'] ?>" 
                                           onclick="return confirm('Are you sure you want to delete this job?')" 
                                           class="btn btn-danger" style="padding: 0.5rem;">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Applications Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title"><i class="fas fa-file-alt"></i> Recent Applications</h3>
                    </div>

                    <?php if (empty($applications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <h4>No applications received yet</h4>
                            <p>Applications will appear here once candidates start applying to your jobs</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Job Applied</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <div class="job-title"><?= htmlspecialchars($app['applicant_name']) ?></div>
                                        <div class="company-name"><?= htmlspecialchars($app['applicant_email']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($app['title']) ?></td>
                                    <td><?= date('M d, Y', strtotime($app['applied_date'])) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $app['status'] ?>">
                                            <?= ucfirst($app['status']) ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <?php if ($app['status'] === 'pending'): ?>
                                            <form class="status-update-form" action="update_application_status.php" method="POST">
                                                <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                                                <button type="submit" name="action" value="accepted" class="btn btn-success" style="padding: 0.5rem;">
                                                    <i class="fas fa-check"></i> Accept
                                                </button>
                                                <button type="submit" name="action" value="rejected" class="btn btn-danger" style="padding: 0.5rem;">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="view_application.php?id=<?= $app['application_id'] ?>" class="btn btn-outline" style="padding: 0.5rem;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- Job Seeker Dashboard -->
                <!-- My Applications Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title"><i class="fas fa-file-alt"></i> My Applications</h3>
                    </div>

                    <?php if (empty($applications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <h4>No applications yet</h4>
                            <p>Start applying to jobs that match your skills and interests</p>
                            <a href="browse_jobs.php" class="btn btn-primary">Browse Jobs</a>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Location</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <div class="job-title"><?= htmlspecialchars($app['title']) ?></div>
                                        <div class="company-name">Full-time</div>
                                    </td>
                                    <td><?= htmlspecialchars($app['company_name']) ?></td>
                                    <td><?= htmlspecialchars($app['location']) ?></td>
                                    <td><?= date('M d, Y', strtotime($app['applied_date'])) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $app['status'] ?>">
                                            <?php 
                                            switch($app['status']) {
                                                case 'accepted':
                                                    echo '<i class="fas fa-check-circle"></i> Accepted';
                                                    break;
                                                case 'rejected':
                                                    echo '<i class="fas fa-times-circle"></i> Rejected';
                                                    break;
                                                default:
                                                    echo '<i class="fas fa-clock"></i> Pending';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="view_job.php?id=<?= $app['job_id'] ?>" class="btn btn-outline" style="padding: 0.5rem;">
                                            <i class="fas fa-eye"></i> View Job
                                        </a>
                                        <?php if ($app['status'] === 'accepted'): ?>
                                            <a href="contact_employer.php?job_id=<?= $app['job_id'] ?>" class="btn btn-success" style="padding: 0.5rem;">
                                                <i class="fas fa-envelope"></i> Contact
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Recommended Jobs Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title"><i class="fas fa-star"></i> Recommended Jobs</h3>
                        <a href="browse_jobs.php" class="btn btn-outline">View All Jobs</a>
                    </div>

                    <?php if (empty($recommended_jobs)): ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h4>No recommendations available</h4>
                            <p>We'll show personalized job recommendations based on your profile and preferences</p>
                        </div>
                    <?php else: ?>
                        <div class="job-grid">
                            <?php foreach ($recommended_jobs as $job): ?>
                            <div class="job-card">
                                <div class="job-header">
                                    <div>
                                        <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                                        <div class="company-name"><?= htmlspecialchars($job['company_name']) ?></div>
                                    </div>
                                    <span class="badge badge-active">New</span>
                                </div>
                                
                                <div class="job-meta">
                                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                    <span><i class="fas fa-clock"></i> Full-time</span>
                                </div>
                                
                                <p style="color: #718096; font-size: 0.9rem; margin-bottom: 1rem;">
                                    <?= htmlspecialchars(substr($job['description'] ?? 'No description available', 0, 120)) ?>...
                                </p>
                                
                                <div class="actions">
                                    <form method="POST" action="apply_job.php" style="display: inline;">
                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Apply Now
                                        </button>
                                    </form>
                                    <a href="view_job.php?id=<?= $job['id'] ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions FAB -->
    <div class="quick-actions">
        <?php if ($is_employer): ?>
            <button class="fab" onclick="location.href='post_job.php'" title="Post New Job">
                <i class="fas fa-plus"></i>
            </button>
        <?php else: ?>
            <button class="fab" onclick="location.href='browse_jobs.php'" title="Browse Jobs">
                <i class="fas fa-search"></i>
            </button>
        <?php endif; ?>
    </div>

    <script>
        // Enhanced form submission with loading states
        document.querySelectorAll('.status-update-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = e.submitter;
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Show notification
                showNotification('Processing application status...', 'info');
            });
        });

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 9999;
                animation: slideIn 0.3s ease;
                ${type === 'success' ? 'background: #48bb78;' : 
                  type === 'error' ? 'background: #e53e3e;' : 
                  type === 'warning' ? 'background: #ed8936;' : 'background: #4299e1;'}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Smooth animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .notification {
                animation: slideIn 0.3s ease;
            }
            
            .data-table tbody tr {
                transition: all 0.3s ease;
            }
            
            .job-card {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .stat-card {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
        `;
        document.head.appendChild(style);

        // Mobile menu functionality
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0px)';
        }

        // Add mobile menu button for small screens
        if (window.innerWidth <= 768) {
            const mobileMenuBtn = document.createElement('button');
            mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            mobileMenuBtn.className = 'mobile-menu-btn';
            mobileMenuBtn.style.cssText = `
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1001;
                background: #4299e1;
                color: white;
                border: none;
                padding: 0.75rem;
                border-radius: 8px;
                cursor: pointer;
                box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
            `;
            mobileMenuBtn.onclick = toggleMobileMenu;
            document.body.appendChild(mobileMenuBtn);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'n':
                        e.preventDefault();
                        <?php if ($is_employer): ?>
                            location.href = 'post_job.php';
                        <?php else: ?>
                            location.href = 'browse_jobs.php';
                        <?php endif; ?>
                        break;
                    case 'h':
                        e.preventDefault();
                        location.href = 'dashboard.php';
                        break;
                }
            }
        });

        // Add tooltips to action buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                if (this.getAttribute('title')) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip';
                    tooltip.textContent = this.getAttribute('title');
                    tooltip.style.cssText = `
                        position: absolute;
                        background: #2d3748;
                        color: white;
                        padding: 0.5rem;
                        border-radius: 4px;
                        font-size: 0.8rem;
                        z-index: 9999;
                        pointer-events: none;
                        white-space: nowrap;
                    `;
                    document.body.appendChild(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = rect.left + 'px';
                    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
                    
                    this.addEventListener('mouseleave', () => tooltip.remove(), { once: true });
                }
            });
        });

        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            // Only refresh if page is visible
            if (!document.hidden) {
                fetch('get_dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    // Update notification badge
                    const badge = document.querySelector('.notification-badge');
                    if (data.pending_applications > 0) {
                        if (badge) {
                            badge.textContent = data.pending_applications;
                        } else {
                            const notificationBtn = document.querySelector('.notification-btn');
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge';
                            newBadge.textContent = data.pending_applications;
                            notificationBtn.appendChild(newBadge);
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                })
                .catch(error => console.log('Stats refresh failed:', error));
            }
        }, 30000);

        // Add success message for successful actions
        <?php if (isset($_SESSION['success_message'])): ?>
            showNotification('<?= $_SESSION['success_message'] ?>', 'success');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            showNotification('<?= $_SESSION['error_message'] ?>', 'error');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>