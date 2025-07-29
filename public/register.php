<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db_connect.php';

$registrationSuccess = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $role = $_POST['role'] ?? '';

    try {
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$username, $email, $password, $role]);

        if ($isAjax) {
            echo "Registration successful!";
            exit;
        }

        $registrationSuccess = true;
    } catch (PDOException $e) {
        $errorMessage = "Error: " . $e->getMessage();
        if ($isAjax) {
            echo $errorMessage;
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | JobMatch</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #e0f7fa, #e1bee7);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      overflow: hidden;
    }

    .container {
      display: flex;
      width: 90%;
      max-width: 1000px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .image-container {
      background: linear-gradient(120deg, #7e57c2, #26c6da);
      color: white;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 40px;
    }

    .image-content h2 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .image-content p {
      font-size: 16px;
    }

    .form-container {
      flex: 1;
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-wrapper {
      width: 100%;
    }

    .form-header h1 {
      font-size: 24px;
      margin-bottom: 5px;
      color: #333;
    }

    .form-header p {
      margin-bottom: 25px;
      color: #666;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
    }

    input, select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 15px;
      transition: all 0.3s ease;
    }

    input:focus, select:focus {
      outline: none;
      border-color: #7e57c2;
      box-shadow: 0 0 0 3px rgba(126, 87, 194, 0.2);
    }

    .btn {
      width: 100%;
      padding: 12px;
      border: none;
      background: #7e57c2;
      color: white;
      font-size: 16px;
      font-weight: 600;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn:hover {
      background: #5e35b1;
    }

    .loading-spinner {
      border: 3px solid #fff;
      border-top: 3px solid #26c6da;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      animation: spin 0.8s linear infinite;
      margin-right: 8px;
      display: none;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .alert {
      display: none;
      padding: 12px;
      border-radius: 8px;
      font-size: 14px;
      margin-bottom: 20px;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
    }

    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
    }

    .link-text {
      margin-top: 20px;
      text-align: center;
      font-size: 14px;
    }

    .link-text a {
      color: #7e57c2;
      text-decoration: none;
      font-weight: 500;
    }

    .link-text a:hover {
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
        border-radius: 0;
        height: 100vh;
      }

      .image-container {
        display: none;
      }
    }
  </style>
</head>
<body>
<div class="container">
  <div class="image-container">
    <div class="image-content">
      <h2>Welcome to JobMatch</h2>
      <p>The easiest way to connect job seekers with their dream employers</p>
    </div>
  </div>

  <div class="form-container">
    <div class="form-wrapper">
      <div class="form-header">
        <h1>Create your account</h1>
        <p>Get started with your free account today</p>
      </div>

      <?php if ($registrationSuccess): ?>
        <div class="alert alert-success" style="display: block;">
          Registration successful! <a href="login.php">Click here to login</a>.
        </div>
      <?php else: ?>
        <div id="successAlert" class="alert alert-success"></div>
        <div id="errorAlert" class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>

        <form id="registerForm" method="post">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
          </div>
          <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" required>
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
          </div>
          <div class="form-group">
            <label for="role">I am a</label>
            <select id="role" name="role">
              <option value="job_seeker">Job Seeker</option>
              <option value="employer">Employer</option>
            </select>
          </div>
          <button type="submit" class="btn">
            <span id="loadingSpinner" class="loading-spinner"></span>
            <span id="buttonText">Create Account</span>
          </button>
        </form>

        <div class="link-text">
          Already have an account? <a href="login.php">Sign in</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registerForm');
    const successAlert = document.getElementById('successAlert');
    const errorAlert = document.getElementById('errorAlert');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const buttonText = document.getElementById('buttonText');

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        loadingSpinner.style.display = 'inline-block';
        buttonText.textContent = 'Processing...';

        const formData = new FormData(form);
        const headers = new Headers();
        headers.append('X-Requested-With', 'XMLHttpRequest');

        fetch(window.location.href, {
          method: 'POST',
          body: formData,
          headers: headers
        })
        .then(response => response.text())
        .then(data => {
          loadingSpinner.style.display = 'none';
          buttonText.textContent = 'Create Account';

          if (data.includes('successful')) {
            successAlert.style.display = 'block';
            successAlert.textContent = data + " ";
            const link = document.createElement('a');
            link.href = "login.php";
            link.textContent = "Login";
            successAlert.appendChild(link);
            form.style.display = 'none';
          } else {
            errorAlert.style.display = 'block';
            errorAlert.textContent = data;
          }
        })
        .catch(() => {
          loadingSpinner.style.display = 'none';
          buttonText.textContent = 'Create Account';
          errorAlert.style.display = 'block';
          errorAlert.textContent = 'An error occurred. Please try again.';
        });
      });
    }
  });
</script>
</body>
</html>
