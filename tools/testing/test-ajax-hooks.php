<?php
/**
 * Test pentru verificarea AJAX handler-urilor înregistrate
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat');
}

// Încarcă jQuery și alte scripturi WordPress
wp_enqueue_script('jquery');

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test AJAX Hooks</title>
    <?php wp_head(); ?>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Test AJAX Hooks înregistrate</h1>
    
    <?php
global $wp_filter;

    // Verifică hook-urile AJAX
    $ajax_hooks = array(
        'wp_ajax_clinica_check_cnp_exists',
        'wp_ajax_nopriv_clinica_check_cnp_exists',
        'wp_ajax_clinica_validate_cnp',
        'wp_ajax_nopriv_clinica_validate_cnp',
        'wp_ajax_clinica_generate_password',
        'wp_ajax_nopriv_clinica_generate_password',
        'wp_ajax_clinica_create_patient',
        'wp_ajax_nopriv_clinica_create_patient'
    );
    
    echo '<div class="info">';
    echo '<h3>📋 Hook-uri AJAX verificate:</h3>';
    echo '<ul>';
    foreach ($ajax_hooks as $hook) {
        $exists = isset($wp_filter[$hook]);
        $status = $exists ? '✅' : '❌';
        echo "<li>{$status} {$hook}</li>";
    }
    echo '</ul>';
    echo '</div>';
    
    // Verifică dacă clasa este încărcată
    echo '<div class="info">';
    echo '<h3>📦 Verificare clase:</h3>';
    echo '<p><strong>Clinica_Patient_Creation_Form:</strong> ' . (class_exists('Clinica_Patient_Creation_Form') ? '✅ Există' : '❌ Nu există') . '</p>';
    
    if (class_exists('Clinica_Patient_Creation_Form')) {
        $patient_form = new Clinica_Patient_Creation_Form();
        echo '<p><strong>Instanțiere:</strong> ✅ Succes</p>';
        
        // Verifică metodele
        $methods = get_class_methods($patient_form);
        echo '<p><strong>Metode disponibile:</strong></p>';
        echo '<pre>' . print_r($methods, true) . '</pre>';
    }
    echo '</div>';
    
    // Test direct funcția cnp_exists
    if (class_exists('Clinica_Patient_Creation_Form')) {
        echo '<div class="warning">';
        echo '<h3>🧪 Test direct funcția cnp_exists:</h3>';
        
        $patient_form = new Clinica_Patient_Creation_Form();
        $reflection = new ReflectionClass($patient_form);
        
        if ($reflection->hasMethod('cnp_exists')) {
            $method = $reflection->getMethod('cnp_exists');
            $method->setAccessible(true);
            
            $test_cnp = '1800404080170';
            $result = $method->invoke($patient_form, $test_cnp);
            
            echo "<p><strong>Test CNP:</strong> {$test_cnp}</p>";
            echo "<p><strong>Rezultat:</strong> " . ($result ? 'TRUE (există)' : 'FALSE (nu există)') . "</p>";
            
            // Verificare directă în baza de date
            global $wpdb;
            $table_name = $wpdb->prefix . 'clinica_patients';
            $exists_in_patients = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE cnp = %s",
                $test_cnp
            ));
            
            $exists_in_users = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_login = %s",
                $test_cnp
            ));
            
            echo "<p><strong>În tabela pacienți:</strong> " . ($exists_in_patients > 0 ? 'GĂSIT' : 'NU EXISTĂ') . "</p>";
            echo "<p><strong>În tabela utilizatori:</strong> " . ($exists_in_users > 0 ? 'GĂSIT' : 'NU EXISTĂ') . "</p>";
            
            $should_exist = ($exists_in_patients > 0 || $exists_in_users > 0);
            echo "<p><strong>Ar trebui să existe:</strong> " . ($should_exist ? 'DA' : 'NU') . "</p>";
            echo "<p><strong>Funcția funcționează corect:</strong> " . ($result === $should_exist ? '✅ DA' : '❌ NU') . "</p>";
            
} else {
            echo '<p>❌ Metoda cnp_exists nu există</p>';
        }
        echo '</div>';
    }
    
    // Verifică dacă plugin-ul este activ
    echo '<div class="info">';
    echo '<h3>🔌 Status Plugin:</h3>';
    $active_plugins = get_option('active_plugins');
    $clinica_active = in_array('clinica/clinica.php', $active_plugins);
    echo '<p><strong>Clinica Plugin Activ:</strong> ' . ($clinica_active ? '✅ DA' : '❌ NU') . '</p>';
    echo '</div>';
    
    // Forțează reîncărcarea claselor
    echo '<div class="warning">';
    echo '<h3>🔄 Forțare Reîncărcare:</h3>';
    echo '<p>Dacă hook-urile nu sunt înregistrate, încearcă să:</p>';
    echo '<ol>';
    echo '<li>Dezactivezi plugin-ul Clinica</li>';
    echo '<li>Reactivezi plugin-ul Clinica</li>';
    echo '<li>Reîncarcă această pagină</li>';
    echo '</ol>';
    echo '</div>';
    ?>
    
    <div class="info">
        <h3>🔗 Link-uri utile:</h3>
        <p><a href="test-ajax-cnp-exists.php">🧪 Test AJAX CNP Exists</a></p>
        <p><a href="test-cnp-duplicate-check.php">🔍 Test Formular CNP Duplicat</a></p>
        <p><a href="<?php echo admin_url('admin.php?page=clinica-patients'); ?>">📊 Pagina Pacienți (Admin)</a></p>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html> 