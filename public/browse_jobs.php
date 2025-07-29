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

// Only job seekers can browse jobs
if ($user['role'] !== 'job_seeker') {
    header('Location: dashboard.php');
    exit;
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query with filters
$where_conditions = ["j.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(j.title LIKE ? OR j.description LIKE ? OR j.company_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($location)) {
    $where_conditions[] = "j.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($category)) {
    $where_conditions[] = "j.category = ?";
    $params[] = $category;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$order_by = match($sort) {
    'oldest' => 'j.created_at ASC',
    'company' => 'j.company_name ASC',
    'title' => 'j.title ASC',
    default => 'j.created_at DESC'
};

// Get jobs with application status
$sql = "
    SELECT j.*, 
           CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END as already_applied,
           a.status as application_status
    FROM jobs j
    LEFT JOIN applications a ON j.id = a.job_id AND a.job_seeker_id = ?
    WHERE $where_clause
    ORDER BY $order_by
";

$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge([$user_id], $params));
$jobs = $stmt->fetchAll();

// Get job categories for filter
$stmt = $pdo->prepare("SELECT DISTINCT category FROM jobs WHERE status = 'active' AND category IS NOT NULL ORDER BY category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get locations for filter
$stmt = $pdo->prepare("SELECT DISTINCT location FROM jobs WHERE status = 'active' AND location IS NOT NULL ORDER BY location");
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Jobs | JobMatch Pro</title>
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

        .search-section {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4a5568;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
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

        .btn-disabled {
            background: #e2e8f0;
            color: #a0aec0;
            cursor: not-allowed;
        }

        .results-section {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .results-count {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
        }

        .sort-select {
            padding: 0.5rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
        }

        .job-grid {
            display: grid;
            gap: 1.5rem;
        }

        .job-card {
            background: #fff;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid #4299e1;
        }

        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .job-header {
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

        .job-meta {
            display: flex;
            gap: 1.5rem;
            margin: 1rem 0;
            font-size: 0.85rem;
            color: #718096;
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .job-description {
            color: #4a5568;
            line-height: 1.6;
            margin: 1rem 0;
        }

        .job-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .badge-applied {
            background: #c6f6d5;
            color: #22543d;
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

        .quick-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-chip {
            padding: 0.5rem 1rem;
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-chip:hover,
        .filter-chip.active {
            background: #4299e1;
            color: white;
            border-color: #4299e1;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .top-bar {
                flex-direction: column;
                gap: 1rem;
            }

            .results-header {
                flex-direction: column;
                gap: 1rem;
            }

            .job-header {
                flex-direction: column;
                gap: 1rem;
            }

            .job-actions {
                flex-direction: column;
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
            <li><a href="browse_jobs.php" class="active"><i class="fas fa-search"></i> Browse Jobs</a></li>
            <li><a href="my_applications.php"><i class="fas fa-file-alt"></i> My Applications</a></li>
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
                <h2>Browse Jobs</h2>
                <p>Find your perfect career opportunity</p>
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

        <!-- Search Section -->
        <div class="search-section">
            <form class="search-form" method="GET">
                <div class="form-group">
                    <label for="search">Job Title or Keywords</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="e.g. Software Engineer, Marketing..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <select id="location" name="location" class="form-control">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc) ?>" 
                                    <?= $location === $loc ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                    <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <!-- Results Section -->
        <div class="results-section">
            <div class="results-header">
                <div class="results-count">
                    <i class="fas fa-briefcase"></i>
                    <?= count($jobs) ?> Jobs Found
                    <?php if (!empty($search) || !empty($location) || !empty($category)): ?>
                        <span style="font-size: 0.9rem; color: #718096;">
                            <?php if (!empty($search)): ?>
                                for "<?= htmlspecialchars($search) ?>"
                            <?php endif; ?>
                            <?php if (!empty($location)): ?>
                                in <?= htmlspecialchars($location) ?>
                            <?php endif; ?>
                            <?php if (!empty($category)): ?>
                                in <?= htmlspecialchars($category) ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <label for="sort" style="font-weight: 500;">Sort by:</label>
                    <select id="sort" name="sort" class="sort-select" onchange="updateSort(this.value)">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Job Title</option>
                        <option value="company" <?= $sort === 'company' ? 'selected' : '' ?>>Company Name</option>
                    </select>
                </div>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No jobs found</h3>
                    <p>Try adjusting your search criteria or check back later for new opportunities.</p>
                    <a href="browse_jobs.php" class="btn btn-primary">View All Jobs</a>
                </div>
            <?php else: ?>
                <div class="job-grid">
                    <?php foreach ($jobs as $job): ?>
                        <div class="job-card">
                            <div class="job-header">
                                <div>
                                    <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                                    <div class="company-name"><?= htmlspecialchars($job['company_name']) ?></div>
                                    <div class="job-meta">
                                        <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                        <span><i class="fas fa-clock"></i> <?= htmlspecialchars($job['job_type'] ?? 'Full-time') ?></span>
                                        <?php if (!empty($job['salary'])): ?>
                                            <span><i class="fas fa-dollar-sign"></i> <?= htmlspecialchars($job['salary']) ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($job['created_at'])) ?></span>
                                    </div>
                                </div>
                                <?php if ($job['already_applied']): ?>
                                    <span class="badge badge-<?= $job['application_status'] ?>">
                                        <?php 
                                        switch($job['application_status']) {
                                            case 'accepted':
                                                echo '<i class="fas fa-check-circle"></i> Accepted';
                                                break;
                                            case 'rejected':
                                                echo '<i class="fas fa-times-circle"></i> Rejected';
                                                break;
                                            default:
                                                echo '<i class="fas fa-clock"></i> Applied';
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="job-description">
                                <?= htmlspecialchars(substr($job['description'] ?? 'No description available', 0, 200)) ?>
                                <?php if (strlen($job['description'] ?? '') > 200): ?>...<?php endif; ?>
                            </div>

                            <?php if (!empty($job['requirements'])): ?>
                                <div style="margin: 1rem 0;">
                                    <strong style="color: #4a5568;">Requirements:</strong>
                                    <p style="color: #718096; font-size: 0.9rem; margin-top: 0.5rem;">
                                        <?= htmlspecialchars(substr($job['requirements'], 0, 150)) ?>
                                        <?php if (strlen($job['requirements']) > 150): ?>...<?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div class="job-actions">
                                <?php if (!$job['already_applied']): ?>
                                    <form method="POST" action="apply_job.php" style="display: inline;">
                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-paper-plane"></i> Apply Now
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>
                                        <i class="fas fa-check"></i> Already Applied
                                    </button>
                                <?php endif; ?>
                                <a href="view_job.php?id=<?= $job['id'] ?>" class="btn btn-outline">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateSort(value) {
            const url = new URL(window.location);
            url.searchParams.set('sort', value);
            window.location = url;
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
                  'background: #4299e1;'}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
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
        `;
        document.head.appendChild(style);

        // Auto-save search preferences
        document.getElementById('search').addEventListener('input', debounce(function() {
            localStorage.setItem('job_search_query', this.value);
        }, 500));

        document.getElementById('location').addEventListener('change', function() {
            localStorage.setItem('job_search_location', this.value);
        });

        document.getElementById('category').addEventListener('change', function() {
            localStorage.setItem('job_search_category', this.value);
        });

        // Load saved preferences
        window.addEventListener('load', function() {
            const savedQuery = localStorage.getItem('job_search_query');
            const savedLocation = localStorage.getItem('job_search_location');
            const savedCategory = localStorage.getItem('job_search_category');
            
            if (savedQuery && !document.getElementById('search').value) {
                document.getElementById('search').value = savedQuery;
            }
            if (savedLocation && !document.getElementById('location').value) {
                document.getElementById('location').value = savedLocation;
            }
            if (savedCategory && !document.getElementById('category').value) {
                document.getElementById('category').value = savedCategory;
            }
        });

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Show success/error messages
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