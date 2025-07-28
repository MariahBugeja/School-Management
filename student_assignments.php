<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include 'includes/header_student.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$student_user_id = $_SESSION['user_id'];
$message = '';

// get Student_id from students table
$stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $student_user_id);
$stmt->execute();
$stmt->bind_result($student_id);
if (!$stmt->fetch()) {
    $stmt->close();
    die("Student record not found.");
}
$stmt->close();

// get classes student is enrolled in
$classes = [];
$stmt = $conn->prepare("SELECT c.class_id, c.class_name FROM class_student cs JOIN classes c ON cs.class_id = c.class_id WHERE cs.student_id = ? ORDER BY c.class_name");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

// get optional class_id filter from URL
$class_id = $_GET['class_id'] ?? null;
if ($class_id !== null && !is_numeric($class_id)) {
    die("Invalid class ID.");
}

$assignments = [];

if ($class_id) {
    // Verify student enrollment in this class
    $stmt = $conn->prepare("SELECT COUNT(*) FROM class_student WHERE class_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $class_id, $student_id);
    $stmt->execute();
    $stmt->bind_result($is_enrolled);
    $stmt->fetch();
    $stmt->close();

    if (!$is_enrolled) {
        die("You are not enrolled in this class.");
    }

    // Fetch assignments for selected class only
    $stmt = $conn->prepare("
        SELECT a.assignment_id, a.class_id, a.title, a.description, a.file_path, a.deadline, c.class_name
        FROM assignments a
        JOIN classes c ON a.class_id = c.class_id
        WHERE a.class_id = ?
        ORDER BY a.deadline DESC
    ");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();

} else {
    // Fetch all assignments for all enrolled classes
    $class_ids = array_column($classes, 'class_id');
    if ($class_ids) {
        $placeholders = implode(',', array_fill(0, count($class_ids), '?'));
        $types = str_repeat('i', count($class_ids));

        $sql = "SELECT a.assignment_id, a.class_id, a.title, a.description, a.file_path, a.deadline, c.class_name
                FROM assignments a
                JOIN classes c ON a.class_id = c.class_id
                WHERE a.class_id IN ($placeholders)
                ORDER BY a.deadline DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$class_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        $stmt->close();
    }
}

//  submission Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    $assignment_id = $_POST['assignment_id'];
    if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
        $message = "Please select a file to upload.";
    } else {
        $uploadDir = __DIR__ . '/uploads/submissions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = basename($_FILES['submission_file']['name']);
        $filePath = $uploadDir . time() . '_' . $fileName;
        $fileDbPath = 'uploads/submissions/' . time() . '_' . $fileName;

        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $filePath)) {
            // Check if submission exists (update or insert)
            $stmt = $conn->prepare("SELECT submission_id FROM submission WHERE assignment_id = ? AND student_id = ?");
            $stmt->bind_param("ii", $assignment_id, $student_id);
            $stmt->execute();
            $stmt->bind_result($existing_submission_id);
            $stmt->fetch();
            $stmt->close();

            if ($existing_submission_id) {
                // Updating existing submission
                $stmt = $conn->prepare("UPDATE submission SET file_path = ?, submitted_on = NOW() WHERE submission_id = ?");
                $stmt->bind_param("si", $fileDbPath, $existing_submission_id);
                $stmt->execute();
                $stmt->close();
                $message = "Submission updated successfully.";
            } else {
                // Inserting new submission
                $stmt = $conn->prepare("INSERT INTO submission (assignment_id, student_id, file_path, submitted_on) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iis", $assignment_id, $student_id, $fileDbPath);
                $stmt->execute();
                $stmt->close();
                $message = "Submission uploaded successfully.";
            }
        } else {
            $message = "Failed to upload submission file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Student Assignments</title>
<style>
    body { max-width: 900px; margin: 20px auto; font-family: Arial, sans-serif; }
    h1 { text-align: center; }
    .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
    th, td { padding: 8px 12px; border: 1px solid #ddd; }
    th { background: #f2f2f2; }
    form { margin: 0; }
    .back-link {
        display: inline-block;
        margin-bottom: 15px;
        text-decoration: none;
        color: #007bff;
    }
    .back-link:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
<h1>Assignments</h1>

<a href="student_dashboard.php" class="back-link">&larr; Back to Dashboard</a>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($assignments): ?>
    <table>
        <thead>
            <tr>
                <th>Class</th>
                <th>Title</th>
                <th>Description</th>
                <th>Deadline</th>
                <th>Assignment File</th>
                <th>Your Submission</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $a): 
                // Check if student submitted this assignment
                $stmt = $conn->prepare("SELECT submission_id, file_path, submitted_on FROM submission WHERE assignment_id = ? AND student_id = ?");
                $stmt->bind_param("ii", $a['assignment_id'], $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $submission = $result->fetch_assoc();
                $stmt->close();
            ?>
            <tr>
                <td><?= htmlspecialchars($a['class_name']) ?></td>
                <td><?= htmlspecialchars($a['title']) ?></td>
                <td><?= nl2br(htmlspecialchars($a['description'])) ?></td>
                <td><?= htmlspecialchars($a['deadline']) ?></td>
                <td>
                    <?php if ($a['file_path']): ?>
                        <a href="<?= htmlspecialchars($a['file_path']) ?>" target="_blank">Download</a>
                    <?php else: ?>
                        No file
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($submission): ?>
                        <a href="<?= htmlspecialchars($submission['file_path']) ?>" target="_blank">View Submission</a><br>
                        Submitted on: <?= htmlspecialchars($submission['submitted_on']) ?><br>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="assignment_id" value="<?= $a['assignment_id'] ?>">
                            <input type="file" name="submission_file" required />
                            <button type="submit">Update Submission</button>
                        </form>
                    <?php else: ?>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="assignment_id" value="<?= $a['assignment_id'] ?>">
                            <input type="file" name="submission_file" required />
                            <button type="submit">Submit Assignment</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No assignments found.</p>
<?php endif; ?>
</body>
</html>
