<?php
session_start();
include __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get student info if user is a student
if ($user_role === 'student') {
    $student_query = "SELECT * FROM students WHERE users_id = '$user_id'";
    $student_result = mysqli_query($conn, $student_query);
    $student_info = mysqli_fetch_assoc($student_result);
    $student_id = $student_info['student_id'];
}

// Handle marks filtering
$filter_student = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$filter_subject = isset($_GET['subject']) ? $_GET['subject'] : '';
$filter_exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';

// Build WHERE clause
$where_conditions = [];
if ($user_role === 'student') {
    $where_conditions[] = "m.student_id = '$student_id'";
} elseif ($filter_student) {
    $where_conditions[] = "m.student_id = '$filter_student'";
}
if ($filter_subject) {
    $where_conditions[] = "m.subject LIKE '%$filter_subject%'";
}
if ($filter_exam_type) {
    $where_conditions[] = "m.exam_type = '$filter_exam_type'";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get marks records
$marks_query = "
    SELECT m.*, s.full_name, s.course, d.department_name
    FROM marks m
    JOIN students s ON m.student_id = s.student_id
    JOIN department d ON s.department_id = d.department_id
    $where_clause
    ORDER BY m.exam_date DESC, s.full_name, m.subject
";

$marks_result = mysqli_query($conn, $marks_query);

// Get all students for admin filter
$students_query = "SELECT student_id, full_name FROM students ORDER BY full_name";
$students_result = mysqli_query($conn, $students_query);

// Get unique subjects for filter
$subjects_query = "SELECT DISTINCT subject FROM marks WHERE subject IS NOT NULL ORDER BY subject";
$subjects_result = mysqli_query($conn, $subjects_query);

// Get unique exam types
$exam_types_query = "SELECT DISTINCT exam_type FROM marks WHERE exam_type IS NOT NULL ORDER BY exam_type";
$exam_types_result = mysqli_query($conn, $exam_types_query);

// Calculate marks statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_exams,
        AVG(obtained_marks) as avg_marks,
        MAX(obtained_marks) as highest_marks,
        MIN(obtained_marks) as lowest_marks,
        AVG((obtained_marks/total_marks)*100) as avg_percentage
    FROM marks m
    " . ($user_role === 'student' ? "WHERE m.student_id = '$student_id'" : '');

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Marks</title>
    <style>
        body {
            background: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #1e1e1e;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
        }
        h2 {
            text-align: center;
            color: #00ffcc;
            margin-bottom: 30px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #2b2b2b;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #00ffcc;
        }
        .stat-label {
            color: #ccc;
        }
        .filters {
            background: #2b2b2b;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }
        select, input, button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: #333;
            color: #fff;
        }
        button {
            background: #00c853;
            color: #000;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #00e676;
        }
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
            min-width: 800px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #333;
            text-align: left;
        }
        th {
            background: #333;
            color: #00ffcc;
        }
        .percentage {
            font-weight: bold;
        }
        .grade-A { color: #4caf50; }
        .grade-B { color: #8bc34a; }
        .grade-C { color: #ffc107; }
        .grade-D { color: #ff9800; }
        .grade-F { color: #f44336; }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #90caf9;
            text-decoration: none;
            padding: 10px 20px;
            background: #333;
            border-radius: 5px;
        }
        .back-link:hover {
            background: #555;
        }
        .performance-link {
            display: inline-block;
            margin: 20px 10px;
            padding: 12px 24px;
            background: #ff6b35;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .performance-link:hover {
            background: #ff8a5c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📝 Marks & Results</h2>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_exams'] ?: 0 ?></div>
                <div class="stat-label">Total Exams</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= round($stats['avg_marks'] ?: 0, 1) ?></div>
                <div class="stat-label">Average Marks</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['highest_marks'] ?: 0 ?></div>
                <div class="stat-label">Highest Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= round($stats['avg_percentage'] ?: 0, 1) ?>%</div>
                <div class="stat-label">Average %</div>
            </div>
        </div>

        <!-- Performance Analysis Link -->
        <div style="text-align: center;">
            <a href="performance_analysis.php" class="performance-link">📊 View Performance Analysis</a>
        </div>

        <!-- Filters -->
        <?php if ($user_role === 'admin'): ?>
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Student:</label>
                        <select name="student_id">
                            <option value="">All Students</option>
                            <?php mysqli_data_seek($students_result, 0); ?>
                            <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                <option value="<?= $student['student_id'] ?>" 
                                    <?= $filter_student == $student['student_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($student['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Subject:</label>
                        <select name="subject">
                            <option value="">All Subjects</option>
                            <?php while ($subject = mysqli_fetch_assoc($subjects_result)): ?>
                                <option value="<?= $subject['subject'] ?>" 
                                    <?= $filter_subject == $subject['subject'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['subject']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Exam Type:</label>
                        <select name="exam_type">
                            <option value="">All Types</option>
                            <?php while ($exam_type = mysqli_fetch_assoc($exam_types_result)): ?>
                                <option value="<?= $exam_type['exam_type'] ?>" 
                                    <?= $filter_exam_type == $exam_type['exam_type'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($exam_type['exam_type']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit">Filter</button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Marks Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <?php if ($user_role === 'admin'): ?>
                        <th>Student</th>
                        <th>Course</th>
                        <?php endif; ?>
                        <th>Subject</th>
                        <th>Exam Type</th>
                        <th>Obtained</th>
                        <th>Total</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($marks_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($marks_result)): 
                            $percentage = ($row['obtained_marks'] / $row['total_marks']) * 100;
                            $grade = $percentage >= 90 ? 'A' : 
                                    ($percentage >= 80 ? 'B' : 
                                    ($percentage >= 70 ? 'C' : 
                                    ($percentage >= 60 ? 'D' : 'F')));
                        ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($row['exam_date'])) ?></td>
                            <?php if ($user_role === 'admin'): ?>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['course']) ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($row['subject']) ?></td>
                            <td><?= htmlspecialchars($row['exam_type']) ?></td>
                            <td><?= $row['obtained_marks'] ?></td>
                            <td><?= $row['total_marks'] ?></td>
                            <td class="percentage"><?= round($percentage, 1) ?>%</td>
                            <td class="grade-<?= $grade ?>"><?= $grade ?></td>
                            <td><?= htmlspecialchars($row['remarks'] ?? '-') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $user_role === 'admin' ? '10' : '8' ?>" style="text-align: center; color: #ccc;">
                                No marks records found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>