# LabActivity7-Ang-PDO

## Setup

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin, choose **Import**, and import `database.sql`.
3. If your MySQL credentials are different from the XAMPP defaults, update the values at the top of `db.php`.
4. Open `register.php` in the browser and create an account.

The application uses `users` for authentication, `posts` for the news feed, and `comments` as the post/user junction table. Posts and comments can only be edited by their authors, and edited records display an `(edited)` marker.