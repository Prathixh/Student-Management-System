<?php
session_start();
include '../config/database.php';

$message = "";

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $password = MD5($_POST['password']); // Replace md5 with bcrypt in production

    $query = "SELECT users_id, role FROM users WHERE email='$email' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $row['users_id'];
        $_SESSION['role'] = $row['role'];

        // Remember me functionality
        if (isset($_POST['remember'])) {
            $cookie_value = base64_encode($row['users_id'] . ':' . $email);
            setcookie('remember_login', $cookie_value, time() + (86400 * 30), "/"); // 30 days
        }

        // Fetch faculty_id if user is faculty
        if ($row['role'] == 'faculty') {
            $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = '{$row['users_id']}'";
            $faculty_result = mysqli_query($conn, $faculty_query);
            if ($faculty_result && mysqli_num_rows($faculty_result) == 1) {
                $faculty_row = mysqli_fetch_assoc($faculty_result);
                $_SESSION['faculty_id'] = $faculty_row['faculty_id'];
            }
        }

        // Fetch student_id if user is student
        if ($row['role'] == 'student') {
            $student_query = "SELECT student_id FROM students WHERE user_id = '{$row['users_id']}'";
            $student_result = mysqli_query($conn, $student_query);
            if ($student_result && mysqli_num_rows($student_result) == 1) {
                $student_row = mysqli_fetch_assoc($student_result);
                $_SESSION['student_id'] = $student_row['student_id'];
            }
        }

        // Redirect based on role
        if ($row['role'] == 'admin') {
            header("Location: ../dashboard.php");
        } elseif ($row['role'] == 'faculty') {
            header("Location: ../faculty_dashboard.php");
        } elseif ($row['role'] == 'student') {
            header("Location: ../student_dashboard.php");
        } else {
            header("Location: ../dashboard.php");
        }
        exit;
    } else {
        $message = "❌ Invalid email or password";
    }
}

