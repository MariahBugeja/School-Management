<?php
if (!isset($_SESSION)) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SchoolVibes - Teacher</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f9f9f9; }

        
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
        }
        .logout:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <a href="teacher_dashboard.php">School Management</a>
        </div>
        <nav>
            <ul>
                <li><a href="teacher_dashboard.php">Dashboard</a></li>
                <li><a href="teacher_assignments.php">Assignments</a></li>
                <li><a href="teacher_grades.php">Grades</a></li>
                <li><a class="logout" href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
