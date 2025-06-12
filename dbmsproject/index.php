<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Info System</title>
    
    <!-- Link to external CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            background:rgb(9, 9, 9); /* Dark background for a professional look */
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            animation: fadeIn 1s ease-out;
        }

        h2 {
            font-size: 36px;
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: 1px;
            color: #fff;
            font-weight: 600;
            animation: slideIn 1s ease-out;
        }

        .login-options {
            display: flex;
            gap: 20px;
            animation: fadeInOptions 1s ease-out;
        }

        .login-options a {
            padding: 15px 30px;
            background: #007bff; /* Professional blue button */
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 500;
            transition: transform 0.3s, background 0.3s;
            display: inline-block;
        }

        .login-options a:hover {
            background: #0056b3; /* Slightly darker blue on hover */
            transform: translateY(-5px); /* Subtle lift effect */
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        @keyframes slideIn {
            0% { transform: translateY(50px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        @keyframes fadeInOptions {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <h2>THARA INSTITUITIONS</h2>
    <div class="login-options">
        <a href="auth/login.php?type=student">Student Login</a>
        <a href="auth/login.php?type=faculty">Faculty Login</a>
        <a href="auth/login.php?type=admin">Admin Login</a>
    </div>
</body>
</html>
