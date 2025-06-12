<?php
session_start();

// Destroy session and remove session variables
$_SESSION = [];
session_destroy();

// Clear the remember me cookie
if (isset($_COOKIE['remember_login'])) {
    setcookie('remember_login', '', time() - 3600, "/");
}

// Redirect to login
header("Location: login.php");
exit;
?>
