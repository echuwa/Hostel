# Hostel Management System - Technical Documentation

## 1. Project Overview
The **Hostel Management System (HostelMS)** is a comprehensive web application designed to streamline the administration of student housing. It provides separate portals for students and administrators, ensuring efficient room allocation, complaint management, and student tracking.

## 2. Technology Stack
- **Backend**: PHP 7.4+ (MySQLi / PDO)
- **Database**: MySQL / MariaDB
- **Frontend**: 
  - HTML5 & CSS3 (Modern Flexbox/Grid)
  - JavaScript (ES6+, jQuery 3.6+)
  - **Styles**: Bootstrap 5, FontAwesome 6
  - **UI Enhancements**: SweetAlert 2 (Modals), Google Fonts (Plus Jakarta Sans)

## 3. System Architecture

### 3.1 Folder Structure
- `/` - Student Portal files (index.php, dashboard.php, etc.)
- `/admin/` - Administrative Interface
- `/includes/` - Core configuration and shared logic
- `/css/` - Global stylesheets (Modernized)
- `/js/` - Frontend logic
- `/assets/` - Images and media
- `/docs/` - Technical documentation

### 3.2 Core Logic
The application follows a modular approach for its configuration:
- `includes/config.php`: Database connection and global settings.
- `includes/security.php`: Security helper functions (CSRF, session binding).
- `includes/checklogin.php`: Middleware for session verification.

## 4. Database Schema
The system operates on a relational database named `Hostel`. Key tables include:

| Table | Description |
|-------|-------------|
| `userregistration` | Stores student account details, status, and gender. |
| `admin` | Stores administrative credentials and roles. |
| `rooms` | Manages available rooms, capacity, and pricing. |
| `courses` | Directory of academic courses. |
| `registration` | Links students to specific room bookings/allocations. |
| `complaints` | Tracks student grievances and admin responses. |
| `debtor_reports` | Monthly/Weekly reports from Block Debtors to Super Admin. |
| `userlog` | Audit trial for student logins. |
| `adminlog` | Audit trial for admin logins. |

## 5. Security Framework
The system implements multiple layers of protection to ensure data integrity:

- **CSRF Protection**: Tokens are generated for sensitive actions (delete, update) and verified server-side.
- **Session Binding**: Prevents session hijacking by binding the session to the user's IP and User Agent.
- **SQL Injection Prevention**: Extensive use of Prepared Statements ($mysqli->prepare).
- **Security Headers**: Implements `X-Frame-Options`, `X-Content-Type-Options`, and `Referrer-Policy`.
- **Output Sanitization**: All user-generated content is escaped using `htmlspecialchars` to prevent XSS.

## 6. Key Features

### 6.1 Administrator Portal
- **Dashboard**: Real-time metrics on students, rooms, and complaints.
- **Student Management**: Full CRUD operations on student profiles and activation.
- **Room Allocation**: Dynamic assignment of rooms based on availability.
- **Complaint Command**: Track, process, and respond to student issues.
- **Debtor Reporting**: Centralized hub to receive and reply to reports from block-specific administrators.
- **Audit Logs**: Detailed tracking of all access and sensitive operations.

### 6.2 Student Portal
- **Registration & Profile**: Personalized student accounts.
- **Room Booking**: Online application for hostel accommodation.
- **Grievance System**: Submit and track complaints with status updates.
- **Financial Status**: View fee eligibility and payment status.

## 7. Setup & Installation

1. **Database Setup**:
   - Create a database named `Hostel`.
   - Import the provided SQL dump (if available) or create tables manually based on schema.
   
2. **Configuration**:
   - Edit `includes/config.php` with your database credentials.
   ```php
   $dbhost = "localhost";
   $dbuser = "your_username";
   $dbpass = "your_password";
   $dbname = "Hostel";
   ```

3. **Web Server**:
   - Point your document root to the project directory.
   - Recommended using Apache or Nginx with PHP support.

## 8. Recent Enhancements
- **Modern UI Overhaul**: Transitioned to a clean, professional Bootstrap 5 design.
- **Security Hardening**: Implemented CSRF protection and advanced session security.
- **Responsive Design**: Fully optimized for mobile and tablet access.
- **Real-time Feedback**: Integration of SweetAlert 2 for all user notifications.
