<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include 'includes/header_student.php'; 

// Ensure the student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Fetch current student data
$stmt = $conn->prepare("
    SELECT u.username, u.email, s.student_id 
    FROM users u
    JOIN students s ON u.user_id = s.user_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student profile not found.");
}

// Calculate attendance rate
$student_id = $student['student_id'];
$attendance_rate = null;

// Total attendance records for this student
$stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($total_classes);
$stmt->fetch();
$stmt->close();

// Count of classes marked 'Present'
$stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ? AND status = 'Present'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($present_count);
$stmt->fetch();
$stmt->close();

if ($total_classes > 0) {
    $attendance_rate = round(($present_count / $total_classes) * 100, 2);
} else {
    $attendance_rate = "N/A";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($email)) {
        $message = "Username and email are required.";
    } else {
        // Update query
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $username, $email, $hashedPassword, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $username, $email, $user_id);
        }

        if ($stmt->execute()) {
            $message = "Profile updated successfully.";
            $_SESSION['username'] = $username;
        } else {
            $message = "Error updating profile.";
        }
        $stmt->close();
    }

    // Refresh student info
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Profile</title>
    <link rel="stylesheet" href="assets/student_profile.css">
</head>
<body>
    <div class="profile-container">
        <h1>Your Profile</h1>

        <p><strong>Attendance Rate:</strong> 
            <?php
                if ($attendance_rate === "N/A") {
                    echo "No attendance data available.";
                } else {
                    echo $attendance_rate . "%";
                }
            ?>
        </p>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" class="profile-form">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($student['username']) ?>" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($student['email']) ?>" required>

            <label for="password">New Password (leave blank if not changing)</label>
            <input type="password" name="password" id="password" placeholder="Enter new password">

            <button type="submit">Update Profile</button>
        </form>
    </div>
</body>
</html>
