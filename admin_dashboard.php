<?php
session_start();

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'includes/db.php';

$message = '';

// --- Helper functions ---
function redirect_with_message($msg) {
    $_SESSION['message'] = $msg;
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Process messages from session (redirects)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// --- HANDLE POST ACTIONS ---

// CREATE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!$username || !$email || !$password || !in_array($role, ['teacher', 'student'])) {
        redirect_with_message("Please fill all user fields correctly.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_with_message("Invalid email format.");
    }

    // Check for existing username/email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirect_with_message("Username or email already exists.");
    }
    $stmt->close();

    // Insert user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_with_message("Error creating user: " . $stmt->error);
    }
    $new_user_id = $stmt->insert_id;
    $stmt->close();

    // Insert into role-specific table
    if ($role === 'teacher') {
        $stmt = $conn->prepare("INSERT INTO teachers (user_id) VALUES (?)");
    } else {
        $stmt = $conn->prepare("INSERT INTO students (user_id) VALUES (?)");
    }
    $stmt->bind_param("i", $new_user_id);
    $stmt->execute();
    $stmt->close();

    redirect_with_message("User created successfully.");
}

// UPDATE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!$username || !$email) {
        redirect_with_message("Please fill all user fields correctly.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_with_message("Invalid email format.");
    }

    // Check for existing username/email on other users
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirect_with_message("Username or email already used by another user.");
    }
    $stmt->close();

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_with_message("Error updating user: " . $stmt->error);
    }
    $stmt->close();

    redirect_with_message("User updated successfully.");
}

// DELETE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = intval($_POST['user_id']);

    // Delete from teachers/students tables first
    $stmt = $conn->prepare("DELETE FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_with_message("Error deleting user: " . $stmt->error);
    }
    $stmt->close();

    redirect_with_message("User deleted successfully.");
}

// CREATE CLASS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_class') {
    $class_name = trim($_POST['class_name'] ?? '');
    $teacher_id = intval($_POST['teacher_id'] ?? 0);

    if (!$class_name || !$teacher_id) {
        redirect_with_message("Please provide class name and select a teacher.");
    }

    // Check if class exists
    $stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_name = ?");
    $stmt->bind_param("s", $class_name);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirect_with_message("Class name already exists.");
    }
    $stmt->close();

    // Insert class
    $stmt = $conn->prepare("INSERT INTO classes (class_name, teacher_id) VALUES (?, ?)");
    $stmt->bind_param("si", $class_name, $teacher_id);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_with_message("Error creating class: " . $stmt->error);
    }
    $stmt->close();

    redirect_with_message("Class created successfully.");
}

// UPDATE CLASS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_class') {
    $class_id = intval($_POST['class_id']);
    $class_name = trim($_POST['class_name'] ?? '');
    $teacher_id = intval($_POST['teacher_id'] ?? 0);

    if (!$class_name || !$teacher_id) {
        redirect_with_message("Please provide class name and select a teacher.");
    }

    // Check for duplicate name on other classes
    $stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_name = ? AND class_id != ?");
    $stmt->bind_param("si", $class_name, $class_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirect_with_message("Another class with this name already exists.");
    }
    $stmt->close();

    $stmt = $conn->prepare("UPDATE classes SET class_name = ?, teacher_id = ? WHERE class_id = ?");
    $stmt->bind_param("sii", $class_name, $teacher_id, $class_id);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_with_message("Error updating class: " . $stmt->error);
    }
    $stmt->close();

    redirect_with_message("Class updated successfully.");
}

// DELETE CLASS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_class') {
    $class_id = intval($_POST['class_id']);

    // Delete student assignments first
    $stmt = $conn->prepare("DELETE FROM class_student WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->close();

    // Delete class
    $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_with_message("Error deleting class: " . $stmt->error);
    }
    $stmt->close();

    redirect_with_message("Class deleted successfully.");
}

// ADD STUDENT TO CLASS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    $class_id = intval($_POST['class_id']);
    $student_id = intval($_POST['student_id']);

    if (!$class_id || !$student_id) {
        redirect_with_message("Please select both class and student.");
    }

    // Check if already assigned
    $stmt = $conn->prepare("SELECT id FROM class_student WHERE class_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $class_id, $student_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirect_with_message("Student already assigned to this class.");
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO class_student (class_id, student_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $class_id, $student_id);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_with_message("Error adding student to class: " . $stmt->error);
    }
    $stmt->close();

    redirect_with_message("Student added to class successfully.");
}

// REMOVE STUDENT FROM CLASS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_student') {
    $id = intval($_POST['id']);

    $stmt = $conn->prepare("DELETE FROM class_student WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_with_message("Error removing student from class: " . $stmt->error);
    }
    $stmt->close();

    redirect_with_message("Student removed from class successfully.");
}

// --- FETCH DATA FOR DISPLAY ---

