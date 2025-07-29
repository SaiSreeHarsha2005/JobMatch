<?php
session_start();

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token.');
    }

    // Include database connection
    require_once '../includes/db_connect.php';

    // Sanitize and validate inputs
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $location = htmlspecialchars(trim($_POST['location']));
    $category = htmlspecialchars(trim($_POST['category']));
    $salary = floatval($_POST['salary']);

    // Assuming you have a logged-in user with an ID stored in the session
    $employer_id = $_SESSION['user_id'] ?? 1; // Replace with actual user ID retrieval

    try {
        // Prepare SQL statement
        $stmt = $pdo->prepare("INSERT INTO jobs (employer_id, title, description, location, category, salary) VALUES (?, ?, ?, ?, ?, ?)");
        // Execute the statement with user inputs
        $stmt->execute([$employer_id, $title, $description, $location, $category, $salary]);

        echo "Job posted successfully!";
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
    }
} else {
    // Redirect if the request method is not POST
    header('Location: post_job.php');
    exit;
}
?>
