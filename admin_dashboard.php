<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header_admin.php';


// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username && $email && $role && $password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, email, role, password) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $username, $email, $role, $password_hash);
                if ($stmt->execute()) {
                    $message = "User added successfully.";

                    $new_user_id = $stmt->insert_id;
                    if ($role === 'teacher') {
                        $stmt2 = $conn->prepare("INSERT INTO teachers (user_id) VALUES (?)");
                        if ($stmt2) {
                            $stmt2->bind_param("i", $new_user_id);
                            $stmt2->execute();
                            $stmt2->close();
                        }
                    } elseif ($role === 'student') {
                        $stmt2 = $conn->prepare("INSERT INTO students (user_id) VALUES (?)");
                        if ($stmt2) {
                            $stmt2->bind_param("i", $new_user_id);
                            $stmt2->execute();
                            $stmt2->close();
                        }
                    } elseif ($role === 'admin') {
                        $stmt2 = $conn->prepare("INSERT INTO admin (user_id) VALUES (?)");
                        if ($stmt2) {
                            $stmt2->bind_param("i", $new_user_id);
                            $stmt2->execute();
                            $stmt2->close();
                        }
                    }
                } else {
                    $message = "Error adding user: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Database error: " . $conn->error;
            }
        } else {
            $message = "Please fill all user fields.";
        }
    }
    elseif ($action === 'update_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($user_id && $username && $email) {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $username, $email, $user_id);
                if ($stmt->execute()) {
                    $message = "User updated successfully.";
                } else {
                    $message = "Error updating user: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Database error: " . $conn->error;
            }
        } else {
            $message = "Please fill all user fields.";
        }
    }
    elseif ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id) {
            $conn->begin_transaction();
            try {
                $conn->query("DELETE FROM teachers WHERE user_id = $user_id");
                $conn->query("DELETE FROM students WHERE user_id = $user_id");
                $conn->query("DELETE FROM admin WHERE user_id = $user_id");
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                $conn->commit();
                $message = "User deleted successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error deleting user: " . $e->getMessage();
            }
        } else {
            $message = "Invalid user ID.";
        }
    }
    elseif ($action === 'create_class') {
        $class_name = trim($_POST['class_name'] ?? '');
        $teacher_id = intval($_POST['teacher_id'] ?? 0);

        if ($class_name && $teacher_id) {
            $stmt = $conn->prepare("INSERT INTO classes (class_name, teacher_id) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("si", $class_name, $teacher_id);
                if ($stmt->execute()) {
                    $message = "Class added successfully.";
                } else {
                    $message = "Error adding class: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Database error: " . $conn->error;
            }
        } else {
            $message = "Please fill all class fields.";
        }
    }
    elseif ($action === 'update_class') {
        $class_id = intval($_POST['class_id'] ?? 0);
        $class_name = trim($_POST['class_name'] ?? '');
        $teacher_id = intval($_POST['teacher_id'] ?? 0);

        if ($class_id && $class_name && $teacher_id) {
            $stmt = $conn->prepare("UPDATE classes SET class_name = ?, teacher_id = ? WHERE class_id = ?");
            if ($stmt) {
                $stmt->bind_param("sii", $class_name, $teacher_id, $class_id);
                if ($stmt->execute()) {
                    $message = "Class updated successfully.";
                } else {
                    $message = "Error updating class: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Database error: " . $conn->error;
            }
        } else {
            $message = "Please fill all class fields.";
        }
    }
    elseif ($action === 'delete_class') {
        $class_id = intval($_POST['class_id'] ?? 0);
        if ($class_id) {
            $conn->begin_transaction();
            try {
                $conn->query("DELETE FROM class_student WHERE class_id = $class_id");
                $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $class_id);
                    $stmt->execute();
                    $stmt->close();
                }
                $conn->commit();
                $message = "Class deleted successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error deleting class: " . $e->getMessage();
            }
        } else {
            $message = "Invalid class ID.";
        }
    }
    elseif ($action === 'add_student') {
        $class_id = intval($_POST['class_id'] ?? 0);
        $student_id = intval($_POST['student_id'] ?? 0);

        if ($class_id && $student_id) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM class_student WHERE class_id = ? AND student_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $class_id, $student_id);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count == 0) {
                    $stmt2 = $conn->prepare("INSERT INTO class_student (class_id, student_id) VALUES (?, ?)");
                    if ($stmt2) {
                        $stmt2->bind_param("ii", $class_id, $student_id);
                        if ($stmt2->execute()) {
                            $message = "Student added to class successfully.";
                        } else {
                            $message = "Error adding student to class: " . $stmt2->error;
                        }
                        $stmt2->close();
                    } else {
                        $message = "Database error: " . $conn->error;
                    }
                } else {
                    $message = "Student is already assigned to this class.";
                }
            } else {
                $message = "Database error: " . $conn->error;
            }
        } else {
            $message = "Please select both class and student.";
        }
    }
    elseif ($action === 'remove_student') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM class_student WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Student removed from class successfully.";
                } else {
                    $message = "Error removing student from class: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Database error: " . $conn->error;
            }
        } else {
            $message = "Invalid record ID.";
        }
    }

    // Redirect to avoid resubmission, preserving filters in URL
    $redirect_url = $_SERVER['PHP_SELF'];
    $query_params = [];
    if (!empty($_GET['user_role'])) $query_params['user_role'] = $_GET['user_role'];
    if (!empty($_GET['class_name'])) $query_params['class_name'] = $_GET['class_name'];
    if (!empty($_GET['sc_class'])) $query_params['sc_class'] = $_GET['sc_class'];
    if (!empty($_GET['sc_student'])) $query_params['sc_student'] = $_GET['sc_student'];
    if ($message) $_SESSION['message'] = $message;
    if (!empty($query_params)) {
        $redirect_url .= '?' . http_build_query($query_params);
    }
    header("Location: $redirect_url");
    exit;
}