// Fetch all users with role teacher or student
$users = [];
$result = $conn->query("SELECT u.user_id, u.username, u.email, u.role, 
    COALESCE(t.teacher_id, 0) AS teacher_id, COALESCE(s.student_id, 0) AS student_id
    FROM users u
    LEFT JOIN teachers t ON u.user_id = t.user_id
    LEFT JOIN students s ON u.user_id = s.user_id
    WHERE u.role IN ('teacher', 'student')
    ORDER BY u.username");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch all classes with teacher usernames
$classes = [];
$result = $conn->query("SELECT c.class_id, c.class_name, c.teacher_id, u.username AS teacher_name
    FROM classes c
    LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
    LEFT JOIN users u ON t.user_id = u.user_id
    ORDER BY c.class_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Fetch all students (for assigning to class)
$students = [];
$result = $conn->query("SELECT s.student_id, u.username FROM students s JOIN users u ON s.user_id = u.user_id ORDER BY u.username");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Fetch all teachers (for assigning class)
$teachers = [];
$result = $conn->query("SELECT t.teacher_id, u.username FROM teachers t JOIN users u ON t.user_id = u.user_id ORDER BY u.username");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// Fetch all student-class assignments with IDs
$class_students = [];
$result = $conn->query("SELECT cs.id, cs.class_id, cs.student_id, c.class_name, u.username AS student_name
    FROM class_student cs
    JOIN classes c ON cs.class_id = c.class_id
    JOIN students s ON cs.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    ORDER BY c.class_name, u.username");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $class_students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Dashboard - Manage Users & Classes</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; }
    h1, h2 { text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
    th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f2f2f2; }
    form.inline { display: inline-block; margin: 0 5px; }
    input, select { padding: 6px; margin: 3px 0; }
    button { cursor: pointer; }
    .message { padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    .success { background-color: #d4edda; color: #155724; }
    .error { background-color: #f8d7da; color: #721c24; }
    a.logout { float: right; color: red; text-decoration: none; font-weight: bold; }
</style>
</head>
<body>

<a href="logout.php" class="logout">Logout</a>
<h1>Admin Dashboard</h1>

<?php if ($message): ?>
    <div class="message <?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- USERS SECTION -->
<h2>Users (Teachers & Students)</h2>
<table>
    <thead>
        <tr>
            <th>Username</th><th>Email</th><th>Role</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <form method="post" class="inline" action="">
                <input type="hidden" name="action" value="update_user" />
                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>" />
                <td><input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required /></td>
                <td><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required /></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td>
                    <button type="submit">Update</button>
            </form>
            <form method="post" class="inline" action="" onsubmit="return confirm('Are you sure you want to delete this user?');">
                <input type="hidden" name="action" value="delete_user" />
                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>" />
                <button type="submit" style="background-color:#dc3545; color:#fff;">Delete</button>
            </form>
                </td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <form method="post" action="">
                <input type="hidden" name="action" value="create_user" />
                <td><input type="text" name="username" required placeholder="New username" /></td>
                <td><input type="email" name="email" required placeholder="New email" /></td>
                <td>
                    <select name="role" required>
                        <option value="">--Role--</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                </td>
                <td>
                    <input type="password" name="password" required placeholder="Password" />
                    <button type="submit">Add User</button>
                </td>
            </form>
        </tr>
    </tbody>
</table>

<!-- CLASSES SECTION -->
<h2>Classes</h2>
<table>
    <thead>
        <tr>
            <th>Class Name</th><th>Teacher</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($classes as $class): ?>
        <tr>
            <form method="post" class="inline" action="">
                <input type="hidden" name="action" value="update_class" />
                <input type="hidden" name="class_id" value="<?= $class['class_id'] ?>" />
                <td><input type="text" name="class_name" value="<?= htmlspecialchars($class['class_name']) ?>" required /></td>
                <td>
                    <select name="teacher_id" required>
                        <option value="">--Select Teacher--</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['teacher_id'] ?>" <?= $teacher['teacher_id'] == $class['teacher_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($teacher['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <button type="submit">Update</button>
            </form>
            <form method="post" class="inline" action="" onsubmit="return confirm('Are you sure you want to delete this class?');">
                <input type="hidden" name="action" value="delete_class" />
                <input type="hidden" name="class_id" value="<?= $class['class_id'] ?>" />
                <button type="submit" style="background-color:#dc3545; color:#fff;">Delete</button>
            </form>
                </td>
        </tr>
        <?php endforeach; ?>

        <tr>
            <form method="post" action="">
                <input type="hidden" name="action" value="create_class" />
                <td><input type="text" name="class_name" required placeholder="New class name" /></td>
                <td>
                    <select name="teacher_id" required>
                        <option value="">--Select Teacher--</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['teacher_id'] ?>"><?= htmlspecialchars($teacher['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><button type="submit">Add Class</button></td>
            </form>
        </tr>
    </tbody>
</table>

<!-- STUDENTS IN CLASSES -->
<h2>Students in Classes</h2>
<table>
    <thead>
        <tr>
            <th>Class</th><th>Student</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($class_students as $cs): ?>
        <tr>
            <td><?= htmlspecialchars($cs['class_name']) ?></td>
            <td><?= htmlspecialchars($cs['student_name']) ?></td>
            <td>
                <form method="post" action="" onsubmit="return confirm('Remove this student from the class?');" style="display:inline-block;">
                    <input type="hidden" name="action" value="remove_student" />
                    <input type="hidden" name="id" value="<?= $cs['id'] ?>" />
                    <button type="submit" style="background-color:#dc3545; color:#fff;">Remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <form method="post" action="">
                <input type="hidden" name="action" value="add_student" />
                <td>
                    <select name="class_id" required>
                        <option value="">--Select Class--</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <select name="student_id" required>
                        <option value="">--Select Student--</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['student_id'] ?>"><?= htmlspecialchars($student['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><button type="submit">Add Student</button></td>
            </form>
        </tr>
    </tbody>
</table>

</body>
</html>
