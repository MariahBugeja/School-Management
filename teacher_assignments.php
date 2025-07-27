<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include 'includes/header_teacher.php'; 

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
$editingAssignment = null;

// Handle delete request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    // Verify that assignment belongs to this teacher before deleting
    $stmt = $conn->prepare("SELECT file_path FROM assignments WHERE assignment_id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $delete_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $fileToDelete = __DIR__ . '/' . $row['file_path'];
        // Delete record
        $stmt_del = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ? AND teacher_id = ?");
        $stmt_del->bind_param("ii", $delete_id, $teacher_id);
        if ($stmt_del->execute()) {
            // Delete file if exists
            if (file_exists($fileToDelete)) {
                unlink($fileToDelete);
            }
            $message = "Assignment deleted successfully.";
        } else {
            $message = "Failed to delete assignment.";
        }
        $stmt_del->close();
    } else {
        $message = "Assignment not found or you don't have permission.";
    }
    $stmt->close();
}

// Handle edit form show
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT assignment_id, class_id, title, description, file_path, deadline 
                            FROM assignments 
                            WHERE assignment_id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $edit_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $editingAssignment = $row;
        $showForm = true;
    } else {
        $message = "Assignment not found or you don't have permission.";
    }
    $stmt->close();
}

// Handle create form show
if (isset($_GET['action']) && $_GET['action'] === 'create') {
    $showForm = true;
}

