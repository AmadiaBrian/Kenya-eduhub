# Kenya Edu Hub

Lightweight PHP learning resources portal. Provides web UI (`/admin`, `/dashboard`, `/auth`) and a simple REST-like API under `/api` for resource upload, download, and user management. Includes PHPMailer for email features and an `uploads/` directory for resource files.

**Prerequisites**
- PHP 7.4+ (PHP 8 recommended)
- MySQL / MariaDB
- Apache (XAMPP recommended for local development)

**Quick Setup**
1. Place the project in your webroot (e.g. `C:\xampp\htdocs\kenyaeduhub`).
2. Create a MySQL database and import SQL files from the [database](database) folder (e.g. `database/add_user_id_to_resources.sql`, `database/users_db (1).sql`).
3. Update database credentials in [config/database.php](config/database.php) and the root [config.php](config.php) if needed.
4. Ensure `uploads/` is writable by the web server.
5. Configure mail settings (SMTP) if you need email features, see `PHPMailer/` and the code under `auth/` and `api/` that calls it.
6. Visit `http://localhost/kenyaeduhub` to open the site. Admin area: [admin/index.php](admin/index.php).

**Files & Important Locations**
- [config.php](config.php) — main app config
- [config/database.php](config/database.php) — DB connection settings
- [admin/](admin) — admin UI and management pages (login, resources, users)
- [api/](api) — API endpoints (login, register, upload, download, resources, users)
- [auth/](auth) — authentication handlers and pages
- [dashboard/](dashboard) — user dashboard pages
- [includes/](includes) — shared PHP includes (`header.php`, `footer.php`, `helpers.php`, etc.)
- [uploads/](uploads) — uploaded resource files (ensure secure permissions)
- [PHPMailer/](PHPMailer) — bundled mailer library used for notifications
- [database/](database) — SQL dumps and migration scripts

**Security Notes**
- Do not deploy with default or test credentials. Search for any `TODO`, `example`, or credentials in `config.php` and `api/config.php` before production.
- Protect `uploads/` from direct execution; serve files through secure download endpoints where possible.
- Use HTTPS in production and keep PHPMailer credentials out of source control.

**Development / Maintenance Tips**
- Useful files to inspect: [includes/helpers.php](includes/helpers.php), [auth/login.php](auth/login.php), [api/upload.php](api/upload.php), [api/download.php](api/download.php).
- To add admin users or seed data, import the SQL files in [database/](database).

**Where to look next**
- Admin login: [admin/login.php](admin/login.php)
- API config: [api/config.php](api/config.php)

If you'd like, I can:
- Run a quick grep for any plaintext credentials or secrets.
- Harden `uploads/` by adding an `.htaccess` or move uploads outside the webroot.
- Expand README with environment-specific steps (Docker, production PHP-FPM + Nginx).

---
Generated on 2026-06-03 by your development assistant.
