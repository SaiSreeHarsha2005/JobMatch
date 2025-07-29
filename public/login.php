<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db_connect.php';

$errorMessage = '';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header("Location: dashboard.php");
        exit;
    } else {
        $errorMessage = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | JobMatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(120deg, #e0f7fa, #e1bee7);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-box {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 420px;
            transition: transform 0.3s ease;
        }

        .login-box:hover {
            transform: translateY(-4px);
        }
        h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            color: #333;
            text-align: center;
        }
        p {
            text-align: center;
            color: #777;
            margin-bottom: 2rem;
            margin-top: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #333;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 16px;
            transition: border 0.3s, box-shadow 0.3s;
        }
        input:focus {
            border-color: #6200ea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(98, 0, 234, 0.2);
        }
        .btn {
            display: block;
            width: 100%;
            background: #6200ea;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background: #4500b5;
        }
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        .forgot-password a {
            text-decoration: none;
            color: #6200ea;
            font-size: 14px;
            transition: color 0.3s;
        }
        .forgot-password a:hover {
            color: #3700b3;
        }
        .error-message {
            background: #ffcdd2;
            color: #b71c1c;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 14px;
        }
        @media (max-width: 500px) {
            .login-box {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Welcome Back</h2>
        <p>Login to your JobMatch account</p>
        <?php if (!empty($errorMessage)) : ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
            <div class="forgot-password">
                <a href="../forgot_password.php">Forgot Password?</a>
            </div>
        </form>
    </div>
</body>
</html>
