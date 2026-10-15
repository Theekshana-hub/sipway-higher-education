# SIPWAY Higher Education — Complete Dynamic Website

A production-ready, database-driven higher education website built with **Core PHP 8.2+**, **MySQL**, **PDO**, **Bootstrap 5**, and modern UI/UX.

**Branding:** Primary Red `#E31E36` · Black `#000000` · White · Light Gray `#F5F5F5`

---

## Features

- Public pages: Home, About, Classes (with filters & pagination), Class Details, Teachers, Teacher Profiles, Contact, Search, Privacy, Terms, 404
- Dynamic content from MySQL (hero, stats, vision/mission, contact details, etc.)
- Admin panel with secure login, dashboard statistics, full CRUD for teachers & classes
- Categories, subjects, testimonials, contact message management (with CSV export)
- Site content editor (no code changes needed for text/images)
- Image uploads with validation
- CSRF protection, password hashing, session security, prepared statements
- Responsive design (desktop, tablet, mobile)

---

## Requirements

- PHP 8.2 or newer (with PDO MySQL, fileinfo, session)
- MySQL 5.7+ / MariaDB 10.3+
- Apache (mod_rewrite optional) or Nginx
- WAMP / XAMPP / MAMP for local development

---

## Installation (Local — WAMP/XAMPP)

### 1. Install WAMP or XAMPP
- Download and install [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/).
- Start **Apache** and **MySQL**.

### 2. Copy the Project
- Copy the entire `sipway-higher-education` folder into your web root:
  - **XAMPP:** `C:\xampp\htdocs\`
  - **WAMP:** `C:\wamp64\www\`
- Final path example: `C:\xampp\htdocs\sipway-higher-education\`

### 3. Create the Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database named: **`sipway_higher_education`**
3. Select the database → **Import** tab
4. Choose the file `database.sql` from the project root
5. Click **Go** and wait for success

### 4. Configure Database Connection
Edit `config.php` (or `db.php` uses values from config):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sipway_higher_education');
define('DB_USER', 'root');
define('DB_PASS', '');          // empty for default XAMPP/WAMP
```

Also set the base URL path:

```php
define('BASE_URL', '/sipway-higher-education');  // match your folder name
```

If the project is in the web root directly, use `define('BASE_URL', '');`

### 5. Run the Administrator Installer
1. Open in browser:  
   `http://localhost/sipway-higher-education/install-admin.php`
2. Default credentials (customize if desired):
   - **Username:** `sipwayadmin`
   - **Password:** `ChangeMe@2026!`
3. Click **Create Administrator**
4. **Important:** Delete or rename `install-admin.php` after successful installation

### 6. Log In to Admin
- URL: `http://localhost/sipway-higher-education/admin/login.php`
- Use the credentials above
- You will be prompted to **change the password** on first login (required)

### 7. Dashboard & Content
- Dashboard: `http://localhost/sipway-higher-education/admin/dashboard.php`
- Add/edit teachers and classes from the sidebar
- Edit public website text, stats, contact info under **Site Content**
- Contact form submissions appear under **Contact Messages**

### 8. Public Website
- Homepage: `http://localhost/sipway-higher-education/`
- or `http://localhost/sipway-higher-education/index.php`

---

## Folder Structure

```
sipway-higher-education/
├── index.php, about.php, classes.php, class-details.php
├── teachers.php, teacher-details.php, contact.php, search.php
├── privacy-policy.php, terms.php, 404.php
├── config.php, db.php, functions.php, database.sql
├── install-admin.php, README.md, .gitignore
├── includes/          (header, navbar, footer, pagination)
├── admin/             (login, dashboard, CRUD pages)
│   └── includes/      (auth, sidebar, header, footer)
├── actions/           (form handlers)
├── assets/css/        (style.css, responsive.css, admin.css)
├── assets/js/         (script.js, admin.js)
├── assets/images/     (logo.png, placeholder.png)
└── uploads/           (teachers, classes, banners, pages, gallery)
```

---

## Security Notes

- Passwords are hashed with `password_hash()` (never stored in plaintext)
- CSRF tokens on all forms
- Prepared statements (PDO) prevent SQL injection
- Output escaped with `htmlspecialchars`
- Upload MIME/type validation
- Admin routes protected by session
- Login attempt lockout after 5 failed tries
- Remove `install-admin.php` after setup
- Set `APP_DEBUG` to `false` and strong DB credentials in production

---

## Deploying to Live Hosting

1. Upload all files via FTP/SFTP or File Manager
2. Create a MySQL database and user on the host
3. Import `database.sql`
4. Update `config.php` with live DB credentials and correct `BASE_URL`
5. Set `APP_ENV` to `production` and `APP_DEBUG` to `false`
6. Run `install-admin.php` once, then delete it
7. Ensure `uploads/` folders are writable (755 or 775)
8. Point domain/document root to the project folder

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Database connection error | Check DB name, user, password, and that MySQL is running |
| Blank page / 500 error | Enable `APP_DEBUG` temporarily; check PHP error log |
| CSS/JS not loading | Verify `BASE_URL` matches your folder path |
| Images not showing | Check `uploads/` permissions and paths |
| Login not working | Re-run installer or reset password via phpMyAdmin (hash a new password with PHP) |
| Session issues | Ensure cookies are enabled and session path is writable |

---

## Default Admin Credentials (Development Only)

- **Username:** `sipwayadmin`
- **Password:** `ChangeMe@2026!`

**Change this password immediately after first login.**

---

## License & Credits

Built for SIPWAY Higher Education.  
Uses Bootstrap 5, Font Awesome, Bootstrap Icons, Chart.js (CDN).

© SIPWAY Higher Education. All rights reserved.
