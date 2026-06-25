-- ============================================================
--  HOSPITAL APPOINTMENT + RECORDS SYSTEM
--  Database Schema
-- ============================================================
--  UUID usage:
--    users              → uuid (public-facing: profile URLs, auth tokens)
--    appointments       → uuid (public-facing: booking links, calendar)
--    medical_records    → uuid (public-facing: shareable record links)
--    prescriptions      → uuid (public-facing: prescription printouts)
--    conversations      → uuid (public-facing: chat URLs)
--    messages           → BIGINT only (internal, high-volume, never exposed)
--    *_profiles         → BIGINT only (internal, always accessed via user)
--    audit_logs         → BIGINT only (internal, admin only)
-- ============================================================


-- ============================================================
--  USERS
--  Users can be either patient, doctor, admin
-- ============================================================
CREATE TABLE users (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                CHAR(36) NOT NULL UNIQUE DEFAULT (UUID()),
    name                VARCHAR(255) NOT NULL,
    email               VARCHAR(255) NOT NULL UNIQUE,
    password            VARCHAR(255) NOT NULL,
    role                ENUM('patient', 'doctor', 'admin') NOT NULL,
    phone               VARCHAR(20) NULL,
    gender              ENUM('male', 'female', 'other') NULL,
    avatar_url          VARCHAR(500) NULL,
    is_active           BOOLEAN NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- ============================================================
--  Specialties
--  1 to 1 used by doctors
-- ============================================================
CREATE TABLE specialties (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,  -- "Cardiology", "Dermatology"
    description TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  DOCTOR PROFILES
--  One-to-one with users where role = 'doctor'
-- ============================================================
CREATE TABLE doctor_profiles (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL UNIQUE,
    specialty_id        BIGINT UNSIGNED NOT NULL,
    license_number      VARCHAR(100) NULL,
    bio                 TEXT NULL,
    consultation_fee    DECIMAL(10, 2) NULL,
    slot_duration_minutes TINYINT UNSIGNED NOT NULL DEFAULT 30;
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (specialty_id) REFERENCES specialties(id)
);


-- ============================================================
--  PATIENT PROFILES
--  One-to-one with users where role = 'patient'
-- ============================================================
CREATE TABLE patient_profiles (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                     BIGINT UNSIGNED NOT NULL UNIQUE,
    date_of_birth               DATE NULL,
    blood_type                  ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
    height_cm                   DECIMAL(5, 2) NULL,
    weight_kg                   DECIMAL(5, 2) NULL,

    -- JSON arrays: ["Penicillin", "Shellfish"]
    allergies                   JSON NULL,
    -- JSON arrays: ["Type 2 Diabetes", "Hypertension"]
    chronic_conditions          JSON NULL,

    emergency_contact_name      VARCHAR(255) NULL,
    emergency_contact_phone     VARCHAR(20) NULL,
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- ============================================================
--  DOCTOR SCHEDULES
--  One doctor = One Schedule
--  Weekly availability template per doctor
--  slot_mask: BIGINT bitboard
--    bit 0  = 08:00 AM
--    bit 1  = 08:15 AM
--    ...
--    bit 35 = 04:45 PM
--  A set bit = that slot is available
-- ============================================================
CREATE TABLE doctor_schedules (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_id   BIGINT UNSIGNED NOT NULL,
    weekday     TINYINT NOT NULL,           -- 0 = Monday, 6 = Sunday
    slot_mask   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_doctor_weekday (doctor_id, weekday),
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
);



-- ============================================================
--  APPOINTMENTS
--  slot_mask: bits representing which 15-min slots this booking occupies
--  Collision check: (existing.slot_mask & new.slot_mask) != 0 → conflict
--  start_time + end_time kept for display and reporting
-- ============================================================
CREATE TABLE appointments (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                CHAR(36) NOT NULL UNIQUE DEFAULT (UUID()),
    patient_id          BIGINT UNSIGNED NOT NULL,
    doctor_id           BIGINT UNSIGNED NOT NULL,
    appointment_date    DATE NOT NULL,
    start_time          TIME NOT NULL,
    end_time            TIME NOT NULL,
    slot_mask           BIGINT UNSIGNED NOT NULL,
    status              ENUM('pending','confirmed','completed','cancelled','no_show')
                            NOT NULL DEFAULT 'pending',
    type                ENUM('in_person', 'telemedicine') NOT NULL DEFAULT 'in_person',
    reason              TEXT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id)  REFERENCES users(id)
);


-- ============================================================
--  MEDICAL RECORDS
--  Created during or after an appointment encounter
--  One appointment = One medical record
--  vitals stored as JSON snapshot (time-series per visit)
--  appointment_id nullable to support walk-ins / emergency entries
-- ============================================================
CREATE TABLE medical_records (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36) NOT NULL UNIQUE DEFAULT (UUID()),
    patient_id      BIGINT UNSIGNED NOT NULL,
    doctor_id       BIGINT UNSIGNED NOT NULL,
    appointment_id  BIGINT UNSIGNED NULL UNIQUE,
    record_date     DATE NOT NULL,
    chief_complaint TEXT NULL,
    diagnosis       TEXT NULL,
    notes           TEXT NULL,

    -- Snapshot of vitals at time of visit: { "bp": "120/80", "hr": 72, "temp_c": 36.6, "weight_kg": 70 }
    vitals          JSON NULL,

    -- [{ "label": "Chest X-Ray", "url": "...", "mime_type": "image/jpeg" }]
    attachments     JSON NULL,

    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id)     REFERENCES users(id),
    FOREIGN KEY (doctor_id)      REFERENCES users(id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);


-- ============================================================
--  PRESCRIPTIONS
--  1 drug = 1 prescription
--  1 medical record = can be multiple prescriptions
-- ============================================================
CREATE TABLE prescriptions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                CHAR(36) NOT NULL UNIQUE DEFAULT (UUID()),
    medical_record_id   BIGINT UNSIGNED NOT NULL,
    patient_id          BIGINT UNSIGNED NOT NULL,    -- denormalized
    doctor_id           BIGINT UNSIGNED NOT NULL,    -- denormalized
    drug_name           VARCHAR(255) NOT NULL,
    dosage              VARCHAR(100) NOT NULL,        -- "500mg"
    frequency           VARCHAR(100) NOT NULL,        -- "3x daily"
    duration            VARCHAR(100) NULL,            -- "7 days"
    instructions        TEXT NULL,                   -- "Take with food"
    valid_until         DATE NULL,
    status              ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    issued_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id)        REFERENCES users(id),
    FOREIGN KEY (doctor_id)         REFERENCES users(id)
);


