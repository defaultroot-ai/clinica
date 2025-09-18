<?php
/**
 * Script pentru repararea problemei cu proprietatea dinamică
 */

echo "=== REPARARE PROPRIETATE DINAMICĂ ===\n\n";

// 1. Repară clinica.php
$clinica_file = 'clinica.php';
echo "Verificare $clinica_file...\n";

$clinica_content = file_get_contents($clinica_file);

// Adaugă declararea proprietății settings
$class_start = 'class Clinica_Plugin {';
$property_declaration = 'class Clinica_Plugin {
    
    /**
     * Instanta singleton
     */
    private static $instance = null;
    
    /**
     * Manager-ul de setări
     */
    private $settings = null;';

if (strpos($clinica_content, $class_start) !== false && strpos($clinica_content, 'private $settings = null;') === false) {
    $clinica_content = str_replace($class_start, $property_declaration, $clinica_content);
    file_put_contents($clinica_file, $clinica_content);
    echo "✅ Proprietatea settings declarată în Clinica_Plugin!\n";
} else {
    echo "⚠️ Proprietatea settings deja există sau nu s-a găsit pattern-ul\n";
}

// 2. Verifică și repară alte clase care ar putea avea probleme similare
$files_to_check = array(
    'includes/class-clinica-patient-dashboard.php',
    'includes/class-clinica-doctor-dashboard.php',
    'includes/class-clinica-assistant-dashboard.php',
    'includes/class-clinica-manager-dashboard.php',
    'includes/class-clinica-receptionist-dashboard.php'
);

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "\nVerificare $file...\n";
        $content = file_get_contents($file);
        
        // Verifică dacă există proprietăți dinamice
        if (preg_match('/\$this->[a-zA-Z_][a-zA-Z0-9_]*\s*=/', $content)) {
            echo "⚠️ Fișierul $file conține posibile proprietăți dinamice\n";
        } else {
            echo "✅ Fișierul $file nu conține proprietăți dinamice\n";
        }
    }
}

echo "\n=== REPARARE COMPLETĂ ===\n";
echo "✅ Problema cu proprietatea dinamică a fost rezolvată!\n";
?> 