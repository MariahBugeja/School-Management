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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    foreach ($_FILES['files']['error'] as $assignment_id => $error) {
        if ($error === UPLOAD_ERR_OK) {
            // Check deadline before accepting
            $stmt = $conn->prepare("SELECT deadline FROM assignments WHERE assignment_id = ?");
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $stmt->bind_result($deadline);
            $stmt->fetch();
            $stmt->close();

            if (strtotime($deadline) < time()) {
                $message .= "Deadline passed for assignment ID $assignment_id. Submission not accepted.<br>";
                continue;
            }

            $tmpName = $_FILES['files']['tmp_name'][$assignment_id];
            $fileName = basename($_FILES['files']['name'][$assignment_id]);
            $uploadDir = __DIR__ . '/uploads/submissions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = time() . '_' . $fileName;
            $filePath = $uploadDir . $newFileName;
            $fileDbPath = 'uploads/submissions/' . $newFileName;

            if (move_uploaded_file($tmpName, $filePath)) {
                // Insert or update submission
                $stmt = $conn->prepare("INSERT INTO submission (assignment_id, student_id, file_path, submitted_on)
                                        VALUES (?, ?, ?, NOW())
                                        ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), submitted_on = NOW()");
                $stmt->bind_param("iis", $assignment_id, $student_id, $fileDbPath);
                $stmt->execute();
                $stmt->close();
                $message .= "Assignment ID $assignment_id submitted successfully.<br>";
            } else {
                $message .= "Failed to upload file for assignment ID $assignment_id.<br>";
            }
        } elseif ($error !== UPLOAD_ERR_NO_FILE) {
            $message .= "Error uploading file for assignment ID $assignment_id.<br>";
        }
    }
}

$classes = [];
$stmt = $conn->prepare("SELECT c.class_id, c.class_name 
                        FROM classes c
                        JOIN class_student cs ON c.class_id = cs.class_id
                        WHERE cs.student_id = ?
                        ORDER BY c.class_name");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

$selected_class_id = $_GET['class_id'] ?? '';
$assignments = [];
if ($selected_class_id) {
    $stmt = $conn->prepare("
        SELECT a.assignment_id, a.title, a.description, a.file_path, a.deadline,
               sub.file_path AS submission_file, sub.submitted_on,
               g.marks, g.status
        FROM assignments a
        LEFT JOIN submission sub ON a.assignment_id = sub.assignment_id AND sub.student_id = ?
        LEFT JOIN grades g ON sub.submission_id = g.submission_id
        WHERE a.class_id = ?
        ORDER BY a.deadline ASC
    ");
    $stmt->bind_param("ii", $student_id, $selected_class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; }
        h1, h2, h3 { text-align: center; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .class-list, .assignment-list { margin: 20px 0; }
        .class-item { background: #f2f2f2; padding: 10px; margin: 5px 0; border-radius: 5px; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
        th { background: #f9f9f9; }
        input[type="file"] { width: 100%; }
        button { padding: 10px 20px; cursor: pointer; margin-top: 10px; display: block; margin-left: auto; margin-right: auto; }
        .assignment-title { font-weight: bold; }
        .assignment-desc { font-size: 0.9em; color: #555; margin-top: 4px; white-space: pre-wrap; }
        form.upload-form { margin-top: 0; }
        .disabled-input {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <h1>Student Dashboard</h1>
    <a href="logout.php" style="float:right; color:red; text-decoration:none; font-weight:bold;">Logout</a>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <?php if (!$selected_class_id): ?>
        <h2>Your Classes</h2>
        <div class="class-list">
            <?php if ($classes): ?>
                <?php foreach ($classes as $class): ?>
                    <div class="class-item">
                        <a href="student_dashboard.php?class_id=<?= $class['class_id'] ?>">
                            <?= htmlspecialchars($class['class_name']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You are not enrolled in any classes.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <h2>Assignments for Class</h2>
        <a href="student_dashboard.php" style="display:inline-block; margin-bottom:10px;">&larr; Return to Dashboard</a>

        <?php if ($assignments): ?>
            <form method="post" enctype="multipart/form-data" id="uploadAllForm">
                <table>
                    <thead>
                        <tr>
                            <th>Title & Description</th>
                            <th>Deadline</th>
                            <th>Assignment</th>
                            <th>Your Submission</th>
                            <th>Upload File</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): 
                            $deadline_passed = (strtotime($a['deadline']) < time());
                        ?>
                            <tr>
                                <td>
                                    <div class="assignment-title"><?= htmlspecialchars($a['title']) ?></div>
                                    <div class="assignment-desc"><?= nl2br(htmlspecialchars($a['description'] ?? '')) ?></div>
                                </td>
                                <td><?= htmlspecialchars($a['deadline']) ?></td>
                                <td>
                                    <?php if ($a['file_path']): ?>
                                        <a href="<?= htmlspecialchars($a['file_path']) ?>" target="_blank">View</a>
                                    <?php else: ?>
                                        No file
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($a['submission_file']): ?>
                                        <a href="<?= htmlspecialchars($a['submission_file']) ?>" target="_blank">View Submission</a><br>
                                        Submitted on: <?= htmlspecialchars($a['submitted_on']) ?>
                                    <?php else: ?>
                                        No submission yet
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($deadline_passed): ?>
                                        <input type="file" name="files[<?= $a['assignment_id'] ?>]" disabled class="disabled-input" title="Deadline passed">
                                    <?php else: ?>
                                        <input type="file" name="files[<?= $a['assignment_id'] ?>]" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($a['marks'] !== null) {
                                        echo htmlspecialchars($a['marks']) . " (" . htmlspecialchars($a['status']) . ")";
                                    } else {
                                        echo "Not graded";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit">Upload & Save</button>
            </form>
        <?php else: ?>
            <p>No assignments found for this class.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
