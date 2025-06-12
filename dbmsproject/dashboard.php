<?php
session_start();

// Redirect to login if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: auth/login.php');
    exit;
}

// If header.php prints "Your Project" or "Home", remove them inside that file too if needed
include 'includes/header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: #f4f4f9;
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
        }

        body.dark-bg {
            background: #121212;
            color: #fff;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 30px auto;
            text-align: center;
        }

        .dashboard-container h1 {
            font-size: 36px;
            margin-bottom: 30px;
            color: #333;
        }

        .dashboard-links {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .card {
            background: #1e1e1e;
            border: 2px solid #333;
            color: #fff;
            padding: 25px 30px;
            text-decoration: none;
            font-size: 18px;
            border-radius: 10px;
            transition: transform 0.3s, background 0.3s, box-shadow 0.3s;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .card:hover {
            background: #272727;
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(0, 255, 255, 0.4);
        }

        .card span {
            display: block;
            margin-top: 10px;
            font-size: 14px;
            color: #ccc;
        }

        .logout {
            background: #ff3b3b;
            color: #fff;
        }

        .logout:hover {
            background: #ff5555;
            box-shadow: 0 4px 20px rgba(255, 0, 0, 0.4);
        }

        @media screen and (max-width: 768px) {
            .dashboard-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body class="dark-bg">
    <div class="dashboard-container">
        <h1>Welcome, Admin</h1>

        <div class="dashboard-links">
            <a href="modules/faculty.php" class="card">
                <i class="fa fa-user-tie" aria-hidden="true"></i>
                <span>Manage Faculty</span>
            </a>
            <a href="modules/students.php" class="card">
                <i class="fa fa-users" aria-hidden="true"></i>
                <span>Manage Students</span>
            </a>
            <a href="modules/courses.php" class="card">
                <i class="fa fa-book" aria-hidden="true"></i>
                <span>Manage Courses</span>
            </a>
            <a href="modules/department.php" class="card">
                <i class="fa fa-building" aria-hidden="true"></i>
                <span>Manage Department</span>
            </a>
            <a href="modules/change_password.php" class="card">
                <i class="fa fa-lock" aria-hidden="true"></i>
                <span>Change Password</span>
            </a>
            <a href="auth/logout.php" class="card logout">
                <i class="fa fa-sign-out-alt" aria-hidden="true"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</body>
</html>

<?php
// Remove unwanted content in includes/footer.php if it shows "Your Project" or "Home"
include 'includes/footer.php'; 
?>
