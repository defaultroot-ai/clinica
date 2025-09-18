<?php
require_once('wp-config.php');
require_once('wp-load.php');

echo "Test Fix - Disponibilitate Astăzi\n";
echo "=================================\n\n";

$today = current_time('Y-m-d');
echo "Data de astăzi: {$today}\n";

// Testez pentru doctorul Coserea Andreea cu serviciul "Consultatie boala cronica"
$doctor_id = 2626;
$service_id = 3;

echo "Doctor ID: {$doctor_id}\n";
echo "Service ID: {$service_id}\n\n";

// Simulez request-ul AJAX
$_POST['doctor_id'] = $doctor_id;
$_POST['service_id'] = $service_id;
$_POST['nonce'] = wp_create_nonce('clinica_dashboard_nonce');

// Apelez funcția direct
ob_start();
$dashboard = new Clinica_Patient_Dashboard();
$dashboard->ajax_get_doctor_availability_days();
$output = ob_get_clean();

// Decodez răspunsul JSON
$response = json_decode($output, true);

if ($response && $response['success'] && is_array($response['data'])) {
    $days = $response['data'];
    $today_found = false;
    
    echo "Zile returnate: " . count($days) . "\n";
    echo "Primele 5 zile:\n";
    
    for($i = 0; $i < min(5, count($days)); $i++) {
        $day = $days[$i];
        echo "- {$day['date']} (full: " . ($day['full'] ? 'DA' : 'NU') . ", today: " . ($day['today'] ? 'DA' : 'NU') . ")\n";
        
        if ($day['date'] === $today) {
            $today_found = true;
        }
    }
    
    echo "\nAstăzi găsit în răspuns: " . ($today_found ? 'DA' : 'NU') . "\n";
    
    if ($today_found) {
        echo "✅ SUCCESS: Ziua de astăzi este acum disponibilă în backend!\n";
    } else {
        echo "❌ PROBLEM: Ziua de astăzi încă nu este disponibilă.\n";
    }
} else {
    echo "❌ EROARE: " . ($response['data'] ?? 'Necunoscut') . "\n";
}

echo "\nTest completat!\n";
?>
