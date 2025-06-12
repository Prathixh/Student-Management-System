<?php
session_start();
include '../config/database.php';

// Debug info to help troubleshoot 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate user session (check if user is logged in)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit("Please log in to access this page");
}

$user_id = $_SESSION['user_id'];
$message = '';

// Set default values to avoid null reference errors
$faculty_id = 1; // Default faculty ID
$faculty_dept = 1; // Default department ID

// First check if faculty_id is already in session
if (isset($_SESSION['faculty_id']) && !empty($_SESSION['faculty_id'])) {
    $faculty_id = $_SESSION['faculty_id'];
} 

if (isset($_SESSION['faculty_dept']) && !empty($_SESSION['faculty_dept'])) {
    $faculty_dept = $_SESSION['faculty_dept'];
} else {
    // Try to get from database if not in session
    $faculty_query = mysqli_query($conn, "SELECT faculty_id, department_id FROM faculty WHERE users_id = '$user_id'");
    
    if ($faculty_query && mysqli_num_rows($faculty_query) > 0) {
        $faculty_data = mysqli_fetch_assoc($faculty_query);
        if (isset($faculty_data['faculty_id']) && !empty($faculty_data['faculty_id'])) {
            $faculty_id = $faculty_data['faculty_id'];
            $_SESSION['faculty_id'] = $faculty_id;
        }
        
        if (isset($faculty_data['department_id']) && !empty($faculty_data['department_id'])) {
            $faculty_dept = $faculty_data['department_id'];
            $_SESSION['faculty_dept'] = $faculty_dept;
        }
    } else {
        // If query failed or no results, add a warning message
        $message = "⚠️ Using default faculty values. Faculty record not found for users_id: $user_id";
    }
}

// Always ensure these IDs are stored in session
$_SESSION['faculty_id'] = $faculty_id;
$_SESSION['faculty_dept'] = $faculty_dept;

// Get default attendance date for today
$attendance_date = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status_array = $_POST['status'];
    
    // Check if attendance already exists for this date
    $check_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance 
                                      WHERE faculty_id = '$faculty_id' 
                                      AND department_id = '$faculty_dept' 
                                      AND attendance_date = '$attendance_date'");
    
    $is_update = false;
    if ($check_query) {
        $row = mysqli_fetch_assoc($check_query);
        if ($row['count'] > 0) {
            $is_update = true;
        }
    }
    
    // Track if all operations are successful
    $all_success = true;
    $error_message = "";
    
    // Start transaction if supported
    if (mysqli_query($conn, "START TRANSACTION")) {
        foreach ($status_array as $student_id => $status) {
            // Verify that this student belongs to the faculty's department
            $student_id = mysqli_real_escape_string($conn, $student_id);
            $verify_query = mysqli_query($conn, "SELECT student_id FROM students WHERE student_id = '$student_id' AND department_id = '$faculty_dept'");
            
            if (!$verify_query || mysqli_num_rows($verify_query) === 0) {
                $all_success = false;
                $error_message = "Unauthorized attempt to mark attendance for student from another department";
                break;
            }
            
            $status = mysqli_real_escape_string($conn, $status);
            
            // Get courses_id from students table or use a default value
            $course_query = mysqli_query($conn, "SELECT courses_id FROM students WHERE student_id = '$student_id' LIMIT 1");
            $courses_id = 1; // Default course ID if none found
            
            if ($course_query && mysqli_num_rows($course_query) > 0) {
                $course_data = mysqli_fetch_assoc($course_query);
                if (isset($course_data['courses_id'])) {
                    $courses_id = $course_data['courses_id'];
                }
            }
            
            $insert_sql = "
                INSERT INTO attendance (student_id, faculty_id, department_id, courses_id, attendance_date, status)
                VALUES ('$student_id', '$faculty_id', '$faculty_dept', '$courses_id', '$attendance_date', '$status')
                ON DUPLICATE KEY UPDATE status = '$status'";
                
            if (!mysqli_query($conn, $insert_sql)) {
                $all_success = false;
                $error_message = "Error marking attendance: " . mysqli_error($conn);
                break;
            }
        }
        
        // Commit or rollback based on success
        if ($all_success) {
            mysqli_query($conn, "COMMIT");
            if ($is_update) {
                $message = "✅ Attendance for $attendance_date updated successfully!";
            } else {
                $message = "✅ Attendance for $attendance_date marked successfully!";
            }
        } else {
            mysqli_query($conn, "ROLLBACK");
            $message = "❌ Error: " . $error_message;
        }
    } else {
        // If transactions not supported, try direct execution
        foreach ($status_array as $student_id => $status) {
            $student_id = mysqli_real_escape_string($conn, $student_id);
            $status = mysqli_real_escape_string($conn, $status);
            
            // Get courses_id from students table or use a default value
            $course_query = mysqli_query($conn, "SELECT courses_id FROM students WHERE student_id = '$student_id' LIMIT 1");
            $courses_id = 1; // Default course ID if none found
            
            if ($course_query && mysqli_num_rows($course_query) > 0) {
                $course_data = mysqli_fetch_assoc($course_query);
                if (isset($course_data['courses_id'])) {
                    $courses_id = $course_data['courses_id'];
                }
            }
            
            $verify_query = mysqli_query($conn, "SELECT student_id FROM students WHERE student_id = '$student_id' AND department_id = '$faculty_dept'");
            if (!$verify_query || mysqli_num_rows($verify_query) === 0) {
                $message = "❌ Error: Unauthorized attempt to mark attendance for student from another department";
                $all_success = false;
                break;
            }
            
            $insert_sql = "
                INSERT INTO attendance (student_id, faculty_id, department_id, courses_id, attendance_date, status)
                VALUES ('$student_id', '$faculty_id', '$faculty_dept', '$courses_id', '$attendance_date', '$status')
                ON DUPLICATE KEY UPDATE status = '$status'";
                
            if (!mysqli_query($conn, $insert_sql)) {
                $message = "❌ Error marking attendance: " . mysqli_error($conn);
                $all_success = false;
                break;
            }
        }
        
        if ($all_success) {
            if ($is_update) {
                $message = "✅ Attendance for $attendance_date updated successfully!";
            } else {
                $message = "✅ Attendance for $attendance_date marked successfully!";
            }
        }
    }
}

