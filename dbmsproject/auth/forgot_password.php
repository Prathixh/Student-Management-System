<?php
include '../config/database.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND role='student'");

    if (mysqli_num_rows($check) == 1) {
        // In real case, send email
        $message = "A password reset link has been sent to your email (simulated).";
    } else {
        $message = "Email not found or not a student account.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dark-bg">
    <div class="login-container">
        <h2>Reset Password</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your registered email" required><br>
            <button type="submit">Send Reset Link</button>
            <p class="success"><?= $message ?></p>
        </form>
    </div>
</body>
</html>
