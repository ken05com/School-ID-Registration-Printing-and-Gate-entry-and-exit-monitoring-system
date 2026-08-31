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
own user — **no root/sudo or system service required**.

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

## Database

- **Name:** `school_id_system`
- **User / Pass:** `school` / `school123` (defined in `includes/config.php`, overridable by env vars `SCHOOL_DB_*`)
- **Data directory:** `database/data/` (includes the live datadir + example data)

Tables: `users`, `students`, `school_ids`, `id_requests`, `gate_logs`, `notifications`.

To rebuild from scratch:

```bash
# stop the app first (Ctrl+C), then:
rm -rf database/data
# restart ./start.sh, then import:
/opt/lampp/bin/mysql --no-defaults --socket=database/mysql.sock \
  -u school -pschool123 < database/schema.sql
```

> `password123` hashes must match `schema.sql`. If the DB was rebuilt with a
> different hash, drop it and re-import the file (it contains the correct hash).

---

## Project Structure

```
app/
├── start.sh                 # one-command launcher (MySQL + PHP)
├── includes/
│   ├── config.php           # DB config + PDO connection
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
│   ├── schema.sql           # DDL + seed data
│   └── data/                # private MariaDB datadir
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
