<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: auth/login.php");
    exit();
}

include 'includes/header.php'; // optional
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
            color: #ccc;
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
        <h1>Welcome, Student</h1>

        <div class="dashboard-links">
            <a href="modules/attendance.php" class="card">
                <i class="fa fa-check-circle"></i>
                <span>View Attendance</span>
            </a>
            <a href="modules/results.php" class="card">
                <i class="fa fa-clipboard-list"></i>
                <span>View Results</span>
            </a>
            <a href="modules/analysis.php" class="card">
                <i class="fa fa-chart-line"></i>
                <span>Performance Analysis</span>
            </a>
            <a href="modules/change_password.php" class="card">
                <i class="fa fa-lock"></i>
                <span>Change Password</span>
            </a>
            <a href="auth/logout.php" class="card logout">
                <i class="fa fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</body>
</html>

<?php
include 'includes/footer.php'; // optional
?>
