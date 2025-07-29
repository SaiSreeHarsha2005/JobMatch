<?php
require 'includes/db_connect.php';

// Use Composer's autoloader instead of manual requires
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    // Check if the email exists in the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate a unique reset token
        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));
        
        // Store the token and its expiration in the database
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);
        
        // Create the reset link
        $reset_link = "http://localhost/job_portal/reset_password.php?token=" . $token;
        
        // Send the reset link via PHPMailer
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'saisreeharshachowdary2005@gmail.com'; // REPLACE with your Gmail address
            $mail->Password   = 'bcqndddjasmagajd';    // REPLACE with your App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            
            // Recipients
            $mail->setFrom('saisreeharshachowdary2005@gmail.com', 'Job Portal');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = '
                <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee;">
                        <h2>Password Reset Request</h2>
                        <p>You requested to reset your password on Job Portal.</p>
                        <p>Please click the button below to reset your password:</p>
                        <p style="text-align: center;">
                            <a href="' . $reset_link . '" 
                               style="background-color: #4CAF50; color: white; padding: 10px 20px; 
                                      text-decoration: none; border-radius: 4px; display: inline-block;">
                                Reset Password
                            </a>
                        </p>
                        <p>Or copy and paste this link into your browser:</p>
                        <p>' . $reset_link . '</p>
                        <p>This link will expire in 1 hour.</p>
                        <p>If you did not request a password reset, please ignore this email.</p>
                    </div>
                </body>
                </html>';
            $mail->AltBody = "Click the following link to reset your password: " . $reset_link;
            
            $mail->send();
            echo "A password reset link has been sent to your email.";
        } catch (Exception $e) {
            echo "Failed to send the password reset email. Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="email"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
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
    <h2>Forgot Password</h2>
    <form method="POST" action="">
        <label>Email:</label>
        <input type="email" name="email" required>
        <button type="submit">Send Reset Link</button>
    </form>
</body>
</html>