# UniCare — Hospital Appointment & Records System

A full-stack, role-based clinic management web app built with **Laravel**, **MySQL**, and **Tailwind CSS**, fully containerized with **Docker**. Patients book appointments against doctors' real availability, doctors run their schedules and document visits, and admins manage the whole user base, all in one system.

> Built as a team project for COMP 016 – Web Development.

---

## Highlights

- **Three role-based portals** (Patient, Doctor, Admin), each with its own dashboard, navigation, and permissions
- **Bitmask-based scheduling engine** that stores availability and checks for booking conflicts with single bitwise operations. It is **up to 28× faster** than checking time ranges for overlap, as [benchmarked](#benchmark) below
- **Clinical documentation workflow**: doctors record vitals, diagnoses, notes, and prescriptions per appointment
- **Guided onboarding** that blocks new doctors and patients from the rest of the app until their profile is complete
- **Normalized relational schema** with foreign keys, cascading deletes, UUIDs for public IDs, and JSON columns for flexible medical data
- **One-command dev environment** using Docker Compose (PHP-FPM, Nginx, MySQL)

---

## Features

### Patients
- Register, log in, and complete a health profile (blood type, height/weight, allergies, chronic conditions, emergency contact)
- Browse doctors and their specialties, and see only the time slots that are actually open
- Book **in-person** or **telemedicine** appointments, and cancel them
- View their medical history: diagnoses, vitals, and prescriptions from past visits

### Doctors
- Onboarding flow to set specialty, license number, consultation fee, and appointment length
- Visual **weekly schedule builder** for setting availability per weekday
- Patient list with upcoming and past appointments
- Confirm or cancel bookings, then record the **encounter** (vitals, chief complaint, diagnosis, notes, prescriptions) when the visit happens

### Admins
- View and manage every user account
- Edit user details, change roles, and activate or deactivate accounts

---

## Technical Deep Dive: Bitmask Scheduling

Scheduling is the hardest part of an appointment system. Instead of storing and comparing rows of time ranges, UniCare represents a doctor's day as a **64-bit integer**, where each bit is a 15-minute slot:

```
bit 0  = 08:00   bit 1  = 08:15   bit 2  = 08:30   ...   bit 35 = 16:45
```

- A doctor's weekly availability is stored as **one integer per weekday** (`doctor_schedules.slot_mask`).
- Each appointment stores the bits it occupies (`appointments.slot_mask`).
- Finding open slots is one expression:

  ```php
  $availableMask = $scheduleMask & ~$bookedMask;
  ```

- Checking whether a requested time is free is another:

  ```php
  ($availableMask & $requestedMask) === $requestedMask
  ```

This makes availability lookups and conflict detection constant-time, keeps the schedule table tiny (7 rows per doctor), and supports any appointment length that's a multiple of 15 minutes (for example, 30- or 45-minute consultations).

### Benchmark

[`benchmarks/scheduling.php`](benchmarks/scheduling.php) compares the bitmask approach with the traditional approach of storing time ranges and checking every booking for overlap. It generates the same random schedules and bookings for both, **checks that both return identical results**, and then times them. It has no dependencies:

```bash
php benchmarks/scheduling.php            # options: --doctors=50 --rounds=9 --seed=42 --markdown
```

Results on PHP 8.3 (median of 9 rounds):

**Building the weekly availability page** (50 doctors × 7 days, mixed 15/30/45/60-minute appointments)

| Occupancy  | Interval overlap | Bitmask   | Speedup   |
| ---------- | ---------------- | --------- | --------- |
| 25% booked | 805.69 µs        | 138.67 µs | **5.8×**  |
| 50% booked | 1.15 ms          | 158.22 µs | **7.2×**  |
| 90% booked | 1.50 ms          | 140.33 µs | **10.7×** |

**Listing free slots for one doctor-day as bookings increase** (15-minute slots, full-day schedule)

| Bookings | Interval overlap | Bitmask | Speedup   |
| -------- | ---------------- | ------- | --------- |
| 0        | 3.44 µs          | 700 ns  | **4.9×**  |
| 9        | 9.66 µs          | 688 ns  | **14.0×** |
| 18       | 15.00 µs         | 730 ns  | **20.6×** |
| 27       | 20.85 µs         | 921 ns  | **22.6×** |
| 36       | 21.37 µs         | 746 ns  | **28.6×** |

The interval approach checks every candidate slot against every booking, so its cost grows with *slots × bookings*. The bitmask approach merges all bookings into one integer and then tests each slot with a single AND, so its cost stays almost flat. **The busier a doctor's day, the bigger the advantage.**

Validating a single booking request is already cheap with either approach: 203 ns with interval overlap vs. 106 ns with the bitmask (**1.9×**).

---

## Architecture

```
┌──────────┐     ┌───────────────┐     ┌──────────────────┐     ┌──────────┐
│ Browser  │ ──▶ │ Nginx (:8000) │ ──▶ │ Laravel/PHP-FPM  │ ──▶ │ MySQL 8  │
└──────────┘     └───────────────┘     └──────────────────┘     └──────────┘
                                         │
                                         ├─ CheckAuth middleware
                                         │    (session auth, role gating, onboarding)
                                         ├─ Patient / Doctor / Admin controllers
                                         └─ Blade + Tailwind + Alpine.js views
```

**Access control:** a custom `CheckAuth` middleware protects each route group by role (`checkauth:patient`, `checkauth:doctor`, `checkauth:admin`). It sends users to their own dashboard if they try to open another role's pages, and makes them finish onboarding before they can use the rest of the app.

**Data model:**

| Table              | Purpose                                                              |
| ------------------ | -------------------------------------------------------------------- |
| `users`            | All accounts, with a `role` of patient, doctor, or admin             |
| `patient_profiles` | Health info; allergies and conditions stored as JSON arrays          |
| `doctor_profiles`  | Specialty, license, fee, and slot duration                           |
| `specialties`      | Medical specialties (Cardiology, Dermatology, …)                     |
| `doctor_schedules` | Weekly availability, one bitmask per weekday                         |
| `appointments`     | Bookings with status lifecycle and the bitmask of occupied slots     |
| `medical_records`  | One per visit: diagnosis, notes, vitals snapshot (JSON), attachments |
| `prescriptions`    | Multiple per medical record, with dosage, frequency, and validity    |

Records that are referenced in URLs use **UUIDs** instead of auto-increment IDs, so IDs can't be guessed, and multi-step writes (onboarding, encounters) are wrapped in **database transactions**.

---

## Tech Stack

| Layer          | Technology                              |
| -------------- | --------------------------------------- |
| Backend        | PHP, Laravel                            |
| Frontend       | Blade, Tailwind CSS 4, Alpine.js, Vite  |
| Database       | MySQL 8.0                               |
| Infrastructure | Docker Compose, Nginx, PHP-FPM          |

---

## Running Locally

**Requirements:** Docker Desktop, Git, Node.js

```bash
git clone https://github.com/Xuinnz/webdev-project.git
cd webdev-project
cp .env.example .env
```

In `.env`, set `DB_HOST=db` and choose a `DB_PASSWORD`. Then:

```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < schema.sql
docker compose exec app php artisan db:seed --class=DoctorViewsTestSeeder
npm install && npm run build
```

Open **http://localhost:8000** and sign in with a demo account (password: `password`):

| Role    | Email                 |
| ------- | --------------------- |
| Doctor  | `doctor@example.com`  |
| Patient | `patient@example.com` |

To browse the database with a GUI client, connect to `127.0.0.1:43306` as `root` with your `DB_PASSWORD`.
