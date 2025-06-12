<?php
session_start();
include __DIR__ . '/../config/database.php';

// Only admins can add/view departments
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';

// Handle "Add or Edit Department" form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_name = mysqli_real_escape_string($conn, $_POST['department_name']);
    $department_code = mysqli_real_escape_string($conn, $_POST['department_code']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $hod_name = mysqli_real_escape_string($conn, $_POST['hod_name']);
    $established_year = mysqli_real_escape_string($conn, $_POST['established_year']);
    $contact_email = mysqli_real_escape_string($conn, $_POST['contact_email']);

    // Validate email format
    if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Invalid email format!";
    } else {
        if (isset($_POST['department_id']) && !empty($_POST['department_id'])) {
            // Edit existing department
            $department_id = $_POST['department_id'];
            $sql = "
                UPDATE department
                SET department_name = '$department_name',
                    department_code = '$department_code',
                    description = '$description',
                    hod_name = '$hod_name',
                    established_year = '$established_year',
                    contact_email = '$contact_email'
                WHERE department_id = $department_id
            ";

            if (mysqli_query($conn, $sql)) {
                $message = "✅ Department '{$department_name}' updated successfully!";
            } else {
                $message = "❌ Error: " . mysqli_error($conn);
            }
        } else {
            // Add new department
            $sql = "
                INSERT INTO department (department_name, department_code, description, hod_name, established_year, contact_email)
                VALUES ('$department_name', '$department_code', '$description', '$hod_name', '$established_year', '$contact_email')
            ";

            if (mysqli_query($conn, $sql)) {
                $message = "✅ Department '{$department_name}' added successfully!";
            } else {
                $message = "❌ Error: " . mysqli_error($conn);
            }
        }
    }
}

// Handle department deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $department_id = $_GET['delete'];
    $delete_query = mysqli_query($conn, "DELETE FROM department WHERE department_id = $department_id");
    if ($delete_query) {
        $message = "✅ Department deleted successfully!";
    } else {
        $message = "❌ Error deleting department: " . mysqli_error($conn);
    }
}

// Fetch all departments to display
$list = mysqli_query($conn, "SELECT department_id, department_name, department_code, description, hod_name, established_year, contact_email FROM department ORDER BY department_id DESC");

// Fetch department data for editing
$department_data = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $department_id = $_GET['edit'];
    $department_query = mysqli_query($conn, "SELECT * FROM department WHERE department_id = $department_id");
    if (mysqli_num_rows($department_query) > 0) {
        $department_data = mysqli_fetch_assoc($department_query);
    } else {
        $message = "❌ Department not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Departments</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #00ffcc;
            --secondary: #00c853;
            --dark-bg: #1e1e1e;
            --darker-bg: #121212;
            --light-text: #f5f5f5;
            --dark-text: #333;
            --danger: #ff4444;
            --warning: #ffbb33;
        }
        
        body.dark-bg {
            background-color: var(--darker-bg);
            color: var(--light-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: var(--dark-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        h2 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 28px;
            position: relative;
            padding-bottom: 10px;
            animation: fadeIn 0.5s ease;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }
        
        .form-container {
            background: rgba(30, 30, 30, 0.8);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 255, 204, 0.1);
            animation: slideIn 0.5s ease;
        }
        
        .form-container:hover {
            box-shadow: 0 8px 20px rgba(0, 255, 204, 0.1);
        }
        
        .form-title {
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: #2d2d2d;
            border: 1px solid #444;
            border-radius: 6px;
            color: var(--light-text);
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0, 255, 204, 0.2);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 16px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: var(--dark-text);
        }
        
        .btn-primary:hover {
            background: #00e6b8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 204, 0.3);
            animation: pulse 0.5s infinite alternate;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #ff3333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.3);
        }
        
        .btn-warning {
            background: var(--warning);
            color: var(--dark-text);
        }
        
        .btn-warning:hover {
            background: #ffaa00;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 187, 51, 0.3);
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }
        
        .message.success {
            background: rgba(0, 200, 83, 0.2);
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }
        
        .message.error {
            background: rgba(255, 68, 68, 0.2);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        .table-responsive {
            overflow-x: auto;
            margin-top: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            animation: fadeIn 0.8s ease;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        
        th {
            background: #333;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        tr {
            transition: all 0.3s;
        }
        
        tr:hover {
            background: rgba(0, 255, 204, 0.05);
            transform: scale(1.02);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            color: #00e6b8;
            transform: translateX(-5px);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }
        
        /* Modal for delete confirmation */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background: var(--dark-bg);
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            animation: slideIn 0.3s;
            border: 1px solid var(--primary);
        }
        
        .modal-title {
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 22px;
        }
        
        .modal-body {
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn-sm {
                width: 100%;
            }
        }
    </style>
</head>
<body class="dark-bg">
    <div class="container">
        <h2><i class="fas fa-building"></i> Manage Departments</h2>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="form-title">
                <i class="fas <?= isset($department_data) ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                <?= isset($department_data) ? 'Edit Department' : 'Add New Department' ?>
            </div>
            <form method="POST" action="department.php">
                <?php if (isset($department_data)): ?>
                    <input type="hidden" name="department_id" value="<?= $department_data['department_id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="department_name"><i class="fas fa-signature"></i> Department Name</label>
                    <input type="text" id="department_name" name="department_name" class="form-control" 
                           value="<?= $department_data['department_name'] ?? '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="department_code"><i class="fas fa-code"></i> Department Code</label>
                    <input type="text" id="department_code" name="department_code" class="form-control" 
                           value="<?= $department_data['department_code'] ?? '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description</label>
                    <textarea id="description" name="description" class="form-control"><?= $department_data['description'] ?? '' ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="hod_name"><i class="fas fa-user-tie"></i> Head of Department</label>
                            <input type="text" id="hod_name" name="hod_name" class="form-control" 
                                   value="<?= $department_data['hod_name'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="established_year"><i class="fas fa-calendar-alt"></i> Established Year</label>
                            <input type="number" id="established_year" name="established_year" class="form-control" 
                                   value="<?= $department_data['established_year'] ?? '' ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="contact_email"><i class="fas fa-envelope"></i> Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-control" 
                           value="<?= $department_data['contact_email'] ?? '' ?>" required>
                </div>
                
                <div class="form-group" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas <?= isset($department_data) ? 'fa-save' : 'fa-plus' ?>"></i>
                        <?= isset($department_data) ? 'Update Department' : 'Add Department' ?>
                    </button>
                    
                    <?php if (isset($department_data)): ?>
                        <a href="department.php" class="btn btn-warning" style="margin-left: 10px;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>HOD</th>
                        <th>Year</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($list)): ?>
                        <tr>
                            <td><?= $row['department_id'] ?></td>
                            <td><?= htmlspecialchars($row['department_name']) ?></td>
                            <td><?= htmlspecialchars($row['department_code']) ?></td>
                            <td><?= htmlspecialchars($row['hod_name']) ?></td>
                            <td><?= htmlspecialchars($row['established_year']) ?></td>
                            <td><?= htmlspecialchars($row['contact_email']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="department.php?edit=<?= $row['department_id'] ?>" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button onclick="confirmDelete(<?= $row['department_id'] ?>)" 
                                            class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <a href="../dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
       