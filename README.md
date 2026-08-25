# Proposed QR-Based Visitor Building Access Control and Monitoring System

**Department:** Legislative Security Bureau | Perimeter Security Group
**Institution:** House of Representatives of the Philippines
**Proposed by:** Migel H. Tan (SMART Internship Program)

📄 **[Read the full proposal paper](./proposed_qr_visitor_access_system.md)**

---

## About This Prototype

This repository is the working software prototype built to accompany the proposal above. It is **not** a finished or production-ready system — it exists to demonstrate, concretely, how the concept described in the paper would actually function: a guard scanning a visitor's QR pass, and the system determining in real time whether that visitor is authorized to enter the specific building where the scan occurred.

As described in the proposal's Introduction, the current visitor access process at the House of Representatives complex relies on physical, color-coded visitor ID cards assigned per building (e.g. a red "VISITOR 0001" card for North Wing), validated manually by security personnel against a physical logbook. This works, but has no centralized way of automatically catching a visitor authorized for one building attempting to enter another.

This prototype implements the paper's core proposed mechanism: each visitor pass is tied to a unique QR token; scanning it at a building's terminal checks that token against a central database and instantly returns **AUTHORIZED** or **UNAUTHORIZED**, while logging every attempt — successful or not — to a centralized audit trail. In line with the proposal, it is meant to _supplement_ existing guards and physical passes, not replace them.

---

## Tech Stack

- **Laravel 11** (PHP 8.4) — backend framework, routing, database ORM
- **MySQL** (via XAMPP) — persistent storage for buildings, passes, and scan logs
- **Blade** — server-rendered views (no separate frontend framework)
- **html5-qrcode** (JS) — webcam-based QR scanning in the browser
- **qrcodejs** (JS) — renders the QR code image client-side from the pass's token
- **Herd** — local PHP server/environment

---

## Folder Structure & What Each One Does

### `app/Http/Controllers/`

The "brains" of each feature area. Each controller handles one part of the system:

- **ScannerController** — powers the guard scanning terminal. Receives a scanned QR token + the guard's building, checks the database, decides AUTHORIZED/UNAUTHORIZED/INVALID, and writes the result to the log.
- **PassController** — handles viewing the pass registry, registering a visitor to an available pass, and generating the printable QR badge view.
- **LogController** — handles viewing, searching/filtering, and CSV-exporting the audit trail of every scan attempt.

### `app/Models/`

Represents the database tables as PHP objects, and defines how they relate to each other:

- **Building** — the 6 HOR buildings (North Wing, South Wing, RVM, North Gate, Main Building, South Wing Annex), each with a color and a printable badge template image.
- **VisitorPass** — an individual physical pass (5 per building, 30 total), holding the visitor's info, status, and unique QR token.
- **ScanLog** — a permanent record of every scan attempt, whether it succeeded or was denied, and why.

### `database/migrations/`

Version-controlled instructions for building/changing the database schema over time (creating tables, adding columns). Running `php artisan migrate` applies these in order. This is how the `buildings`, `visitor_passes`, and `scan_logs` tables were created.

### `database/seeders/`

Scripts that populate the database with starting data instead of you entering it by hand. `DatabaseSeeder.php` auto-creates the 6 buildings (with their colors and badge templates) and generates all 30 passes with their QR tokens. Running `php artisan db:seed` re-applies this safely (it updates existing rows rather than duplicating them).

### `resources/views/`

All the actual HTML pages (as Blade templates), organized by feature — mirrors the controllers:

- `layouts/app.blade.php` — the shared page shell (header, nav bar) every page extends from.
- `scanner/` — the guard's live scanning terminal page.
- `passes/` — the pass registry (grid of all 30 passes) and the individual printable QR badge page.
- `logs/` — the searchable audit log table.

### `public/images/passes/`

Stores the 6 badge template PNGs (one per building, exported from Canva) that get used as the visual background for each printed pass — the QR code and pass number are overlaid on top of these programmatically.

### `public/`

The actual web-facing entry point of the app (`index.php` lives here) — this is the only folder a browser can reach directly. Anything meant to be publicly accessible (like the badge template images) has to live inside `public/`.

### `routes/web.php`

The map connecting URLs to controller actions — e.g. visiting `/passes` runs `PassController@index`, submitting the scan form calls `ScannerController@scan`. If a page or button doesn't do anything, this file is usually where the wiring is checked first.

### `config/`

App-wide settings (database connection defaults, app name, timezone, etc.) — rarely needs manual editing since most of it is controlled via `.env`.

### `.env`

Your local environment's secrets/settings — database credentials, app URL, debug mode. Not shared/committed; this is what makes the app connect to _your_ specific MySQL instance.

### `storage/`

Laravel's internal working directory — logs (`storage/logs/laravel.log` is the first place to check when something breaks), cached views, and file uploads if the app ever handles those.

### `vendor/`

All third-party PHP packages Laravel and Composer installed (e.g. the framework itself). Auto-generated — never edited by hand, and not something you need to touch.

---

## Core Flow (How a Scan Actually Works)

1. A visitor is registered and assigned an available pass under **Passes** → gets a unique QR token like `HOR-20TH-NW-0001-SEC2026`.
2. Guard opens **Scanner**, selects which building entrance they're stationed at.
3. Guard scans the visitor's QR (webcam, USB scanner, or manual paste).
4. `ScannerController@scan` checks: does this token exist? Is the pass active? Does its assigned building match the guard's current station?
5. Result (AUTHORIZED / UNAUTHORIZED / INVALID / EXPIRED / REVOKED) is shown instantly and written to `scan_logs`.
6. **Logs** page shows the full history, searchable and exportable to CSV.

## Common Commands

```powershell
php artisan migrate       # apply database schema changes
php artisan db:seed       # populate/update buildings + passes
php artisan optimize:clear # clear cached config/routes/views (fixes weird stale behavior)
php artisan tinker        # interactive PHP shell to poke at the database directly
php artisan serve          #see the webpage
```
