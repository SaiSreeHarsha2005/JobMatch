<?php
// Secure session setup
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'localhost', // Change this in production
    'secure' => false, // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
session_regenerate_id(true); // Prevent session fixation
require __DIR__ . '/../includes/db_connect.php';
// Redirect if not logged in or not an employer
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
        $employer_id = $_SESSION['user_id'];
        try {
            $stmt = $pdo->prepare("INSERT INTO jobs (employer_id, title, description, location, category, salary, company_name, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$employer_id, $title, $description, $location, $category, $salary, $company_name]);
            $successMessage = "✅ Job posted successfully!";
        } catch (Exception $e) {
            $errorMessage = "❌ Failed to post job. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post a Job | Job Portal</title>
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
        <h2>Post a New Job</h2>

        <?php if ($successMessage): ?>
            <div class="message success"><?php echo $successMessage; ?></div>
        <?php elseif ($errorMessage): ?>
            <div class="message error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="company_name">Company Name</label>
                <input type="text" id="company_name" name="company_name" required>
            </div>
            <div class="form-group">
                <label for="title">Job Title</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="description">Job Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" required>
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" required>
            </div>
            <div class="form-group">
                <label for="salary">Salary</label>
                <input type="number" id="salary" name="salary" step="0.01" required>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button type="submit">Post Job</button>
        </form>
    </div>
</body>
</html>