// Show message if set
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get filter parameters from GET
$user_filter_role = $_GET['user_role'] ?? '';
$class_filter_name = $_GET['class_name'] ?? '';
$sc_filter_class = $_GET['sc_class'] ?? '';
$sc_filter_student = $_GET['sc_student'] ?? '';

// --- FETCH DATA FOR DISPLAY WITH FILTERS ---

// Fetch all users with role teacher or student, filtered by role if set
$users = [];
$user_sql = "SELECT u.user_id, u.username, u.email, u.role, 
    COALESCE(t.teacher_id, 0) AS teacher_id, COALESCE(s.student_id, 0) AS student_id
    FROM users u
    LEFT JOIN teachers t ON u.user_id = t.user_id
    LEFT JOIN students s ON u.user_id = s.user_id
    WHERE u.role IN ('teacher', 'student')";

$params = [];
$types = "";

if ($user_filter_role === 'teacher' || $user_filter_role === 'student') {
    $user_sql .= " AND u.role = ?";
    $types .= "s";
    $params[] = $user_filter_role;
}

$user_sql .= " ORDER BY u.username";

$stmt = $conn->prepare($user_sql);
if ($stmt) {
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}

// Fetch all admins
$admins = [];
$admin_sql = "SELECT a.admin_id, u.user_id, u.username, u.email 
              FROM admin a 
              JOIN users u ON a.user_id = u.user_id
              ORDER BY u.username";
