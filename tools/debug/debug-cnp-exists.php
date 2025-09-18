<?php
/**
 * Debug script pentru verificarea CNP-ului în baza de date
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat');
}

// Încarcă jQuery și alte scripturi WordPress
wp_enqueue_script('jquery');

// CNP-ul de test
$test_cnp = '1800404080170';

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug CNP Exists</title>
    <?php wp_head(); ?>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1, h2 { color: #333; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Debug CNP Exists - <?php echo $test_cnp; ?></h1>

    <?php
    global $wpdb;
    
    // 1. Verificare în tabela pacienți
    $table_name = $wpdb->prefix . 'clinica_patients';
    $exists_in_patients = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE cnp = %s",
        $test_cnp
    ));
    
    echo "<h2>1. Verificare în tabela pacienți ({$table_name})</h2>";
    echo "Rezultat: " . ($exists_in_patients > 0 ? "GĂSIT ({$exists_in_patients})" : "NU EXISTĂ") . "<br>";
    
    if ($exists_in_patients > 0) {
        $patient_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE cnp = %s",
            $test_cnp
        ));
        echo "<pre>Detalii pacient: " . print_r($patient_data, true) . "</pre>";
    }
    
    // 2. Verificare în tabela utilizatori WordPress
    $exists_in_users = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_login = %s",
        $test_cnp
    ));
    
    echo "<h2>2. Verificare în tabela utilizatori ({$wpdb->users})</h2>";
    echo "Rezultat: " . ($exists_in_users > 0 ? "GĂSIT ({$exists_in_users})" : "NU EXISTĂ") . "<br>";
    
    if ($exists_in_users > 0) {
        $user_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->users} WHERE user_login = %s",
            $test_cnp
        ));
        echo "<pre>Detalii utilizator: " . print_r($user_data, true) . "</pre>";
    }
    
    // 3. Test funcția cnp_exists
    echo "<h2>3. Test funcția cnp_exists()</h2>";
    
    // Încarcă clasa formularului
    require_once('includes/class-clinica-patient-creation-form.php');
    $patient_form = new Clinica_Patient_Creation_Form();
    
    // Folosește Reflection pentru a accesa metoda privată
    $reflection = new ReflectionClass($patient_form);
    $method = $reflection->getMethod('cnp_exists');
    $method->setAccessible(true);
    $cnp_exists_result = $method->invoke($patient_form, $test_cnp);
    
    echo "Rezultat funcție cnp_exists(): " . ($cnp_exists_result ? "TRUE" : "FALSE") . "<br>";
    
    // 4. Concluzie
    echo "<h2>4. Concluzie</h2>";
    $should_exist = ($exists_in_patients > 0 || $exists_in_users > 0);
    echo "CNP-ul ar trebui să fie detectat ca existent: " . ($should_exist ? "DA" : "NU") . "<br>";
    echo "Funcția cnp_exists() funcționează corect: " . ($cnp_exists_result === $should_exist ? "DA" : "NU") . "<br>";
    
    // 5. Debug queries
    echo "<h2>5. Debug Queries</h2>";
    echo "Query pacienți: " . $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE cnp = %s", $test_cnp) . "<br>";
    echo "Query utilizatori: " . $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_login = %s", $test_cnp) . "<br>";
    
    // 6. Test AJAX handler
    echo "<h2>6. Test AJAX Handler</h2>";
    echo "<button onclick='testAjax()'>Testează AJAX Handler</button>";
    echo "<div id='ajax-result'></div>";
    ?>

    <script>
    function testAjax() {
        var resultDiv = document.getElementById('ajax-result');
        resultDiv.innerHTML = 'Testare în curs...';
        
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'clinica_check_cnp_exists',
                cnp: '<?php echo $test_cnp; ?>',
                nonce: '<?php echo wp_create_nonce('clinica_check_cnp_exists'); ?>'
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                if (response.success) {
                    resultDiv.innerHTML = 'AJAX Handler funcționează - CNP există: ' + response.data.exists;
                } else {
                    resultDiv.innerHTML = 'AJAX Handler eroare: ' + response.data;
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error);
                resultDiv.innerHTML = 'Eroare AJAX: ' + error;
            }
        });
    }
    </script>
    
    <?php wp_footer(); ?>
</body>
</html> 