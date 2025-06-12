<?php
$to = $adminEmail; // Admin email address
$subject = "New Student Registration Awaiting Approval";
$headers = "From: no-reply@yourwebsite.com" . "\r\n" .
           "Reply-To: no-reply@yourwebsite.com" . "\r\n" .
           "Content-Type: text/html; charset=UTF-8" . "\r\n";

// The email body
$message = "
<html>
<head>
  <title>New Student Registration Awaiting Approval</title>
</head>
<body>
  <h2>Dear Admin,</h2>
  <p>A new student has registered on the platform and is awaiting your approval.</p>
  <p>Please review and approve or reject the student's registration by logging into the admin panel.</p>
  <p><b>Student Details:</b></p>
  <p>Name: {$studentName}</p>
  <p>Email: {$studentEmail}</p>
  <p>Role: Student</p>
  <p><a href='admin_approve_link_here'>Click here to approve</a></p>
  <p>Thank you for using our platform.</p>
</body>
</html>
";

// Send the email
mail($to, $subject, $message, $headers);
?>
