-- Script pentru crearea tabelului wp_clinica_settings
-- Clinică Medicală

CREATE TABLE IF NOT EXISTS `wp_clinica_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'string',
  `setting_group` varchar(100) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserare setări implicite
INSERT IGNORE INTO `wp_clinica_settings` (`setting_key`, `setting_value`, `setting_type`, `setting_group`, `is_public`) VALUES
-- Informații clinică
('clinic_name', 'Clinică Medicală', 'string', 'clinic', 1),
('clinic_address', 'Strada Exemplu, Nr. 123, București', 'string', 'clinic', 1),
('clinic_phone', '+40 21 123 4567', 'string', 'clinic', 1),
('clinic_email', 'contact@clinica.ro', 'string', 'clinic', 1),
('clinic_website', 'https://clinica.ro', 'string', 'clinic', 1),
('clinic_logo', '', 'string', 'clinic', 1),
('working_hours', 'Luni-Vineri: 8:00-18:00, Sâmbătă: 8:00-14:00', 'string', 'clinic', 1),

-- Configurare email
('email_from_name', 'Clinică Medicală', 'string', 'email', 0),
('email_from_address', 'noreply@clinica.ro', 'string', 'email', 0),
('email_smtp_host', 'smtp.gmail.com', 'string', 'email', 0),
('email_smtp_port', '587', 'string', 'email', 0),
('email_smtp_username', '', 'string', 'email', 0),
('email_smtp_password', '', 'string', 'email', 0),
('email_smtp_encryption', 'tls', 'string', 'email', 0),

-- Configurare programări
('appointment_duration', '30', 'integer', 'appointments', 0),
('appointment_interval', '15', 'integer', 'appointments', 0),
('appointment_advance_days', '30', 'integer', 'appointments', 0),

-- Configurare notificări
('notifications_enabled', '1', 'boolean', 'notifications', 0),
('reminder_days', '1,3', 'string', 'notifications', 0),
('confirmation_required', '1', 'boolean', 'notifications', 0),

-- Configurare securitate
('session_timeout', '3600', 'integer', 'security', 0),
('login_attempts', '5', 'integer', 'security', 0),
('lockout_duration', '900', 'integer', 'security', 0),

-- Configurare interfață
('items_per_page', '20', 'integer', 'interface', 0),
('cache_enabled', '1', 'boolean', 'interface', 0),
('auto_refresh', '0', 'boolean', 'interface', 0); 