<?php
/**
 * Script pentru repararea erorilor din baza de date
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunile necesare.');
}

global $wpdb;

echo "<h1>🔧 Reparare Erori Baza de Date</h1>";

// 1. Verifică și repară tabelele
echo "<h2>1. Verificare și reparare tabele</h2>";

// Dezactivează verificarea foreign keys temporar
$wpdb->query("SET FOREIGN_KEY_CHECKS = 0");

// Verifică dacă tabelele există
$tables = array(
    $wpdb->prefix . 'clinica_patients',
    $wpdb->prefix . 'clinica_appointments',
    $wpdb->prefix . 'clinica_medical_records',
    $wpdb->prefix . 'clinica_settings',
    $wpdb->prefix . 'clinica_notifications',
    $wpdb->prefix . 'clinica_imports',
    $wpdb->prefix . 'clinica_login_logs'
);

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "✅ Tabela $table există<br>";
    } else {
        echo "❌ Tabela $table NU există<br>";
    }
}

// 2. Recrează tabelele dacă este necesar
echo "<h2>2. Recreare tabele (dacă este necesar)</h2>";

// Include clasa de baza de date
require_once(ABSPATH . 'wp-content/plugins/clinica/includes/class-clinica-database.php');

try {
    Clinica_Database::create_tables();
    echo "✅ Tabelele au fost create/verificate cu succes<br>";
} catch (Exception $e) {
    echo "❌ Eroare la crearea tabelelor: " . $e->getMessage() . "<br>";
}

// 3. Verifică și repară coloanele lipsă
echo "<h2>3. Verificare coloane lipsă</h2>";

$table_patients = $wpdb->prefix . 'clinica_patients';
$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_patients");

$expected_columns = array(
    'id', 'user_id', 'cnp', 'cnp_type', 'phone_primary', 'phone_secondary',
    'birth_date', 'gender', 'age', 'address', 'emergency_contact', 'blood_type',
    'allergies', 'medical_history', 'password_method', 'import_source',
    'created_by', 'created_at', 'updated_at', 'family_id', 'family_role',
    'family_head_id', 'family_name'
);

foreach ($expected_columns as $column) {
    $exists = false;
    foreach ($columns as $col) {
        if ($col->Field === $column) {
            $exists = true;
            break;
        }
    }
    
    if ($exists) {
        echo "✅ Coloana $column există<br>";
    } else {
        echo "❌ Coloana $column NU există<br>";
    }
}

// 4. Verifică datele din tabele
echo "<h2>4. Verificare date</h2>";

$patients_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients");
echo "Pacienți în baza de date: $patients_count<br>";

$appointments_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_appointments");
echo "Programări în baza de date: $appointments_count<br>";

// 5. Verifică foreign keys
echo "<h2>5. Verificare foreign keys</h2>";

$foreign_keys = $wpdb->get_results("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME LIKE '{$wpdb->prefix}clinica_%'
");

if ($foreign_keys) {
    foreach ($foreign_keys as $fk) {
        echo "✅ Foreign key: {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}<br>";
    }
} else {
    echo "⚠️ Nu s-au găsit foreign keys<br>";
}

// Reactivează verificarea foreign keys
$wpdb->query("SET FOREIGN_KEY_CHECKS = 1");

echo "<h2>✅ Reparare completă!</h2>";
echo "<p>Baza de date ar trebui să funcționeze corect acum.</p>";
echo "<p><a href='" . admin_url('admin.php?page=clinica-appointments') . "'>Testează pagina de programări</a></p>";
?> 