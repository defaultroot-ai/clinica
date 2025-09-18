<?php
/**
 * Test pentru a verifica dacă AJAX-ul funcționează corect
 */

// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test AJAX CNP - Clinica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-section {
            background: #f5f5f5;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 3px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        input {
            padding: 10px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        button {
            padding: 10px 20px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>Test AJAX CNP - Clinica</h1>
    
    <div class="test-section">
        <h2>Test 1: Hook-uri AJAX</h2>
        <?php
        global $wp_filter;
        
        $hooks = [
            'wp_ajax_clinica_validate_cnp',
            'wp_ajax_nopriv_clinica_validate_cnp',
            'wp_ajax_clinica_create_patient',
            'wp_ajax_nopriv_clinica_create_patient',
            'wp_ajax_clinica_generate_password',
            'wp_ajax_nopriv_clinica_generate_password'
        ];
        
        foreach ($hooks as $hook) {
            if (isset($wp_filter[$hook])) {
                echo "<div class='result success'>✓ $hook - ÎNREGISTRAT</div>";
            } else {
                echo "<div class='result error'>✗ $hook - NU ESTE ÎNREGISTRAT</div>";
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test 2: Validare CNP Direct</h2>
        <?php
        $cnp = '1800404080170';
        $validator = new Clinica_CNP_Validator();
        $result = $validator->validate_cnp($cnp);
        
        if ($result['valid']) {
            echo "<div class='result success'>✓ CNP $cnp este VALID</div>";
            
            $parser = new Clinica_CNP_Parser();
            $parsed = $parser->parse_cnp($cnp);
            echo "<div class='result info'>Data nașterii: {$parsed['birth_date']}</div>";
            echo "<div class='result info'>Sex: {$parsed['gender']}</div>";
            echo "<div class='result info'>Vârsta: {$parsed['age']}</div>";
        } else {
            echo "<div class='result error'>✗ CNP $cnp este INVALID: {$result['error']}</div>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test 3: AJAX Simulat</h2>
        <p>Introduceți un CNP pentru a testa validarea:</p>
        <input type="text" id="test-cnp" placeholder="Introduceți CNP" maxlength="13">
        <button onclick="testCNP()">Testează CNP</button>
        <div id="ajax-result"></div>
    </div>
    
    <div class="test-section">
        <h2>Test 4: Logica JavaScript</h2>
        <p>Testează logica de validare JavaScript:</p>
        <input type="text" id="js-test-cnp" placeholder="Testează logica JS" maxlength="13">
        <div id="js-result"></div>
    </div>
    
    <script>
        // Test AJAX
        function testCNP() {
            var cnp = document.getElementById('test-cnp').value;
            var resultDiv = document.getElementById('ajax-result');
            
            if (!cnp) {
                resultDiv.innerHTML = '<div class="result error">Introduceți un CNP</div>';
                return;
            }
            
            resultDiv.innerHTML = '<div class="result info">Se testează...</div>';
            
            // Simulează cererea AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resultDiv.innerHTML = '<div class="result success">✓ CNP valid: ' + cnp + '</div>';
                            } else {
                                resultDiv.innerHTML = '<div class="result error">✗ CNP invalid: ' + response.data + '</div>';
                            }
                        } catch (e) {
                            resultDiv.innerHTML = '<div class="result error">✗ Eroare la parsarea răspunsului</div>';
                        }
                    } else {
                        resultDiv.innerHTML = '<div class="result error">✗ Eroare la cererea AJAX</div>';
                    }
                }
            };
            
            var data = 'action=clinica_validate_cnp&cnp=' + encodeURIComponent(cnp) + '&nonce=<?php echo wp_create_nonce('clinica_frontend_nonce'); ?>';
            xhr.send(data);
        }
        
        // Test logica JavaScript
        document.getElementById('js-test-cnp').addEventListener('input', function() {
            var cnp = this.value;
            var resultDiv = document.getElementById('js-result');
            
            // Curăță rezultatul anterior
            resultDiv.innerHTML = '';
            
            if (cnp.length === 0) {
                return;
            }
            
            // Aplică logica de validare
            if (cnp.length !== 13) {
                resultDiv.innerHTML = '<div class="result info">Introduceți toate cele 13 cifre (' + cnp.length + '/13)</div>';
                return;
            }
            
            if (!/^\d{13}$/.test(cnp)) {
                resultDiv.innerHTML = '<div class="result error">CNP-ul trebuie să conțină doar cifre</div>';
                return;
            }
            
            // Simulează validarea pentru CNP-ul de test
            if (cnp === '1800404080170') {
                resultDiv.innerHTML = '<div class="result success">CNP valid - se va face cererea AJAX</div>';
            } else {
                resultDiv.innerHTML = '<div class="result error">CNP invalid - se va face cererea AJAX</div>';
            }
        });
    </script>
</body>
</html> 