<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Student Portal</title>
<style>
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f2f2f2;
  }
  .navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 10px 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  }
  .navbar .logo a {
    font-size: 1.5rem;
    color: #333;
    text-decoration: none;
    font-weight: bold;
  }
  .navbar ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: 20px;
  }
  .navbar ul li a {
    color: #333;
    text-decoration: none;
    font-size: 1rem;
    font-weight: 500;
    transition: color 0.3s ease;
  }
  .navbar ul li a:hover {
    color: #007bff;
  }
  .logout {
    background: #007bff;
    color: white;
    padding: 5px 12px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: background 0.3s ease;
  }
  .logout:hover {
    background: #0056b3;
  }
</style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <a href="student_dashboard.php">Student Portal</a>
    </div>
    <ul>
      <li><a href="student_dashboard.php">Dashboard</a></li>
      <li><a href="student_classes.php">My Classes</a></li>
      <li><a href="student_assignments.php">Assignments</a></li>
      <li><a href="student_grades.php">Grades</a></li>
      <li><a href="student_profile.php">Profile</a></li>
    </ul>
    <a href="logout.php" class="logout">Logout</a>
  </nav>
</body>
</html>
