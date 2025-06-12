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

// Handle attendance filtering
$filter_student = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$filter_subject = isset($_GET['subject']) ? $_GET['subject'] : '';
$filter_month = isset($_GET['month']) ? $_GET['month'] : '';

// Build WHERE clause
$where_conditions = [];
if ($user_role === 'student') {
    $where_conditions[] = "a.student_id = '$student_id'";
} elseif ($filter_student) {
    $where_conditions[] = "a.student_id = '$filter_student'";
}
if ($filter_subject) {
    $where_conditions[] = "c.course_name LIKE '%$filter_subject%'";
}
if ($filter_month) {
    $where_conditions[] = "DATE_FORMAT(a.attendance_date, '%Y-%m') = '$filter_month'";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get attendance records
$attendance_query = "
    SELECT a.*, s.full_name, s.course, d.department_name, c.course_name, c.course_code, f.faculty_name
    FROM attendance a
    JOIN students s ON a.student_id = s.student_id
    JOIN department d ON s.department_id = d.department_id
    JOIN courses c ON a.courses_id = c.courses_id
    JOIN faculty f ON a.faculty_id = f.faculty_id
    $where_clause
    ORDER BY a.attendance_date DESC, s.full_name, c.course_name
";

$attendance_result = mysqli_query($conn, $attendance_query);

// Get attendance statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_classes,
        COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
        COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent_count,
        ROUND((COUNT(CASE WHEN a.status = 'Present' THEN 1 END) / COUNT(*)) * 100, 2) as attendance_percentage
    FROM attendance a
    " . ($user_role === 'student' ? "WHERE a.student_id = '$student_id'" : '');

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get subject-wise statistics
$subject_stats_query = "
    SELECT 
        c.course_name,
        c.course_code,
        COUNT(*) as total_classes,
        COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
        ROUND((COUNT(CASE WHEN a.status = 'Present' THEN 1 END) / COUNT(*)) * 100, 2) as percentage
    FROM attendance a
    JOIN courses c ON a.courses_id = c.courses_id
    " . ($user_role === 'student' ? "WHERE a.student_id = '$student_id'" : '') . "
    GROUP BY c.courses_id
    ORDER BY c.course_name
";

$subject_stats_result = mysqli_query($conn, $subject_stats_query);

// Get all students for admin filter
if ($user_role === 'admin') {
    $students_query = "SELECT student_id, full_name FROM students ORDER BY full_name";
    $students_result = mysqli_query($conn, $students_query);
}

// Get unique subjects for filter
$subjects_query = "SELECT DISTINCT c.course_name FROM courses c ORDER BY c.course_name";
$subjects_result = mysqli_query($conn, $subjects_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 2.5em;
        }
        .content {
            padding: 30px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
        }
        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .chart-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .chart-box h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 1.3em;
        }
        .filters {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        select, input, button {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            background: white;
            color: #2c3e50;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        select:focus, input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: bold;
            cursor: pointer;
            border: none;
            transition: transform 0.3s ease;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .table-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            min-width: 800px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e1e8ed;
        }
        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
        }
        .status-present {
            background: #d4edda;
            color: #155724;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .status-absent {
            background: #f8d7da;
            color: #721c24;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .back-link:hover {
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📊 Attendance Overview</h2>
        </div>

        <div class="content">
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total_classes'] ?: 0 ?></div>
                    <div class="stat-label">Total Classes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['present_count'] ?: 0 ?></div>
                    <div class="stat-label">Classes Attended</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['absent_count'] ?: 0 ?></div>
                    <div class="stat-label">Classes Missed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['attendance_percentage'] ?: 0 ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>

            <!-- Charts -->
            <?php if ($stats['total_classes'] > 0): ?>
            <div class="charts-container">
                <div class="chart-box">
                    <h3>Overall Attendance</h3>
                    <canvas id="overallChart" width="300" height="200"></canvas>
                </div>
                <div class="chart-box">
                    <h3>Subject-wise Attendance</h3>
                    <canvas id="subjectChart" width="300" height="200"></canvas>
                </div>
            </div>
            <?php endif; ?>

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
                                    <option value="<?= $subject['course_name'] ?>" 
                                        <?= $filter_subject == $subject['course_name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['course_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Month:</label>
                            <input type="month" name="month" value="<?= $filter_month ?>">
                        </div>
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button type="submit">Filter Results</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Attendance Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <?php if ($user_role === 'admin'): ?>
                            <th>Student Name</th>
                            <th>Course</th>
                            <?php endif; ?>
                            <th>Subject</th>
                            <th>Faculty</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($attendance_result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($attendance_result)): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($row['attendance_date'])) ?></td>
                                <?php if ($user_role === 'admin'): ?>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['course']) ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($row['course_name']) ?> (<?= htmlspecialchars($row['course_code']) ?>)</td>
                                <td><?= htmlspecialchars($row['faculty_name']) ?></td>
                                <td>
                                    <span class="status-<?= strtolower($row['status']) ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['remarks'] ?? '-') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $user_role === 'admin' ? '7' : '5' ?>" style="text-align: center; color: #666; padding: 40px;">
                                    📝 No attendance records found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>

    <script>
        // Overall Attendance Pie Chart
        <?php if ($stats['total_classes'] > 0): ?>
        const overallCtx = document.getElementById('overallChart').getContext('2d');
        const overallChart = new Chart(overallCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [<?= $stats['present_count'] ?>, <?= $stats['absent_count'] ?>],
                    backgroundColor: ['#2ecc71', '#e74c3c'],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });

        // Subject-wise Attendance Bar Chart
        const subjectCtx = document.getElementById('subjectChart').getContext('2d');
        const subjectChart = new Chart(subjectCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    $subjects = [];
                    $percentages = [];
                    mysqli_data_seek($subject_stats_result, 0);
                    while ($row = mysqli_fetch_assoc($subject_stats_result)) {
                        $subjects[] = '"' . $row['course_code'] . '"';
                        $percentages[] = $row['percentage'];
                    }
                    echo implode(', ', $subjects);
                ?>],
                datasets: [{
                    label: 'Attendance %',
                    data: [<?= implode(', ', $percentages) ?>],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>