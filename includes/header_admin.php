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
<title>Admin Dashboard</title>
<style>
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f2f2ff;
  }
  .navbar {
    max-width:900px;
    margin:0 auto;
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
    color: #007bfd;
  }
  .logout {
    background: #007bfd;
    color: white !important;
    padding: 5px 12px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: background 0.3s ease;
  }
  .logout:hover {
    background: #0156b0;
    color: white !important;
  }
</style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <a href="admin_dashboard.php">Admin Dashboard</a>
    </div>
    <a href="logout.php" class="logout">Logout</a>
  </nav>
</body>
</html>
