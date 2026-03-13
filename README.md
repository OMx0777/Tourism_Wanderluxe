# WanderLuxe - Tourism Management System

A full-stack, database-driven web application built with PHP and MariaDB on Arch Linux.

## Features
- **Tourists:** Search destinations, book hotels with live price calculation, and manage personal itineraries.
- **Managers:** List properties and manage guest arrivals with check-in/out tracking.
- **Admins:** Full control over the geography (States, Districts, Places) via a secure dashboard.
- **Security:** Password hashing, session management, and role-based access control.

## Setup
1. Import `wanderluxe_database.sql` into MariaDB.
2. Update `config.php` with your database credentials.
3. Start the server: `php -S localhost:8000`
