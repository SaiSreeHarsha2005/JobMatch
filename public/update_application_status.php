<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['role'] !== 'employer') {
    $_SESSION['error_message'] = 'Unauthorized access. Only employers can update application status.';
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = $_POST['application_id'] ?? '';
    $action = $_POST['action'] ?? '';
    
    // Validate input
    if (empty($application_id) || empty($action)) {
        $_SESSION['error_message'] = 'Missing required fields.';
        header('Location: dashboard.php');
        exit;
    }
    
    // Validate action
    if (!in_array($action, ['accepted', 'rejected'])) {
        $_SESSION['error_message'] = 'Invalid action specified.';
        header('Location: dashboard.php');
        exit;
    }
    
    try {
        // Verify that this application belongs to a job posted by this employer
        $stmt = $pdo->prepare("
            SELECT a.id, a.job_seeker_id, j.employer_id, j.title, u.username, u.email
            FROM applications a
            JOIN jobs j ON a.job_id = j.id
            JOIN users u ON a.job_seeker_id = u.id
            WHERE a.id = ? AND j.employer_id = ?
        ");
        $stmt->execute([$application_id, $user_id]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $_SESSION['error_message'] = 'Application not found or you do not have permission to update it.';
            header('Location: dashboard.php');
            exit;
        }
        
        // Update application status
        $stmt = $pdo->prepare("UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$action, $application_id]);
        
        if ($stmt->rowCount() > 0) {
            // Create notification for the job seeker (if notifications table exists)
            try {
                $notification_title = $action === 'accepted' ? 'Application Accepted!' : 'Application Update';
                $notification_message = $action === 'accepted' 
                    ? "Great news! Your application for '{$application['title']}' has been accepted. The employer may contact you soon."
                    : "Your application for '{$application['title']}' has been reviewed.";
                
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, type, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $application['job_seeker_id'],
                    $notification_title,
                    $notification_message,
                    $action === 'accepted' ? 'success' : 'info'
                ]);
            } catch (PDOException $e) {
                // Notifications table might not exist, continue without error
            }
            
            $_SESSION['success_message'] = "Application " . ($action === 'accepted' ? 'accepted' : 'rejected') . " successfully!";
            
            // Optional: Send email notification to job seeker
            if (function_exists('mail')) {
                $to = $application['email'];
                $subject = "Job Application Update - " . $application['title'];
                $message = $action === 'accepted' 
                    ? "Dear {$application['username']},\n\nCongratulations! Your application for the position '{$application['title']}' has been accepted. The employer may contact you soon for the next steps.\n\nBest regards,\nJobMatch Pro Team"
                    : "Dear {$application['username']},\n\nThank you for your interest in the position '{$application['title']}'. After careful consideration, we have decided to move forward with other candidates at this time.\n\nWe encourage you to continue applying for other opportunities.\n\nBest regards,\nJobMatch Pro Team";
                
                $headers = "From: noreply@jobmatch.com\r\n";
                $headers .= "Reply-To: noreply@jobmatch.com\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                @mail($to, $subject, $message, $headers);
            }
            
        } else {
            $_SESSION['error_message'] = 'Failed to update application status. Please try again.';
        }
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    }
    
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
}

header('Location: dashboard.php');
exit;
?>