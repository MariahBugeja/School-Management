<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'includes/db.php'; 
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Basic validation
    if (!$username || !$email || !$password || !in_array($role, ['teacher', 'student'])) {
        $message = "Please fill all fields correctly.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        // Check if username or email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Username or email already exists.";
        } else {
            // Hash password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;

                // Insert into role table
                if ($role === 'teacher') {
                    $stmt2 = $conn->prepare("INSERT INTO teachers (user_id) VALUES (?)");
                } else { // student
                    $stmt2 = $conn->prepare("INSERT INTO students (user_id) VALUES (?)");
                }
                $stmt2->bind_param("i", $new_user_id);
                $stmt2->execute();
                $stmt2->close();

                $message = "User created successfully.";
            } else {
                $message = "Database error: Unable to create user.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard - Create User</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px auto; max-width: 400px; }
        label { display: block; margin-top: 15px; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; }
        button { margin-top: 20px; padding: 12px; width: 100%; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .message { margin-top: 20px; padding: 10px; color: #fff; background: #d9534f; }
        .success { background: #5cb85c; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>Create New User</h2>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, 'successfully') !== false ? 'success' : '' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus />

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required />

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />

        <label for="role">Role</label>
        <select id="role" name="role" required>
            <option value="">-- Select role --</option>
            <option value="teacher">Teacher</option>
            <option value="student">Student</option>
        </select>

        <button type="submit">Create User</button>
    </form>
</body>
</html>
