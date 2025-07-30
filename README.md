#  JobMatch: A Comprehensive Job Portal System

##  Project Overview

JobMatch is a dynamic, web-based Job Portal System meticulously crafted to streamline the connection between job seekers and employers. It serves as a centralized, intuitive platform where individuals can effortlessly discover relevant employment opportunities, while employers can efficiently post, manage, and review job applications.

Built as a full-stack solution using **PHP** and **MySQL**, this system runs seamlessly in a local development environment (XAMPP, WAMP, or MAMP). It showcases practical, real-world application development, integrating secure authentication, robust database management, and role-based access control.

## Key Features

JobMatch comes packed with essential functionalities to provide a complete user experience:

###  User Authentication
* Handles secure user registration, login, and logout processes.
* **Strong Password Security:** Passwords are securely stored using `password_hash()` and verified with `password_verify()`.
* **Session Management:** Utilizes PHP sessions (`session_start()`, `session_regenerate_id(true)`) to maintain active user sessions and prevent common session-related vulnerabilities like session fixation.
* **Forgot Password:** Implements a secure password reset flow via email, powered by **PHPMailer**.

###  Employer Module
* Empowers employers to post new job openings, and efficiently manage (edit or delete) their existing listings.
* Comprehensive job details including title, description, location, category, salary, and company name are stored and managed in the database.

###  Job Seeker Module
* Enables job seekers to explore and search for job opportunities using various filters (e.g., job type, location, category).
* Facilitates direct application submission to desired jobs.
* Provides a dedicated section for job seekers to track the status of their applications.
* Includes a personal profile management feature.

###  Admin Dashboard
* Offers an administrative interface for system oversight, allowing admins to view and manage all user accounts.
* Provides capabilities to monitor and manage job postings across the platform.
* Admins have control to delete or block users and job posts if necessary.

###  Application Tracker
* A dedicated module that meticulously tracks and displays the current status of each job application (e.g., pending review, accepted, rejected).

###  Core Backend Design
* Developed entirely in **PHP**, interacting robustly with a **MySQL database**.
* Utilizes **PDO** for secure database connections, configured with `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` and `PDO::ATTR_EMULATE_PREPARES => false` to mitigate **SQL Injection** risks.
* Implements **Role-Based Access Control (RBAC)** to ensure different access levels and privileges for job seekers, employers, and administrators.
* Includes **CSRF (Cross-Site Request Forgery) token** implementation for enhanced form submission security.

##  System Architecture

JobMatch is built upon a clear and organized three-layer system structure:

* **Presentation Layer (Frontend):**
    * This is the user-facing part, comprising web pages built with **HTML**, styled with **CSS**, and enhanced with interactive behavior using **JavaScript**. (e.g., login page, job listing search results).
* **Application Layer (Backend):**
    * This layer consists of all **PHP** scripts that process business logic. It handles critical functions like user authentication, processing job applications, and facilitating secure communication between the frontend and the database.
* **Data Layer (Database):**
    * This is the persistent storage for the application, powered by a **MySQL database**. It stores all essential records, including user information, job postings, and application details, managed efficiently via phpMyAdmin (within XAMPP).

## 🛠️ Technologies Used

* **Backend Language:** PHP
* **Database System:** MySQL
* **Local Server Environment:** Apache (via XAMPP)
* **Database Management:** phpMyAdmin
* **Email Sending:** PHPMailer (managed via Composer)
* **Frontend Technologies:** HTML, CSS, JavaScript
* **PHP Dependency Management:** Composer

##  Setup and Installation (Local Environment)

To get JobMatch up and running on your local machine, follow these steps:

1.  **Clone the Repository:**
    Start by cloning the project to your local machine:
    ```bash
    git clone [https://github.com/SaiSreeHarsha2005/JobPortal.git](https://github.com/SaiSreeHarsha2005/JobPortal.git)
    cd JobPortal
    ```
    *(Note: Adjust `JobPortal` in the path if your repository name is `JobMatch`)*

2.  **Place Project in Web Server Root:**
    Move the entire `JobPortal` folder into your XAMPP's `htdocs` directory (e.g., `C:\xampp\htdocs\JobPortal`).

