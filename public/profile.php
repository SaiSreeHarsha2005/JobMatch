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

// Only job seekers can access profile
if ($user['role'] !== 'job_seeker') {
    header('Location: dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $skills = trim($_POST['skills'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $education = trim($_POST['education'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $linkedin = trim($_POST['linkedin'] ?? '');

        // Validate required fields
        if (empty($full_name) || empty($email)) {
            throw new Exception('Full name and email are required.');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Email address is already in use.');
        }

        // Handle profile picture upload
        $profile_picture = $user['profile_picture'] ?? null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('Invalid file type. Please upload a JPG, PNG, or GIF image.');
            }

            if ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
                throw new Exception('File size too large. Please upload an image smaller than 5MB.');
            }

            $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filepath)) {
                if ($profile_picture && file_exists('../uploads/profiles/' . $profile_picture)) {
                    unlink('../uploads/profiles/' . $profile_picture);
                }
                $profile_picture = $filename;
            }
        }

        // Handle resume upload
        $resume = $user['resume'] ?? null;
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/resumes/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('Invalid resume file type. Please upload a PDF, DOC, or DOCX file.');
            }

            if ($_FILES['resume']['size'] > 10 * 1024 * 1024) {
                throw new Exception('Resume file size too large. Please upload a file smaller than 10MB.');
            }

            $filename = 'resume_' . $user_id . '_' . time() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['resume']['tmp_name'], $filepath)) {
                if ($resume && file_exists('../uploads/resumes/' . $resume)) {
                    unlink('../uploads/resumes/' . $resume);
                }
                $resume = $filename;
            }
        }

        // Update user profile
        $stmt = $pdo->prepare("
            UPDATE users SET 
                full_name = ?, email = ?, phone = ?, location = ?, bio = ?, 
                skills = ?, experience = ?, education = ?, website = ?, 
                linkedin = ?, profile_picture = ?, resume = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $full_name, $email, $phone, $location, $bio,
            $skills, $experience, $education, $website,
            $linkedin, $profile_picture, $resume, $user_id
        ]);

        $_SESSION['success_message'] = 'Profile updated successfully!';
        header('Location: profile.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Refresh user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's application statistics
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_applications,
           SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications
    FROM applications WHERE job_seeker_id = ?
");
$stmt->execute([$user_id]);
$app_stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile | JobMatch Pro</title>
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
            max-width: calc(100vw - 300px);
            overflow-x: hidden;
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

        .profile-container {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            max-width: 100%;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .profile-picture {
            position: relative;
        }

        .profile-picture img,
        .profile-picture-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #4299e1;
        }

        .profile-picture-placeholder {
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .upload-btn {
            position: absolute;
            bottom: -5px;
            right: -5px;
            background: #4299e1;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .profile-info h3 {
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: #4299e1;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #718096;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4a5568;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s;
            resize: vertical;
        }

        .form-control:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }

        textarea.form-control {
            min-height: 80px;
        }

        .file-input-wrapper {
            position: relative;
            display: block;
            cursor: pointer;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            color: #718096;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-input-label:hover {
            border-color: #4299e1;
            color: #4299e1;
        }

        .current-file {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f7fafc;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #4a5568;
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

        .btn-outline {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #4a5568;
        }

        .btn-outline:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e2e8f0;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        .section-divider {
            margin: 2rem 0;
            border-top: 2px solid #e2e8f0;
            position: relative;
        }

        .section-title {
            background: white;
            padding: 0 1rem;
            position: absolute;
            top: -0.6rem;
            left: 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #4a5568;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                max-width: 100vw;
                padding: 1rem;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .profile-stats {
                justify-content: center;
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
            <li><a href="my_applications.php"><i class="fas fa-file-alt"></i> My Applications</a></li>
            <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
      
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="welcome-text">
                <h2>My Profile</h2>
                <p>Manage your professional information</p>
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

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error_message'] ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Profile Content -->
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-picture">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_picture']) ?>" 
                             alt="Profile Picture" id="profileImage">
                    <?php else: ?>
                        <div class="profile-picture-placeholder" id="profilePlaceholder">
                            <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <button type="button" class="upload-btn" onclick="document.getElementById('profilePictureInput').click()">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <div class="profile-info">
                    <h3><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></h3>
                    <p style="color: #718096;"><?= htmlspecialchars($user['location'] ?? 'Location not specified') ?></p>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?= $app_stats['total_applications'] ?? 0 ?></div>
                            <div class="stat-label">Applications</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $app_stats['accepted_applications'] ?? 0 ?></div>
                            <div class="stat-label">Accepted</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Form -->
            <form method="POST" enctype="multipart/form-data">
                <!-- Hidden file input -->
                <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" style="display: none;" onchange="previewImage(this)">
                
                <!-- Basic Information -->
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control" 
                               value="<?= htmlspecialchars($user['location'] ?? '') ?>" 
                               placeholder="e.g. New York, NY">
                    </div>
                </div>

                <div class="form-group">
                    <label for="bio">Professional Bio</label>
                    <textarea id="bio" name="bio" class="form-control" rows="3" 
                              placeholder="Brief description about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <div class="section-divider">
                    <span class="section-title">Professional Details</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="skills">Skills</label>
                        <textarea id="skills" name="skills" class="form-control" rows="2" 
                                  placeholder="JavaScript, Python, Communication..."><?= htmlspecialchars($user['skills'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" class="form-control" 
                               value="<?= htmlspecialchars($user['website'] ?? '') ?>" 
                               placeholder="https://yourwebsite.com">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="experience">Work Experience</label>
                        <textarea id="experience" name="experience" class="form-control" rows="3" 
                                  placeholder="Brief work experience summary..."><?= htmlspecialchars($user['experience'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="education">Education</label>
                        <textarea id="education" name="education" class="form-control" rows="3" 
                                  placeholder="Educational background..."><?= htmlspecialchars($user['education'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label for="linkedin">LinkedIn Profile</label>
                    <input type="url" id="linkedin" name="linkedin" class="form-control" 
                           value="<?= htmlspecialchars($user['linkedin'] ?? '') ?>" 
                           placeholder="https://linkedin.com/in/yourprofile">
                </div>

                <div class="section-divider">
                    <span class="section-title">Resume Upload</span>
                </div>

                <div class="form-group">
                    <label for="resume">Resume/CV</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="resume" name="resume" class="file-input" 
                               accept=".pdf,.doc,.docx" onchange="updateFileName(this, 'resumeFileName')">
                        <label for="resume" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span id="resumeFileName">Choose resume file</span>
                        </label>
                    </div>
                    <?php if (!empty($user['resume'])): ?>
                        <div class="current-file">
                            <i class="fas fa-file-pdf"></i>
                            <span>Current: <?= htmlspecialchars($user['resume']) ?></span>
                            <a href="download_resume.php?file=<?= urlencode($user['resume']) ?>" 
                               style="margin-left: auto; color: #4299e1;">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="resetForm()">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const profileImage = document.getElementById('profileImage');
                    const profilePlaceholder = document.getElementById('profilePlaceholder');
                    
                    if (profileImage) {
                        profileImage.src = e.target.result;
                    } else if (profilePlaceholder) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Profile Picture';
                        img.id = 'profileImage';
                        img.style.cssText = 'width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #4299e1;';
                        profilePlaceholder.parentNode.replaceChild(img, profilePlaceholder);
                    }
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        function updateFileName(input, targetId) {
            const target = document.getElementById(targetId);
            if (input.files.length > 0) {
                target.textContent = input.files[0].name;
            } else {
                target.textContent = 'Choose resume file';
            }
        }

        function resetForm() {
            if (confirm('Are you sure you want to reset all changes?')) {
                location.reload();
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!fullName || !email) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
        });
    </script>
</body>
</html>