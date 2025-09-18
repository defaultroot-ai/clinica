-- Script pentru crearea tabelelor necesare pentru Robotul Telefonic AI
-- Clinică Medicală

-- Tabel pentru identificări AI
CREATE TABLE IF NOT EXISTS `wp_clinica_ai_identifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(255) NOT NULL,
    `patient_id` INT NULL,
    `success` TINYINT(1) DEFAULT 0,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_identifier` (`identifier`),
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_created_at` (`created_at`)
);

-- Tabel pentru apeluri WebRTC
CREATE TABLE IF NOT EXISTS `wp_clinica_webrtc_calls` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `call_id` VARCHAR(255) NOT NULL,
    `patient_id` INT NOT NULL,
    `call_type` ENUM('webrtc', 'transfer') DEFAULT 'webrtc',
    `status` ENUM('active', 'completed', 'failed') DEFAULT 'active',
    `duration` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_call_id` (`call_id`),
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`patient_id`) REFERENCES `wp_clinica_patients`(`id`) ON DELETE CASCADE
);

-- Tabel pentru conversații AI
CREATE TABLE IF NOT EXISTS `wp_clinica_webrtc_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `call_id` VARCHAR(255) NOT NULL,
    `message_type` ENUM('user', 'ai', 'system') DEFAULT 'user',
    `content` TEXT NOT NULL,
    `audio_file` VARCHAR(255) NULL,
    `intention` VARCHAR(50) NULL,
    `confidence` DECIMAL(3,2) NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_call_id` (`call_id`),
    INDEX `idx_message_type` (`message_type`),
    INDEX `idx_timestamp` (`timestamp`)
);

-- Tabel pentru conversații AI avansate
CREATE TABLE IF NOT EXISTS `wp_clinica_ai_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `session_id` VARCHAR(255) NOT NULL,
    `message_type` ENUM('user', 'ai') DEFAULT 'user',
    `content` TEXT NOT NULL,
    `intention` VARCHAR(50) NULL,
    `confidence` DECIMAL(3,2) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`patient_id`) REFERENCES `wp_clinica_patients`(`id`) ON DELETE CASCADE
);

-- Tabel pentru routing decizii
CREATE TABLE IF NOT EXISTS `wp_clinica_ai_routing` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `original_intention` VARCHAR(50) NOT NULL,
    `final_destination` VARCHAR(50) NOT NULL,
    `routing_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_conversation_id` (`conversation_id`),
    INDEX `idx_destination` (`final_destination`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`conversation_id`) REFERENCES `wp_clinica_ai_conversations`(`id`) ON DELETE CASCADE
);

-- Tabel pentru programări AI
CREATE TABLE IF NOT EXISTS `wp_clinica_ai_appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `appointment_type` VARCHAR(50) NOT NULL,
    `suggested_slots` TEXT NULL,
    `confirmed_slot` DATETIME NULL,
    `status` ENUM('suggested', 'confirmed', 'cancelled') DEFAULT 'suggested',
    `ai_confidence` DECIMAL(3,2) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`patient_id`) REFERENCES `wp_clinica_patients`(`id`) ON DELETE CASCADE
);

-- Tabel pentru statistici AI
CREATE TABLE IF NOT EXISTS `wp_clinica_ai_statistics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL,
    `total_calls` INT DEFAULT 0,
    `successful_identifications` INT DEFAULT 0,
    `ai_conversations` INT DEFAULT 0,
    `human_transfers` INT DEFAULT 0,
    `average_call_duration` INT DEFAULT 0,
    `success_rate` DECIMAL(5,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_date` (`date`),
    INDEX `idx_date` (`date`)
);

-- Tabel pentru configurări AI
CREATE TABLE IF NOT EXISTS `wp_clinica_ai_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(100) NOT NULL,
    `config_value` TEXT NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_config_key` (`config_key`)
);

-- Inserare configurări implicite
INSERT IGNORE INTO `wp_clinica_ai_config` (`config_key`, `config_value`, `description`) VALUES
('ai_enabled', '1', 'Robotul AI este activat'),
('ai_greeting_enabled', '1', 'Salutare personalizată activată'),
('ai_appointment_suggestions', '1', 'Sugestii programări activate'),
('ai_automatic_routing', '1', 'Routing automat activat'),
('ai_emergency_transfer', '1', 'Transfer automat pentru cazuri urgente'),
('ai_language', 'ro', 'Limba principală pentru AI'),
('ai_confidence_threshold', '0.7', 'Prag de încredere pentru AI'),
('ai_max_conversation_length', '10', 'Numărul maxim de mesaje într-o conversație'),
('ai_transfer_on_negative_sentiment', '1', 'Transfer automat pentru sentiment negativ'),
('ai_working_hours_start', '08:30', 'Ora de început a programului'),
('ai_working_hours_end', '19:30', 'Ora de sfârșit a programului');

-- Tabel pentru log-uri AI
CREATE TABLE IF NOT EXISTS `wp_clinica_ai_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `log_level` ENUM('info', 'warning', 'error', 'debug') DEFAULT 'info',
    `message` TEXT NOT NULL,
    `context` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_log_level` (`log_level`),
    INDEX `idx_created_at` (`created_at`)
);

-- Comentarii pentru tabele
ALTER TABLE `wp_clinica_ai_identifications` COMMENT = 'Log pentru identificările pacienților prin AI';
ALTER TABLE `wp_clinica_webrtc_calls` COMMENT = 'Apeluri WebRTC pentru robotul AI';
ALTER TABLE `wp_clinica_webrtc_conversations` COMMENT = 'Conversații în timpul apelurilor WebRTC';
ALTER TABLE `wp_clinica_ai_conversations` COMMENT = 'Conversații AI avansate cu pacienții';
ALTER TABLE `wp_clinica_ai_routing` COMMENT = 'Decizii de routing pentru apeluri';
ALTER TABLE `wp_clinica_ai_appointments` COMMENT = 'Programări sugerate de AI';
ALTER TABLE `wp_clinica_ai_statistics` COMMENT = 'Statistici pentru robotul AI';
ALTER TABLE `wp_clinica_ai_config` COMMENT = 'Configurări pentru robotul AI';
ALTER TABLE `wp_clinica_ai_logs` COMMENT = 'Log-uri pentru robotul AI';

-- Verificare și raportare
SELECT 
    'Tabele create cu succes pentru Robotul Telefonic AI' as status,
    COUNT(*) as table_count
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name LIKE 'wp_clinica_ai_%';

-- Afișare tabele create
SHOW TABLES LIKE 'wp_clinica_ai_%'; 