3.  **Database Setup:**
    * Open your phpMyAdmin interface (usually via `http://localhost/phpmyadmin/`).
    * Create a new database named `job_portal_db`.
    * **Import the Database Schema:**
        * Locate the `database_schema.sql` file in the root of this project.
        * In phpMyAdmin, select your `job_portal_db`, go to the "Import" tab, and upload this `.sql` file to create all necessary tables (`users`, `jobs`, `applications`, etc.) and populate initial data if any.
        * *(If `database_schema.sql` is missing, you'll need to manually create your tables or generate an SQL dump from your existing working database).*

4.  **Install Composer Dependencies:**
    * Open your terminal or command prompt.
    * Navigate to your project's root directory (`C:\xampp\htdocs\JobPortal`).
    * Install the required PHP libraries (like PHPMailer) using Composer:
        ```bash
        composer install
        ```
        *(If you don't have Composer installed, download it from [getcomposer.org](https://getcomposer.org/)).*

5.  **Configure Database Connection:**
    * Open `includes/db_connect.php`.
    * Verify that `$host`, `$dbname`, `$username`, and `$password` match your local MySQL configuration.
    * **SECURITY ALERT:** For local XAMPP, `username = 'root'` and `password = ''` are common, but **THESE CREDENTIALS MUST BE CHANGED FOR ANY PRODUCTION DEPLOYMENT** for security reasons.

6.  **Configure PHPMailer for Emails:**
    * Open `forgot_password.php`.
    * Locate the `$mail->Username` and `$mail->Password` lines.
    * Replace `'saisreeharshachowdary2005@gmail.com'` with your actual Gmail address.
    * Replace `'bcqndddjasmagajd'` with your **Gmail App Password**. (You'll need to generate this from your Google Account security settings if you use 2FA).
    * *(This setup is for demonstration. For production, use environment variables or a more secure configuration method for email credentials).*

## 🚦 Usage

1.  **Start your Apache and MySQL servers** using the XAMPP Control Panel.
2.  **Access the application** in your web browser: `http://localhost/JobPortal/public/index.php` (or `http://localhost/JobMatch/public/index.php` depending on your folder name).
3.  **Explore the functionalities:**
    * Register as a new job seeker or employer.
    * Log in to your account.
    * Browse available job listings.
    * Employers can post new jobs.
    * Job seekers can apply for jobs and manage their applications.
    * Access the admin dashboard (requires an admin user).

##  Project Structure
JobMatch/
├── admin/                         # Admin panel pages and associated assets
│   ├── assets/
│   ├── dashboard.php
│   ├── footer.php
│   ├── header.php
│   ├── manage_applications.php
│   ├── manage_jobs.php
│   └── manage_users.php
├── includes/                      # Shared PHP scripts, configurations, and utilities
│   ├── logs/                      # Potential directory for application logs
│   ├── csrf_token.php             # CSRF token generation and validation
│   ├── db_connect.php             # Database connection script
│   ├── footer.php                 # Shared HTML footer for public pages
│   └── header.php                 # Shared HTML header for public pages (includes session, DB, CSRF init)
├── public/                        # User-facing pages (accessible via web browser)
│   ├── assets/                    # Public assets (CSS, JavaScript, images)
│   │   ├── css/
│   │   └── js/
│   ├── applications.php           # Handles job applications display
│   ├── apply_job.php              # Form for applying to a job
│   ├── browse_jobs.php            # Lists and filters jobs
│   ├── dashboard.php              # Job seeker dashboard
│   ├── dashboard1.php             # (Review if this file is necessary or redundant)
│   ├── edit_job.php               # Edit job posting form
│   ├── index.php                  # Main landing page
│   ├── job_details.php            # Displays details of a single job
│   ├── login.php                  # User login page
│   ├── logout.php                 # Handles user logout
│   ├── manage_jobs.php            # Job seeker's view/management of own job postings
│   ├── my_applications.php        # Job seeker's application history
│   ├── post_job.php               # Form for employers to post jobs
│   ├── process_form.php           # General script for handling form submissions
│   ├── profile.php                # User profile management
│   ├── register.php               # User registration page
│   ├── search_jobs.php            # Job search functionality
│   ├── update_application_status.php # Updates application status (likely for employer/admin)
│   ├── view_application.php       # Displays details of a specific application
│   └── view_job.php               # Displays details of a specific job (public view)
├── uploads/                       # Directory for user-uploaded files (e.g., resumes, profile pictures)
├── vendor/                        # Composer-managed third-party libraries (auto-generated by composer install)
├── composer.json                  # Defines Composer project dependencies
├── composer.lock                  # Locks Composer dependencies to exact versions
├── forgot_password.php            # Initiates password reset process
├── reset_password.php             # Handles password reset form submission
├── test_connection.php            # Script to test database connection
├── test_email.php                 # Script to test PHPMailer email sending
├── view_jobs.php                  # Basic script to display all jobs (might be a development test)
├── database_schema.sql            # REQUIRED: Your MySQL database schema (SQL dump)
└── README.md                      # This documentation file

##  Disclaimers and Ethical Use

This project is created primarily for **educational, learning, and portfolio demonstration purposes**. It showcases web development skills and principles, including security considerations.

* **Production Deployment:** While security measures like password hashing, prepared statements, CSRF tokens, and session management are implemented, this project should be reviewed and hardened by a security expert before any production deployment. Practices like hardcoded database/email credentials and direct web server root exposure (if applicable) are common in development but are **NOT secure for live environments.**
* **OWASP Guidelines:** Refer to the [OWASP Top Ten](https://owasp.org/www-project-top-ten/) for comprehensive guidance on secure coding practices in web applications.

##  Authors


* M. SAI SREE HARSHA 
* M. ROSHAN