// Check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_login'])) {
    $cookie_data = base64_decode($_COOKIE['remember_login']);
    list($user_id, $email) = explode(':', $cookie_data);
    
    $query = "SELECT users_id, role FROM users WHERE email='$email' AND users_id='$user_id'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $row['users_id'];
        $_SESSION['role'] = $row['role'];
        
        // Redirect based on role
        if ($row['role'] == 'admin') {
            header("Location: ../dashboard.php");
        } elseif ($row['role'] == 'faculty') {
            header("Location: ../faculty_dashboard.php");
        } elseif ($row['role'] == 'student') {
            header("Location: ../student_dashboard.php");
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Student Information System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Poppins', sans-serif; 
        }
        body {
            background: radial-gradient(circle at center, #0f0f0f, #000000);
            height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            color: #fff;
            overflow: hidden;
        }
        .login-container {
            background: rgba(30, 30, 30, 0.9);
            padding: 2.5rem;
            border-radius: 15px;
            width: 380px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transform: scale(0.95);
            opacity: 0;
            animation: scale-in 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards 0.2s;
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(0, 255, 198, 0.1),
                rgba(0, 255, 198, 0) 70%
            );
            transform: rotate(30deg);
            z-index: -1;
        }
        @keyframes scale-in {
            0% { transform: scale(0.95); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        h2 {
            text-align: center; 
            margin-bottom: 1.8rem; 
            color: #00ffc6;
            font-weight: 600;
            font-size: 1.8rem;
            letter-spacing: 1px;
            position: relative;
        }
        h2::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: #00ffc6;
            margin: 0.5rem auto 0;
            border-radius: 3px;
        }
        .input-group {
            position: relative;
            margin-bottom: 1.2rem;
        }
        .input-group i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #777;
            transition: 0.3s;
        }
        form input {
            width: 100%; 
            padding: 12px 15px 12px 40px; 
            background: rgba(40, 40, 40, 0.8); 
            border: 1px solid #333; 
            border-radius: 8px;
            color: #fff; 
            transition: 0.3s;
            font-size: 0.95rem;
        }
        form input:focus {
            border-color: #00ffc6; 
            background: rgba(50, 50, 50, 0.9); 
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 255, 198, 0.2);
        }
        form input:focus + i {
            color: #00ffc6;
        }
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            cursor: pointer;
            transition: 0.3s;
        }
        .toggle-password:hover {
            color: #00ffc6;
        }
        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0 1.5rem;
            font-size: 0.9rem;
        }
        .remember-me {
            display: flex;
            align-items: center;
        }
        .remember-me input {
            width: auto;
            margin-right: 8px;
            accent-color: #00ffc6;
        }
        .forgot-password a {
            color: #777;
            text-decoration: none;
            transition: 0.3s;
        }
        .forgot-password a:hover {
            color: #00ffc6;
        }
        form button {
            width: 100%; 
            padding: 12px; 
            background: linear-gradient(135deg, #00ffc6, #00d4aa);
            color: #000; 
            border: none; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.3s;
            font-size: 1rem;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 255, 198, 0.3);
        }
        form button:hover { 
            background: linear-gradient(135deg, #00d4aa, #00b393);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 255, 198, 0.4);
        }
        form button:active {
            transform: translateY(0);
        }
        .message { 
            text-align: center; 
            color: #ff4d4d; 
            margin-bottom: 1.2rem;
            font-size: 0.9rem;
            padding: 8px;
            background: rgba(255, 77, 77, 0.1);
            border-radius: 5px;
            border-left: 3px solid #ff4d4d;
        }
        .footer {
            text-align: center; 
            font-size: 0.8rem; 
            color: #777; 
            margin-top: 1.5rem;
        }
        .floating-bubbles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        .bubble {
            position: absolute;
            bottom: -100px;
            background: rgba(0, 255, 198, 0.05);
            border-radius: 50%;
            animation: float-up 15s infinite ease-in;
        }
        @keyframes float-up {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 0.1; }
            100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="floating-bubbles">
        <?php 
        // Create 15 floating bubbles
        for ($i = 0; $i < 15; $i++) {
            $size = rand(50, 200);
            $left = rand(0, 100);
            $delay = rand(0, 10);
            $duration = rand(10, 20);
            echo '<div class="bubble" style="width: '.$size.'px; height: '.$size.'px; left: '.$left.'%; animation-delay: '.$delay.'s; animation-duration: '.$duration.'s;"></div>';
        }
        ?>
    </div>
    
    <div class="login-container">
        <h2><i class="fas fa-lock" style="margin-right: 10px;"></i>Login</h2>
        <?php if ($message): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required value="<?= isset($_COOKIE['remember_login']) ? explode(':', base64_decode($_COOKIE['remember_login']))[1] : '' ?>">
            </div>
            
            <div class="input-group password-container">
                <i class="fas fa-key"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>
            
            <div class="options">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember" <?= isset($_COOKIE['remember_login']) ? 'checked' : '' ?>>
                    <label for="remember">Remember me</label>
                </div>
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot password?</a>
                </div>
            </div>
            
            <button type="submit">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>Sign In
            </button>
        </form>
        <div class="footer">© <?= date('Y') ?> Thara Instituition | v2.0</div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Add floating animation to login container on hover
        const loginContainer = document.querySelector('.login-container');
        loginContainer.addEventListener('mousemove', (e) => {
            const xAxis = (window.innerWidth / 2 - e.pageX) / 25;
            const yAxis = (window.innerHeight / 2 - e.pageY) / 25;
            loginContainer.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });

        loginContainer.addEventListener('mouseenter', () => {
            loginContainer.style.transition = 'all 0.1s ease';
        });

        loginContainer.addEventListener('mouseleave', () => {
            loginContainer.style.transform = 'rotateY(0deg) rotateX(0deg)';
            loginContainer.style.transition = 'all 0.5s ease';
        });
    </script>
</body>
</html>