// Generate and download attendance report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    // Get date range for the report
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01'); // First day of current month
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d'); // Today

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');
    
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Output the column headings
    fputcsv($output, ['Student ID', 'Student Name', 'Total Classes', 'Present', 'Absent', 'Attendance Percentage']);
    
    // Get all students from the faculty's department
    $students_query = mysqli_query($conn, "
        SELECT student_id, full_name
        FROM students
        WHERE department_id = '$faculty_dept'
        ORDER BY full_name
    ");
    
    if ($students_query && mysqli_num_rows($students_query) > 0) {
        while ($student = mysqli_fetch_assoc($students_query)) {
            $student_id = $student['student_id'];
            
            // Count all classes in the date range
            $total_classes_query = mysqli_query($conn, "
                SELECT COUNT(DISTINCT attendance_date) as total_classes
                FROM attendance
                WHERE faculty_id = '$faculty_id'
                AND department_id = '$faculty_dept'
                AND attendance_date BETWEEN '$start_date' AND '$end_date'
            ");
            
            $total_classes = 0;
            if ($total_classes_query && mysqli_num_rows($total_classes_query) > 0) {
                $total_classes_data = mysqli_fetch_assoc($total_classes_query);
                $total_classes = $total_classes_data['total_classes'];
            }
            
            // Count present days for this student
            $present_query = mysqli_query($conn, "
                SELECT COUNT(*) as present_count
                FROM attendance
                WHERE student_id = '$student_id'
                AND faculty_id = '$faculty_id'
                AND department_id = '$faculty_dept'
                AND status = 'Present'
                AND attendance_date BETWEEN '$start_date' AND '$end_date'
            ");
            
            $present_count = 0;
            if ($present_query && mysqli_num_rows($present_query) > 0) {
                $present_data = mysqli_fetch_assoc($present_query);
                $present_count = $present_data['present_count'];
            }
            
            // Count absent days for this student
            $absent_query = mysqli_query($conn, "
                SELECT COUNT(*) as absent_count
                FROM attendance
                WHERE student_id = '$student_id'
                AND faculty_id = '$faculty_id'
                AND department_id = '$faculty_dept'
                AND status = 'Absent'
                AND attendance_date BETWEEN '$start_date' AND '$end_date'
            ");
            
            $absent_count = 0;
            if ($absent_query && mysqli_num_rows($absent_query) > 0) {
                $absent_data = mysqli_fetch_assoc($absent_query);
                $absent_count = $absent_data['absent_count'];
            }
            
            // Calculate attendance percentage
            $attendance_percentage = ($total_classes > 0) ? round(($present_count / $total_classes) * 100, 2) : 0;
            
            // Add a row to the CSV
            fputcsv($output, [
                $student_id,
                $student['full_name'],
                $total_classes,
                $present_count,
                $absent_count,
                $attendance_percentage . '%'
            ]);
        }
    }
    
    // Close the file pointer
    fclose($output);
    exit; // Stop further execution to ensure proper CSV download
}

// Get department name - with error handling
$department_name = 'Your Department';
$dept_query = mysqli_query($conn, "SELECT department_name FROM departments WHERE department_id = '$faculty_dept'");
if ($dept_query && mysqli_num_rows($dept_query) > 0) {
    $dept_data = mysqli_fetch_assoc($dept_query);
    if (isset($dept_data['department_name'])) {
        $department_name = $dept_data['department_name'];
    }
}

// Check if attendance has already been marked for today's date
$attendance_check_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance 
                                             WHERE faculty_id = '$faculty_id' 
                                             AND department_id = '$faculty_dept' 
                                             AND attendance_date = '$attendance_date'");
$is_attendance_marked = false;
if ($attendance_check_query) {
    $row = mysqli_fetch_assoc($attendance_check_query);
    if ($row['count'] > 0) {
        $is_attendance_marked = true;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: #1e1e1e;
            padding: 20px;
            border-radius: 8px;
            color: #fff;
        }
        h2 {
            text-align: center;
            color: #00ffcc;
        }
        .department-info {
            text-align: center;
            color: #00ffcc;
            margin-bottom: 20px;
        }
        form input, form select, form button {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: none;
            border-radius: 4px;
        }
        form input, form select {
            background: #333;
            color: #fff;
        }
        form button {
            background: #00c853;
            color: #000;
            font-weight: bold;
            cursor: pointer;
        }
        .message {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }
        .error {
            background-color: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }
        .warning {
            background-color: rgba(255, 152, 0, 0.2);
            color: #ff9800;
        }
        table {
            width: 100%;
            margin-top: 20px;
            color: white;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #444;
            text-align: left;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #00ffcc;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .date-selection {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .date-selection input {
            flex: 3;
            margin-right: 10px;
        }
        .date-selection button {
            flex: 1;
        }
        .attendance-status {
            font-size: 14px;
            margin-top: 5px;
            font-style: italic;
        }
        .tab-container {
            margin-top: 20px;
        }
        .tab-buttons {
            display: flex;
            margin-bottom: 20px;
        }
        .tab-button {
            flex: 1;
            padding: 10px;
            border: none;
            background: #333;
            color: #fff;
            cursor: pointer;
            border-radius: 4px 4px 0 0;
            margin-right: 2px;
        }
        .tab-button.active {
            background: #00c853;
            color: #000;
        }
        .tab-content {
            display: none;
            padding: 15px;
            background: #2d2d2d;
            border-radius: 0 0 4px 4px;
        }
        .tab-content.active {
            display: block;
        }
        .report-form {
            display: flex;
            flex-direction: column;
        }
        .date-range {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .date-range label {
            min-width: 80px;
            display: flex;
            align-items: center;
        }
        .date-range input {
            flex: 1;
        }
    </style>
    <script>
        // Function to switch tabs
        function openTab(evt, tabName) {
            var i, tabcontent, tabbuttons;
            
            // Hide all tab content
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            
            // Remove "active" class from all tab buttons
            tabbuttons = document.getElementsByClassName("tab-button");
            for (i = 0; i < tabbuttons.length; i++) {
                tabbuttons[i].className = tabbuttons[i].className.replace(" active", "");
            }
            
            // Show the current tab and add "active" class to the button
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
        
        // Function to initialize tabs when page loads
        window.onload = function() {
            // Set the first tab as active by default
            document.getElementsByClassName("tab-button")[0].click();
        };
    </script>
</head>
<body class="dark-bg">

<div class="container">
    <h2>Attendance Management</h2>
    <div class="department-info">Department: <?= htmlspecialchars($department_name) ?></div>

    <?php if (strpos($message, '⚠️') !== false): ?>
        <p class="message warning">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php elseif (strpos($message, '✅') !== false): ?>
        <p class="message success">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php elseif ($message): ?>
        <p class="message error">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-button" onclick="openTab(event, 'mark-attendance')">Mark Attendance</button>
            <button class="tab-button" onclick="openTab(event, 'attendance-report')">Attendance Report</button>
        </div>
        
        <!-- Mark Attendance Tab -->
        <div id="mark-attendance" class="tab-content">
            <!-- Form to pick attendance date -->
            <form method="POST" action="attendance.php" class="date-selection">
                <input type="date" name="attendance_date" required 
                       value="<?php echo htmlspecialchars($attendance_date); ?>" 
                       onchange="this.form.submit()">
            </form>

            <?php
            // Fetch students from the faculty's department
            $students_query = mysqli_query($conn, "
                SELECT s.student_id, s.full_name, 
                      (SELECT status FROM attendance 
                       WHERE student_id = s.student_id 
                       AND attendance_date = '$attendance_date' 
                       AND faculty_id = '$faculty_id') as current_status
                FROM students s
                WHERE s.department_id = '$faculty_dept'
                ORDER BY s.full_name
            ");

            if (!$students_query) {
                echo "<p class='message error'>Error fetching students: " . mysqli_error($conn) . "</p>";
            } else {
                if (mysqli_num_rows($students_query) > 0) {
                    if ($is_attendance_marked) {
                        echo "<div class='attendance-status'>Attendance for this date has already been marked. You can update it below.</div>";
                    }
            ?>

            <!-- Attendance form -->
            <form method="POST" action="attendance.php">
                <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($attendance_date); ?>">

                <table>
                    <tr>
                        <th>Student Name</th>
                        <th>Status</th>
                    </tr>
                    <?php while ($student = mysqli_fetch_assoc($students_query)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td>
                                <select name="status[<?php echo $student['student_id']; ?>]" required>
                                    <?php 
                                    if ($student['current_status'] == 'Present') {
                                        echo '<option value="Present" selected>Present</option>';
                                        echo '<option value="Absent">Absent</option>';
                                    } else if ($student['current_status'] == 'Absent') {
                                        echo '<option value="Present">Present</option>';
                                        echo '<option value="Absent" selected>Absent</option>';
                                    } else {
                                        // Default to Present if no attendance marked yet
                                        echo '<option value="Present" selected>Present</option>';
                                        echo '<option value="Absent">Absent</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>

                <button type="submit">
                    <?php echo $is_attendance_marked ? 'Update Attendance' : 'Submit Attendance'; ?>
                </button>
            </form>

            <?php
                } else {
                    echo "<p class='message warning'>No students found for this department. Please make sure students are registered in the '" . htmlspecialchars($department_name) . "' department.</p>";
                }
            }
            ?>
        </div>
        
        <!-- Attendance Report Tab -->
        <div id="attendance-report" class="tab-content">
            <h3 style="color: #00ffcc; margin-bottom: 15px;">Generate Attendance Report</h3>
            
            <?php
            // Calculate percentage for display in the report tab
            $start_date_default = date('Y-m-01'); // First day of current month
            $end_date_default = date('Y-m-d'); // Today
            
            // Get count of distinct attendance dates
            $total_days_query = mysqli_query($conn, "
                SELECT COUNT(DISTINCT attendance_date) as total_days
                FROM attendance
                WHERE faculty_id = '$faculty_id'
                AND department_id = '$faculty_dept'
                AND attendance_date BETWEEN '$start_date_default' AND '$end_date_default'
            ");
            
            $total_days = 0;
            if ($total_days_query && mysqli_num_rows($total_days_query) > 0) {
                $total_days_data = mysqli_fetch_assoc($total_days_query);
                $total_days = $total_days_data['total_days'];
            }
            ?>
            
            <div class="attendance-status">
                Total attendance days recorded: <?php echo $total_days; ?> 
                (<?php echo date('M d, Y', strtotime($start_date_default)); ?> to 
                <?php echo date('M d, Y', strtotime($end_date_default)); ?>)
            </div>
            
            <form method="POST" action="attendance.php" class="report-form">
                <div class="date-range">
                    <label for="start_date">From:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date_default; ?>" required>
                    
                    <label for="end_date">To:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date_default; ?>" required>
                </div>
                
                <button type="submit" name="generate_report" value="1">Download CSV Report</button>
            </form>
            
            <div style="margin-top: 20px;">
                <h4 style="color: #00ffcc;">Report Preview</h4>
                <table>
                    <tr>
                        <th>Student Name</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Attendance %</th>
                    </tr>
                    <?php
                    // Fetch students with their attendance statistics
                    $preview_query = mysqli_query($conn, "
                        SELECT s.student_id, s.full_name
                        FROM students s
                        WHERE s.department_id = '$faculty_dept'
                        ORDER BY s.full_name
                        LIMIT 10
                    ");
                    
                    if ($preview_query && mysqli_num_rows($preview_query) > 0) {
                        while ($student = mysqli_fetch_assoc($preview_query)) {
                            $student_id = $student['student_id'];
                            
                            // Count present days
                            $present_query = mysqli_query($conn, "
                                SELECT COUNT(*) as present_count
                                FROM attendance
                                WHERE student_id = '$student_id'
                                AND faculty_id = '$faculty_id'
                                AND department_id = '$faculty_dept'
                                AND status = 'Present'
                                AND attendance_date BETWEEN '$start_date_default' AND '$end_date_default'
                            ");
                            
                            $present_count = 0;
                            if ($present_query && mysqli_num_rows($present_query) > 0) {
                                $present_data = mysqli_fetch_assoc($present_query);
                                $present_count = $present_data['present_count'];
                            }
                            
                            // Count absent days
                            $absent_query = mysqli_query($conn, "
                                SELECT COUNT(*) as absent_count
                                FROM attendance
                                WHERE student_id = '$student_id'
                                AND faculty_id = '$faculty_id'
                                AND department_id = '$faculty_dept'
                                AND status = 'Absent'
                                AND attendance_date BETWEEN '$start_date_default' AND '$end_date_default'
                            ");
                            
                            $absent_count = 0;
                            if ($absent_query && mysqli_num_rows($absent_query) > 0) {
                                $absent_data = mysqli_fetch_assoc($absent_query);
                                $absent_count = $absent_data['absent_count'];
                            }
                            
                            // Get total attendance days for this faculty/department (not per student)
                            $total_faculty_days_query = mysqli_query($conn, "
                                SELECT COUNT(DISTINCT attendance_date) as total_days
                                FROM attendance
                                WHERE faculty_id = '$faculty_id'
                                AND department_id = '$faculty_dept'
                                AND attendance_date BETWEEN '$start_date_default' AND '$end_date_default'
                            ");
                            
                            $total_faculty_days = 0;
                            if ($total_faculty_days_query && mysqli_num_rows($total_faculty_days_query) > 0) {
                                $total_faculty_data = mysqli_fetch_assoc($total_faculty_days_query);
                                $total_faculty_days = $total_faculty_data['total_days'];
                            }
                            
                            // Calculate attendance percentage based on total faculty attendance days
                            $attendance_percentage = ($total_faculty_days > 0) ? round(($present_count / $total_faculty_days) * 100, 2) : 0;
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($student['full_name']) . "</td>";
                            echo "<td>" . $present_count . "</td>";
                            echo "<td>" . $absent_count . "</td>";
                            echo "<td>" . $attendance_percentage . "%</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No students found</td></tr>";
                    }
                    ?>
                </table>
                <?php if ($preview_query && mysqli_num_rows($preview_query) >= 10): ?>
                    <div style="text-align: center; margin-top: 10px; font-style: italic;">
                        Showing first 10 students. Download the report to see all.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <a href="../faculty_dashboard.php" class="back-link">Back to Dashboard</a>
</div>

</body>
</html>