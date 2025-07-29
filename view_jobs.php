<?php
require 'includes/db_connect.php';

$stmt = $pdo->query("SELECT * FROM jobs");
$jobs = $stmt->fetchAll();

if ($jobs) {
    foreach ($jobs as $job) {
        echo "<div>";
        echo "<h3>" . htmlspecialchars($job['title']) . "</h3>";
        echo "<p>" . nl2br(htmlspecialchars($job['description'])) . "</p>";
        echo "<p><strong>Location:</strong> " . htmlspecialchars($job['location']) . "</p>";
        echo "<p><strong>Category:</strong> " . htmlspecialchars($job['category']) . "</p>";
        echo "<p><strong>Salary:</strong> ₹" . number_format($job['salary'], 2) . "</p>";
        echo "</div><hr>";
    }
} else {
    echo "No job postings available.";
}
?>
