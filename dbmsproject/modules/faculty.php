<?php
session_start();
include __DIR__ . '/../config/database.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize user input
    $faculty_name  = mysqli_real_escape_string($conn, $_POST['name']);
    $email         = mysqli_real_escape_string($conn, $_POST['email']);
    $password      = md5($_POST['password']);
    $qualification = mysqli_real_escape_string($conn, $_POST['qualification']);
    $phone         = mysqli_real_escape_string($conn, $_POST['phone']);
    $dept_id       = mysqli_real_escape_string($conn, $_POST['dept_id']);

    // Create a new user (faculty)
    $query_user = "
        INSERT INTO users (email, password, role, approved)
        VALUES ('$email', '$password', 'faculty', 1)
    ";

    if (mysqli_query($conn, $query_user)) {
        $user_id = mysqli_insert_id($conn); // Get the last inserted user_id

        // Insert faculty details with the users_id
        $query_faculty = "
            INSERT INTO faculty (users_id, faculty_name, phone, qualification, department_id, email)
            VALUES ($user_id, '$faculty_name', '$phone', '$qualification', '$dept_id', '$email')
        ";

        if (mysqli_query($conn, $query_faculty)) {
            $message = "✅ Faculty “{$faculty_name}” added successfully!";
        } else {
            $message = "❌ Error adding faculty details: " . mysqli_error($conn);
        }
    } else {
        $message = "❌ Error creating user: " . mysqli_error($conn);
    }
}

// Fetch faculty list
$result = mysqli_query($conn, // Fetching faculty with user details including faculty_id
$query = "
    SELECT f.faculty_id, u.users_id, u.email, f.faculty_name, f.phone, f.qualification, d.department_name, u.created_at
    FROM faculty f
    JOIN users u ON u.users_id = f.users_id
    JOIN department d ON d.department_id = f.department_id
    ORDER BY f.faculty_id DESC
");

if (!$result) {
    die("❌ Query Error: " . mysqli_error($conn));
}

// Fetch departments for dropdown
$departments = mysqli_query($conn, "
    SELECT department_id, department_name FROM department ORDER BY department_name
");

if (!$departments) {
    die("❌ Dept Query Error: " . mysqli_error($conn));
}

// CSV download functionality
if (isset($_GET['download_csv']) && $_GET['download_csv'] == 'true') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="faculty_data.csv"');
    $output = fopen('php://output', 'w');

    fputcsv($output, ['Faculty ID', 'Email', 'Name', 'Department', 'Phone', 'Qualification', 'Joined At']);

    $csv_result = mysqli_query($conn, "
        SELECT f.faculty_id, u.email, f.faculty_name, d.department_name, f.phone, f.qualification, u.created_at
        FROM faculty f
        JOIN users u ON u.users_id = f.users_id
        JOIN department d ON d.department_id = f.department_id
        ORDER BY f.faculty_id DESC
    ");

    if (!$csv_result) {
        die("❌ CSV Query Error: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($csv_result)) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Faculty</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body.dark-bg { background:rgb(18, 18, 18); color: #fff; font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        h1, h2 { text-align: center; color: #00ffcc; }
        .msg { text-align: center; color: #4caf50; margin-bottom: 20px; }
        form { background: #1e1e1e; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        form input, form button, form select { width: 100%; padding: 10px; margin: 8px 0; border: none; border-radius: 4px; }
        form input { background: #333; color: #fff; }
        form button { background: #00c853; color: #000; font-weight: bold; cursor: pointer; transition: background 0.3s ease; }
        form button:hover { background: #00e676; }
        table { width: 100%; border-collapse: collapse; background: #1e1e1e; margin-top: 20px; opacity: 0; transform: translateY(-20px); transition: opacity 0.5s ease, transform 0.5s ease; pointer-events: none; }
        table.visible { opacity: 1; transform: translateY(0); pointer-events: auto; }
        th, td { padding: 12px; border: 1px solid #444; text-align: center; }
        th { background: #333; }
        a.btn { display: inline-block; padding: 6px 12px; background: rgb(239, 58, 22); color: #fff; text-decoration: none; border-radius: 4px; transition: background 0.3s ease, transform 0.3s ease; }
        a.btn:hover { background: rgb(247, 42, 6); transform: scale(1.1); }
        .download-btn { display: inline-block; padding: 10px 360px; background-color: rgb(17, 170, 19); color: white; font-size: 16px; border-radius: 5px; text-decoration: none; transition: transform 0.3s ease, background-color 0.3s ease; cursor: pointer; }
        .download-btn:hover { background-color: rgb(27, 189, 46); transform: scale(1.05); }
        #viewFacultyBtn {
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
        #viewFacultyBtn:hover {
            background: #00897b;
            transform: scale(1.05);
        }
        #viewFacultyBtn:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body class="dark-bg">
    <div class="container">
        <h1>Manage Faculty</h1>
        <?php if ($message): ?>
            <p class="msg"><?= $message ?></p>
        <?php endif; ?>

        <form method="POST" action="faculty.php">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="qualification" placeholder="Qualification" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <select name="dept_id" required>
                <option value="">Select Department</option>
                <?php while ($dept = mysqli_fetch_assoc($departments)): ?>
                    <option value="<?= $dept['department_id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit">Add Faculty</button>
        </form>

        <div style="text-align: center; margin-top: 10px;">
            <a href="faculty.php?download_csv=true" class="download-btn">Download CSV</a>
        </div>

        <div style="text-align: center;">
            <button id="viewFacultyBtn" onclick="toggleFacultyTable()">View Faculty</button>
        </div>

        <table id="facultyTable">
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Name</th>
                <th>Department</th>
                <th>Phone</th>
                <th>Qualification</th>
                <th>Joined At</th>
                <th>Action</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['faculty_id'] ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['faculty_name']) ?></td>
                <td><?= htmlspecialchars($row['department_name']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['qualification']) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td>
                    <a class="btn" href="faculty.php?delete_id=<?= $row['faculty_id'] ?>"
                        onclick="return confirm('Delete this faculty?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <a href="../dashboard.php" class="back">← Back to Dashboard</a>
    </div>

    <script>
        function toggleFacultyTable() {
            const table = document.getElementById('facultyTable');
            const button = document.getElementById('viewFacultyBtn');

            table.classList.toggle('visible');

            if (table.classList.contains('visible')) {
                button.textContent = 'Hide Faculty';
            } else {
                button.textContent = 'View Faculty';
            }
        }
    </script>
</body>
</html>
