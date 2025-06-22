# Bookwartz

Bookwartz is a native PHP-based book management system. It allows administrators to manage book entries and categories, and provides users with the ability to browse and view available books.

---

## Requirements

- PHP 7.4+ (tested with XAMPP)
- MySQL (included in XAMPP)
- A modern web browser (Chrome, Firefox, etc.)

---

## Installation

### 1. Clone or Download the Project

Place the project folder inside your `htdocs` directory:
git clone https://github.com/louatiaminn/bookwartz.git

Or download and extract it to:
C:\xampp\htdocs\bookwartz

### 2. Create the Database
Open XAMPP and start Apache and MySQL.

Go to http://localhost/phpmyadmin

Create a new database named: book
u'll find the book.sql in the db Folder import it into the new database.

### 3. Configure Database Connection
Open includes/cnx.php and make sure the connection details are correct:
$pdo = new PDO("mysql:host=localhost;dbname=bookwartz", "root", "");

## Usage

Access the Application and then Go to:

http://localhost/bookwartz/index.php

Admin Login
Use the following credentials to log in as an administrator:

Username: admin

Password: admin123

The admin panel allows you to manage books and categories.

User Access

To access the user side of the application:

Click Register

Fill in the form to create a new user account

After registration, you can log in and browse books as a user.


## the structure :

bookwartz/
│
├── assets/          Static files (CSS, JS, fonts)
├── categories/      Logic related to book categories
├── controllers/     Handles form submissions and logic
├── images/          Contains static images
├── includes/        Shared code such as database connection
├── uploads/         Stores uploaded book cover images
├── views/           Frontend templates
└── index.php        Entry point of the application

## License
This project is intended for educational use only. No license has been applied.


## Support
If you like this project, don't forget to give it a star on GitHub.

