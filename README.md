Job Portal System (JobMatch)

Project Overview
JobMatch is a web-based application designed to streamline the interaction between job seekers and employers. It provides a centralized platform where individuals can find relevant employment opportunities, and employers can efficiently post, manage, and review job applications. 

Built using PHP and MySQL, this system runs locally via tools like XAMPP, making it accessible for development in a contained environment. It integrates user authentication, job posting modules, a job seeker interface for applications and search, and an administrative dashboard for managing users and listings. The project reflects a full-stack solution with practical real-world applications, incorporating secure login mechanisms, database-driven job management, and role-based access control (RBAC). 



Key Features

User Authentication

Handles user sign-up, login, and logout processes. 




Passwords are securely stored using 

password_hash() and verified with password_verify(). 




PHP sessions are used to maintain active user sessions, with session management features like 

session_start() and session_regenerate_id(true) to prevent session fixation. 



Includes a "Forgot Password" functionality with email-based reset links, utilizing PHPMailer.

Employer Module

Employers can post new job openings, and edit or delete existing ones. 


Job details such as title, description, location, category, salary, and company name are stored in the database. 


Job Seeker Module

Job seekers can search for jobs using filters (e.g., job type, location, category). 


They can submit job applications, which are recorded in the system and can be tracked for status updates. 


Includes a personal profile management section.

Admin Dashboard

Admins have an interface to view all user accounts, monitor job postings, and oversee platform analytics. 

Admins can delete or block users and posts if necessary. 

Application Tracker

Tracks which job seeker applied for which job and displays the current status of the application (e.g., pending, accepted, rejected). 


Core Backend Design

Built with PHP, interacting with a MySQL database. 

Uses PDO for database connections with PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION and PDO::ATTR_EMULATE_PREPARES => false for enhanced security against SQL injection.

Implements Role-Based Access Control (RBAC) to provide different access levels for job seekers, employers, and administrators. 

CSRF (Cross-Site Request Forgery) token implementation for form security.

System Architecture
The Job Portal utilizes a three-layer system structure for clear organization and development ease: 

Presentation Layer (Frontend): User interface built with HTML, CSS, and JavaScript.  (e.g., login page, job listing search). 


Application Layer (Backend): PHP scripts handling logic such as login credential checks, job application processing, and communication between the frontend and database. 

Data Layer (Database): MySQL database, managed with phpMyAdmin via XAMPP, storing all user information, job posts, applications, and other records. 

Technologies Used

Backend: PHP

Database: MySQL

Web Server: Apache (via XAMPP)

Database Management: phpMyAdmin

Email Sending: PHPMailer (managed by Composer)

Frontend: HTML, CSS, JavaScript

Dependency Management: Composer

Setup and Installation

To run this project locally, you will need to set up a local web server environment (like XAMPP, WAMP, or MAMP) and a MySQL database.

Clone the repository:

Bash

git clone https://github.com/SaiSreeHarsha2005/JobPortal.git
cd JobPortal
(Adjust JobPortal if you used a different repository name like JobMatch)

Place the project in your web server's document root:
Move the JobPortal folder into your XAMPP's htdocs directory (e.g., C:\xampp\htdocs\JobPortal).

Set up the Database:

Open phpMyAdmin (usually via http://localhost/phpmyadmin/).

Create a new database named 

job_portal_db. 

Import your database schema (e.g., from a database_schema.sql file if you have one – you will need to add this file to your project and include it in your Git commit). If you don't have an SQL schema file, you'll need to manually create the necessary tables (users, jobs, applications, etc.) based on your project's requirements.

Install Composer Dependencies:

Open your terminal/command prompt.

Navigate to your project's root directory:

Bash

cd C:\xampp\htdocs\JobPortal
Run Composer to install PHPMailer and other dependencies:

Bash

composer install
(If you don't have Composer installed, download it from getcomposer.org).

Configure Database Connection:

Verify credentials in includes/db_connect.php. For local XAMPP, username = 'root' and password = '' are common, but MUST be changed for production.

Configure PHPMailer (for password reset/email tests):

In forgot_password.php, ensure Username and Password for smtp.gmail.com are correctly set to your Gmail address and App Password (not your regular Gmail password).

Usage

Start your Apache and MySQL servers via the XAMPP Control Panel.

Access the application in your web browser: http://localhost/JobPortal/public/index.php (or http://localhost/JobMatch/public/index.php depending on your folder name).

Explore the functionalities: Register as a job seeker or employer, log in, browse jobs, post jobs, apply for jobs, manage applications, and explore the admin dashboard.