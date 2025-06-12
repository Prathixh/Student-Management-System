<?php
$host = "localhost";
$user = "root";  // Change this if needed
$password = "";  // Change this if needed
$dbname = "dbmsnew";

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8 for proper encoding
$conn->set_charset("utf8");

?>
