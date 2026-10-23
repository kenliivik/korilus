-- Korilus login, groups and spot sync (MariaDB). Applied by: php api/migrate.php (safe to run again).
-- Times are Unix seconds, except spots.updated_at which is milliseconds from the phone that last changed it.

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(60) NOT NULL DEFAULT '',
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at INT UNSIGNED NOT NULL
) DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS login_codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  code_hash CHAR(64) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  expires_at INT UNSIGNED NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  INDEX (email),
  INDEX (ip)
) DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS sessions (
  token_hash CHAR(64) PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  expires_at INT UNSIGNED NOT NULL,
  INDEX (user_id)
) DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `groups` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL,
  created_at INT UNSIGNED NOT NULL
) DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS group_members (
  group_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role VARCHAR(10) NOT NULL DEFAULT 'member', -- owner | member
  created_at INT UNSIGNED NOT NULL,
  PRIMARY KEY (group_id, user_id),
  INDEX (user_id)
) DEFAULT CHARSET = utf8mb4;

-- Someone without an account who was invited, to the app (group_id NULL) or into a group.
CREATE TABLE IF NOT EXISTS invites (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  group_id INT UNSIGNED NULL,
  invited_by INT UNSIGNED NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  INDEX (email),
  INDEX (group_id)
) DEFAULT CHARSET = utf8mb4;

-- data = the spot as the app keeps it (name, lat, lng, visits). group_id NULL = private. Deleted spots stay as empty tombstones.
CREATE TABLE IF NOT EXISTS spots (
  id VARCHAR(64) PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  group_id INT UNSIGNED NULL,
  data MEDIUMTEXT NOT NULL,
  updated_at BIGINT UNSIGNED NOT NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  INDEX (user_id),
  INDEX (group_id)
) DEFAULT CHARSET = utf8mb4;

-- Ideas and feedback sent from Seaded → "Saada tagasisidet". Works without login; user_id is set when logged in.
CREATE TABLE IF NOT EXISTS feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message TEXT NOT NULL,
  email VARCHAR(190) NOT NULL DEFAULT '',
  user_id INT UNSIGNED NULL,
  ip VARCHAR(45) NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  done TINYINT(1) NOT NULL DEFAULT 0,
  INDEX (ip),
  INDEX (user_id)
) DEFAULT CHARSET = utf8mb4;

-- People who know each other because one invited the other (both directions), so they can add each other to groups without typing an email.
CREATE TABLE IF NOT EXISTS contacts (
  user_id INT UNSIGNED NOT NULL,
  contact_id INT UNSIGNED NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, contact_id),
  INDEX (contact_id)
) DEFAULT CHARSET = utf8mb4;

-- Invitation emails sent, so the daily limits hold even when an invite is withdrawn and sent again.
CREATE TABLE IF NOT EXISTS mail_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  email VARCHAR(190) NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  INDEX (user_id),
  INDEX (email)
) DEFAULT CHARSET = utf8mb4;
