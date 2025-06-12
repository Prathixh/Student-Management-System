<?php
// export_faculty.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include __DIR__ . '/../config/database.php';

// Fetch all faculty
$sql = "
  SELECT f.id, u.email, f.name, f.department, f.phone, f.qualification, u.created_at
  FROM faculty f
  JOIN users u ON u.id = f.user_id
  ORDER BY f.id
";
$result = mysqli_query($conn, $sql);

// CSV filename
$filename = "faculty_report_" . date('Ymd_His') . ".csv";

// Send headers to force download
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=$filename");

// Open output stream
$output = fopen('php://output', 'w');

// Output column headers
fputcsv($output, ['ID','Email','Name','Department','Phone','Qualification','Created At']);

// Output rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['email'],
        $row['name'],
        $row['department'],
        $row['phone'],
        $row['qualification'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
?>
