<?php
session_start();
include 'includes/db.php';

// to check if teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$teacher_id = null;

$stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $teacher_id = $row['teacher_id'];
} else {
    // if no teacher_id found - redirect or error
    echo "Teacher record not found.";
    exit;
}
$stmt->close();

$message = '';

// getting all classes for this teacher
$classes = [];
$stmt = $conn->prepare("SELECT class_id, class_name FROM classes WHERE teacher_id = ? ORDER BY class_name");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

// get selected class and date
$selected_class_id = $_GET['class_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');

// fetching students in selected class
$students = [];
if ($selected_class_id) {
    $stmt = $conn->prepare("SELECT s.student_id, u.username FROM class_student cs
        JOIN students s ON cs.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE cs.class_id = ?
        ORDER BY u.username");
    $stmt->bind_param("i", $selected_class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

// attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $attendance = $_POST['attendance'];
    $date = $_POST['date'];
    $class_id = $_POST['class_id'];

    foreach ($attendance as $student_id => $status) {
        // Insert or update attendance
        $stmt = $conn->prepare("INSERT INTO attendance (class_id, student_id, date, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)");
        $stmt->bind_param("iiss", $class_id, $student_id, $date, $status);
        $stmt->execute();
        $stmt->close();
    }
    $message = "Attendance saved successfully.";
}

// Attendance History
$filter_date = $_GET['filter_date'] ?? '';
$filter_name = $_GET['filter_name'] ?? '';
$attendance_history = [];

$sql = "SELECT a.attendance_id, u.username AS student_name, c.class_name, a.date, a.status
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        JOIN classes c ON a.class_id = c.class_id
        WHERE c.teacher_id = ?";
$params = [$teacher_id];
$types = "i";

if (!empty($filter_date)) {
    $sql .= " AND a.date = ?";
    $types .= "s";
    $params[] = $filter_date;
}
if (!empty($filter_name)) {
    $sql .= " AND u.username LIKE ?";
    $types .= "s";
    $params[] = "%" . $filter_name . "%";
}

$sql .= " ORDER BY a.date DESC, u.username";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $attendance_history[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; }
        h1, h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .message { padding: 10px; margin: 10px 0; border-radius: 5px; background: #d4edda; color: #155724; }
        .filter-form { margin: 10px 0; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
        .filter-form label { font-weight: bold; }
        .filter-form input, .filter-form select { padding: 5px; }
    </style>
    <script>
        function autoSubmit() {
            document.getElementById('classDateForm').submit();
        }
    </script>
</head>
<body>
    <h1>Teacher Dashboard</h1>
    <a href="logout.php" style="float:right; color:red; text-decoration:none; font-weight:bold;">Logout</a>
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

 
    <h2>Take Attendance</h2>
    <form method="get" id="classDateForm">
        <label for="class_id">Class:</label>
        <select name="class_id" id="class_id" onchange="autoSubmit()">
            <option value="">--Select Class--</option>
            <?php foreach ($classes as $class): ?>
                <option value="<?= $class['class_id'] ?>" <?= $class['class_id'] == $selected_class_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($class['class_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="date">Date:</label>
        <input type="date" name="date" id="date" value="<?= htmlspecialchars($selected_date) ?>" onchange="autoSubmit()">
    </form>

    <?php if ($selected_class_id && $students): ?>
        <form method="post">
            <input type="hidden" name="class_id" value="<?= htmlspecialchars($selected_class_id) ?>">
            <input type="hidden" name="date" value="<?= htmlspecialchars($selected_date) ?>">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['username']) ?></td>
                        <td>
                            <select name="attendance[<?= $student['student_id'] ?>]">
                                <option value="Present">Present</option>
                                <option value="Absent">Absent</option>
                                <option value="Late">Late</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit">Save Attendance</button>
        </form>
    <?php elseif ($selected_class_id): ?>
        <p>No students found in this class.</p>
    <?php endif; ?>

    <!-- Attendance history -->
    <h2>Attendance History</h2>
    <form method="get" class="filter-form" id="attendanceFilterForm">
        <input type="hidden" name="class_id" value="<?= htmlspecialchars($selected_class_id) ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($selected_date) ?>">

        <label for="filter_date">Date:</label>
        <input type="date" id="filter_date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>"
               onchange="this.form.submit();">

        <label for="filter_name">Student Name:</label>
        <input type="text" id="filter_name" name="filter_name" 
               value="<?= htmlspecialchars($filter_name) ?>" 
               placeholder="Enter name...">
    </form>

    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($attendance_history): ?>
                <?php foreach ($attendance_history as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['class_name']) ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center;">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
      let typingTimer;
      const doneTypingInterval = 500; // half second delay
      const filterNameInput = document.getElementById('filter_name');

      filterNameInput.addEventListener('keyup', () => {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
          filterNameInput.form.submit();
        }, doneTypingInterval);
      });

      filterNameInput.addEventListener('keydown', () => {
        clearTimeout(typingTimer);
      });
    </script>
</body>
</html>
