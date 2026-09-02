# School ID Registration, Printing & Gate Monitoring System

A full-stack **PHP + MySQL** web application based on the Group 2 proposal
(*Advanced Web Development and Design*). It automates student ID
registration, ID printing with QR codes, and gate entry/exit monitoring.

![Design] Modern minimalist — white cards on a light beige background with a
dark maroon color scheme, **Inter** font.

---

## Features

| Area | What it does |
|------|--------------|
| **Login / Auth** | Session-based, role-aware access for 5 user types |
| **Dashboard** | Live stats: students, on-campus count, IDs, gate activity |
| **Student Registration** | Registrar/ID Staff register new students |
| **Student Management** | Approve / reject / delete students |
| **ID Printing** | Generates a QR-encoded ID card, marks prints |
| **Gate Monitoring** | Scan QR (camera or manual) → valid/invalid with ✓ / ✗ |
| **Reports** | Date-filtered gate report + CSV export |
| **Notifications** | Bell alerts (unread count, list, mark read) |
| **User Management** | Admin-only: create / enable / disable / delete users |

## Roles & Permissions

| Role | Access |
|------|--------|
| `admin` | Everything |
| `registrar` | Register & approve students |
| `id_staff` | Register + print IDs |
| `security_guard` | Gate monitoring only |
| `student` | *(reserved)* |

## Tech Stack

- **Backend:** PHP 8.2 (PDO, prepared statements)
- **Database:** MariaDB/MySQL
- **QR:** bundled `phpqrcode` library + `gd`
- **Frontend:** HTML/CSS/JS (no framework), camera QR scanning via `BarcodeDetector`
- **Server:** PHP built-in dev server (no Apache needed)

---

## Quick Start

This bundles a **private, self-contained MariaDB instance** that runs as your
own user — **no root/sudo or system service required** (Linux).

### 1. Run

```bash
cd "/home/keneth/Downloads/web project/app"
./start.sh
```

The script starts a private MySQL instance (port 3307) then the PHP server.
Open **http://localhost:8000** in your browser. Stop with **Ctrl+C**.

> Requires XAMPP (`/opt/lampp`) which is already installed on this machine,
> and `gd` enabled (it is).

### 2. Log in

Demo accounts (password is **`password123`**):

| Email | Role |
|-------|------|
| `admin@school.edu` | Administrator |
| `registrar@school.edu` | Registrar |
| `idstaff@school.edu` | ID Staff |
| `guard@school.edu` | Security Guard |

---

## Windows (XAMPP for Windows)

The database layer (.env, migrations, installer) is fully cross-platform. On
Windows, use Microsoft's `php.exe` from XAMPP instead of `start.sh`.

### Install on a new Windows machine

1. Install **XAMPP for Windows** (e.g. `C:\xampp`), open the **XAMPP Control
   Panel**, and start **MySQL**.
2. Copy this project folder anywhere (e.g. `C:\xampp\htdocs\school-id`).
3. Double-click **`install.bat`** — or run from cmd:
   ```bat
   C:\xampp\php\php.exe install.php
   ```
   It detects `127.0.0.1:3306 root / (empty password)` (the XAMPP default),
   creates the database, writes `.env`, and runs the migrations.
   > Stock XAMPP MySQL uses user `root` with no password. If yours has a
   > password, run `install.php --user=root --pass=YOURPASS --yes` instead.

### Run on Windows

Double-click **`start.bat`** (or run `php -S 0.0.0.0:8000 -t public` from the
project folder). It ensures MySQL is up and serves the app at
**http://localhost:8000**. Login accounts are identical to Linux.

> The app uses root-absolute URLs (`/gate.php`, `/assets/...`), so it must be
> served from its `public/` directory via the PHP server — not from an Apache
> virtual host subpath.

---

## Database

- **Name:** `school_id_system`
- **DB config:** `.env` (mirrors Laravel's `DB_*` keys), read by `includes/config.php`
- **User / Pass:** `school` / `school123` (overridable via `.env` or env vars)
- **Data directory:** `database/data/` (includes the live datadir + example data)

### Schema via migrations (Laravel-style)

The database is built and versioned with **numbered migration files** in
`database/migrations/` — the same concept as `php artisan migrate`. A small
runner records each applied migration in a `migrations` tracking table.

Tables: `users`, `students`, `school_ids`, `id_requests`, `gate_logs`,
`notifications`.

#### Run pending migrations

```bash
/opt/lampp/bin/php database/migrate.php
```

Only migrations not yet recorded in the `migrations` table are applied, in
filename order. Idempotent — safe to run repeatedly on the live DB.

#### Check what’s applied / pending

```bash
/opt/lampp/bin/php database/migrate.php --status
```

#### Re-run seed data only

```bash
/opt/lampp/bin/php database/migrate.php --seed
```

> `password123` hashes live in the seed migration
> (`..._000007_seed_base_data.sql`) and must not change, or login breaks.

### Rebuild from scratch (disposable database only)

The legacy `database/schema.sql` is kept for reference and documents the
original manual import. For a clean, disposable DB:

```bash
# stop the app first (Ctrl+C), then:
rm -rf database/data
# restart ./start.sh, then run the migrations:
/opt/lampp/bin/php database/migrate.php --fresh
```

> `--fresh` **drops all tables** in the database before re-creating them. Use
> it only on a disposable DB — never on the live one.

---

## Project Structure

```
app/
├── start.sh                 # Linux launcher (MySQL + PHP)
├── start.bat                # Windows launcher
├── install.php              # one-command setup / installer (cross-platform)
├── install.bat              # Windows installer wrapper
├── includes/
│   ├── config.php           # DB config (.env) + PDO connection
│   ├── auth.php             # login / roles / access control
│   ├── functions.php        # helpers (flash, notify, IDs, etc.)
│   ├── header.php / footer.php  # shared layout
├── public/                  # web root served by PHP
│   ├── index.php            # login
│   ├── dashboard.php
│   ├── register.php         # student registration
│   ├── students.php         # manage / approve
│   ├── id_printing.php      # QR ID card + printing
│   ├── gate.php             # gate entry/exit monitoring
│   ├── reports.php          # reports + CSV export
│   ├── notifications.php
│   ├── users.php            # admin user management
│   ├── qr.php               # QR image endpoint
│   └── assets/              # css + js
├── database/
│   ├── migrations/           # numbered migration files (Laravel-style)
│   ├── migrate.php           # migration runner (php migrate.php)
│   ├── schema.sql            # legacy DDL + seed (reference only)
│   └── data/                 # private MariaDB datadir
├── .env                      # env / DB settings (Laravel-style)
└── qr-lib/
    └── phpqrcode.php        # QR library
```

## Using the Gate (QR)

1. Log in as `guard@school.edu` (or admin).
2. Open **Gate Monitoring**. Grant camera permission to scan QR cards live,
   or type the ID number / QR value manually.
3. A valid, active card shows a **green ✓** and logs entry/exit (entry and exit
   alternate per student). Invalid / expired / blocked cards show a red/amber ✗.

The sample cards you can test: `2023-0001` (Juan Dela Cruz), `2023-0002`
(Maria Santos), `2025-0005` (Luis Mendoza) — print them from **ID Printing**.

## Troubleshooting

- **Port 3307 busy** — change `PORT` at the top of `start.sh` and
  `define('DB_PORT', ...)` in `includes/config.php` to match.
- **QR shows broken image** — ensure `gd` is loaded (`php -m | grep gd`).
- **Camera doesn't scan** — the browser must support `BarcodeDetector`
  (Chrome/Edge on desktop). Manual entry always works.
