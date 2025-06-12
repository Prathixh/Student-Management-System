<?php
include __DIR__ . '/../config/database.php';

// Only admins can access
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch all student data
$query = "
    SELECT s.student_id, s.users_id, s.full_name, s.email, s.course, s.year, s.dob, s.address, s.phone, d.department_name
    FROM students s
    JOIN department d ON s.department_id = d.department_id
    ORDER BY s.student_id DESC
";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Error fetching student data: " . mysqli_error($conn));
}

// Set headers to force download of the CSV file
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=students_report.csv');

// Open PHP output stream
$output = fopen('php://output', 'w');

// Write the column headers
$headers = ['Student ID', 'User ID', 'Full Name', 'Email', 'Course', 'Department', 'Year', 'DOB', 'Address', 'Phone'];
fputcsv($output, $headers);

// Write each row of student data to the CSV
while ($row = mysqli_fetch_assoc($result)) {
    $student_data = [
        $row['student_id'],
        $row['users_id'],
        $row['full_name'],
        $row['email'],
        $row['course'],
        $row['department_name'],
        $row['year'],
        $row['dob'],
        $row['address'],
        $row['phone']
    ];
    fputcsv($output, $student_data);
}

// Close the output stream
fclose($output);
exit;
?>
