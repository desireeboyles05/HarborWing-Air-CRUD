# HarborWing Air CRUD Application

PHP + MySQL CRUD application for the existing `harborwing_air` database from the Midterm Drill.

## Requirements
- PHP 8.1 or newer with PDO MySQL enabled
- MySQL 8.0.16 or newer
- Apache/PHP local server (XAMPP is suitable)

## Setup
1. Make sure the `harborwing_air` database from your Midterm Drill already exists and contains the seed data.
2. **Do not rerun the original `schema.sql` on an existing database.** It begins with `DROP DATABASE IF EXISTS harborwing_air` and will delete and recreate that database.
3. Copy `config.example.php` to `config.php`.
4. Edit `config.php` with your local MySQL host, port, database, username, and password. `config.php` is excluded from Git.
5. Put this folder in your server's web directory, such as `xampp/htdocs/HarborWing-Air-CRUD`.
6. Start Apache and MySQL, then open `http://localhost/HarborWing-Air-CRUD/`.

## Features
- CRUD: passengers, booking agents, reservations, payments, airports, flight schedules, flight legs, and flight costs
- Add/remove payment links and flight-leg links on reservations without deleting the linked payment or leg
- Reservation details with related passenger, agent, payment, and flight-leg information
- Search/filter records
- Read-only calendar and reference records
- Prepared statements, server-side validation, CSRF tokens, and database constraint error messages
- Safe deletes governed by the existing foreign-key constraints

## Database dependency
This application uses the database from your separate Midterm Drill project; it does not create an unrelated database and does not change the schema.

Before submitting, replace the line below with the URL of your actual Midterm Drill GitHub repository:

`Midterm Drill repository: ADD_YOUR_MIDTERM_GITHUB_URL_HERE`

If the Midterm database schema changes, update the Midterm SQL scripts and Workbench diagram as required by the assignment.

## Demo workflow
1. Create a passenger.
2. Create a reservation for that passenger.
3. Create two flight legs and link them to the reservation.
4. Create a payment and link it to the reservation.
5. Open Reservation Details.
6. Update a record.
7. Remove a payment or flight-leg link and confirm the linked record still exists.
8. Delete an eligible unreferenced record.
9. Try an invalid/duplicate operation and show the error message.

## How a user action reaches MySQL
For example, when a user submits a passenger form, `index.php` validates the submitted values and uses the field allow-list in `catalog.php` to choose the permitted table and columns. The values are sent to MySQL through PDO prepared statements. MySQL then enforces its primary-key, unique-key, check, and foreign-key constraints. The application displays a success or understandable error message.

## Security notes
- Never commit `config.php`, passwords, or real personal data.
- Use synthetic/demo data only.
- This project is intended for a local school demonstration and does not include user login/role management.
