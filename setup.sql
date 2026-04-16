-- ============================================================
--  Rural WiFi BillFlow — setup.sql  (Fresh Install)
--  Run ONLY once in phpMyAdmin → SQL tab.
--  Database: rural_db
--
--  NORMALIZATION NOTES (1NF / 2NF / 3NF):
--  1NF — All columns atomic; no repeating groups.
--  2NF — Every non-key column depends on the WHOLE primary key.
--         (No composite-key partial dependencies found.)
--  3NF — No transitive dependencies.
--         · admins + customers → merged into single `users` table
--           (role='admin'|'customer' differentiates them).
--           Eliminates duplicate columns: username, password_hash,
--           full_name, created_at.
--         · `amount` removed from invoices — it was transitively
--           dependent on plan_id → plans.monthly_fee. Always derive
--           at query time via JOIN on plans.
--         · gcash_settings table eliminated — gcash_number and
--           gcash_name now live as rows in `settings`, alongside
--           SMTP and app config (one table, one place).
-- ============================================================

CREATE DATABASE IF NOT EXISTS rural_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE rural_db;

-- ── PLANS ────────────────────────────────────────────────────
-- Defined first — users.plan_id FK references this table.
-- 3NF: monthly_fee lives here only. Invoices derive cost via JOIN.
CREATE TABLE IF NOT EXISTS plans (
  plan_id     INT           AUTO_INCREMENT PRIMARY KEY,
  plan_name   VARCHAR(80)   NOT NULL UNIQUE,
  monthly_fee DECIMAL(10,2) NOT NULL,
  speed_mbps  INT           DEFAULT NULL,
  is_active   TINYINT(1)    DEFAULT 1
);

INSERT INTO plans (plan_name, monthly_fee, speed_mbps) VALUES
  ('Standard 50Mbps',  899.00,  50),
  ('Premium 100Mbps', 1299.00, 100)
ON DUPLICATE KEY UPDATE
  monthly_fee = VALUES(monthly_fee),
  speed_mbps  = VALUES(speed_mbps);


-- ── USERS (admins + customers unified) ───────────────────────
-- role: 'admin' | 'customer'
-- Customer-specific columns (plan_id, billing_day, status,
-- email, phone, address) are NULL for admin rows — pragmatic
-- single-table approach that avoids unnecessary JOINs at this scale.
-- billing_day: updated to DAY(paid_date) on every accepted payment.
-- status: 'active' | 'suspended' (admin decision only — no auto-term).
CREATE TABLE IF NOT EXISTS users (
  user_id       INT          AUTO_INCREMENT PRIMARY KEY,
  role          ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  username      VARCHAR(60)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(120) NOT NULL,
  -- customer-specific (NULL for admins)
  email         VARCHAR(180) DEFAULT NULL,
  phone         VARCHAR(20)  DEFAULT NULL,
  address       VARCHAR(255) DEFAULT NULL,
  plan_id       INT          DEFAULT NULL,
  billing_day   TINYINT      DEFAULT NULL,
  status        ENUM('active','suspended') DEFAULT NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (plan_id) REFERENCES plans(plan_id)
);

-- ⚠️  Default password is 'password' — CHANGE BEFORE GO-LIVE!
-- Run reset_admin.php after setup to set your real password.
INSERT INTO users (role, username, password_hash, full_name) VALUES
  ('admin', 'admin',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'System Administrator')
ON DUPLICATE KEY UPDATE user_id = user_id;


-- ── INVOICES ─────────────────────────────────────────────────
-- customer_id references users(user_id) where role='customer'.
-- plan_id kept for historical record (customer may change plans).
-- amount REMOVED (3NF) — derive from plans.monthly_fee at query time:
--   SELECT i.*, p.monthly_fee AS amount FROM invoices i
--   JOIN plans p ON i.plan_id = p.plan_id
-- status: unpaid | overdue | paid
-- paid_date: set when proof accepted → next billing_day = DAY(paid_date)
-- payment_method: gcash | cash
CREATE TABLE IF NOT EXISTS invoices (
  invoice_id       INT         AUTO_INCREMENT PRIMARY KEY,
  customer_id      INT         NOT NULL,           -- → users (role='customer')
  plan_id          INT         NOT NULL,
  billing_month    VARCHAR(30) NOT NULL,            -- e.g. "April 2025"
  due_date         DATE        NOT NULL,
  status           ENUM('unpaid','overdue','paid') DEFAULT 'unpaid',
  paid_date        DATE        DEFAULT NULL,
  payment_method   ENUM('gcash','cash')            DEFAULT NULL,
  sent_to_customer TINYINT(1)  DEFAULT 0,
  sent_date        DATE        DEFAULT NULL,
  created_at       TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(user_id)  ON DELETE CASCADE,
  FOREIGN KEY (plan_id)     REFERENCES plans(plan_id)
);


