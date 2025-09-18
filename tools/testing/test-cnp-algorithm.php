<?php
/**
 * Test Algoritm de Control CNP - Clinica
 * 
 * Acest script testează algoritmul de control CNP în detaliu
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
    <title>Test Algoritm CNP - Clinica</title>
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
        .code-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin: 10px 0;
        }
        .algorithm-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .algorithm-table th,
        .algorithm-table td {
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .algorithm-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .digit-cell {
            background-color: #e3f2fd;
            font-weight: bold;
        }
        .control-cell {
            background-color: #fff3e0;
            font-weight: bold;
        }
        .product-cell {
            background-color: #f3e5f5;
        }
        .sum-cell {
            background-color: #e8f5e8;
            font-weight: bold;
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
        .cnp-input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 5px;
            font-size: 16px;
            width: 200px;
        }
        .result-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧮 Test Algoritm de Control CNP - Clinica</h1>
        
        <div class="status info">
            <strong>Scop:</strong> Testează algoritmul de control CNP în detaliu pentru români și străini.
        </div>

        <!-- Test Manual CNP -->
        <div class="test-section">
            <h3>🔢 Test Manual CNP</h3>
            
            <div>
                <input type="text" id="manual-cnp" class="cnp-input" placeholder="Introduceți un CNP" maxlength="13">
                <button class="test-button" onclick="testManualCNP()">Testează Algoritmul</button>
            </div>
            
            <div id="manual-result" class="result-box" style="display: none;"></div>
        </div>

        <!-- Test Algoritm Românesc -->
        <div class="test-section">
            <h3>🇷🇴 Test Algoritm CNP Românesc</h3>
            
            <?php
            $test_cnp = '1800404080170';
            $control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
            $sum = 0;
            
            echo '<div class="code-block">';
            echo '<strong>Test algoritm pentru CNP românesc:</strong> ' . $test_cnp . '<br><br>';
            
            echo '<table class="algorithm-table">';
            echo '<tr><th>Poz.</th><th>Digit</th><th>Control</th><th>Produs</th><th>Suma Parțială</th></tr>';
            
            for ($i = 0; $i < 12; $i++) {
                $digit = $test_cnp[$i];
                $control = $control_digits[$i];
                $product = $digit * $control;
                $sum += $product;
                
                echo '<tr>';
                echo '<td>' . ($i + 1) . '</td>';
                echo '<td class="digit-cell">' . $digit . '</td>';
                echo '<td class="control-cell">' . $control . '</td>';
                echo '<td class="product-cell">' . $product . '</td>';
                echo '<td class="sum-cell">' . $sum . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            
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

        <!-- Test Algoritm Străin -->
        <div class="test-section">
            <h3>🌍 Test Algoritm CNP Străin</h3>
            
            <?php
            $test_cnp_foreign = '0123456789012';
            $control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
            $sum = 0;
            
            echo '<div class="code-block">';
            echo '<strong>Test algoritm pentru CNP străin:</strong> ' . $test_cnp_foreign . '<br><br>';
            
            echo '<table class="algorithm-table">';
            echo '<tr><th>Poz.</th><th>Digit</th><th>Control</th><th>Produs</th><th>Suma Parțială</th></tr>';
            
            for ($i = 0; $i < 12; $i++) {
                $digit = $test_cnp_foreign[$i];
                $control = $control_digits[$i];
                $product = $digit * $control;
                $sum += $product;
                
                echo '<tr>';
                echo '<td>' . ($i + 1) . '</td>';
                echo '<td class="digit-cell">' . $digit . '</td>';
                echo '<td class="control-cell">' . $control . '</td>';
                echo '<td class="product-cell">' . $product . '</td>';
                echo '<td class="sum-cell">' . $sum . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            
            echo "<br><strong>Suma totală:</strong> " . $sum . "<br>";
            $control_digit = $sum % 11;
            if ($control_digit == 10) {
                $control_digit = 1;
            }
            echo "<strong>Cifra de control calculată:</strong> " . $control_digit . "<br>";
            echo "<strong>Cifra de control din CNP:</strong> " . $test_cnp_foreign[12] . "<br>";
            echo "<strong>Rezultat:</strong> " . ($control_digit == $test_cnp_foreign[12] ? "✅ Valid" : "❌ Invalid");
            echo '</div>';
            ?>
        </div>

        <!-- Test Multiple CNP-uri -->
        <div class="test-section">
            <h3>📊 Test Multiple CNP-uri</h3>
            
            <?php
            $validator = new Clinica_CNP_Validator();
            $parser = new Clinica_CNP_Parser();
            
            $test_cnps = [
                '1800404080170' => 'Român masculin 1980',
                '2800404080171' => 'Română feminină 1980',
                '5123456789012' => 'Român masculin 2012',
                '6123456789013' => 'Română feminină 2012',
                '0123456789012' => 'Străin permanent',
                '9123456789012' => 'Străin temporar',
                '1234567890123' => 'CNP invalid',
                '1800404080171' => 'CNP cu cifră control greșită',
            ];
            
            echo '<table class="algorithm-table">';
            echo '<tr><th>CNP</th><th>Descriere</th><th>Tip</th><th>Valid</th><th>Data Nașterii</th><th>Sex</th><th>Vârstă</th></tr>';
            
            foreach ($test_cnps as $cnp => $description) {
                $validation = $validator->validate_cnp($cnp);
                $parsed = $parser->parse_cnp($cnp);
                
                $row_class = $validation['valid'] ? 'valid-cnp' : 'invalid-cnp';
                
                echo '<tr>';
                echo '<td>' . $cnp . '</td>';
                echo '<td>' . $description . '</td>';
                echo '<td>' . ($parsed['cnp_type'] ?? 'N/A') . '</td>';
                echo '<td>' . ($validation['valid'] ? '✅ Valid' : '❌ Invalid') . '</td>';
                echo '<td>' . ($parsed['birth_date'] ?? 'N/A') . '</td>';
                echo '<td>' . ($parsed['gender'] ?? 'N/A') . '</td>';
                echo '<td>' . ($parsed['age'] ?? 'N/A') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            ?>
        </div>

        <!-- Test Cazuri Speciale -->
        <div class="test-section">
            <h3>⚠️ Test Cazuri Speciale</h3>
            
            <?php
            $special_cases = [
                '0000000000000' => 'Toate cifrele 0',
                '9999999999999' => 'Toate cifrele 9',
                '1111111111111' => 'Toate cifrele 1',
                '1234567890123' => 'Secvență crescătoare',
                '9876543210987' => 'Secvență descrescătoare',
            ];
            
            echo '<table class="algorithm-table">';
            echo '<tr><th>CNP</th><th>Descriere</th><th>Valid</th><th>Rezultat</th></tr>';
            
            foreach ($special_cases as $cnp => $description) {
                $validation = $validator->validate_cnp($cnp);
                
                echo '<tr>';
                echo '<td>' . $cnp . '</td>';
                echo '<td>' . $description . '</td>';
                echo '<td>' . ($validation['valid'] ? '✅ Valid' : '❌ Invalid') . '</td>';
                echo '<td>' . ($validation['error'] ?? 'OK') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            ?>
        </div>

        <!-- Test Performance -->
        <div class="test-section">
            <h3>⚡ Test Performance</h3>
            
            <?php
            $start_time = microtime(true);
            
            // Testează 1000 de CNP-uri
            for ($i = 0; $i < 1000; $i++) {
                $test_cnp = '1800404080170';
                $validator->validate_cnp($test_cnp);
            }
            
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time) * 1000; // în milisecunde
            
            echo '<div class="code-block">';
            echo '<strong>Test performance:</strong><br>';
            echo '1000 validări CNP în: ' . number_format($execution_time, 2) . ' ms<br>';
            echo 'Timp mediu per validare: ' . number_format($execution_time / 1000, 4) . ' ms<br>';
            echo 'Validări per secundă: ' . number_format(1000 / ($execution_time / 1000), 0) . '<br>';
            echo '</div>';
            ?>
        </div>

        <!-- Butoane de acțiune -->
        <div class="test-section">
            <h3>🎯 Acțiuni</h3>
            
            <button class="test-button" onclick="location.reload()">🔄 Reîncarcă Testul</button>
            <button class="test-button" onclick="window.open('test-cnp-validation.php', '_blank')">🧪 Test Validare Completă</button>
            <button class="test-button" onclick="window.open('test-ajax-cnp-exists.php', '_blank')">🔍 Test AJAX</button>
            <button class="test-button" onclick="window.open('admin.php?page=clinica-create-patient', '_blank')">👤 Test Formular</button>
        </div>
    </div>

    <script>
    function testManualCNP() {
        var cnp = document.getElementById('manual-cnp').value;
        var resultDiv = document.getElementById('manual-result');
        
        if (!cnp) {
            alert('Introduceți un CNP pentru testare');
            return;
        }
        
        if (cnp.length !== 13) {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<strong>❌ CNP Invalid:</strong> CNP-ul trebuie să aibă exact 13 caractere';
            return;
        }
        
        if (!/^\d{13}$/.test(cnp)) {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<strong>❌ CNP Invalid:</strong> CNP-ul trebuie să conțină doar cifre';
            return;
        }
        
        // Simulează calculul algoritmului
        var control_digits = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
        var sum = 0;
        
        for (var i = 0; i < 12; i++) {
            sum += parseInt(cnp[i]) * control_digits[i];
        }
        
        var control_digit = sum % 11;
        if (control_digit == 10) {
            control_digit = 1;
        }
        
        var is_valid = control_digit == parseInt(cnp[12]);
        
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<strong>Rezultat test manual:</strong><br>' +
            'CNP: ' + cnp + '<br>' +
            'Suma: ' + sum + '<br>' +
            'Cifra de control calculată: ' + control_digit + '<br>' +
            'Cifra de control din CNP: ' + cnp[12] + '<br>' +
            'Valid: ' + (is_valid ? '✅ Da' : '❌ Nu');
    }
    
    // Permite testarea cu Enter
    document.getElementById('manual-cnp').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            testManualCNP();
        }
    });
    </script>
</body>
</html> 