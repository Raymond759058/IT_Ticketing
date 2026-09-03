# IT Ticketing System

A complete, self-contained **PHP + MySQL + Bootstrap 5** IT support ticketing system.
Built with **native PHP (no framework)** so it runs on both **XAMPP** (local) and **cPanel** (shared hosting) with zero extra dependencies.

---

## ✨ Features

- **Auth**: Register / Login for Super Admin, IT Admin, Technician, and User roles
  - Show/Hide Password, Remember Me, Forgot/Reset Password, CSRF protection, bcrypt password hashing
- **Admin Dashboard**: live stats, Chart.js charts (7-day trend, status doughnut, priority bar), recent tickets
- **Ticket Management**: create, assign, edit, update status, close, delete; attachments (image/PDF)
- **Search & Filter**: by ticket #, subject, user, technician, status, priority, department, category, and date range (today / 7 days / month)
- **Visual status indicators**: 🔴 Open · 🟡 Pending · 🔵 In Progress · 🟢 Resolved/Closed · 🟣 High Priority · ⚫ Cancelled
- **Technician Dashboard**: assigned tickets, accept from unassigned pool, work notes, status updates, completed tickets
- **User Dashboard**: create/view/reply/close own tickets
- **Reports**: daily / weekly / monthly tickets, status summary, technician performance, department stats — printable and exportable to CSV (Excel-compatible)
- **Audit Logs**: every login, ticket action, and admin change is logged
- **Role-based access control** enforced on every page
- **Responsive UI** built with Bootstrap 5 + Bootstrap Icons

---

## 📁 Folder Structure

```
it-ticketing-system/
├── admin/              Admin & IT Admin pages (dashboard, tickets, users, depts, categories, priorities, reports, settings, audit logs)
├── technician/          Technician dashboard & ticket views
├── user/                 End-user dashboard, ticket creation & list
├── auth/                Login, Register, Forgot/Reset Password, Logout
├── assets/               CSS & JS
├── config/db.php        Database connection settings
├── database/schema.sql  Full DB schema + seed data
├── includes/             Shared header/sidebar/footer + functions.php (helpers)
├── uploads/               Ticket & reply attachments (write-protected against script execution)
├── ticket-view.php       Shared ticket detail page (used by all roles)
├── profile.php            My Profile / change password (all roles)
└── index.php               Entry point / redirector
```

---

## 🚀 Installation (XAMPP)

1. Copy the `it-ticketing-system` folder into `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), create a new database is not required — just click **Import**, choose `database/schema.sql`, and run it. (It creates the database `it_ticketing_system` automatically.)
4. Open `config/db.php` and confirm the credentials match your MySQL setup (defaults `root` / empty password work out of the box on XAMPP).
5. Visit `http://localhost/it-ticketing-system/` in your browser.
6. Log in with the default Super Admin account:
   - **Email:** `admin@ittickets.local`
   - **Password:** `Admin@123`
7. **Immediately change this password** via *My Profile → Change Password*.

---

## 🚀 Installation (cPanel)

1. Zip the contents of `it-ticketing-system/` and upload via **File Manager** (or FTP) into `public_html/` (or a subfolder).
2. Extract the zip on the server.
3. In cPanel, open **MySQL Databases**: create a database and a database user, and assign the user to the database with **All Privileges**.
4. Open **phpMyAdmin**, select your new (empty) database, go to **Import**, and upload `database/schema.sql`.
   > Note: the schema file includes a `CREATE DATABASE` statement — if your host doesn't allow that, just remove the first two lines (`CREATE DATABASE...` and `USE...`) before importing into your pre-created database.
5. Edit `config/db.php` with your cPanel database name, username, and password (cPanel DB names/users are usually prefixed, e.g. `cpaneluser_it_ticketing`).
6. Visit your domain (e.g. `https://yourdomain.com/`) and log in with the default Super Admin account above.
7. Change the default password immediately.

---

## ⚙️ Configuration Notes

- **Ticket number prefix**, **site name/email**, **registration toggle**, and **email notifications** can all be changed from *Admin → System Settings* (Super Admin only).
- **Email sending** uses PHP's built-in `mail()` function (`includes/functions.php → sendNotificationEmail()`), which works on most cPanel hosts out of the box. For XAMPP/local development, or for more reliable delivery, swap this function for [PHPMailer](https://github.com/PHPMailer/PHPMailer) with your SMTP credentials.
- **File uploads** are limited to 5MB and restricted to jpg/jpeg/png/gif/pdf/doc/docx. Adjust limits in `includes/functions.php → handleUpload()`.
- **PDF/Excel export**: Reports currently export to CSV (opens natively in Excel/Sheets) and support browser Print-to-PDF. For native binary XLSX/PDF generation, drop in [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) or [TCPDF](https://github.com/tecnickcom/TCPDF) via Composer and extend `admin/report-export.php`.
- **Security**: all forms use CSRF tokens, all queries use PDO prepared statements, passwords are hashed with bcrypt, and the `uploads/` folder blocks script execution via `.htaccess`.

---

## 👥 Roles Summary

| Role | Can Do |
|---|---|
| **Super Admin** | Everything, including System Settings and assigning the Super Admin role |
| **IT Admin** | Manage tickets, users, departments, categories, priorities, reports, audit logs |
| **Technician** | View/accept assigned tickets, update status, add work notes, mark resolved |
| **User** | Create/view/reply to/close their own tickets |

---

## 🔒 Default Credentials

| Email | Password | Role |
|---|---|---|
| admin@ittickets.local | Admin@123 | Super Admin |

**Change this password immediately after your first login.**
