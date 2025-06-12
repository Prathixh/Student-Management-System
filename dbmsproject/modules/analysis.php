<?php
session_start();
include '../config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit("Please log in to access this page");
}

$user_id = $_SESSION['user_id'];

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

$dept_name = "GRAPH";
$dept_res = mysqli_query($conn, "SELECT department_name FROM departments WHERE department_id = '$faculty_dept'");
if ($dept_res && mysqli_num_rows($dept_res) > 0) {
    $dept_name = mysqli_fetch_assoc($dept_res)['department_name'];
}

$courses = [];
$course_query = mysqli_query($conn, "SELECT courses_id, course_name FROM courses WHERE faculty_id = '$faculty_id'");
while ($row = mysqli_fetch_assoc($course_query)) {
    $courses[$row['courses_id']] = $row['course_name'];
}

$selected_course = $_POST['course_id'] ?? '';
$students_data = [];
$average_marks = 0;
$pass_percentage = 0;
$fail_percentage = 0;

if ($selected_course) {
    $query = "
        SELECT s.student_id, s.full_name, IFNULL(m.marks_obtained, 0) as marks_obtained
        FROM students s
        LEFT JOIN marks m ON s.student_id = m.student_id 
            AND m.courses_id = '$selected_course' 
            AND m.faculty_id = '$faculty_id'
        WHERE s.department_id = '$faculty_dept'
    ";
    $result = mysqli_query($conn, $query);

    if ($result) {
        $total = 0;
        $count = 0;
        $pass = 0;
        $fail = 0;

        while ($row = mysqli_fetch_assoc($result)) {
            $marks = (float)$row['marks_obtained'];
            $status = ($marks >= 40) ? 'Pass' : 'Fail';

            $students_data[] = [
                'student_id' => $row['student_id'],
                'full_name' => $row['full_name'],
                'marks' => $marks,
                'status' => $status
            ];

            $total += $marks;
            $count++;
            if ($status === 'Pass') $pass++;
            else $fail++;
        }

        $average_marks = ($count > 0) ? round($total / $count, 2) : 0;
        $pass_percentage = ($count > 0) ? round(($pass / $count) * 100, 2) : 0;
        $fail_percentage = ($count > 0) ? round(($fail / $count) * 100, 2) : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Marks Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 25px;
            background-color: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        select, button {
            padding: 12px 15px;
            border-radius: 6px;
            margin: 10px 0;
            border: none;
            background-color: #333;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        select:hover, button:hover {
            background-color: #444;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        button {
            background-color: #4CAF50;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #373737;
            text-align: center;
        }
        th {
            background-color: #333;
            color: #fff;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #2a2a2a;
        }
        tr:hover {
            background-color: #373737;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        .pass { 
            color: #66bb6a;
            font-weight: bold;
        }
        .fail { 
            color: #f44336;
            font-weight: bold;
        }
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            gap: 20px;
        }
        .stat-box {
            flex: 1;
            background: linear-gradient(145deg, #2a2a2a, #1e1e1e);
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            color: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            border-top: 4px solid;
        }
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        .stat-box.avg {
            border-color: #2196F3;
        }
        .stat-box.pass {
            border-color: #66bb6a;
        }
        .stat-box.fail {
            border-color: #f44336;
        }
        .stat-box h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #aaa;
        }
        .stat-box .value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-box.avg .value {
            color: #2196F3;
        }
        .stat-box.pass .value {
            color: #66bb6a;
        }
        .stat-box.fail .value {
            color: #f44336;
        }
        .chart-container {
            position: relative;
            width: 100%;
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: #2a2a2a;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        h2, h3 {
            text-align: center;
            color: #fff;
            margin-bottom: 25px;
        }
        h2 {
            font-size: 28px;
            margin-bottom: 30px;
            color: #4CAF50;
        }
        h3 {
            font-size: 22px;
            margin-top: 40px;
        }
        label {
            color: #ddd;
            font-size: 16px;
            margin-right: 10px;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
        .delay-1 {
            animation-delay: 0.2s;
        }
        .delay-2 {
            animation-delay: 0.4s;
        }
        .delay-3 {
            animation-delay: 0.6s;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Marks Analysis - <?= htmlspecialchars($dept_name) ?></h2>

    <form method="POST" class="fade-in">
        <div class="form-group">
            <label for="course_id">Select Course:</label>
            <select name="course_id" id="course_id" required>
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $id => $name): ?>
                    <option value="<?= $id ?>" <?= ($selected_course == $id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Analyze</button>
        </div>
    </form>

    <?php if ($selected_course && $students_data): ?>
        <h3 class="fade-in delay-1">Results for <?= htmlspecialchars($courses[$selected_course]) ?></h3>

        <div class="stats-container fade-in delay-1">
            <div class="stat-box avg">
                <h3>Average Marks</h3>
                <div class="value"><?= $average_marks ?></div>
                <div>out of 100</div>
            </div>
            <div class="stat-box pass">
                <h3>Pass Percentage</h3>
                <div class="value"><?= $pass_percentage ?>%</div>
                <div><?= round($pass_percentage/100 * count($students_data)) ?> students</div>
            </div>
            <div class="stat-box fail">
                <h3>Fail Percentage</h3>
                <div class="value"><?= $fail_percentage ?>%</div>
                <div><?= round($fail_percentage/100 * count($students_data)) ?> students</div>
            </div>
        </div>

        <div class="chart-container fade-in delay-2">
            <canvas id="passFailChart"></canvas>
        </div>

        <div class="fade-in delay-3">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Marks</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students_data as $data): ?>
                        <tr>
                            <td><?= htmlspecialchars($data['student_id']) ?></td>
                            <td><?= htmlspecialchars($data['full_name']) ?></td>
                            <td><?= $data['marks'] ?></td>
                            <td class="<?= $data['status'] === 'Pass' ? 'pass' : 'fail' ?>">
                                <?= $data['status'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('passFailChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Pass', 'Fail'],
                        datasets: [{
                            data: [<?= $pass_percentage ?>, <?= $fail_percentage ?>],
                            backgroundColor: [
                                'rgba(76, 175, 80, 0.8)',
                                'rgba(244, 67, 54, 0.8)'
                            ],
                            borderColor: [
                                'rgba(76, 175, 80, 1)',
                                'rgba(244, 67, 54, 1)'
                            ],
                            borderWidth: 1,
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 2000,
                            easing: 'easeOutQuart',
                            animateScale: true,
                            animateRotate: true
                        },
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: '#fff',
                                    font: {
                                        size: 14
                                    },
                                    padding: 20
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.raw + '%';
                                    }
                                },
                                bodyFont: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Pass/Fail Distribution',
                                color: '#fff',
                                font: {
                                    size: 18,
                                    weight: 'bold'
                                },
                                padding: {
                                    top: 10,
                                    bottom: 20
                                }
                            }
                        },
                        cutout: '65%'
                    }
                });
            });
        </script>

    <?php elseif ($selected_course): ?>
        <p class="fade-in">No marks data found for the selected course.</p>
    <?php endif; ?>
</div>

</body>
</html>