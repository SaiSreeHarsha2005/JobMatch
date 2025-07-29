<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Job Portal</title>
    <link rel="stylesheet" href="/job_portal/public/assets/css/style.css" />
    <script src="/job_portal/public/assets/js/script.js" defer></script>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f9f9f9;
        }
        header {
            background-color: #2c3e50;
            color: white;
            padding: 16px 24px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .container {
            padding: 24px;
        }
    </style>
</head>
<body>
<header>
    <h1>Job Portal</h1>
</header>
<div class="container">