-- ============================================================
--  CONVERSATIONS
--  One row per unique pair of participants
--  participant_a always holds the lower user_id (enforced in app layer)
--  last message will be handled by app layer
-- ============================================================
CREATE TABLE conversations (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                CHAR(36) NOT NULL UNIQUE DEFAULT (UUID()),
    participant_a       BIGINT UNSIGNED NOT NULL,   -- lower user_id
    participant_b       BIGINT UNSIGNED NOT NULL,   -- higher user_id

    -- Denormalized for inbox
    last_message_id     BIGINT UNSIGNED NULL,
    last_message_at     TIMESTAMP NULL,
    last_message_body   VARCHAR(255) NULL,          -- truncated preview

    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_participants (participant_a, participant_b),
    FOREIGN KEY (participant_a) REFERENCES users(id),
    FOREIGN KEY (participant_b) REFERENCES users(id)
);


-- ============================================================
--  MESSAGES
--  last_message_id in conversations references this table but not actually FK
-- ============================================================
CREATE TABLE messages (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id     BIGINT UNSIGNED NOT NULL,
    sender_id           BIGINT UNSIGNED NOT NULL,
    body                TEXT NOT NULL,
    is_read             BOOLEAN NOT NULL DEFAULT FALSE,
    read_at             TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id)       REFERENCES users(id)
);

-- -- ============================================================
-- --  AUDIT LOGS
-- --  Commented out kasi di pa naman kailangan
-- --  Admin dashboard
-- --  Written by Laravel Model Observers — not DB triggers
-- --  target_type follows Laravel morph convention (model class name)
-- -- ============================================================
-- CREATE TABLE audit_logs (
--     id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     user_id     BIGINT UNSIGNED NULL,           -- NULL = system action
--     action      VARCHAR(100) NOT NULL,          -- e.g. 'appointment.cancelled'
--     target_type VARCHAR(100) NULL,              -- e.g. 'App\Models\Appointment'
--     target_id   BIGINT UNSIGNED NULL,
--     payload     JSON NULL,                      -- before/after snapshot
--     ip_address  VARCHAR(45) NULL,
--     created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
-- );