<?php
/**
 * Test Validare CNP - Clinica
 * 
 * Acest script testează sistemul de validare CNP pentru români și străini
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in WordPress context
if (!function_exists('wp_enqueue_script')) {
    die('WordPress nu este încărcat corect.');
}

// Verifică dacă suntem în admin
if (!is_admin()) {
    wp_redirect(admin_url());
    exit;
}

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die(__('Nu aveți permisiunea de a accesa această pagină.', 'clinica'));
}

// Încarcă clasele necesare
require_once('includes/class-clinica-cnp-validator.php');
require_once('includes/class-clinica-cnp-parser.php');

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Validare CNP - Clinica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #333;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .test-button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .test-button:hover {
            background: #005a87;
        }
        .code-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin: 10px 0;
        }
        .cnp-test-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .cnp-test-form input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 5px;
            font-size: 16px;
        }
        .cnp-test-form button {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .cnp-test-form button:hover {
            background: #218838;
        }
        .result-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
        }
        .test-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .test-table th,
        .test-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .test-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .valid-cnp {
            background-color: #d4edda;
            color: #155724;
        }
        .invalid-cnp {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Test Validare CNP - Clinica</h1>
        
        <div class="status info">
            <strong>Scop:</strong> Testează sistemul de validare CNP pentru români și străini.
        </div>

        <!-- Test Clase -->
        <div class="test-section">
            <h3>🔧 Test Clase CNP</h3>
            
            <?php
            // Testează dacă clasele există
            if (class_exists('Clinica_CNP_Validator')) {
                echo '<div class="status success">✅ Clasa Clinica_CNP_Validator există</div>';
            } else {
                echo '<div class="status error">❌ Clasa Clinica_CNP_Validator NU există</div>';
            }
            
            if (class_exists('Clinica_CNP_Parser')) {
                echo '<div class="status success">✅ Clasa Clinica_CNP_Parser există</div>';
            } else {
                echo '<div class="status error">❌ Clasa Clinica_CNP_Parser NU există</div>';
            }
            ?>
        </div>

        <!-- Test Validare CNP Românesc -->
        <div class="test-section">
            <h3>🇷🇴 Test CNP Românesc</h3>
            
            <?php
            $validator = new Clinica_CNP_Validator();
            $parser = new Clinica_CNP_Parser();
            
            // CNP-uri de test pentru români
            $romanian_cnps = [
                '1800404080170', // CNP valid masculin
                '2800404080171', // CNP valid feminin
                '1234567890123', // CNP invalid
                '1800404080171', // CNP invalid (cifră control greșită)
            ];
            
            echo '<table class="test-table">';
            echo '<tr><th>CNP</th><th>Tip</th><th>Valid</th><th>Data Nașterii</th><th>Sex</th><th>Vârstă</th><th>Rezultat</th></tr>';
            
            foreach ($romanian_cnps as $cnp) {
                $validation = $validator->validate_cnp($cnp);
                $parsed = $parser->parse_cnp($cnp);
                
                $row_class = $validation['valid'] ? 'valid-cnp' : 'invalid-cnp';
                
                echo '<tr class="' . $row_class . '">';
                echo '<td>' . $cnp . '</td>';
                echo '<td>' . ($parsed['cnp_type'] ?? 'N/A') . '</td>';
                echo '<td>' . ($validation['valid'] ? '✅ Valid' : '❌ Invalid') . '</td>';
                echo '<td>' . ($parsed['birth_date'] ?? 'N/A') . '</td>';
                echo '<td>' . ($parsed['gender'] ?? 'N/A') . '</td>';
                echo '<td>' . ($parsed['age'] ?? 'N/A') . '</td>';
                echo '<td>' . ($validation['error'] ?? 'OK') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            ?>
        </div>

        <!-- Test Validare CNP Străini -->
        <div class="test-section">
            <h3>🌍 Test CNP Străini</h3>
            
            <?php
            // CNP-uri de test pentru străini
            $foreign_cnps = [
                '0123456789012', // CNP străin permanent valid
                '0987654321098', // CNP străin permanent valid
                '9123456789012', // CNP străin temporar valid
                '9876543210987', // CNP străin temporar valid
            ];
            
            echo '<table class="test-table">';
            echo '<tr><th>CNP</th><th>Tip</th><th>Valid</th><th>Data Nașterii</th><th>Sex</th><th>Vârstă</th><th>Rezultat</th></tr>';
            
            foreach ($foreign_cnps as $cnp) {
                $validation = $validator->validate_cnp($cnp);
                $parsed = $parser->parse_cnp($cnp);
                
                $row_class = $validation['valid'] ? 'valid-cnp' : 'invalid-cnp';
                
                echo '<tr class="' . $row_class . '">';
                echo '<td>' . $cnp . '</td>';
                echo '<td>' . ($parsed['cnp_type'] ?? 'N/A') . '</td>';
                echo '<td>' . ($validation['valid'] ? '✅ Valid' : '❌ Invalid') . '</td>';
                echo '<td>' . ($parsed['birth_date'] ?? 'N/A') . '</td>';
                echo '<td>' . ($parsed['gender'] ?? 'N/A') . '</td>';
                echo '<td>' . ($parsed['age'] ?? 'N/A') . '</td>';
                echo '<td>' . ($validation['error'] ?? 'OK') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            ?>
        </div>

        <!-- Test AJAX -->
        <div class="test-section">
            <h3>🔗 Test AJAX Validare CNP</h3>
            
            <div class="cnp-test-form">
                <h4>Testează validarea CNP prin AJAX:</h4>
                <input type="text" id="test-cnp" placeholder="Introduceți un CNP" maxlength="13">
                <button onclick="testCNPValidation()">Validează CNP</button>
                
                <div id="ajax-result" class="result-box" style="display: none;"></div>
            </div>
            
            <div class="status info">
                <strong>Note:</strong> Acest test verifică dacă handler-ul AJAX funcționează corect.
            </div>
        </div>

        <!-- Test Algoritm de Control -->
        <div class="test-section">
            <h3>🔢 Test Algoritm de Control</h3>
            
            <?php
            // Testează algoritmul de control manual
            $test_cnp = '1800404080170';
            $control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
            $sum = 0;
            
            echo '<div class="code-block">';
            echo '<strong>Test algoritm pentru CNP:</strong> ' . $test_cnp . '<br><br>';
            
            for ($i = 0; $i < 12; $i++) {
                $digit = $test_cnp[$i];
                $control = $control_digits[$i];
                $product = $digit * $control;
                $sum += $product;
                
                echo "Pozitia " . ($i + 1) . ": " . $digit . " × " . $control . " = " . $product . "<br>";
            }
            
            echo "<br><strong>Suma totală:</strong> " . $sum . "<br>";
            $control_digit = $sum % 11;
            if ($control_digit == 10) {
                $control_digit = 1;
            }
            echo "<strong>Cifra de control calculată:</strong> " . $control_digit . "<br>";
            echo "<strong>Cifra de control din CNP:</strong> " . $test_cnp[12] . "<br>";
            echo "<strong>Rezultat:</strong> " . ($control_digit == $test_cnp[12] ? "✅ Valid" : "❌ Invalid");
            echo '</div>';
            ?>
        </div>

        <!-- Test Edge Cases -->
        <div class="test-section">
            <h3>⚠️ Test Edge Cases</h3>
            
            <?php
            $edge_cases = [
                '' => 'CNP gol',
                '123' => 'CNP prea scurt',
                '123456789012345' => 'CNP prea lung',
                '123456789012a' => 'CNP cu litere',
                '123456789012 ' => 'CNP cu spațiu',
                '0000000000000' => 'Toate cifrele 0',
                '9999999999999' => 'Toate cifrele 9',
            ];
            
            echo '<table class="test-table">';
            echo '<tr><th>CNP</th><th>Descriere</th><th>Valid</th><th>Rezultat</th></tr>';
            
            foreach ($edge_cases as $cnp => $description) {
                $validation = $validator->validate_cnp($cnp);
                
                $row_class = $validation['valid'] ? 'valid-cnp' : 'invalid-cnp';
                
                echo '<tr class="' . $row_class . '">';
                echo '<td>' . ($cnp ?: '(gol)') . '</td>';
                echo '<td>' . $description . '</td>';
                echo '<td>' . ($validation['valid'] ? '✅ Valid' : '❌ Invalid') . '</td>';
                echo '<td>' . ($validation['error'] ?? 'OK') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            ?>
        </div>

        <!-- Test Parsing -->
        <div class="test-section">
            <h3>📅 Test Parsing CNP</h3>
            
            <?php
            $test_cnps = [
                '1800404080170' => 'Român masculin 1980',
                '2800404080171' => 'Română feminină 1980',
                '5123456789012' => 'Român masculin 2012',
                '6123456789013' => 'Română feminină 2012',
                '0123456789012' => 'Străin permanent',
                '9123456789012' => 'Străin temporar',
            ];
            
            echo '<table class="test-table">';
            echo '<tr><th>CNP</th><th>Descriere</th><th>Data Nașterii</th><th>Sex</th><th>Vârstă</th><th>Tip CNP</th></tr>';
            
            foreach ($test_cnps as $cnp => $description) {
                $parsed = $parser->parse_cnp($cnp);
                
                echo '<tr>';
                echo '<td>' . $cnp . '</td>';
                echo '<td>' . $description . '</td>';
                echo '<td>' . ($parsed['birth_date'] ?? 'N/A') . '</td>';
                echo '<td>' . ($parsed['gender'] ?? 'N/A') . '</td>';
                echo '<td>' . ($parsed['age'] ?? 'N/A') . '</td>';
                echo '<td>' . ($parsed['cnp_type'] ?? 'N/A') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            ?>
        </div>

        <!-- Butoane de acțiune -->
        <div class="test-section">
            <h3>🎯 Acțiuni</h3>
            
            <button class="test-button" onclick="location.reload()">🔄 Reîncarcă Testul</button>
            <button class="test-button" onclick="window.open('test-cnp-algorithm.php', '_blank')">🧮 Test Algoritm Detaliat</button>
            <button class="test-button" onclick="window.open('test-ajax-cnp-exists.php', '_blank')">🔍 Test Verificare Existență CNP</button>
            <button class="test-button" onclick="window.open('admin.php?page=clinica-create-patient', '_blank')">👤 Test Formular Creare Pacient</button>
        </div>
    </div>

    <script>
    function testCNPValidation() {
        var cnp = document.getElementById('test-cnp').value;
        var resultDiv = document.getElementById('ajax-result');
        
        if (!cnp) {
            alert('Introduceți un CNP pentru testare');
            return;
        }
        
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = 'Se validează...';
        
        // Simulează o cerere AJAX
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            resultDiv.innerHTML = '<strong>✅ CNP Valid!</strong><br>' +
                                'Data nașterii: ' + (response.data.birth_date || 'N/A') + '<br>' +
                                'Sex: ' + (response.data.gender || 'N/A') + '<br>' +
                                'Vârstă: ' + (response.data.age || 'N/A') + '<br>' +
                                'Tip CNP: ' + (response.data.cnp_type || 'N/A');
                        } else {
                            resultDiv.innerHTML = '<strong>❌ CNP Invalid:</strong> ' + response.data;
                        }
                    } catch (e) {
                        resultDiv.innerHTML = '<strong>❌ Eroare:</strong> Răspuns invalid de la server';
                    }
                } else {
                    resultDiv.innerHTML = '<strong>❌ Eroare:</strong> Nu s-a putut conecta la server';
                }
            }
        };
        
        xhr.send('action=clinica_validate_cnp&cnp=' + encodeURIComponent(cnp) + '&nonce=<?php echo wp_create_nonce('clinica_doctor_nonce'); ?>');
    }
    
    // Permite testarea cu Enter
    document.getElementById('test-cnp').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            testCNPValidation();
        }
    });
    </script>
</body>
</html> 