<?php
session_start();
include __DIR__ . '/../config/database.php';

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';

// Handle Add Student form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $password   = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $course     = mysqli_real_escape_string($conn, $_POST['course']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $year       = mysqli_real_escape_string($conn, $_POST['year']);
    $dob        = mysqli_real_escape_string($conn, $_POST['dob']);
    $address    = mysqli_real_escape_string($conn, $_POST['address']);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);

    $insert_user_sql = "
        INSERT INTO users (email, password, role)
        VALUES ('$email', '$password', 'student')
    ";

    if (mysqli_query($conn, $insert_user_sql)) {
        $users_id = mysqli_insert_id($conn);

        $insert_student_sql = "
            INSERT INTO students (full_name, email, course, department_id, year, dob, address, phone, users_id)
            VALUES ('$full_name', '$email', '$course', '$department', '$year', '$dob', '$address', '$phone', '$users_id')
        ";

        if (mysqli_query($conn, $insert_student_sql)) {
            $update_sql = "UPDATE department SET no_of_students = no_of_students + 1 WHERE department_id = '$department'";
            if (!mysqli_query($conn, $update_sql)) {
                die('Error updating department count: ' . mysqli_error($conn));
            }
            $message = "✅ Student \"$full_name\" added successfully!";
        } else {
            $message = "❌ Error adding student: " . mysqli_error($conn);
        }
    } else {
        $message = "❌ Error inserting user: " . mysqli_error($conn);
    }
}

$list = mysqli_query($conn, "
  SELECT s.student_id, s.users_id, s.full_name, s.email, s.course, s.year, s.dob, s.address, s.phone, d.department_name
  FROM students s
  JOIN department d ON s.department_id = d.department_id
  ORDER BY s.student_id DESC
");

if (!$list) {
    die("Error fetching students: " . mysqli_error($conn));
}

$departments = mysqli_query($conn, "SELECT department_id, department_name FROM department ORDER BY department_name");

if (!$departments) {
    die("Error fetching departments: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Students</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      background: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
    }
    .container {
      max-width: 1000px;
      margin: 40px auto;
      background: #1e1e1e;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.5);
    }
    h2 {
      text-align: center;
      color: #00ffcc;
      margin-bottom: 20px;
    }
    form input, form button, form select {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: none;
      border-radius: 5px;
    }
    form input, form select {
      background: #2b2b2b;
      color: #fff;
    }
    form button {
      background: #00c853;
      color: #000;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s;
    }
    form button:hover {
      background: #00e676;
    }
    .message {
      text-align: center;
      margin: 15px 0;
      font-size: 16px;
      color: #4caf50;
    }
    .action-btn {
      display: inline-block;
      margin: 20px auto;
      padding: 12px 40px;
      background: #00695c;
      color: #fff;
      font-size: 16px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s ease, transform 0.3s ease;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }
    .action-btn:hover {
      background: #00897b;
      transform: scale(1.05);
    }
    .action-btn:active {
      transform: scale(0.98);
    }
    .table-container {
      overflow-x: auto;
      margin-top: 20px;
      display: none;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: #222;
      border-radius: 8px;
      overflow: hidden;
      min-width: 900px;
    }
    th, td {
      padding: 12px;
      border: 1px solid #333;
      text-align: left;
    }
    th {
      background: #333;
    }
    a.back {
      display: block;
      text-align: center;
      margin-top: 20px;
      color: #90caf9;
      text-decoration: none;
    }
    .export-btn {
      text-align: center;
      margin: 20px 0;
    }
  </style>
  <script>
    function toggleTable() {
      const tableContainer = document.querySelector('.table-container');
      const toggleBtn = document.getElementById('toggleBtn');
      if (tableContainer.style.display === 'none' || tableContainer.style.display === '') {
        tableContainer.style.display = 'block';
        toggleBtn.textContent = 'Hide Students';
      } else {
        tableContainer.style.display = 'none';
        toggleBtn.textContent = 'View Students';
      }
    }
  </script>
</head>
<body>
  <div class="container">
    <h2>Manage Students</h2>

    <?php if ($message): ?>
      <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="students.php">
      <input type="text"     name="full_name"  placeholder="Full Name"   required>
      <input type="email"    name="email"      placeholder="Email"       required>
      <input type="password" name="password"   placeholder="Password"    required>
      <input type="text"     name="course"     placeholder="Course"      required>
      
      <select name="department" required>
        <option value="">Select Department</option>
        <?php while ($dept = mysqli_fetch_assoc($departments)): ?>
          <option value="<?= $dept['department_id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
        <?php endwhile; ?>
      </select>

      <input type="text"     name="year"       placeholder="Year (e.g. 1st)" required>
      <input type="date"     name="dob"        placeholder="Date of Birth" required>
      <input type="text"     name="address"    placeholder="Address"     required>
      <input type="text"     name="phone"      placeholder="Phone"       required>
      <button type="submit">Add Student</button>
    </form>

    <div class="export-btn">
      <form method="GET" action="export_students.php">
        <button type="submit">Download Students Report (CSV)</button>
      </form>
    </div>

    <div style="text-align:center;">
      <button class="action-btn" id="toggleBtn" onclick="toggleTable()">View Students</button>
    </div>

    <div class="table-container">
      <table>
        <tr>
          <th>ID</th>
          <th>User ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Course</th>
          <th>Department</th>
          <th>Year</th>
          <th>DOB</th>
          <th>Address</th>
          <th>Phone</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($list)): ?>
        <tr>
          <td><?= $row['student_id'] ?></td>
          <td><?= $row['users_id'] ?></td>
          <td><?= htmlspecialchars($row['full_name']) ?></td>
          <td><?= htmlspecialchars($row['email']) ?></td>
          <td><?= htmlspecialchars($row['course']) ?></td>
          <td><?= htmlspecialchars($row['department_name']) ?></td>
          <td><?= htmlspecialchars($row['year']) ?></td>
          <td><?= htmlspecialchars($row['dob']) ?></td>
          <td><?= htmlspecialchars($row['address']) ?></td>
          <td><?= htmlspecialchars($row['phone']) ?></td>
        </tr>
        <?php endwhile; ?>
      </table>
    </div>

    <a href="../dashboard.php" class="back">← Back to Dashboard</a>
  </div>
</body>
</html>
