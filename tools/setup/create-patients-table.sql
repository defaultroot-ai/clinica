-- Script pentru crearea tabelului de pacienți și date de test
-- Clinică Medicală

-- Tabel pentru pacienți
CREATE TABLE IF NOT EXISTS `wp_clinica_patients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `cnp` VARCHAR(13) UNIQUE NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) NULL,
    `birth_date` DATE NULL,
    `address` TEXT NULL,
    `emergency_contact` VARCHAR(255) NULL,
    `medical_history` TEXT NULL,
    `allergies` TEXT NULL,
    `insurance_number` VARCHAR(50) NULL,
    `status` ENUM('active', 'inactive', 'deceased') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cnp` (`cnp`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
);

-- Date de test pentru pacienți
INSERT IGNORE INTO `wp_clinica_patients` 
(`first_name`, `last_name`, `cnp`, `phone`, `email`, `birth_date`, `address`, `status`) VALUES
('Ion', 'Popescu', '1234567890123', '0722123456', 'ion.popescu@email.com', '1985-03-15', 'Strada Primaverii 15, București', 'active'),
('Maria', 'Ionescu', '2345678901234', '0733123456', 'maria.ionescu@email.com', '1990-07-22', 'Bulevardul Libertății 45, București', 'active'),
('Gheorghe', 'Dumitrescu', '3456789012345', '0744123456', 'gheorghe.dumitrescu@email.com', '1978-11-08', 'Strada Florilor 78, București', 'active'),
('Elena', 'Stoica', '4567890123456', '0755123456', 'elena.stoica@email.com', '1992-05-12', 'Aleea Trandafirilor 23, București', 'active'),
('Vasile', 'Marinescu', '5678901234567', '0766123456', 'vasile.marinescu@email.com', '1983-09-30', 'Strada Mărășești 67, București', 'active'),
('Ana', 'Constantinescu', '6789012345678', '0777123456', 'ana.constantinescu@email.com', '1987-12-03', 'Bulevardul Unirii 89, București', 'active'),
('Mihai', 'Radu', '7890123456789', '0788123456', 'mihai.radu@email.com', '1995-01-18', 'Strada Victoriei 34, București', 'active'),
('Carmen', 'Munteanu', '8901234567890', '0799123456', 'carmen.munteanu@email.com', '1989-06-25', 'Aleea Castanilor 56, București', 'active'),
('Alexandru', 'Neagu', '9012345678901', '0700123456', 'alexandru.neagu@email.com', '1981-04-07', 'Strada Republicii 12, București', 'active'),
('Diana', 'Florescu', '0123456789012', '0711123456', 'diana.florescu@email.com', '1993-08-14', 'Bulevardul Decebal 78, București', 'active');

-- Verificare date inserate
SELECT 
    COUNT(*) as total_patients,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_patients
FROM wp_clinica_patients; 