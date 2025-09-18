<?php
/**
 * Test pentru verificarea erorilor JavaScript din dashboard-uri
 * 
 * Acest script testează:
 * 1. Dacă variabila clinicaAssistantAjax este definită
 * 2. Dacă handler-ele AJAX sunt înregistrate
 * 3. Dacă Bootstrap tooltip-ul funcționează
 * 4. Dacă dashboard-urile se încarcă fără erori
 */

// Verifică dacă WordPress este încărcat
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Verifică dacă utilizatorul este autentificat și este administrator
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat pentru a rula acest test.');
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

if (!in_array('administrator', $user_roles)) {
    wp_die('Trebuie să fiți administrator pentru a rula acest test.');
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Erori Dashboard - Clinica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .dashboard-preview {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .button {
            background-color: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .button:hover {
            background-color: #005a87;
        }
        .console-output {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Test Erori Dashboard - Clinica</h1>
        
        <div class="test-section">
            <div class="test-title">1. Verificare Clase Dashboard</div>
            <?php
            $dashboard_classes = [
                'Clinica_Patient_Dashboard',
                'Clinica_Doctor_Dashboard', 
                'Clinica_Assistant_Dashboard',
                'Clinica_Manager_Dashboard'
            ];
            
            foreach ($dashboard_classes as $class) {
                if (class_exists($class)) {
                    echo '<div class="test-result success">✓ Clasa ' . $class . ' este încărcată</div>';
                } else {
                    echo '<div class="test-result error">✗ Clasa ' . $class . ' NU este încărcată</div>';
                }
            }
            ?>
        </div>
        
        <div class="test-section">
            <div class="test-title">2. Verificare Handler-e AJAX</div>
            <?php
            $ajax_handlers = [
                'clinica_get_assistant_overview',
                'clinica_get_assistant_appointments',
                'clinica_get_assistant_patients',
                'clinica_create_appointment',
                'clinica_update_appointment_status',
                'clinica_get_doctors_list'
            ];
            
            foreach ($ajax_handlers as $handler) {
                if (has_action('wp_ajax_' . $handler)) {
                    echo '<div class="test-result success">✓ Handler AJAX ' . $handler . ' este înregistrat</div>';
                } else {
                    echo '<div class="test-result error">✗ Handler AJAX ' . $handler . ' NU este înregistrat</div>';
                }
            }
            ?>
        </div>
        
        <div class="test-section">
            <div class="test-title">3. Test Dashboard-uri Frontend</div>
            <p>Testează dacă dashboard-urile se încarcă fără erori JavaScript:</p>
            
            <button class="button" onclick="testPatientDashboard()">Test Dashboard Pacient</button>
            <button class="button" onclick="testDoctorDashboard()">Test Dashboard Doctor</button>
            <button class="button" onclick="testAssistantDashboard()">Test Dashboard Asistent</button>
            <button class="button" onclick="testManagerDashboard()">Test Dashboard Manager</button>
            
            <div id="dashboard-test-results"></div>
        </div>
        
        <div class="test-section">
            <div class="test-title">4. Console Output</div>
            <div id="console-output" class="console-output">Console-ul va afișa aici erorile JavaScript...</div>
        </div>
        
        <div class="test-section">
            <div class="test-title">5. Test Bootstrap Tooltip</div>
            <button class="button" title="Acesta este un tooltip de test" onclick="testTooltip()">Test Tooltip</button>
            <div id="tooltip-test-result"></div>
        </div>
    </div>

    <script src="<?php echo includes_url('js/jquery/jquery.min.js'); ?>"></script>
    <script>
        // Interceptează console.log pentru a afișa în pagina de test
        var originalConsoleLog = console.log;
        var originalConsoleError = console.error;
        var originalConsoleWarn = console.warn;
        
        function addToConsole(type, message) {
            var consoleOutput = document.getElementById('console-output');
            var timestamp = new Date().toLocaleTimeString();
            consoleOutput.innerHTML += '[' + timestamp + '] [' + type + '] ' + message + '\n';
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
        
        console.log = function(message) {
            originalConsoleLog.apply(console, arguments);
            addToConsole('LOG', message);
        };
        
        console.error = function(message) {
            originalConsoleError.apply(console, arguments);
            addToConsole('ERROR', message);
        };
        
        console.warn = function(message) {
            originalConsoleWarn.apply(console, arguments);
            addToConsole('WARN', message);
        };
        
        function testPatientDashboard() {
            console.log('Testare Dashboard Pacient...');
            var resultDiv = document.getElementById('dashboard-test-results');
            
            // Simulează încărcarea dashboard-ului pacientului
            if (typeof clinicaPatientAjax !== 'undefined') {
                resultDiv.innerHTML = '<div class="test-result success">✓ Dashboard Pacient - clinicaPatientAjax este definit</div>';
            } else {
                resultDiv.innerHTML = '<div class="test-result warning">⚠ Dashboard Pacient - clinicaPatientAjax nu este definit (se folosesc date demo)</div>';
            }
        }
        
        function testDoctorDashboard() {
            console.log('Testare Dashboard Doctor...');
            var resultDiv = document.getElementById('dashboard-test-results');
            
            // Testează tooltip-ul
            if (typeof $.fn.tooltip !== 'undefined') {
                resultDiv.innerHTML = '<div class="test-result success">✓ Dashboard Doctor - Bootstrap tooltip este disponibil</div>';
            } else {
                resultDiv.innerHTML = '<div class="test-result warning">⚠ Dashboard Doctor - Bootstrap tooltip nu este disponibil</div>';
            }
        }
        
        function testAssistantDashboard() {
            console.log('Testare Dashboard Asistent...');
            var resultDiv = document.getElementById('dashboard-test-results');
            
            if (typeof clinicaAssistantAjax !== 'undefined') {
                resultDiv.innerHTML = '<div class="test-result success">✓ Dashboard Asistent - clinicaAssistantAjax este definit</div>';
            } else {
                resultDiv.innerHTML = '<div class="test-result warning">⚠ Dashboard Asistent - clinicaAssistantAjax nu este definit (se folosesc date demo)</div>';
            }
        }
        
        function testManagerDashboard() {
            console.log('Testare Dashboard Manager...');
            var resultDiv = document.getElementById('dashboard-test-results');
            
            if (typeof clinicaManagerAjax !== 'undefined') {
                resultDiv.innerHTML = '<div class="test-result success">✓ Dashboard Manager - clinicaManagerAjax este definit</div>';
            } else {
                resultDiv.innerHTML = '<div class="test-result warning">⚠ Dashboard Manager - clinicaManagerAjax nu este definit (se folosesc date demo)</div>';
            }
        }
        
        function testTooltip() {
            var resultDiv = document.getElementById('tooltip-test-result');
            
            if (typeof $.fn.tooltip !== 'undefined') {
                $('[title]').tooltip();
                resultDiv.innerHTML = '<div class="test-result success">✓ Tooltip-ul Bootstrap funcționează</div>';
            } else {
                resultDiv.innerHTML = '<div class="test-result error">✗ Tooltip-ul Bootstrap nu este disponibil</div>';
            }
        }
        
        // Test automat la încărcarea paginii
        window.onload = function() {
            console.log('Pagină încărcată - începe testarea...');
            
            // Testează dacă jQuery este disponibil
            if (typeof $ !== 'undefined') {
                console.log('✓ jQuery este disponibil');
            } else {
                console.error('✗ jQuery nu este disponibil');
            }
            
            // Testează dacă Bootstrap este disponibil
            if (typeof $.fn.tooltip !== 'undefined') {
                console.log('✓ Bootstrap tooltip este disponibil');
            } else {
                console.warn('⚠ Bootstrap tooltip nu este disponibil');
            }
        };
    </script>
</body>
</html> 