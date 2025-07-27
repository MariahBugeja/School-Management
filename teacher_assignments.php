<?php
session_start();
include_once __DIR__ . '/includes/db.php';

// Check if teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

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
        die("No teacher record found for this user.");
    }
    $stmt->close();
}

$message = "";
$showForm = false;

if (isset($_GET['action']) && $_GET['action'] === 'create') {
    $showForm = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $class_id = $_POST['class_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $deadline = $_POST['deadline'] ?? '';

    if (!$class_id || !$title || !$deadline) {
        $message = "Please fill in all required fields.";
        $showForm = true;
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $message = "Please upload a valid assignment file.";
        $showForm = true;
    } else {
        // Handle file upload
        $uploadDir = __DIR__ . '/uploads/assignments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = basename($_FILES['file']['name']);
        $uniqueFileName = time() . '_' . $fileName;
        $filePath = $uploadDir . $uniqueFileName;
        $fileDbPath = 'uploads/assignments/' . $uniqueFileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            // Insert into assignments
            $stmt = $conn->prepare("INSERT INTO assignments (class_id, teacher_id, title, description, file_path, deadline) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissss", $class_id, $teacher_id, $title, $description, $fileDbPath, $deadline);
            if ($stmt->execute()) {
                $message = "Assignment created successfully.";
                $showForm = false;
            } else {
                $message = "Database error: Could not save assignment.";
                unlink($filePath); // Delete file if DB save fails
                $showForm = true;
            }
            $stmt->close();
        } else {
            $message = "Failed to upload the file.";
            $showForm = true;
        }
    }
}

// get classes taught by this teacher (needed for form dropdown)
$classes = [];
$stmt = $conn->prepare("SELECT class_id, class_name FROM classes WHERE teacher_id = ? ORDER BY class_name");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

// get assignments for dashboard list (only if not showing form)
$assignments = [];
if (!$showForm) {
    $stmt = $conn->prepare("SELECT a.assignment_id, a.title, a.file_path, a.deadline, c.class_name
                            FROM assignments a
                            JOIN classes c ON a.class_id = c.class_id
                            WHERE a.teacher_id = ?
                            ORDER BY a.deadline DESC");
    $stmt->bind_param("i", $teacher_id);
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
    <meta charset="UTF-8" />
    <title>Teacher Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; }
        h1, h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .message { padding: 10px; margin: 10px 0; border-radius: 5px; background: #d4edda; color: #155724; }
        a.btn { display: inline-block; padding: 8px 14px; margin: 10px 0; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; }
        a.btn:hover { background: #0056b3; }
        form { max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
        label { font-weight: bold; }
        input, select, textarea, button { padding: 8px; font-size: 1em; }
        button { font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Teacher Dashboard</h1>
    <a href="logout.php" style="float:right; color:red; text-decoration:none; font-weight:bold;">Logout</a>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($showForm): ?>
        <h2>Create New Assignment</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="create_assignment" value="1" />
            <label for="class_id">Class</label>
            <select name="class_id" id="class_id" required>
                <option value="">-- Select Class --</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="title">Title</label>
            <input type="text" name="title" id="title" required />

            <label for="description">Description (optional)</label>
            <textarea name="description" id="description" rows="4"></textarea>

            <label for="file">Assignment Document</label>
            <input type="file" name="file" id="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" required />

            <label for="deadline">Deadline</label>
            <input type="date" name="deadline" id="deadline" required />

            <button type="submit">Create Assignment</button>
        </form>
        <p style="text-align:center; margin-top: 10px;"><a href="teacher_dashboard.php">Back to Dashboard</a></p>
    <?php else: ?>
        <h2>Assignments</h2>
        <a href="?action=create" class="btn">+ Create New Assignment</a>

        <?php if ($assignments): ?>
            <table>
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Title</th>
                        <th>Deadline</th>
                        <th>File</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?= htmlspecialchars($assignment['class_name']) ?></td>
                            <td><?= htmlspecialchars($assignment['title']) ?></td>
                            <td><?= htmlspecialchars($assignment['deadline']) ?></td>
                            <td><a href="<?= htmlspecialchars($assignment['file_path']) ?>" target="_blank">View File</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No assignments created yet.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
