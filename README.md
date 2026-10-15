# School Desk

An English-language school management portal built with Laravel 12 and MySQL. The application has Administrator, Teacher, Student, and Parent roles.

## Requirements

- PHP 8.2 or later with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, and `zip` enabled
- Composer
- MySQL or MariaDB

## Run locally with XAMPP on Windows

1. Start **MySQL** from the XAMPP Control Panel.
2. Create a database named `school_management` in phpMyAdmin, or run `CREATE DATABASE school_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;` in MySQL.
3. Copy `.env.example` to `.env` and set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your MySQL account.
4. Install PHP dependencies with `composer install`. If Composer is not on PATH but XAMPP PHP is installed, use `C:\xampp\php\php.exe composer.phar install` after downloading Composer's PHAR.
5. Generate the application key with `C:\xampp\php\php.exe artisan key:generate`.
6. Create the tables and sample records with `C:\xampp\php\php.exe artisan migrate --seed`.
7. Start the local web server with `C:\xampp\php\php.exe artisan serve` and open the address shown in the terminal.

The default timezone is `Africa/Nairobi`. Change `APP_TIMEZONE` in `.env` if the school uses another timezone.

## Demo accounts

The seeder creates these local demonstration accounts. They all use the password `School123!`.

| Role | Email |
| --- | --- |
| Administrator | `admin@school.test` |
| Teacher | `teacher@school.test` |
| Form One teacher | `teacher.formone@school.test` |
| Form Two teacher | `teacher.formtwo@school.test` |
| Form Three teacher | `teacher.formthree@school.test` |
| Student | `student@school.test` |
| Second student | `student2@school.test` |
| Parent | `parent@school.test` |

The demo roster has Form One through Form Four, each with sections A–D. Sections A and B are male classes; sections C and D are female classes. Each class has 30 students and a separate lesson-attendance roster. Demo student emails follow `student.demo.<class-code>.<number>@school.test`, where class codes are `f1a` through `f4d`; Form Four A's generated accounts start at `003` because its first two demo students are retained.

The seeded curriculum has English, Mathematics, Social Studies, Tarbiya, Arabic, Science, Physics, Geography, and Somali, assigned to all 16 classes. Each class has six 45-minute periods per day, Saturday–Wednesday: periods 1–4 end at 10:00 AM, followed by a 45-minute break and periods 5–6. Subject teachers are assigned by form; emails follow `teacher.<form>.<subject-code>@school.test` (for example, `teacher.formone.eng101@school.test`). The existing Form Four Mathematics teacher account is `teacher@school.test`.

Change or remove demo credentials before using the system with real school data.

## Included features

- Administrator-managed accounts, classes, courses, teacher assignments, parent links, and timetable sessions
- Regular weekly timetable entries and date-specific sessions, including Form Four Thursday morning or afternoon lessons
- Teacher attendance recording for each lesson and assessment mark entry
- Student and parent views for courses, timetables, attendance, and marks
- Role checks on server routes and data scoped to the signed-in account
