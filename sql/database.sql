CREATE DATABASE IF NOT EXISTS gordao_cortes
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE gordao_cortes;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('client','admin') NOT NULL DEFAULT 'client',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS haircuts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
  price DECIMAL(10,2) NOT NULL,
  image_url VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_haircuts_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  haircut_id INT UNSIGNED NOT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  notes VARCHAR(255) NULL,
  status ENUM('scheduled','confirmed','done','cancelled') NOT NULL DEFAULT 'scheduled',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_appointments_date_time (appointment_date, appointment_time),
  KEY idx_appointments_user (user_id),
  KEY idx_appointments_cut (haircut_id),
  CONSTRAINT fk_appointments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_appointments_cut FOREIGN KEY (haircut_id) REFERENCES haircuts (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO users (name, email, password_hash, role, active)
VALUES ('Administrador', 'admin@gordaocortes.com', 'admin123', 'admin', 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  role = VALUES(role),
  active = VALUES(active);

INSERT INTO haircuts (name, description, duration_minutes, price, image_url, active)
VALUES
  ('Corte Degradê', 'Degradê moderno com acabamento na navalha.', 40, 45.00, 'https://images.unsplash.com/photo-1517832606299-7ae9b720a186?auto=format&fit=crop&w=1000&q=80', 1),
  ('Social Clássico', 'Visual limpo e alinhado para qualquer ocasião.', 35, 40.00, 'https://images.unsplash.com/photo-1622286342621-4bd786c2447c?auto=format&fit=crop&w=1000&q=80', 1),
  ('Corte + Barba', 'Pacote completo de corte e barba desenhada.', 60, 70.00, 'https://images.unsplash.com/photo-1599351431402-3ccf5f5d4f4e?auto=format&fit=crop&w=1000&q=80', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);
