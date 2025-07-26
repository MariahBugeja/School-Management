<?php
include 'includes/db.php';

$username = 'Admin1';
$newPassword = 'Admin20021!';

// Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

if (!$hashedPassword) {
    die("Failed to hash password.");
}

// Prepare and execute update query
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ss", $hashedPassword, $username);

if ($stmt->execute()) {
    echo "Password for user '{$username}' has been updated successfully.<br>";
    echo "New password hash: {$hashedPassword}";
} else {
    echo "Error updating password: " . $stmt->error;
}

$stmt->close();
$conn->close();