-- ── PAYMENT PROOFS ───────────────────────────────────────────
-- Stores GCash screenshots submitted by customers.
-- reviewed_by references users(user_id) where role='admin'.
-- proof_status: pending | accepted | rejected
CREATE TABLE IF NOT EXISTS payment_proofs (
  proof_id         INT          AUTO_INCREMENT PRIMARY KEY,
  invoice_id       INT          NOT NULL,
  customer_id      INT          NOT NULL,           -- → users (role='customer')
  gcash_ref        VARCHAR(50)  NOT NULL,
  proof_image      VARCHAR(255) DEFAULT NULL,
  proof_status     ENUM('pending','accepted','rejected') DEFAULT 'pending',
  rejection_reason TEXT         DEFAULT NULL,
  submitted_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  reviewed_at      DATETIME     DEFAULT NULL,
  reviewed_by      INT          DEFAULT NULL,       -- → users (role='admin')
  FOREIGN KEY (invoice_id)  REFERENCES invoices(invoice_id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES users(user_id)       ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(user_id)       ON DELETE SET NULL
);


-- ── MESSAGES ─────────────────────────────────────────────────
-- All messages scoped to a customer (user_id) conversation thread.
-- sender_id references users(user_id) — either role may send.
CREATE TABLE IF NOT EXISTS messages (
  message_id   INT  AUTO_INCREMENT PRIMARY KEY,
  customer_id  INT  NOT NULL,                       -- thread owner → users (customer)
  sender_type  ENUM('admin','customer') NOT NULL,
  sender_id    INT  NOT NULL,                       -- → users (whoever sent it)
  message_text TEXT NOT NULL,
  is_read      TINYINT(1) DEFAULT 0,
  sent_at      TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id)   REFERENCES users(user_id) ON DELETE CASCADE
);


-- ── NOTIFICATIONS ────────────────────────────────────────────
-- recipient_id references users(user_id) — either role.
-- invoice_id is nullable (not all notifications are invoice-related).
CREATE TABLE IF NOT EXISTS notifications (
  notif_id       INT          AUTO_INCREMENT PRIMARY KEY,
  recipient_type ENUM('admin','customer') NOT NULL,
  recipient_id   INT          NOT NULL,             -- → users
  type           VARCHAR(60)  NOT NULL,
  title          VARCHAR(200) NOT NULL,
  message        TEXT         NOT NULL,
  invoice_id     INT          DEFAULT NULL,         -- soft ref → invoices
  is_read        TINYINT(1)   DEFAULT 0,
  is_dismissed   TINYINT(1)   DEFAULT 0,
  created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (recipient_id) REFERENCES users(user_id)       ON DELETE CASCADE,
  FOREIGN KEY (invoice_id)   REFERENCES invoices(invoice_id) ON DELETE SET NULL
);


-- ── SETTINGS (SMTP + GCASH + APP unified) ────────────────────
-- 3NF: one config table for everything — no separate gcash_settings.
-- Keys: gcash_number, gcash_name, smtp_*, from_*, app_url.
CREATE TABLE IF NOT EXISTS settings (
  id            INT         AUTO_INCREMENT PRIMARY KEY,
  setting_key   VARCHAR(80) NOT NULL UNIQUE,
  setting_value TEXT        DEFAULT NULL,
  updated_at    TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  -- GCash (previously separate gcash_settings table)
  ('gcash_number',    '09811062389'),
  ('gcash_name',      'Jeferson Castos'),
  -- SMTP / Email
  ('smtp_host',       'smtp.gmail.com'),
  ('smtp_port',       '587'),
  ('smtp_username',   ''),
  ('smtp_password',   ''),
  ('smtp_encryption', 'tls'),
  ('from_email',      ''),
  ('from_name',       'Rural WiFi'),
  -- App / Portal
  ('app_url',         '');
