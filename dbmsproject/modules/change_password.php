<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include('../config/database.php');

// Fetch the current user's role
$user_id = $_SESSION['user_id'];
$query_role = "SELECT role FROM users WHERE users_id = ?";
$stmt_role = $conn->prepare($query_role);
$stmt_role->bind_param("i", $user_id);
$stmt_role->execute();
$result_role = $stmt_role->get_result();

if ($result_role->num_rows > 0) {
    $row_role = $result_role->fetch_assoc();
    $user_role = $row_role['role']; // e.g. 'admin', 'faculty', 'student'
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = md5($_POST['old_password']);
    $new_password = md5($_POST['new_password']);

    // Verify old password
    $query = "SELECT * FROM users WHERE users_id = ? AND password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $old_password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Old password is correct, update with the new password
        $query_update = "UPDATE users SET password = ? WHERE users_id = ?";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param("si", $new_password, $user_id);

        if ($stmt_update->execute()) {
            $message = "✅ Password changed successfully!";
        } else {
            $message = "❌ Error changing password.";
        }
    } else {
        $message = "❌ Incorrect old password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #121212;
            color: #fff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            width: 400px;
            background: #1e1e1e;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            padding: 40px;
            border-radius: 12px;
            animation: fadeIn 0.6s ease-out forwards;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #00c853;
        }

        .msg {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 16px;
            color: #ccc;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #555;
            border-radius: 8px;
            background: #333;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: #00c853;
            background-color: #444;
        }

        .form-group button {
            width: 100%;
            padding: 12px;
            background-color: #00c853;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.3s, transform 0.3s;
        }

        .form-group button:hover {
            background-color: #00e676;
            transform: translateY(-3px);
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s;
        }

        .back:hover {
            text-decoration: underline;
            color: #0056b3;
        }

        .eye-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            cursor: pointer;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(-50px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Success and error message styles */
        .msg.success {
            color: #4CAF50;
        }

        .msg.error {
            color: #F44336;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Change Password</h2>

    <?php if ($message): ?>
        <p class="msg <?= strpos($message, 'Error') === false ? 'success' : 'error' ?>"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="old_password">Old Password</label>
            <input type="password" name="old_password" id="old_password" required>
        </div>

        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" required>
            <i class="fa fa-eye eye-icon" id="toggleOldPassword"></i>
        </div>

        <div class="form-group">
            <button type="submit">Change Password</button>
        </div>
    </form>

    <a href="../dashboard.php" class="back">← Back to Dashboard</a>
</div>

<script>
    // Toggle password visibility
    const oldPasswordField = document.getElementById('old_password');
    const newPasswordField = document.getElementById('new_password');
    const toggleOldPassword = document.getElementById('toggleOldPassword');
    const toggleNewPassword = document.getElementById('toggleNewPassword');

    toggleOldPassword.addEventListener('click', () => {
        if (oldPasswordField.type === "password") {
            oldPasswordField.type = "text";
        } else {
            oldPasswordField.type = "password";
        }
    });

    toggleNewPassword.addEventListener('click', () => {
        if (newPasswordField.type === "password") {
            newPasswordField.type = "text";
        } else {
            newPasswordField.type = "password";
        }
    });
</script>

</body>
</html>
