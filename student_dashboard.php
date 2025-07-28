<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include 'includes/header_student.php'; 

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Fetch student_id
$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
        $_SESSION['student_id'] = $student_id;
    } else {
        die("No student record found.");
    }
    $stmt->close();
}

$message = "";

// Fetch classes with teacher names
$classes = [];
$sql = "
    SELECT c.class_id, c.class_name, u.username AS teacher_name
    FROM classes c
    JOIN class_student cs ON c.class_id = cs.class_id
    JOIN teachers t ON c.teacher_id = t.teacher_id
    JOIN users u ON t.user_id = u.user_id
    WHERE cs.student_id = ?
    ORDER BY c.class_name
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {

    // Get total assignments and not submitted count
    $stmt2 = $conn->prepare("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN s.submission_id IS NULL THEN 1 ELSE 0 END) AS not_submitted
        FROM assignments a
        LEFT JOIN submission s ON a.assignment_id = s.assignment_id AND s.student_id = ?
        WHERE a.class_id = ?
    ");
    if (!$stmt2) {
        die("SQL Error (assignments): " . $conn->error);
    }
    $stmt2->bind_param("ii", $student_id, $row['class_id']);
    $stmt2->execute();
    $stats = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    $row['total_assignments'] = $stats['total'] ?? 0;
    $row['not_submitted'] = $stats['not_submitted'] ?? 0;

    $classes[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; }
        h1, h2 { text-align: center; }
        .class-list { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .class-item {
    display: block;
    background: #007bff;
    color: white;
    padding: 20px;
    width: 30%;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    transition: transform 0.2s, background 0.2s;
    text-decoration: none;
}
.class-item:hover {
    transform: scale(1.03);
    background: #0056b3;
}
.class-item h3 {
    margin-top: 0;
    font-size: 1.3em;
}
.class-item p {
    margin: 5px 0;
    font-size: 0.9em;
}
 
        @media (max-width: 800px) {
            .class-item { width: 45%; }
        }
        @media (max-width: 500px) {
            .class-item { width: 100%; }
        }
    </style>
</head>
<body>
    <h1>Student Dashboard</h1>
    
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h2>Your Classes</h2>
    <div class="class-list">
        <?php if ($classes): ?>
            <?php foreach ($classes as $class): ?>
                <a class="class-item" href="student_assignments.php?class_id=<?= $class['class_id'] ?>">
    <h3><?= htmlspecialchars($class['class_name']) ?></h3>
    <p><strong>Teacher:</strong> <?= htmlspecialchars($class['teacher_name'] ?? 'N/A') ?></p>
    <p><strong>Total Assignments:</strong> <?= $class['total_assignments'] ?></p>
    <p><strong>Not Submitted:</strong> <?= $class['not_submitted'] ?></p>
</a>

            <?php endforeach; ?>
        <?php else: ?>
            <p>You are not enrolled in any classes.</p>
        <?php endif; ?>
    </div>
</body>
</html>
