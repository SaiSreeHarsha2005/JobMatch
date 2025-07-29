<?php
/**
 * Database Connection Script
 *
 * This script establishes a PDO connection to the MySQL database.
 * It's configured for development (XAMPP/localhost) with common settings.
 *
 * IMPORTANT SECURITY NOTE:
 * For production environments, database credentials (host, dbname, username, password)
 * MUST NOT be hardcoded directly in this file. Use environment variables,
 * a separate configuration file outside the web root, or a secrets management
 * solution to store these sensitive details securely.
 */

$host = 'localhost';
$dbname = 'job_portal_db';
$username = 'root'; // Highly insecure for production, change immediately.
$password = '';     // Highly insecure for production, change immediately.

try {
    // Attempt to establish a PDO database connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            // Set error mode to throw exceptions on errors
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Disable emulated prepared statements for better security against SQL injection
            PDO::ATTR_EMULATE_PREPARES => false,
            // Set default fetch mode to associative arrays (optional, but often useful)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // If connection is successful, you might optionally set a flag or just let execution continue
    // For example: $db_connected = true;

} catch (PDOException $e) {
    // Log the actual connection error details (e.g., to PHP's error log)
    error_log("Database connection error in db_connect.php: " . $e->getMessage());

    // In a production environment, display a generic error message to users
    // and prevent sensitive database error details from being exposed.
    // For development, you might temporarily display $e->getMessage() for debugging.
    die("A database connection error occurred. Please try again later or contact support.");
}

// The $pdo object is now available for use in any script that includes db_connect.php
?>