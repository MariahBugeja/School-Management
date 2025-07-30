# School Management System

A **PHP based School Management System** with role-based dashboards for students, teachers, and administrators.  
It manages classes, assignments, submissions, and grades.

## **Features**
- **Role-based login** (Admin, Teacher, Student)
- **Student Dashboard**:
  - Displays enrolled classes with teacher name and pending assignments count
  - Access assignments and grades
- **Teacher Dashboard**:
  - Manage classes and assignments
  - Grade student submissions
- **Admin Dashboard**:
  - Create and manage users (students, teachers, admins)
  - Manage classes.
- **Assignments Module**:
  - Students can view all assignments or filter by class
  - Upload and update submissions
- **Grades Module**:
  - Students view grades by assignment
    
## **Installation**
Clone Repository:

git clone (https://github.com/MariahBugeja/School-Management)
cd  School_Management
Install dependencies:

## **composer install**
Database setup:

## **Create a new MySQL database**
Import the SQL file from the database/ folder.
Run the app:

Start your server (e.g., MAMP/XAMPP).
Access the application via browser at (http://localhost:8888/schoolvibes2/School-Management/login.php)

## **Project Structure**
**School-Management/**

- includes/ # Shared files (db connection, headers and footer)
- uploads/ # Assignment submissions
- student_dashboard.php # Student dashboard
─ student_assignments.php# Student assignments page
─ student_grades.php # Student grades page
─ teacher_dashboard.php # Teacher dashboard
─ admin_dashboard.php # Admin dashboard
─ login.php # Login page
─ README.md # Project documentation


## **Roles**
Admin: Manages the users and classes.
Teacher: Creates assignments and grades submissions of students.
Student: Can Views classes, submit and view assignments, and view grades.


Mariah Bugeja - Developer / Designer
