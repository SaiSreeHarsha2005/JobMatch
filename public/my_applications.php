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

// Only job seekers can view applications
if ($user['role'] !== 'job_seeker') {
    header('Location: dashboard.php');
    exit;
}

// Get filter parameter
$status_filter = $_GET['status'] ?? 'all';

// Build query with status filter
$where_conditions = ["a.job_seeker_id = ?"];
$params = [$user_id];

if ($status_filter !== 'all') {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get applications with job details
$sql = "
    SELECT a.id AS application_id, a.status, a.created_at AS applied_date, a.resume,
           j.id AS job_id, j.title, j.location, j.company_name, j.description, j.salary, j.job_type,
           u.username AS employer_name, u.email AS employer_email
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON j.employer_id = u.id
    WHERE $where_clause
    ORDER BY a.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Get application statistics
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_applications,
           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
           SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications,
           SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications
    FROM applications WHERE job_seeker_id = ?
");
$stmt->execute([$user_id]);
$app_stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Applications | JobMatch Pro</title>
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

        .applications-section {
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

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .filter-tab {
            padding: 0.75rem 1.5rem;
            border: none;
            background: #e2e8f0;
            color: #4a5568;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            text-decoration: none;
        }

        .filter-tab:hover,
        .filter-tab.active {
            background: linear-gradient(135deg, #4299e1, #667eea);
            color: white;
            transform: translateY(-2px);
        }

        .applications-grid {
            display: grid;
            gap: 1.5rem;
        }

        .application-card {
            background: #fff;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid;
        }

        .application-card.pending { border-left-color: #ed8936; }
        .application-card.accepted { border-left-color: #48bb78; }
        .application-card.rejected { border-left-color: #e53e3e; }

        .application-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .job-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .company-name {
            color: #4299e1;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .application-meta {
            display: flex;
            gap: 1.5rem;
            margin: 1rem 0;
            font-size: 0.85rem;
            color: #718096;
            flex-wrap: wrap;
        }

        .application-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .application-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
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

        .btn-outline {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #4a5568;
        }

        .btn-outline:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e53e3e, #fc8181);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #4a5568;
        }

        .job-description {
            color: #4a5568;
            line-height: 1.6;
            margin: 1rem 0;
            font-size: 0.9rem;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
            margin: 1rem 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            height: 100%;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.75rem;
            top: 0.25rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4299e1;
        }

        .timeline-date {
            font-size: 0.8rem;
            color: #718096;
            font-weight: 500;
        }

        .timeline-status {
            font-weight: 600;
            color: #2d3748;
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

            .section-header {
                flex-direction: column;
                gap: 1rem;
            }

            .application-header {
                flex-direction: column;
                gap: 1rem;
            }

            .application-actions {
                flex-direction: column;
            }

            .filter-tabs {
                flex-wrap: wrap;
            }

            .application-meta {
                flex-direction: column;
                gap: 0.5rem;
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
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="browse_jobs.php"><i class="fas fa-search"></i> Browse Jobs</a></li>
            <li><a href="my_applications.php" class="active"><i class="fas fa-file-alt"></i> My Applications</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="welcome-text">
                <h2>My Applications</h2>
                <p>Track your job application status and manage your career opportunities</p>
            </div>
            <div class="user-actions">
                <div class="user-profile">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($user['username']) ?></span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                <div class="stat-number"><?= $app_stats['total_applications'] ?? 0 ?></div>
                <div class="stat-label">Total Applications</div>
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
        </div>

        <!-- Applications Section -->
        <div class="applications-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-file-alt"></i> Application History</h3>
                <a href="browse_jobs.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Apply to More Jobs
                </a>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?= $status_filter === 'all' ? 'active' : '' ?>">
                    All Applications (<?= $app_stats['total_applications'] ?? 0 ?>)
                </a>
                <a href="?status=pending" class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">
                    Pending (<?= $app_stats['pending_applications'] ?? 0 ?>)
                </a>
                <a href="?status=accepted" class="filter-tab <?= $status_filter === 'accepted' ? 'active' : '' ?>">
                    Accepted (<?= $app_stats['accepted_applications'] ?? 0 ?>)
                </a>
                <a href="?status=rejected" class="filter-tab <?= $status_filter === 'rejected' ? 'active' : '' ?>">
                    Rejected (<?= $app_stats['rejected_applications'] ?? 0 ?>)
                </a>
            </div>

            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>
                        <?php if ($status_filter === 'all'): ?>
                            No applications yet
                        <?php else: ?>
                            No <?= $status_filter ?> applications
                        <?php endif; ?>
                    </h3>
                    <p>
                        <?php if ($status_filter === 'all'): ?>
                            Start applying to jobs that match your skills and interests
                        <?php else: ?>
                            You don't have any <?= $status_filter ?> applications at the moment
                        <?php endif; ?>
                    </p>
                    <a href="browse_jobs.php" class="btn btn-primary">Browse Jobs</a>
                </div>
            <?php else: ?>
                <div class="applications-grid">
                    <?php foreach ($applications as $app): ?>
                        <div class="application-card <?= $app['status'] ?>">
                            <div class="application-header">
                                <div>
                                    <div class="job-title"><?= htmlspecialchars($app['title']) ?></div>
                                    <div class="company-name"><?= htmlspecialchars($app['company_name']) ?></div>
                                    <div class="application-meta">
                                        <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($app['location']) ?></span>
                                        <span><i class="fas fa-clock"></i> <?= htmlspecialchars($app['job_type'] ?? 'Full-time') ?></span>
                                        <?php if ($app['salary']): ?>
                                            <span><i class="fas fa-dollar-sign"></i> <?= htmlspecialchars($app['salary']) ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-calendar"></i> Applied on <?= date('M d, Y', strtotime($app['applied_date'])) ?></span>
                                        <span><i class="fas fa-user"></i> Employer: <?= htmlspecialchars($app['employer_name']) ?></span>
                                    </div>
                                </div>
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
                                            echo '<i class="fas fa-clock"></i> Pending Review';
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="job-description">
                                <?= htmlspecialchars(substr($app['description'] ?? 'No description available', 0, 200)) ?>
                                <?php if (strlen($app['description'] ?? '') > 200): ?>...<?php endif; ?>
                            </div>

                           

                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-date"><?= date('M d, Y g:i A', strtotime($app['applied_date'])) ?></div>
                                    <div class="timeline-status">Application Submitted</div>
                                </div>
                                <?php if ($app['status'] !== 'pending'): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-date">Recently</div>
                                        <div class="timeline-status">
                                            Application <?= ucfirst($app['status']) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="application-actions">
                                <a href="view_job.php?id=<?= $app['job_id'] ?>" class="btn btn-outline">
                                    <i class="fas fa-eye"></i> View Job Details
                                </a>
                                <a href="view_application.php?id=<?= $app['application_id'] ?>" class="btn btn-outline">
                                    <i class="fas fa-file-alt"></i> View Application
                                </a>
                                <?php if ($app['status'] === 'accepted'): ?>
                                    <a href="contact_employer.php?job_id=<?= $app['job_id'] ?>" class="btn btn-success">
                                        <i class="fas fa-envelope"></i> Contact Employer
                                    </a>
                                <?php endif; ?>
                                <?php if ($app['status'] === 'pending'): ?>
                                    <button onclick="withdrawApplication(<?= $app['application_id'] ?>)" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Withdraw Application
                                    </button>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function withdrawApplication(applicationId) {
            if (confirm('Are you sure you want to withdraw this application? This action cannot be undone.')) {
                fetch('withdraw_application.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({application_id: applicationId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Application withdrawn successfully', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification('Failed to withdraw application', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred', 'error');
                });
            }
        }

        

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
            }, 4000);
        }

        // Add animation styles
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
            
            .application-card {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .stat-card {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
        `;
        document.head.appendChild(style);

        // Auto-refresh application status every 60 seconds
        setInterval(() => {
            if (!document.hidden) {
                fetch('check_application_updates.php')
                .then(response => response.json())
                .then(data => {
                    if (data.updates && data.updates.length > 0) {
                        data.updates.forEach(update => {
                            showNotification(`Application for "${update.job_title}" has been ${update.status}`, 
                                           update.status === 'accepted' ? 'success' : 
                                           update.status === 'rejected' ? 'error' : 'info');
                        });
                        
                        // Reload page after showing notifications
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    }
                })
                .catch(error => console.log('Update check failed:', error));
            }
        }, 60000);

        // Show success/error messages
        <?php if (isset($_SESSION['success_message'])): ?>
            showNotification('<?= $_SESSION['success_message'] ?>', 'success');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            showNotification('<?= $_SESSION['error_message'] ?>', 'error');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'j':
                        e.preventDefault();
                        location.href = 'browse_jobs.php';
                        break;
                    case 'h':
                        e.preventDefault();
                        location.href = 'dashboard.php';
                        break;
                }
            }
        });

        // Add tooltips and enhanced interactions
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

        // Filter animation
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                if (!this.classList.contains('active')) {
                    document.querySelectorAll('.application-card').forEach(card => {
                        card.style.opacity = '0.5';
                        card.style.transform = 'scale(0.95)';
                    });
                }
            });
        });

        // Progressive loading for large lists
        if (document.querySelectorAll('.application-card').length > 10) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            document.querySelectorAll('.application-card').forEach((card, index) => {
                if (index > 5) {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    observer.observe(card);
                }
            });
        }
    </script>
</body>
</html>