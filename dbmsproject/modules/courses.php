<?php
session_start();
include __DIR__ . '/../config/database.php';

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';

// Handle Add/Edit Course form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        // Handle delete
        $delete_id = intval($_POST['delete_id']);
        $delete_sql = "DELETE FROM courses WHERE courses_id = $delete_id";
        if (mysqli_query($conn, $delete_sql)) {
            $message = "✅ Course deleted successfully!";
        } else {
            $message = "❌ Delete error: " . mysqli_error($conn);
        }
    } else {
        // Handle add/edit
        $courses_id     = isset($_POST['courses_id']) ? intval($_POST['courses_id']) : 0;
        $course_name    = mysqli_real_escape_string($conn, $_POST['course_name']);
        $course_code    = mysqli_real_escape_string($conn, $_POST['course_code']);
        $faculty_id     = mysqli_real_escape_string($conn, $_POST['faculty_id']);
        $department_id  = mysqli_real_escape_string($conn, $_POST['department_id']);
        $credit         = mysqli_real_escape_string($conn, $_POST['credit']);
        $semester       = mysqli_real_escape_string($conn, $_POST['semester']);
        $description    = mysqli_real_escape_string($conn, $_POST['description']);

        if ($courses_id > 0) {
            // Update existing course
            $sql = "UPDATE courses SET 
                    course_name = '$course_name',
                    course_code = '$course_code',
                    faculty_id = '$faculty_id',
                    department_id = '$department_id',
                    credit = '$credit',
                    semester = '$semester',
                    description = '$description',
                    updated_at = NOW()
                    WHERE courses_id = $courses_id";
            $action = "updated";
        } else {
            // Insert new course
            $sql = "INSERT INTO courses (course_name, course_code, faculty_id, department_id, credit, semester, description, created_at)
                    VALUES ('$course_name', '$course_code', '$faculty_id', '$department_id', '$credit', '$semester', '$description', NOW())";
            $action = "added";
        }

        if (mysqli_query($conn, $sql)) {
            $message = "✅ Course \"$course_name\" $action successfully!";
        } else {
            $message = "❌ Database error: " . mysqli_error($conn);
        }
    }
}

// Fetch courses list
$list = mysqli_query($conn, "
  SELECT c.courses_id, c.course_name, c.course_code, f.faculty_name, d.department_name, c.credit, c.semester, c.description, c.created_at
  FROM courses c
  JOIN faculty f ON c.faculty_id = f.faculty_id
  JOIN department d ON c.department_id = d.department_id
  ORDER BY c.courses_id DESC
");

if (!$list) {
    die("Error fetching courses: " . mysqli_error($conn));
}

// Fetch departments for dropdown
$departments = mysqli_query($conn, "SELECT department_id, department_name FROM department ORDER BY department_name");
if (!$departments) {
    die("Error fetching departments: " . mysqli_error($conn));
}

// Fetch course data for editing
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_query = mysqli_query($conn, "SELECT * FROM courses WHERE courses_id = $edit_id");
    $edit_data = mysqli_fetch_assoc($edit_query);
    
    // Fetch faculties for the department of the course being edited
    if ($edit_data) {
        $faculties = mysqli_query($conn, "SELECT faculty_id, faculty_name FROM faculty WHERE department_id = {$edit_data['department_id']} ORDER BY faculty_name");
    }
}

