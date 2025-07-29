<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Logged Out | JobMatch</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Optional Auto Redirect -->
  <!-- <meta http-equiv="refresh" content="4;url=login.php"> -->
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f5f7fa;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }
    .card {
      background: #fff;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      text-align: center;
      max-width: 400px;
    }
    .card h1 {
      color: #004080;
      margin-bottom: 1rem;
    }
    .card p {
      margin-bottom: 2rem;
      color: #333;
    }
    .btn {
      padding: 0.6rem 1.5rem;
      margin: 0 0.5rem;
      border: none;
      border-radius: 8px;
      text-decoration: none;
      font-size: 1rem;
      color: #fff;
      background-color: #004080;
      transition: background 0.3s;
    }
    .btn:hover {
      background-color: #002f5f;
    }
    .btn-outline {
      background-color: transparent;
      color: #004080;
      border: 2px solid #004080;
    }
    .btn-outline:hover {
      background-color: #004080;
      color: #fff;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>You've been logged out</h1>
    <p>Thank you for using JobMatch. Come back soon!</p>
    <div>
      <a href="login.php" class="btn">Login Again</a>
      <a href="register.php" class="btn btn-outline">Register</a>
    </div>
  </div>
</body>
</html>
