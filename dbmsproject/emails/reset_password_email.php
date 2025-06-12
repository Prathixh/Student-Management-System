<?php
$to = $studentEmail; // Student email address
$subject = "Password Reset Request";
$headers = "From: no-reply@yourwebsite.com" . "\r\n" .
           "Reply-To: no-reply@yourwebsite.com" . "\r\n" .
           "Content-Type: text/html; charset=UTF-8" . "\r\n";

// The email body
$message = "
<html>
<head>
  <title>Password Reset Request</title>
</head>
<body>
  <h2>Hello {$studentName},</h2>
  <p>We received a request to reset your password. Please click the link below to reset your password.</p>
  <p><a href='password_reset_link_here'>Click here to reset your password</a></p>
  <p>If you did not request a password reset, please ignore this email.</p>
  <p>Thank you for using our platform.</p>
</body>
</html>
";

// Send the email
mail($to, $subject, $message, $headers);
?>
