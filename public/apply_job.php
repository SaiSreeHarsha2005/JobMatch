<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require '../includes/db_connect.php';

$message = '';
$job_id = $_GET['job_id'] ?? $_POST['job_id'] ?? null;

// Validate job_id
if (!$job_id || !is_numeric($job_id)) {
    die("Job not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['full_name'], $_POST['email'], $_FILES['resume'])) {
        $user_id = $_SESSION['user_id'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $resume = $_FILES['resume'];

        // Get employer_id from the job
        $stmt = $pdo->prepare("SELECT employer_id FROM jobs WHERE id = ?");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch();

        if (!$job) {
            die("Job not found.");
        }

        $employer_id = $job['employer_id'];

        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $maxFileSize = 2 * 1024 * 1024; // 2MB

        if (!empty($resume['tmp_name'])) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($resume['tmp_name']);

            if (!in_array($mimeType, $allowedMimeTypes)) {
                $message = 'Invalid file type.';
            } elseif ($resume['size'] > $maxFileSize) {
                $message = 'File is too large.';
            } else {
                $extension = pathinfo($resume['name'], PATHINFO_EXTENSION);
                $randomName = bin2hex(random_bytes(12)) . '.' . $extension;
                $uploadDir = __DIR__ . '/../uploads/';
                $resumePath = $uploadDir . $randomName;

                if (move_uploaded_file($resume['tmp_name'], $resumePath)) {
                    $stmt = $pdo->prepare("INSERT INTO applications 
                        (job_id, employer_id, job_seeker_id, full_name, email, resume, status, application_date, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Applied', NOW(), NOW())");
                    $stmt->execute([$job_id, $employer_id, $user_id, $full_name, $email, $randomName]);
                    $message = 'Application submitted successfully!';
                } else {
                    $message = 'Failed to upload file.';
                }
            }
        } else {
            $message = 'Please upload a resume file.';
        }
    } else {
        $message = 'All fields are required.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for Job | JobMatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .form-card {
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        .form-card h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
            color: #333;
        }
        input[type="text"],
        input[type="email"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        input[type="file"] {
            background-color: #f9f9f9;
        }
        button {
            background-color: #2575fc;
            color: #fff;
            padding: 14px;
            font-size: 16px;
            width: 100%;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #1e60d1;
        }
        .message {
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
            color: green;
        }
    </style>
</head>
<body>
    <div class="form-card">
        <h2>Apply for Job</h2>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="apply_job.php?job_id=<?php echo htmlspecialchars($job_id); ?>" enctype="multipart/form-data">
            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="resume">Upload Resume (.pdf, .doc, .docx, max 2MB):</label>
                <input type="file" name="resume" accept=".pdf,.doc,.docx" required>
            </div>

            <button type="submit">Submit Application</button>
        </form>
    </div>
</body>
</html>