$result = $conn->query($admin_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Fetch all classes with teacher usernames, filtered by class name (LIKE)
$classes = [];
$class_sql = "SELECT c.class_id, c.class_name, c.teacher_id, u.username AS teacher_name
    FROM classes c
    LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
    LEFT JOIN users u ON t.user_id = u.user_id
    WHERE 1=1";

$params = [];
$types = "";

if ($class_filter_name !== '') {
    $class_sql .= " AND c.class_name LIKE ?";
    $types .= "s";
    $params[] = "%$class_filter_name%";
}

$class_sql .= " ORDER BY c.class_name";

$stmt = $conn->prepare($class_sql);
if ($stmt) {
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    $stmt->close();
}

// Fetch all students (for dropdown)
$students = [];
$result = $conn->query("SELECT s.student_id, u.username FROM students s JOIN users u ON s.user_id = u.user_id ORDER BY u.username");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Fetch all teachers (for dropdown)
$teachers = [];
$result = $conn->query("SELECT t.teacher_id, u.username FROM teachers t JOIN users u ON t.user_id = u.user_id ORDER BY u.username");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// Fetch all student-class assignments with filters on class and student
$class_students = [];
$cs_sql = "SELECT cs.id, cs.class_id, cs.student_id, c.class_name, u.username AS student_name
    FROM class_student cs
    JOIN classes c ON cs.class_id = c.class_id
    JOIN students s ON cs.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    WHERE 1=1";

$params = [];
$types = "";

if ($sc_filter_class !== '') {
    $cs_sql .= " AND c.class_id = ?";
    $types .= "i";
    $params[] = intval($sc_filter_class);
}
if ($sc_filter_student !== '') {
    $cs_sql .= " AND s.student_id = ?";
    $types .= "i";
    $params[] = intval($sc_filter_student);
}

$cs_sql .= " ORDER BY c.class_name, u.username";

$stmt = $conn->prepare($cs_sql);
if ($stmt) {
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $class_students[] = $row;
    }
    $stmt->close();
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
    .filter-form { margin-bottom: 15px; }
    .filter-form label { margin-right: 10px; font-weight: bold; }
    .filter-form select, .filter-form input[type="text"] { padding: 5px; margin-right: 15px; }
</style>
</head>
<body>


<?php if ($message): ?>
    <div class="message <?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- USERS SECTION -->
<h2>Users (Teachers & Students)</h2>

<form method="get" class="filter-form">
    <label for="user_role">Filter by Role:</label>
    <select id="user_role" name="user_role" onchange="this.form.submit()">
        <option value="">-- All Roles --</option>
        <option value="teacher" <?= $user_filter_role === 'teacher' ? 'selected' : '' ?>>Teacher</option>
        <option value="student" <?= $user_filter_role === 'student' ? 'selected' : '' ?>>Student</option>
    </select>
    <?php if ($class_filter_name !== ''): ?>
        <input type="hidden" name="class_name" value="<?= htmlspecialchars($class_filter_name) ?>">
    <?php endif; ?>
    <?php if ($sc_filter_class !== ''): ?>
        <input type="hidden" name="sc_class" value="<?= htmlspecialchars($sc_filter_class) ?>">
    <?php endif; ?>
    <?php if ($sc_filter_student !== ''): ?>
        <input type="hidden" name="sc_student" value="<?= htmlspecialchars($sc_filter_student) ?>">
    <?php endif; ?>
</form>

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
                <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
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
                        <option value="">--Select Role--</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                </td>
                <td><input type="password" name="password" required placeholder="Password" />
                    <button type="submit">Add User</button></td>
            </form>
        </tr>
    </tbody>
</table>

<!-- ADMINS SECTION -->
<h2>Admins</h2>

<table>
    <thead>
        <tr>
            <th>Username</th><th>Email</th><th>Password (leave blank to keep)</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($admins as $admin): ?>
        <tr>
            <form method="post" class="inline" action="">
                <input type="hidden" name="action" value="update_user" />
                <input type="hidden" name="user_id" value="<?= $admin['user_id'] ?>" />
                <td><input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required /></td>
                <td><input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required /></td>
                <td><input type="password" name="password" placeholder="New password" autocomplete="new-password" /></td>
                <td>
                    <button type="submit">Update</button>
            </form>
            <form method="post" class="inline" action="" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                <input type="hidden" name="action" value="delete_user" />
                <input type="hidden" name="user_id" value="<?= $admin['user_id'] ?>" />
                <button type="submit" style="background-color:#dc3545; color:#fff;">Delete</button>
            </form>
                </td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <form method="post" action="">
                <input type="hidden" name="action" value="create_user" />
                <td><input type="text" name="username" required placeholder="New admin username" /></td>
                <td><input type="email" name="email" required placeholder="New admin email" /></td>
                <td><input type="password" name="password" required placeholder="Password" /></td>
                <td>
                    <input type="hidden" name="role" value="admin" />
                    <button type="submit">Add Admin</button>
                </td>
            </form>
        </tr>
    </tbody>
</table>


<!-- CLASSES SECTION -->
<h2>Classes</h2>

<form method="get" class="filter-form">
    <label for="class_name">Filter by Class Name:</label>
    <input type="text" id="class_name" name="class_name" value="<?= htmlspecialchars($class_filter_name) ?>" />
    <button type="submit">Filter</button>
    <?php if ($user_filter_role !== ''): ?>
        <input type="hidden" name="user_role" value="<?= htmlspecialchars($user_filter_role) ?>">
    <?php endif; ?>
    <?php if ($sc_filter_class !== ''): ?>
        <input type="hidden" name="sc_class" value="<?= htmlspecialchars($sc_filter_class) ?>">
    <?php endif; ?>
    <?php if ($sc_filter_student !== ''): ?>
        <input type="hidden" name="sc_student" value="<?= htmlspecialchars($sc_filter_student) ?>">
    <?php endif; ?>
</form>

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
            <form method="post" class="inline" action="" onsubmit="return confirm('Delete this class?');">
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

<!-- CLASS STUDENTS SECTION -->
<h2>Class Students</h2>

<form method="get" class="filter-form">
    <label for="sc_class">Filter by Class:</label>
    <select id="sc_class" name="sc_class" onchange="this.form.submit()">
        <option value="">-- All Classes --</option>
        <?php foreach ($classes as $class): ?>
            <option value="<?= $class['class_id'] ?>" <?= $sc_filter_class == $class['class_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($class['class_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="sc_student">Filter by Student:</label>
    <select id="sc_student" name="sc_student" onchange="this.form.submit()">
        <option value="">-- All Students --</option>
        <?php foreach ($students as $student): ?>
            <option value="<?= $student['student_id'] ?>" <?= $sc_filter_student == $student['student_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($student['username']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if ($user_filter_role !== ''): ?>
        <input type="hidden" name="user_role" value="<?= htmlspecialchars($user_filter_role) ?>">
    <?php endif; ?>
    <?php if ($class_filter_name !== ''): ?>
        <input type="hidden" name="class_name" value="<?= htmlspecialchars($class_filter_name) ?>">
    <?php endif; ?>
</form>

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
