<?php
require 'includes/db_connect.php';

if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];
$current_time = date('Y-m-d H:i:s');

// Fetch user with valid (non-expired) token
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > ?");
$stmt->execute([$token, $current_time]);
$user = $stmt->fetch();

if (!$user) {
    die("Token has expired. Please request a new password reset link.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        echo "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        echo "Password must be at least 6 characters long.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password and clear reset token
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);

        echo "Password has been reset successfully.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        h2 {
            color: #333;
        }
        form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            margin-top: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h2>Reset Your Password</h2>
    <form method="POST">
        <label>New Password:</label>
        <input type="password" name="new_password" required>

        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required>

        <button type="submit">Reset Password</button>
    </form>
</body>
</html>