// AJAX handler: fetch faculties based on department_id
if (isset($_GET['ajax_department_id'])) {
    $dept_id = intval($_GET['ajax_department_id']);
    $faculties = mysqli_query($conn, "SELECT faculty_id, faculty_name FROM faculty WHERE department_id = $dept_id ORDER BY faculty_name");
    $result = [];
    while ($row = mysqli_fetch_assoc($faculties)) {
        $result[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Courses</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #00ffcc;
      --secondary: #009688;
      --dark-bg: #1e1e1e;
      --darker-bg: #121212;
      --light-text: #e0e0e0;
      --lighter-text: #ffffff;
      --danger: #ff4444;
      --warning: #ffbb33;
      --success: #00C851;
      --info: #33b5e5;
      --border: #333;
      --input-bg: #2d2d2d;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    
    body {
      background-color: var(--darker-bg);
      color: var(--light-text);
      min-height: 100vh;
      padding: 20px;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      background-color: var(--dark-bg);
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      animation: fadeIn 0.5s ease-out;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    h2 {
      text-align: center;
      color: var(--primary);
      margin-bottom: 30px;
      font-size: 28px;
      position: relative;
      padding-bottom: 10px;
    }
    
    h2::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 3px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      border-radius: 3px;
    }
    
    .message {
      text-align: center;
      margin: 20px 0;
      padding: 15px;
      border-radius: 5px;
      font-weight: 500;
      animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .success {
      background-color: rgba(0, 200, 83, 0.2);
      color: var(--success);
      border-left: 4px solid var(--success);
    }
    
    .error {
      background-color: rgba(255, 68, 68, 0.2);
      color: var(--danger);
      border-left: 4px solid var(--danger);
    }
    
    .form-container {
      background-color: rgba(0, 0, 0, 0.2);
      padding: 25px;
      border-radius: 8px;
      margin-bottom: 30px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      border: 1px solid var(--border);
    }
    
    .form-title {
      color: var(--primary);
      margin-bottom: 20px;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    label {
      display: block;
      margin-bottom: 8px;
      color: var(--light-text);
      font-weight: 500;
    }
    
    input, select, textarea {
      width: 100%;
      padding: 12px 15px;
      background-color: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: 6px;
      color: var(--lighter-text);
      font-size: 14px;
      transition: all 0.3s ease;
    }
    
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 2px rgba(0, 255, 204, 0.2);
    }
    
    textarea {
      min-height: 100px;
      resize: vertical;
    }
    
    .btn {
      padding: 12px 25px;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    
    .btn-primary {
      background-color: var(--primary);
      color: #000;
    }
    
    .btn-primary:hover {
      background-color: #00e6b8;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 255, 204, 0.3);
    }
    
    .btn-secondary {
      background-color: var(--secondary);
      color: white;
    }
    
    .btn-secondary:hover {
      background-color: #00897b;
      transform: translateY(-2px);
    }
    
    .btn-danger {
      background-color: var(--danger);
      color: white;
    }
    
    .btn-danger:hover {
      background-color: #ff3333;
      transform: translateY(-2px);
    }
    
    .btn-sm {
      padding: 8px 15px;
      font-size: 13px;
    }
    
    .action-buttons {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 20px;
    }
    
    .table-responsive {
      overflow-x: auto;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      background-color: var(--dark-bg);
      border-radius: 8px;
      overflow: hidden;
    }
    
    th, td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }
    
    th {
      background-color: rgba(0, 150, 136, 0.2);
      color: var(--primary);
      font-weight: 600;
      text-transform: uppercase;
      font-size: 13px;
      letter-spacing: 0.5px;
    }
    
    tr:hover {
      background-color: rgba(255, 255, 255, 0.03);
    }
    
    tr:last-child td {
      border-bottom: none;
    }
    
    .actions-cell {
      white-space: nowrap;
    }
    
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 30px;
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .back-link:hover {
      color: #00e6b8;
      transform: translateX(-5px);
    }
    
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      animation: fadeIn 0.3s ease-out;
    }
    
    .modal-content {
      background-color: var(--dark-bg);
      padding: 30px;
      border-radius: 10px;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      animation: slideUp 0.3s ease-out;
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .modal-title {
      color: var(--danger);
      font-size: 20px;
    }
    
    .close-modal {
      background: none;
      border: none;
      color: var(--light-text);
      font-size: 24px;
      cursor: pointer;
    }
    
    .modal-body {
      margin-bottom: 20px;
    }
    
    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 15px;
      }
      
      .form-grid {
        grid-template-columns: 1fr;
      }
      
      th, td {
        padding: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h2><i class="fas fa-book-open"></i> Manage Courses</h2>

    <?php if ($message): ?>
      <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <div class="form-container">
      <h3 class="form-title">
        <i class="fas fa-<?= $edit_data ? 'edit' : 'plus' ?>"></i>
        <?= $edit_data ? 'Edit Course' : 'Add New Course' ?>
      </h3>
      
      <form method="POST" action="courses.php">
        <?php if ($edit_data): ?>
          <input type="hidden" name="courses_id" value="<?= $edit_data['courses_id'] ?>">
        <?php endif; ?>
        
        <div class="form-grid">
          <div class="form-group">
            <label for="course_name">Course Name</label>
            <input type="text" id="course_name" name="course_name" 
                   value="<?= $edit_data ? htmlspecialchars($edit_data['course_name']) : '' ?>" required>
          </div>
          
          <div class="form-group">
            <label for="course_code">Course Code</label>
            <input type="text" id="course_code" name="course_code" 
                   value="<?= $edit_data ? htmlspecialchars($edit_data['course_code']) : '' ?>" required>
          </div>
          
          <div class="form-group">
            <label for="department_id">Department</label>
            <select id="department_id" name="department_id" required onchange="fetchFaculties(this.value)">
              <option value="">Select Department</option>
              <?php 
              mysqli_data_seek($departments, 0); // Reset pointer
              while ($dept = mysqli_fetch_assoc($departments)): 
              ?>
                <option value="<?= $dept['department_id'] ?>" 
                  <?= ($edit_data && $edit_data['department_id'] == $dept['department_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($dept['department_name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="faculty_id">Faculty</label>
            <select id="faculty_id" name="faculty_id" required>
              <option value="">Select Faculty</option>
              <?php if ($edit_data && isset($faculties)): ?>
                <?php while ($faculty = mysqli_fetch_assoc($faculties)): ?>
                  <option value="<?= $faculty['faculty_id'] ?>" 
                    <?= ($edit_data['faculty_id'] == $faculty['faculty_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($faculty['faculty_name']) ?>
                  </option>
                <?php endwhile; ?>
              <?php endif; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="credit">Credit</label>
            <input type="number" id="credit" name="credit" 
                   value="<?= $edit_data ? htmlspecialchars($edit_data['credit']) : '' ?>" required>
          </div>
          
          <div class="form-group">
            <label for="semester">Semester</label>
            <input type="text" id="semester" name="semester" 
                   value="<?= $edit_data ? htmlspecialchars($edit_data['semester']) : '' ?>" required>
          </div>
        </div>
        
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description"><?= $edit_data ? htmlspecialchars($edit_data['description']) : '' ?></textarea>
        </div>
        
        <div class="action-buttons">
          <?php if ($edit_data): ?>
            <a href="courses.php" class="btn btn-secondary">
              <i class="fas fa-times"></i> Cancel
            </a>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-<?= $edit_data ? 'save' : 'plus' ?>"></i> 
            <?= $edit_data ? 'Update Course' : 'Add Course' ?>
          </button>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Course Name</th>
            <th>Code</th>
            <th>Faculty</th>
            <th>Department</th>
            <th>Credit</th>
            <th>Semester</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          mysqli_data_seek($list, 0); // Reset pointer
          while ($row = mysqli_fetch_assoc($list)): 
          ?>
          <tr>
            <td><?= $row['courses_id'] ?></td>
            <td><?= htmlspecialchars($row['course_name']) ?></td>
            <td><?= htmlspecialchars($row['course_code']) ?></td>
            <td><?= htmlspecialchars($row['faculty_name']) ?></td>
            <td><?= htmlspecialchars($row['department_name']) ?></td>
            <td><?= htmlspecialchars($row['credit']) ?></td>
            <td><?= htmlspecialchars($row['semester']) ?></td>
            <td class="actions-cell">
              <a href="courses.php?edit=<?= $row['courses_id'] ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Edit
              </a>
              <button onclick="showDeleteModal(<?= $row['courses_id'] ?>, '<?= htmlspecialchars(addslashes($row['course_name'])) ?>')" 
                      class="btn btn-danger btn-sm">
                <i class="fas fa-trash-alt"></i> Delete
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <a href="../dashboard.php" class="back-link">
      <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
        <button class="close-modal" onclick="hideDeleteModal()">&times;</button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete the course "<span id="courseToDelete"></span>"?</p>
        <p class="text-muted">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="hideDeleteModal()">
          <i class="fas fa-times"></i> Cancel
        </button>
        <form method="POST" action="courses.php" style="display: inline;">
          <input type="hidden" name="delete_id" id="deleteId">
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-trash-alt"></i> Delete
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    function fetchFaculties(deptId) {
      if (!deptId) {
        document.getElementById('faculty_id').innerHTML = '<option value="">Select Faculty</option>';
        return;
      }
      
      fetch(`courses.php?ajax_department_id=${deptId}`)
        .then(response => response.json())
        .then(data => {
          const facultySelect = document.getElementById('faculty_id');
          facultySelect.innerHTML = '<option value="">Select Faculty</option>';
          
          if (data.length === 0) {
            facultySelect.innerHTML += '<option value="">No faculty available</option>';
          } else {
            data.forEach(faculty => {
              const option = document.createElement('option');
              option.value = faculty.faculty_id;
              option.textContent = faculty.faculty_name;
              facultySelect.appendChild(option);
            });
          }
        })
        .catch(error => console.error('Error:', error));
    }
    
    function showDeleteModal(id, name) {
      document.getElementById('courseToDelete').textContent = name;
      document.getElementById('deleteId').value = id;
      document.getElementById('deleteModal').style.display = 'flex';
    }
    
    function hideDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('deleteModal');
      if (event.target === modal) {
        hideDeleteModal();
      }
    }
    
    // If editing, fetch faculties for the selected department
    <?php if ($edit_data): ?>
      window.onload = function() {
        fetchFaculties(<?= $edit_data['department_id'] ?>);
      };
    <?php endif; ?>
  </script>
</body>
</html>