<?php
// Secure session setup
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
session_regenerate_id(true);

require __DIR__ . '/../includes/db_connect.php';

// Redirect if not logged in or not employer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header('Location: login.php');
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$successMessage = "";
$errorMessage = "";

// Get job ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Job ID.");
}
$job_id = intval($_GET['id']);
$employer_id = $_SESSION['user_id'];

// Fetch job details to pre-fill form
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->execute([$job_id, $employer_id]);
$job = $stmt->fetch();

if (!$job) {
    die("Job not found or you do not have permission to edit it.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errorMessage = "Invalid CSRF token.";
    } else {
        $company_name = htmlspecialchars(trim($_POST['company_name']));
        $title = htmlspecialchars(trim($_POST['title']));
        $description = htmlspecialchars(trim($_POST['description']));
        $location = htmlspecialchars(trim($_POST['location']));
        $category = htmlspecialchars(trim($_POST['category']));
        $salary = floatval($_POST['salary']);

        try {
            $stmt = $pdo->prepare("
                UPDATE jobs 
                SET company_name = ?, title = ?, description = ?, location = ?, category = ?, salary = ?, updated_at = NOW()
                WHERE id = ? AND employer_id = ?
            ");
            $stmt->execute([$company_name, $title, $description, $location, $category, $salary, $job_id, $employer_id]);
            $successMessage = "✅ Job updated successfully!";
            
            // Refresh job data
            $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
            $stmt->execute([$job_id, $employer_id]);
            $job = $stmt->fetch();
        } catch (Exception $e) {
            $errorMessage = "❌ Failed to update job. Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Job | Job Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #667eea, #764ba2);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .form-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        .form-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-weight: 600;
            color: #333;
            display: block;
            margin-bottom: 6px;
        }
        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        textarea {
            resize: vertical;
            height: 100px;
        }
        button {
            background-color: #667eea;
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background-color: #5a67d8;
        }
        .message {
            margin-top: 10px;
            text-align: center;
            font-weight: 600;
        }
        .message.success {
            color: green;
        }
        .message.error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Job</h2>

        <?php if ($successMessage): ?>
            <div class="message success"><?php echo $successMessage; ?></div>
        <?php elseif ($errorMessage): ?>
            <div class="message error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="company_name">Company Name</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($job['company_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="title">Job Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Job Description</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($job['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($job['location']); ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($job['category']); ?>" required>
            </div>

            <div class="form-group">
                <label for="salary">Salary</label>
                <input type="number" id="salary" name="salary" value="<?php echo htmlspecialchars($job['salary']); ?>" step="0.01" required>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <button type="submit">Update Job</button>
        </form>
    </div>
</body>
</html>
