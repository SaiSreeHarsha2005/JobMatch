<?php
require_once '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    die("❌ Job ID not provided.");
}

$job_id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    die("❌ Job not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($job['title']); ?> - Job Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
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
        .details-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 25px;
        }
        .detail-group {
            margin-bottom: 20px;
        }
        .detail-label {
            font-weight: 600;
            color: #444;
        }
        .detail-value {
            margin-top: 5px;
            font-size: 16px;
            color: #222;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="details-container">
        <h1><?php echo htmlspecialchars($job['title']); ?></h1>
        <div class="subtitle"><?php echo htmlspecialchars($job['company_name']); ?></div>

        <div class="detail-group">
            <div class="detail-label">Job Description</div>
            <div class="detail-value"><?php echo nl2br(htmlspecialchars($job['description'])); ?></div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Location</div>
            <div class="detail-value"><?php echo htmlspecialchars($job['location']); ?></div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Category</div>
            <div class="detail-value"><?php echo htmlspecialchars($job['category']); ?></div>
        </div>

        <div class="detail-group">
            <div class="detail-label">Salary</div>
            <div class="detail-value">₹<?php echo number_format($job['salary'], 2); ?></div>
        </div>
    </div>
</body>
</html>
