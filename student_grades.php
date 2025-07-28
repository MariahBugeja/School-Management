<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include 'includes/header_student.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $student_user_id);
$stmt->execute();
$stmt->bind_result($student_id);
if (!$stmt->fetch()) {
    $stmt->close();
    die("Student record not found.");
}
$stmt->close();
// Get Grades info
$sql = "
    SELECT 
        c.class_name, 
        u.username AS teacher_name,
        a.title AS assignment_title,
        g.marks, 
        g.status
    FROM grades g
    JOIN submission s ON g.submission_id = s.submission_id
    JOIN assignments a ON s.assignment_id = a.assignment_id
    JOIN classes c ON a.class_id = c.class_id
    JOIN teachers t ON c.teacher_id = t.teacher_id
    JOIN users u ON t.user_id = u.user_id
    WHERE s.student_id = ?
    ORDER BY c.class_name, a.title
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$grades = [];
while ($row = $result->fetch_assoc()) {
    $grades[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Grades</title>
    <style>
        body { max-width: 900px; margin: 20px auto; font-family: Arial, sans-serif; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f2f2f2; }
        .back-link { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #007bff; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Your Grades</h1>
    <a href="student_dashboard.php" class="back-link">&larr; Back to Dashboard</a>

    <?php if ($grades): ?>
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Teacher</th>
                    <th>Assignment</th>
                    <th>Marks</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $g): ?>
                <tr>
                    <td><?= htmlspecialchars($g['class_name']) ?></td>
                    <td><?= htmlspecialchars($g['teacher_name']) ?></td>
                    <td><?= htmlspecialchars($g['assignment_title']) ?></td>
                    <td><?= htmlspecialchars($g['marks']) ?></td>
                    <td><?= htmlspecialchars($g['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No grades found.</p>
    <?php endif; ?>
</body>
</html>
