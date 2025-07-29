<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connect.php';

$keyword = $_GET['keyword'] ?? '';
$location = $_GET['location'] ?? '';
$category = $_GET['category'] ?? '';
$min_salary = $_GET['min_salary'] ?? '';
$max_salary = $_GET['max_salary'] ?? '';

$query = "SELECT * FROM jobs WHERE 1=1";
$params = [];

if ($keyword) {
    $query .= " AND title LIKE ?";
    $params[] = "%$keyword%";
}
if ($location) {
    $query .= " AND location LIKE ?";
    $params[] = "%$location%";
}
if ($category) {
    $query .= " AND category LIKE ?";
    $params[] = "%$category%";
}
if ($min_salary !== '') {
    $query .= " AND salary >= ?";
    $params[] = $min_salary;
}
if ($max_salary !== '') {
    $query .= " AND salary <= ?";
    $params[] = $max_salary;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Jobs | JobMatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fa;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .search-container, .results-container {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 700px;
            margin-bottom: 30px;
        }

        .search-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .search-container form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .search-container input,
        .search-container button {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .search-container button {
            grid-column: span 2;
            background-color: #2575fc;
            color: white;
            border: none;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #1e60d1;
        }

        .job {
            border-bottom: 1px solid #eee;
            padding: 16px 0;
        }

        .job:last-child {
            border-bottom: none;
        }

        .job h3 {
            margin: 0 0 10px;
            color: #2575fc;
        }

        .job p {
            margin: 4px 0;
            color: #555;
        }

        .apply-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 14px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .apply-link:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

    <div class="search-container">
        <h2>Search Jobs</h2>
        <form method="GET" action="search_jobs.php">
            <input type="text" name="keyword" placeholder="Keyword" value="<?= htmlspecialchars($keyword) ?>">
            <input type="text" name="location" placeholder="Location" value="<?= htmlspecialchars($location) ?>">
            <input type="text" name="category" placeholder="Category" value="<?= htmlspecialchars($category) ?>">
            <input type="number" name="min_salary" placeholder="Min Salary" value="<?= htmlspecialchars($min_salary) ?>">
            <input type="number" name="max_salary" placeholder="Max Salary" value="<?= htmlspecialchars($max_salary) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="results-container">
        <?php if (count($jobs) > 0): ?>
            <?php foreach ($jobs as $job): ?>
                <div class="job">
                    <h3><?= htmlspecialchars($job['title']) ?></h3>
                    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($job['description'])) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
                    <p><strong>Category:</strong> <?= htmlspecialchars($job['category']) ?></p>
                    <p><strong>Salary:</strong> ₹<?= htmlspecialchars($job['salary']) ?></p>
                    
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No jobs found matching your criteria.</p>
        <?php endif; ?>
    </div>

</body>
</html>
