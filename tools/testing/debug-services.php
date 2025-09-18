<?php
/**
 * Script temporar pentru debug servicii
 */

// Încarcă WordPress
require_once '../../../wp-load.php';

echo "<h1>DEBUG SERVICII</h1>";

// Verifică dacă tabelul serviciilor există
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_services';

echo "<h2>1. Verificare tabel servicii</h2>";
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
echo "Tabel servicii există: " . ($table_exists ? 'DA' : 'NU') . "<br>";

if ($table_exists) {
    // Verifică structura tabelului
    echo "<h3>Structura tabelului:</h3>";
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Verifică serviciile
    echo "<h3>Servicii din baza de date:</h3>";
    $services = $wpdb->get_results("SELECT * FROM {$table_name}");
    echo "Număr servicii găsite: " . count($services) . "<br>";
    echo "<pre>";
    print_r($services);
    echo "</pre>";
    
    // Verifică dacă există servicii cu durata 0 sau NULL
    echo "<h3>Servicii cu durata 0 sau NULL:</h3>";
    $services_without_duration = $wpdb->get_results("SELECT * FROM {$table_name} WHERE duration IS NULL OR duration = 0");
    echo "Număr servicii fără durată: " . count($services_without_duration) . "<br>";
    if (!empty($services_without_duration)) {
        echo "<pre>";
        print_r($services_without_duration);
        echo "</pre>";
    }
} else {
    echo "<p style='color: red;'>TABELUL SERVICII NU EXISTĂ!</p>";
}

// Verifică dacă există alocări doctor-serviciu
echo "<h2>2. Verificare alocări doctor-serviciu</h2>";
$allocations_table = $wpdb->prefix . 'clinica_doctor_services';
$allocations_exist = $wpdb->get_var("SHOW TABLES LIKE '{$allocations_table}'");

if ($allocations_exist) {
    echo "Tabel alocări există: DA<br>";
    
    $allocations = $wpdb->get_results("SELECT * FROM {$allocations_table}");
    echo "Număr alocări găsite: " . count($allocations) . "<br>";
    
    if (!empty($allocations)) {
        echo "<h3>Alocări existente:</h3>";
        echo "<pre>";
        print_r($allocations);
        echo "</pre>";
        
        // Verifică alocările pentru primul doctor
        if (!empty($allocations)) {
            $first_doctor = $allocations[0]->doctor_id;
            echo "<h3>Alocări pentru doctorul {$first_doctor}:</h3>";
            $doctor_allocations = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$allocations_table} WHERE doctor_id = %d",
                $first_doctor
            ));
            echo "<pre>";
            print_r($doctor_allocations);
            echo "</pre>";
        }
    }
} else {
    echo "<p style='color: red;'>TABELUL ALOCHERI NU EXISTĂ!</p>";
}

// Verifică dacă există doctori
echo "<h2>3. Verificare doctori</h2>";
$doctors = get_users(array('role__in' => array('clinica_doctor', 'clinica_manager')));
echo "Număr doctori găsiți: " . count($doctors) . "<br>";

if (!empty($doctors)) {
    echo "<h3>Primii 3 doctori:</h3>";
    echo "<pre>";
    $first_doctors = array_slice($doctors, 0, 3);
    foreach ($first_doctors as $doctor) {
        echo "ID: {$doctor->ID}, Nume: {$doctor->display_name}, Rol: " . implode(', ', $doctor->roles) . "\n";
    }
    echo "</pre>";
}

// Testează metoda get_all_services din pagina de timeslots
echo "<h2>4. Test metoda get_all_services</h2>";
if (class_exists('Clinica_Doctor_Timeslots_Admin')) {
    echo "Clasa Clinica_Doctor_Timeslots_Admin există<br>";
    
    // Creează o instanță temporară
    $reflection = new ReflectionClass('Clinica_Doctor_Timeslots_Admin');
    $method = $reflection->getMethod('get_all_services');
    $method->setAccessible(true);
    
    $admin_instance = new Clinica_Doctor_Timeslots_Admin();
    $services_from_method = $method->invoke($admin_instance);
    
    echo "Servicii din metoda get_all_services: " . count($services_from_method) . "<br>";
    echo "<pre>";
    print_r($services_from_method);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Clasa Clinica_Doctor_Timeslots_Admin NU EXISTĂ!</p>";
}

echo "<hr>";
echo "<p>Debug completat la: " . date('Y-m-d H:i:s') . "</p>";
?>
