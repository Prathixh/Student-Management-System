<?php
session_start();
// Redirect if not logged in or not faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header('Location: auth/login.php');
    exit;
}

include 'includes/header.php'; // Optional header
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f4f4f9;
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
        }
        body.dark-mode {
            background: #121212;
            color: #fff;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            text-align: center;
            padding: 0 20px;
        }
        .dashboard-container h1 {
            font-size: 36px;
            margin-bottom: 30px;
            color: #ccc;
        }
        .dashboard-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
        }
        .card {
            background: #1e1e1e;
            border: 2px solid #333;
            color: #fff;
            padding: 30px 20px;
            border-radius: 12px;
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s, background 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .card:hover {
            background: #272727;
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(0,255,255,0.4);
        }
        .card i {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .card span {
            display: block;
            font-size: 15px;
            color: #bbb;
        }
        .logout {
            background: #ff3b3b;
            border: 2px solid #ff3b3b;
        }
        .logout:hover {
            background: #ff5555;
            box-shadow: 0 4px 20px rgba(255,0,0,0.4);
        }
        @media (max-width: 768px) {
            .dashboard-container h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body class="dark-mode">
    <div class="dashboard-container">
        <h1>Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Faculty'; ?></h1>

        <div class="dashboard-links">
            <a href="modules/attendance.php" class="card">
                <i class="fa fa-check-circle"></i>
                <span>Mark / View Attendance</span>
            </a>
            <a href="modules/update_marks.php" class="card">
                <i class="fa fa-pen"></i>
                <span>Update Student Marks</span>
            </a>
            <a href="modules/analysis.php" class="card">
                <i class="fa fa-chart-line"></i>
                <span>Student Performance Analysis</span>
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

    <script>
        // Optional: You can add a dark mode toggle if needed
        const toggleDarkMode = () => {
            document.body.classList.toggle('dark-mode');
            // Save the dark mode status to session/localStorage if needed
        }
    </script>

</body>
</html>

<?php
include 'includes/footer.php'; // Optional footer
?>
