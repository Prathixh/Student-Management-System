<?php
include __DIR__ . '/../config/database.php';

$dept_id = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;

$faculties = mysqli_query($conn, "SELECT id, name FROM faculty WHERE dept_id = $dept_id");

if (mysqli_num_rows($faculties) > 0) {
    echo '<option value="">Select Faculty</option>';
    while ($f = mysqli_fetch_assoc($faculties)) {
        echo '<option value="' . $f['id'] . '">' . htmlspecialchars($f['name']) . '</option>';
    }
} else {
    echo '<option value="">No faculty available</option>';
}
?>
