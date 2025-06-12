<?php
session_start();
include '../config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit("Please log in to access this page");
}

$user_id = $_SESSION['user_id'];

// Check if the marks table has the correct structure
function checkMarksTableStructure($conn) {
    // Try to get the structure of the marks table
    $result = mysqli_query($conn, "DESCRIBE marks");
    
    if (!$result) {
        // Table doesn't exist or other error
        return "Marks table error: " . mysqli_error($conn);
    }
    
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }
    
    // Check if required fields exist
    $required_fields = ['id', 'student_id', 'courses_id', 'faculty_id', 'marks_obtained', 'created_at'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!in_array($field, $columns)) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        return "Missing fields in marks table: " . implode(', ', $missing_fields);
    }
    
    return null; // No errors
}

// Run structure check during initialization
$table_error = checkMarksTableStructure($conn);
if ($table_error) {
    // Display as error message
    $message = "❌ Database structure issue: " . $table_error;
}
$faculty_id = $_SESSION['faculty_id'] ?? null;
$faculty_dept = $_SESSION['faculty_dept'] ?? null;

if (!$faculty_id || !$faculty_dept) {
    $faculty_query = mysqli_query($conn, "SELECT faculty_id, department_id FROM faculty WHERE users_id = '$user_id'");
    if ($faculty_query && mysqli_num_rows($faculty_query) > 0) {
        $faculty_data = mysqli_fetch_assoc($faculty_query);
        $faculty_id = $faculty_data['faculty_id'];
        $faculty_dept = $faculty_data['department_id'];
        $_SESSION['faculty_id'] = $faculty_id;
        $_SESSION['faculty_dept'] = $faculty_dept;
    } else {
        exit("Faculty information not found.");
    }
}

// Get department name - FIXED: Moved up and executing query regardless of previous session value
$department_name = 'Handling Department';
$dept_query = mysqli_query($conn, "SELECT department_name FROM departments WHERE department_id = '$faculty_dept'");
if ($dept_query && mysqli_num_rows($dept_query) > 0) {
    $dept_data = mysqli_fetch_assoc($dept_query);
    $department_name = $dept_data['department_name'];
    // Store in session to avoid future queries
    $_SESSION['department_name'] = $department_name;
} else {
    // Try to get from session if query failed
    $department_name = $_SESSION['department_name'] ?? 'Handling Department';
}

