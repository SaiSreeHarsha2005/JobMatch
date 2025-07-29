<?php
$to = "qasaisriharsha2005@gmail.com"; // Your real email to test
$subject = "Test email from XAMPP";
$message = "Hello! This is a test email sent from XAMPP using Mercury Mail Server.";
$headers = "From: saisreeharshachowdary2005@gmail.com\r\n";

if(mail($to, $subject, $message, $headers)) {
    echo "Test email sent successfully!";
} else {
    echo "Failed to send test email.";
}
?>