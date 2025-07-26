<?php
include 'includes/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $role, $email);
    $stmt->execute();
    $user_id = $stmt->insert_id;

    if ($role == "student") {
        $dob = $_POST['date_of_birth'];
        $roll = $_POST['roll_number'];
        $stmt2 = $conn->prepare("INSERT INTO students (user_id, date_of_birth, roll_number) VALUES (?, ?, ?)");
        $stmt2->bind_param("iss", $user_id, $dob, $roll);
        $stmt2->execute();
    } elseif ($role == "teacher") {
        $dept = $_POST['department'];
        $qual = $_POST['qualification'];
        $stmt2 = $conn->prepare("INSERT INTO teachers (user_id, department, qualification) VALUES (?, ?, ?)");
        $stmt2->bind_param("iss", $user_id, $dept, $qual);
        $stmt2->execute();
    } elseif ($role == "admin") {
        $stmt2 = $conn->prepare("INSERT INTO admins (user_id) VALUES (?)");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
    }

    $message = "Registration successful! <a href='login.php'>Login now</a>";
}
?>

<link rel="stylesheet" href="assets/style.css">

<div class="form-container">
    <h2>Sign Up</h2>
    <?php if ($message) echo "<p class='success-message'>$message</p>"; ?>
    <form method="post" action="signup.php">
        <div class="form-input">
            <input type="text" name="username" placeholder="Username" required>
        </div>
        <div class="form-input">
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-input">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <div class="form-input">
            <select name="role" id="roleSelect" onchange="showFields()" required>
                <option value="">Select Role</option>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <div id="studentFields" style="display:none;">
            <div class="form-input">
                <input type="date" name="date_of_birth" placeholder="Date of Birth">
            </div>
            <div class="form-input">
                <input type="text" name="roll_number" placeholder="Roll Number">
            </div>
        </div>

        <div id="teacherFields" style="display:none;">
            <div class="form-input">
                <input type="text" name="department" placeholder="Department">
            </div>
            <div class="form-input">
                <input type="text" name="qualification" placeholder="Qualification">
            </div>
        </div>

        <div class="form-submit">
            <input type="submit" value="Register">
        </div>
        <div class="link">
            <a href="login.ph
