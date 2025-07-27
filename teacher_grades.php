<?php
session_start();
include_once __DIR__ . '/includes/db.php';

// Check teacher logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// make sure we have the teacher_id
$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $teacher_id = $row['teacher_id'];
        $_SESSION['teacher_id'] = $teacher_id;
    } else {
        die("No teacher record found.");
    }
    $stmt->close();
}

$message = '';

// get assignments created by this teacher
$assignments = [];
$stmt = $conn->prepare("SELECT assignment_id, title FROM assignments WHERE teacher_id = ? ORDER BY deadline DESC");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $assignments[] = $row;
}
$stmt->close();

$selected_assignment_id = $_GET['assignment_id'] ?? '';
$submissions = [];

// get submissions for selected assignment with student info
if ($selected_assignment_id) {
    $stmt = $conn->prepare("SELECT sub.submission_id, sub.student_id, sub.file_path, sub.submitted_on,
                                  u.username, g.marks, g.status
                           FROM submission sub
                           JOIN students s ON sub.student_id = s.student_id
                           JOIN users u ON s.user_id = u.user_id
                           LEFT JOIN grades g ON g.submission_id = sub.submission_id
                           WHERE sub.assignment_id = ?");
    $stmt->bind_param("i", $selected_assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
    $stmt->close();
}

// Handle grading POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grades'])) {
    foreach ($_POST['grades'] as $submission_id => $marks) {
        $marks = (int)$marks;

        // Use to insert or update marks
        $stmt = $conn->prepare("
            INSERT INTO grades (submission_id, marks)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE marks = VALUES(marks)
        ");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ii", $submission_id, $marks);
        $stmt->execute();
        $stmt->close();
    }
    $message = "Grades saved successfully.";
    header("Location: teacher_grades.php?assignment_id=" . urlencode($selected_assignment_id));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Grade Submissions</title>
<style>
    body { max-width: 900px; margin: 20px auto; font-family: Arial, sans-serif; }
    h1, h2 { text-align: center; }
    .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
    th { background: #f2f2f2; }
    input[type="number"] { width: 70px; }
    button { padding: 8px 15px; cursor: pointer; }
</style>
</head>
<body>
<h1>Grade Student Submissions</h1>
<a href="teacher_dashboard.php" style="text-decoration:none;">&larr; Back to Dashboard</a>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="get" style="text-align:center; margin-bottom: 20px;">
    <label for="assignment_id">Select Assignment:</label>
    <select name="assignment_id" id="assignment_id" onchange="this.form.submit()" required>
        <option value="">--Select--</option>
        <?php foreach ($assignments as $a): ?>
            <option value="<?= $a['assignment_id'] ?>" <?= $a['assignment_id'] == $selected_assignment_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($a['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($selected_assignment_id): ?>
    <?php if ($submissions): ?>
        <form method="post">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Submission</th>
                        <th>Submitted On</th>
                        <th>Marks (out of 100)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td><?= htmlspecialchars($sub['username']) ?></td>
                            <td><a href="<?= htmlspecialchars($sub['file_path']) ?>" target="_blank">View Submission</a></td>
                            <td><?= htmlspecialchars($sub['submitted_on']) ?></td>
                            <td>
                                <input type="number" name="grades[<?= $sub['submission_id'] ?>]" min="0" max="100" 
                                       value="<?= isset($sub['marks']) ? (int)$sub['marks'] : '' ?>" required>
                            </td>
                            <td><?= isset($sub['status']) ? htmlspecialchars($sub['status']) : 'Not graded' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="text-align:center;">
                <button type="submit">Save Grades</button>
            </div>
        </form>
    <?php else: ?>
        <p style="text-align:center;">No submissions found for this assignment.</p>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