// Handle form submission for create or update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['create_assignment']) || isset($_POST['update_assignment']))) {
    $class_id = $_POST['class_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $deadline = $_POST['deadline'] ?? '';

    if (!$class_id || !$title || !$deadline) {
        $message = "Please fill in all required fields.";
        $showForm = true;
        // Restore form values for editing if applicable
        if (isset($_POST['update_assignment'])) {
            $editingAssignment = [
                'assignment_id' => $_POST['assignment_id'],
                'class_id' => $class_id,
                'title' => $title,
                'description' => $description,
                'deadline' => $deadline,
                'file_path' => $_POST['existing_file_path'] ?? ''
            ];
        }
    } else {
        $uploadDir = __DIR__ . '/uploads/assignments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileDbPath = $_POST['existing_file_path'] ?? ''; // For update with no new file upload

        // Check if file uploaded (create: required, update: optional)
        $fileUploaded = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;

        if ($fileUploaded) {
            $fileName = basename($_FILES['file']['name']);
            $uniqueFileName = time() . '_' . $fileName;
            $filePath = $uploadDir . $uniqueFileName;
            $fileDbPath = 'uploads/assignments/' . $uniqueFileName;

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                $message = "Failed to upload the file.";
                $showForm = true;
                if (isset($_POST['update_assignment'])) {
                    $editingAssignment = [
                        'assignment_id' => $_POST['assignment_id'],
                        'class_id' => $class_id,
                        'title' => $title,
                        'description' => $description,
                        'deadline' => $deadline,
                        'file_path' => $_POST['existing_file_path'] ?? ''
                    ];
                }
                goto render_form;
            }
        } elseif (!isset($_POST['create_assignment'])) {
            // On update, file upload optional - keep old file
            $fileDbPath = $_POST['existing_file_path'] ?? '';
        } else {
            $message = "Please upload a valid assignment file.";
            $showForm = true;
            goto render_form;
        }

        if (isset($_POST['create_assignment'])) {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO assignments (class_id, teacher_id, title, description, file_path, deadline) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissss", $class_id, $teacher_id, $title, $description, $fileDbPath, $deadline);
            if ($stmt->execute()) {
                $message = "Assignment created successfully.";
                $showForm = false;
            } else {
                $message = "Database error: Could not save assignment.";
                if ($fileUploaded && file_exists($filePath)) {
                    unlink($filePath);
                }
                $showForm = true;
            }
            $stmt->close();
        } elseif (isset($_POST['update_assignment'])) {
            // Update existing
            $assignment_id = (int)$_POST['assignment_id'];

            // Check ownership first
            $stmt_check = $conn->prepare("SELECT file_path FROM assignments WHERE assignment_id = ? AND teacher_id = ?");
            $stmt_check->bind_param("ii", $assignment_id, $teacher_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($row_check = $result_check->fetch_assoc()) {
                $oldFilePath = __DIR__ . '/' . $row_check['file_path'];

                $stmt_check->close();

                // Update DB
                $stmt = $conn->prepare("UPDATE assignments SET class_id = ?, title = ?, description = ?, file_path = ?, deadline = ? WHERE assignment_id = ? AND teacher_id = ?");
                $stmt->bind_param("issssii", $class_id, $title, $description, $fileDbPath, $deadline, $assignment_id, $teacher_id);
                if ($stmt->execute()) {
                    $message = "Assignment updated successfully.";
                    $showForm = false;

                    // Delete old file if new uploaded
                    if ($fileUploaded && file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                } else {
                    $message = "Failed to update assignment.";
                    $showForm = true;
                    $editingAssignment = [
                        'assignment_id' => $assignment_id,
                        'class_id' => $class_id,
                        'title' => $title,
                        'description' => $description,
                        'deadline' => $deadline,
                        'file_path' => $fileDbPath
                    ];
                }
                $stmt->close();
            } else {
                $message = "Assignment not found or you don't have permission.";
                $showForm = false;
            }
        }
    }
}

render_form:

// get classes for form dropdown
$classes = [];
$stmt = $conn->prepare("SELECT class_id, class_name FROM classes WHERE teacher_id = ? ORDER BY class_name");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

// get assignments list if not showing form
$assignments = [];
if (!$showForm) {
    $stmt = $conn->prepare("SELECT a.assignment_id, a.title, a.description, a.file_path, a.deadline, c.class_name
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
        a.btn { display: inline-block; padding: 8px 14px; margin: 10px 0 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; }
        a.btn:hover { background: #0056b3; }
        form { max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
        label { font-weight: bold; }
        input, select, textarea, button { padding: 8px; font-size: 1em; }
        button { font-weight: bold; cursor: pointer; }
        .actions a {
            margin-right: 10px;
            text-decoration: none;
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-weight: 600;
        }
        .actions a.edit {
            background-color: #28a745;
        }
        .actions a.delete {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <h1>Teacher Dashboard</h1>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($showForm): ?>
        <h2><?= $editingAssignment ? "Edit Assignment" : "Create New Assignment" ?></h2>
        <form method="post" enctype="multipart/form-data">
            <?php if ($editingAssignment): ?>
                <input type="hidden" name="update_assignment" value="1" />
                <input type="hidden" name="assignment_id" value="<?= (int)$editingAssignment['assignment_id'] ?>" />
                <input type="hidden" name="existing_file_path" value="<?= htmlspecialchars($editingAssignment['file_path']) ?>" />
            <?php else: ?>
                <input type="hidden" name="create_assignment" value="1" />
            <?php endif; ?>

            <label for="class_id">Class</label>
            <select name="class_id" id="class_id" required>
                <option value="">-- Select Class --</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= $class['class_id'] ?>" <?= ($editingAssignment && $editingAssignment['class_id'] == $class['class_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['class_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="title">Title</label>
            <input type="text" name="title" id="title" required value="<?= $editingAssignment ? htmlspecialchars($editingAssignment['title']) : '' ?>" />

            <label for="description">Description (optional)</label>
            <textarea name="description" id="description" rows="4"><?= $editingAssignment ? htmlspecialchars($editingAssignment['description']) : '' ?></textarea>

            <label for="file">Assignment Document
                <?php if ($editingAssignment && $editingAssignment['file_path']): ?>
                    <br /><small>Current file: <a href="<?= htmlspecialchars($editingAssignment['file_path']) ?>" target="_blank">View</a></small>
                <?php endif; ?>
            </label>
            <input type="file" name="file" id="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" <?= $editingAssignment ? '' : 'required' ?> />

            <label for="deadline">Deadline</label>
            <input type="date" name="deadline" id="deadline" required value="<?= $editingAssignment ? htmlspecialchars($editingAssignment['deadline']) : '' ?>" />

            <button type="submit"><?= $editingAssignment ? "Update Assignment" : "Create Assignment" ?></button>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?= htmlspecialchars($assignment['class_name']) ?></td>
                            <td><?= htmlspecialchars($assignment['title']) ?></td>
                            <td><?= htmlspecialchars($assignment['deadline']) ?></td>
                            <td><a href="<?= htmlspecialchars($assignment['file_path']) ?>" target="_blank">View File</a></td>
                            <td class="actions">
                                <a href="?action=edit&id=<?= $assignment['assignment_id'] ?>" class="edit">Edit</a>
                                <a href="?action=delete&id=<?= $assignment['assignment_id'] ?>" class="delete" onclick="return confirm('Are you sure you want to delete this assignment?');">Delete</a>
                            </td>
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
