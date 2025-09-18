<?php
/**
 * Test simplu pentru AJAX handler clinica_check_cnp_exists
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
    <title>Test AJAX CNP Exists</title>
    <?php wp_head(); ?>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        input { padding: 8px; margin: 5px; width: 200px; }
    </style>
</head>
<body>
    <h1>🧪 Test AJAX Handler clinica_check_cnp_exists</h1>
    
    <div class="info">
        <h3>Test CNP-uri:</h3>
        <p><strong>CNP Existent:</strong> 1800404080170 (cel pe care l-ai menționat)</p>
        <p><strong>CNP Nou:</strong> 1234567890123 (pentru test)</p>
    </div>
    
    <div>
        <input type="text" id="cnp-input" placeholder="Introduceți CNP-ul" value="1800404080170">
        <button onclick="testCNP()">Testează CNP</button>
        <button onclick="testCNPNew()">Testează CNP Nou</button>
    </div>
    
    <div id="results"></div>
    
    <div class="info">
        <h3>Debug Info:</h3>
        <p><strong>AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
        <p><strong>Action:</strong> clinica_check_cnp_exists</p>
        <p><strong>Nonce:</strong> <?php echo wp_create_nonce('clinica_check_cnp_exists'); ?></p>
        <p><strong>jQuery loaded:</strong> <span id="jquery-status">Verificare...</span></p>
    </div>

    <script>
    function testCNP() {
        testCNPExists('1800404080170');
    }
    
    function testCNPNew() {
        testCNPExists('1234567890123');
    }
    
    function testCNPExists(cnp) {
        var resultsDiv = document.getElementById('results');
        resultsDiv.innerHTML = '<div class="info">🔄 Testare CNP: ' + cnp + '...</div>';
        
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'clinica_check_cnp_exists',
                cnp: cnp,
                nonce: '<?php echo wp_create_nonce('clinica_check_cnp_exists'); ?>'
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                
                if (response.success) {
                    var message = response.data.exists ? 
                        '❌ CNP-ul EXISTĂ deja în sistem' : 
                        '✅ CNP-ul este DISPONIBIL';
                    
                    resultsDiv.innerHTML = '<div class="' + (response.data.exists ? 'error' : 'success') + '">' +
                        '<h4>Rezultat pentru CNP: ' + cnp + '</h4>' +
                        '<p>' + message + '</p>' +
                        '<p><strong>Response:</strong> ' + JSON.stringify(response) + '</p>' +
                        '</div>';
                } else {
                    resultsDiv.innerHTML = '<div class="error">' +
                        '<h4>Eroare pentru CNP: ' + cnp + '</h4>' +
                        '<p>' + response.data + '</p>' +
                        '<p><strong>Response:</strong> ' + JSON.stringify(response) + '</p>' +
                        '</div>';
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error);
                console.log('Status:', status);
                console.log('Response Text:', xhr.responseText);
                
                resultsDiv.innerHTML = '<div class="error">' +
                    '<h4>Eroare AJAX pentru CNP: ' + cnp + '</h4>' +
                    '<p><strong>Error:</strong> ' + error + '</p>' +
                    '<p><strong>Status:</strong> ' + status + '</p>' +
                    '<p><strong>Response:</strong> ' + xhr.responseText + '</p>' +
                    '</div>';
            }
        });
    }
    
    // Test automat la încărcare
    jQuery(document).ready(function() {
        console.log('🧪 Test AJAX CNP Exists - Încărcat');
        console.log('AJAX URL:', '<?php echo admin_url('admin-ajax.php'); ?>');
        console.log('Action:', 'clinica_check_cnp_exists');
        
        // Verifică dacă jQuery este încărcat
        document.getElementById('jquery-status').innerHTML = '✅ jQuery este încărcat';
        
        // Test automat cu CNP-ul existent
        setTimeout(function() {
            testCNP();
        }, 1000);
    });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html> 