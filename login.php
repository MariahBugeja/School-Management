<?php
session_start();
include 'includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = 'Please enter both username and password.';
    } else {
        // Case-insensitive username search
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE LOWER(username) = LOWER(?)");
        $usernameLower = strtolower($username);
        $stmt->bind_param("s", $usernameLower);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            echo '<pre>DB User data:' . print_r($user, true) . "</pre>";
            echo "Input password: " . htmlspecialchars($password) . "<br>";
if (password_verify($password, $user['password'])) {
    echo "Password verification: SUCCESS<br>";

    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // Redirect based on role
    if ($user['role'] === 'admin') {
        header('Location: admin_dashboard.php');
        exit();
    } elseif ($user['role'] === 'teacher') {
        header('Location: teacher_dashboard.php');
        exit();
    } elseif ($user['role'] === 'student') {
        header('Location: student_dashboard.php');
        exit();
    } else {
        // fallback redirect or error
        header('Location: login.php');
        exit();
    }
} else {
    echo "Password verification: FAILED<br>";
    $message = 'Invalid username or password.';
}

        } else {
            echo "User not found or multiple users with same username.<br>";
            $message = 'Invalid username or password.';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Login Debug</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 30px;
        background-color: #f7f9fc;
    }
    .container {
        max-width: 320px;
        margin: 0 auto;
        padding: 25px 30px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }
    form {
        display: flex;
        flex-direction: column;
    }
    label {
        font-weight: 600;
        margin-bottom: 6px;
        color: #555;
    }
    input[type="text"],
    input[type="password"] {
        padding: 10px 12px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 16px;
        transition: border-color 0.3s;
    }
    input[type="text"]:focus,
    input[type="password"]:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 8px rgba(0,123,255,0.3);
    }
    button {
        background-color: #007bff;
        color: white;
        padding: 12px;
        font-size: 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s;
        font-weight: 600;
    }
    button:hover {
        background-color: #0056b3;
    }
    .message {
        color: #d9534f;
        margin-bottom: 15px;
        font-weight: 600;
        text-align: center;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Login</h2>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label for="username">Username:</label>
        <input id="username" name="username" type="text" required autofocus />

        <label for="password">Password:</label>
        <input id="password" name="password" type="password" required />

        <button type="submit" class="btn-submit">Log In</button>
    </form>
</div>
</body>
</html>