// Get courses taught by the faculty from the courses table
$courses_query = mysqli_query($conn, "
    SELECT courses_id, course_name 
    FROM courses 
    WHERE faculty_id = '$faculty_id'
");

if (!$courses_query || mysqli_num_rows($courses_query) === 0) {
    // If no courses assigned to faculty, try finding courses from the faculty's department
    $courses_query = mysqli_query($conn, "
        SELECT courses_id, course_name 
        FROM courses 
        WHERE department_id = '$faculty_dept'
    ");
}

if (!$courses_query || mysqli_num_rows($courses_query) === 0) {
    $no_courses = true;
} else {
    $no_courses = false;
    $courses = [];
    while ($course = mysqli_fetch_assoc($courses_query)) {
        $courses[$course['courses_id']] = $course['course_name'];
    }
}

$message = '';
$selected_course = null;
$report_data = null;
$pass_percentage = 0;
$fail_percentage = 0;
$average_score = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['course_id'])) {
        $selected_course = mysqli_real_escape_string($conn, $_POST['course_id']);
    }
    
    // Handle marks update
    if (isset($_POST['marks']) && $selected_course) {
        $marks_array = $_POST['marks'];
        $all_success = true;
        
        foreach ($marks_array as $student_id => $marks_obtained) {
            $student_id = mysqli_real_escape_string($conn, $student_id);
            $marks_obtained = mysqli_real_escape_string($conn, $marks_obtained);

            // Verify student is in this department
            $verify_query = mysqli_query($conn, "
                SELECT student_id 
                FROM students 
                WHERE student_id = '$student_id' 
                AND department_id = '$faculty_dept'
            ");

            if (!$verify_query || mysqli_num_rows($verify_query) === 0) {
                $all_success = false;
                $message = "❌ Error: Student not found or not in your department (student_id: $student_id)";
                break;
            }

            // Check if record exists
            $check_query = "
                SELECT * FROM marks 
                WHERE student_id = '$student_id' 
                AND courses_id = '$selected_course' 
                AND faculty_id = '$faculty_id'
            ";
            
            $check_exists = mysqli_query($conn, $check_query);
            
            // Debug the query if it fails
            if (!$check_exists) {
                $all_success = false;
                $message = "❌ Error checking record: " . mysqli_error($conn) . " (Query: $check_query)";
                break;
            }
            
            if (mysqli_num_rows($check_exists) > 0) {
                // Update existing record
                $update_sql = "
                    UPDATE marks 
                    SET marks_obtained = '$marks_obtained', created_at = NOW() 
                    WHERE student_id = '$student_id' 
                    AND courses_id = '$selected_course' 
                    AND faculty_id = '$faculty_id'
                ";
                $update_result = mysqli_query($conn, $update_sql);
                
                if (!$update_result) {
                    $all_success = false;
                    $message = "❌ Error updating marks: " . mysqli_error($conn) . " (Query: $update_sql)";
                    break;
                }
            } else {
                // Insert new record
                $insert_sql = "
                    INSERT INTO marks (student_id, courses_id, faculty_id, marks_obtained, created_at)
                    VALUES ('$student_id', '$selected_course', '$faculty_id', '$marks_obtained', NOW())
                ";
                $insert_result = mysqli_query($conn, $insert_sql);
                
                if (!$insert_result) {
                    $all_success = false;
                    $message = "❌ Error inserting marks: " . mysqli_error($conn) . " (Query: $insert_sql)";
                    break;
                }
            }
        }

        if ($all_success) {
            $message = "✅ Marks updated successfully!";
        }
    }
    
    // Generate CSV report
    if (isset($_POST['download_csv']) && $selected_course) {
        $course_name = $courses[$selected_course] ?? 'Unknown Course';
        
        $csv_query = mysqli_query($conn, "
            SELECT s.student_id, s.full_name, IFNULL(m.marks_obtained, 0) as marks_obtained,
                   CASE WHEN IFNULL(m.marks_obtained, 0) >= 40 THEN 'Pass' ELSE 'Fail' END as status
            FROM students s
            LEFT JOIN marks m ON s.student_id = m.student_id 
                AND m.courses_id = '$selected_course' 
                AND m.faculty_id = '$faculty_id'
            WHERE s.department_id = '$faculty_dept'
            ORDER BY s.full_name
        ");
        
        if (!$csv_query) {
            $message = "❌ Error generating CSV: " . mysqli_error($conn);
        } else if (mysqli_num_rows($csv_query) > 0) {
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="marks_report_' . $selected_course . '.csv"');
            
            // Create output stream
            $output = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($output, ['Student ID', 'Student Name', 'Course', 'Marks', 'Status']);
            
            // Add data rows
            while ($row = mysqli_fetch_assoc($csv_query)) {
                fputcsv($output, [
                    $row['student_id'],
                    $row['full_name'],
                    $course_name,
                    $row['marks_obtained'],
                    $row['status']
                ]);
            }
            
            // Close the output stream
            fclose($output);
            exit;
        }
    }
}
?>

<!-- HTML Form for Updating Marks -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Marks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #00c853;
            --secondary-color: #00796b;
            --accent-color: #00ffcc;
            --bg-color: #1e1e1e;
            --card-bg: #2a2a2a;
            --table-bg: #333;
            --border-color: #444;
            --text-color: #fff;
            --success-color: #4caf50;
            --error-color: #f44336;
            --info-color: #2196F3;
            --warning-color: #ff9800;
        }
        
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: var(--bg-color);
            padding: 30px;
            border-radius: 10px;
            color: var(--text-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        h2 {
            text-align: center;
            color: var(--accent-color);
            margin-bottom: 25px;
            font-size: 28px;
        }
        
        table {
            width: 100%;
            margin-top: 20px;
            color: white;
            border-collapse: collapse;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border-radius: 8px;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        table th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        table tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        table tr:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .message.success { 
            background: rgba(76, 175, 80, 0.15); 
            color: var(--success-color); 
            border-left: 4px solid var(--success-color);
        }
        
        .message.error { 
            background: rgba(244, 67, 54, 0.15); 
            color: var(--error-color); 
            border-left: 4px solid var(--error-color);
        }
        
        button {
            background-color: var(--primary-color);
            color: #000;
            font-weight: bold;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            margin-top: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            font-size: 16px;
        }
        
        button:hover {
            background-color: #00e676;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        
        .btn-csv {
            background-color: var(--warning-color);
            margin-left: 10px;
        }
        
        .btn-csv:hover {
            background-color: #ffb74d;
        }
        
        .back-link {
            color: var(--accent-color);
            display: block;
            text-align: center;
            margin-top: 30px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: #4dffdb;
            text-decoration: underline;
        }
        
        .course-selector {
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .course-selector select {
            padding: 12px;
            border-radius: 6px;
            background-color: #444;
            color: white;
            border: 1px solid #555;
            margin-right: 15px;
            font-size: 16px;
            min-width: 250px;
            transition: all 0.3s ease;
        }
        
        .course-selector select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(0, 255, 204, 0.2);
            outline: none;
        }
        
        .department-info {
            background-color: var(--secondary-color);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 18px;
            font-weight: 500;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .button-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }
        
        /* Improved marks input field */
        .marks-input {
            width: 80px;
            padding: 10px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .marks-input:focus {
            border-color: var(--accent-color);
            background-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 2px rgba(0, 255, 204, 0.2);
            outline: none;
        }
        
        .marks-input::-webkit-inner-spin-button, 
        .marks-input::-webkit-outer-spin-button { 
            opacity: 1;
            height: 22px;
        }
        
        /* Fade-in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.4s ease-in-out;
        }
        
        /* Section headers */
        .section-header {
            color: var(--accent-color);
            margin: 30px 0 15px 0;
            font-size: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
    </style>
</head>
<body class="dark-bg">

<div class="container fade-in">
    <h2>Update Student Marks</h2>
    <div class="department-info">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 10px;">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
        </svg>
        Department: <?= htmlspecialchars($department_name) ?>
    </div>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($no_courses): ?>
        <div class="message error">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 10px;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            You are not assigned to teach any courses. Please contact the administrator.
        </div>
    <?php else: ?>
        <!-- Course Selection Form -->
        <div class="course-selector">
            <form method="POST" action="update_marks.php">
                <label for="course_id" style="font-size: 16px; margin-right: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    Select Course:
                </label>
                <select name="course_id" id="course_id" required>
                    <option value="">-- Select a course --</option>
                    <?php foreach ($courses as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($selected_course == $id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Show Students
                </button>
            </form>
        </div>

        <?php if ($selected_course): ?>
            <?php
            // Modified query to show ALL students from the department
            // regardless of course enrollment
            $students_query = mysqli_query($conn, "
                SELECT s.student_id, s.full_name, IFNULL(m.marks_obtained, '') as marks_obtained
                FROM students s
                LEFT JOIN marks m ON s.student_id = m.student_id 
                    AND m.courses_id = '$selected_course' 
                    AND m.faculty_id = '$faculty_id'
                WHERE s.department_id = '$faculty_dept'
                ORDER BY s.full_name
            ");
            ?>

            <?php if ($students_query && mysqli_num_rows($students_query) > 0): ?>
                <h3 class="section-header">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 10px;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Student Marks Entry for: <?= htmlspecialchars($courses[$selected_course] ?? 'Selected Course') ?>
                </h3>
                
                <form method="POST" action="update_marks.php">
                    <input type="hidden" name="course_id" value="<?= $selected_course ?>">
                    <table>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Marks Obtained</th>
                        </tr>
                        <?php while ($row = mysqli_fetch_assoc($students_query)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['student_id']) ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td>
                                    <input type="number" name="marks[<?= $row['student_id'] ?>]" min="0" max="100" 
                                           value="<?= htmlspecialchars($row['marks_obtained']) ?>" required class="marks-input">
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                    <div class="button-container">
                        <button type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Update Marks
                        </button>
                        <button type="submit" name="download_csv" class="btn-csv">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Download CSV Report
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <p class="message error">No students are enrolled in this department.</p>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <a href="../faculty_dashboard.php" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to Dashboard
    </a>
</div>

</body>
</html>