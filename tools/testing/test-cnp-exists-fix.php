<?php
/**
 * Test pentru verificarea funcției cnp_exists
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    wp_die('Acces restricționat');
}

// Test CNP-ul specificat de utilizator
$test_cnp = '1800404080170';

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Verificare CNP Exists - Clinica</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .test-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .test-header {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        
        .test-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .test-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .test-content {
            padding: 40px;
        }
        
        .test-results {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .test-results h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .result-item {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .result-item.success {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .result-item.error {
            border-color: #dc3545;
            background: #f8d7da;
        }
        
        .result-item.warning {
            border-color: #ffc107;
            background: #fff3cd;
        }
        
        .result-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .result-value {
            font-family: monospace;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 3px;
            border: 1px solid #dee2e6;
        }
        
        .test-actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .test-btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .test-btn:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        .test-btn.success {
            background: #27ae60;
        }
        
        .test-btn.success:hover {
            background: #229954;
        }
        
        .debug-info {
            background: #e9ecef;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1>🔧 Test Verificare CNP Exists</h1>
            <p>Testează funcția cnp_exists pentru CNP-ul: <?php echo $test_cnp; ?></p>
        </div>
        
        <div class="test-content">
            <div class="test-results">
                <h3>📊 Rezultate Verificare CNP</h3>
                
                <?php
                global $wpdb;
                
                // Test 1: Verificare în tabela pacienți
                $table_name = $wpdb->prefix . 'clinica_patients';
                $exists_in_patients = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE cnp = %s",
                    $test_cnp
                ));
                
                $patient_query = $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE cnp = %s",
                    $test_cnp
                );
                $patient_data = $wpdb->get_row($patient_query);
                ?>
                
                <div class="result-item <?php echo $exists_in_patients > 0 ? 'success' : 'error'; ?>">
                    <div class="result-label">1. Verificare în tabela pacienți (<?php echo $table_name; ?>)</div>
                    <div class="result-value">
                        <?php echo $exists_in_patients > 0 ? '✅ GĂSIT' : '❌ NU EXISTĂ'; ?> 
                        (<?php echo $exists_in_patients; ?> înregistrări)
                    </div>
                    <?php if ($patient_data): ?>
                        <div style="margin-top: 10px; font-size: 12px;">
                            <strong>Detalii pacient:</strong><br>
                            User ID: <?php echo $patient_data->user_id; ?><br>
                            Nume: <?php echo $patient_data->first_name . ' ' . $patient_data->last_name; ?><br>
                            Email: <?php echo $patient_data->email; ?><br>
                            Telefon: <?php echo $patient_data->phone_primary; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php
                // Test 2: Verificare în tabela utilizatori WordPress
                $exists_in_users = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_login = %s",
                    $test_cnp
                ));
                
                $user_query = $wpdb->prepare(
                    "SELECT * FROM {$wpdb->users} WHERE user_login = %s",
                    $test_cnp
                );
                $user_data = $wpdb->get_row($user_query);
                ?>
                
                <div class="result-item <?php echo $exists_in_users > 0 ? 'success' : 'error'; ?>">
                    <div class="result-label">2. Verificare în tabela utilizatori (<?php echo $wpdb->users; ?>)</div>
                    <div class="result-value">
                        <?php echo $exists_in_users > 0 ? '✅ GĂSIT' : '❌ NU EXISTĂ'; ?> 
                        (<?php echo $exists_in_users; ?> înregistrări)
                    </div>
                    <?php if ($user_data): ?>
                        <div style="margin-top: 10px; font-size: 12px;">
                            <strong>Detalii utilizator:</strong><br>
                            ID: <?php echo $user_data->ID; ?><br>
                            Username: <?php echo $user_data->user_login; ?><br>
                            Email: <?php echo $user_data->user_email; ?><br>
                            Display Name: <?php echo $user_data->display_name; ?><br>
                            Role: <?php echo get_user_role($user_data->ID); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php
                // Test 3: Verificare cu funcția cnp_exists
                $patient_form = new Clinica_Patient_Creation_Form();
                $reflection = new ReflectionClass($patient_form);
                $method = $reflection->getMethod('cnp_exists');
                $method->setAccessible(true);
                $cnp_exists_result = $method->invoke($patient_form, $test_cnp);
                ?>
                
                <div class="result-item <?php echo $cnp_exists_result ? 'success' : 'error'; ?>">
                    <div class="result-label">3. Rezultat funcția cnp_exists()</div>
                    <div class="result-value">
                        <?php echo $cnp_exists_result ? '✅ TRUE (CNP există)' : '❌ FALSE (CNP nu există)'; ?>
                    </div>
                </div>
                
                <?php
                // Test 4: Verificare AJAX handler
                ?>
                
                <div class="result-item warning">
                    <div class="result-label">4. Test AJAX Handler</div>
                    <div class="result-value">
                        <button onclick="testAjaxHandler()" class="test-btn">🧪 Testează AJAX Handler</button>
                        <div id="ajax-result" style="margin-top: 10px;"></div>
                    </div>
                </div>
                
                <div class="result-item <?php echo ($exists_in_patients > 0 || $exists_in_users > 0) ? 'success' : 'error'; ?>">
                    <div class="result-label">5. Concluzie Finală</div>
                    <div class="result-value">
                        <?php 
                        $should_exist = ($exists_in_patients > 0 || $exists_in_users > 0);
                        echo $should_exist ? '✅ CNP-ul TREBUIE să fie detectat ca existent' : '❌ CNP-ul NU ar trebui să fie detectat';
                        ?>
                    </div>
                    <div style="margin-top: 10px; font-size: 12px;">
                        <strong>Status funcție cnp_exists:</strong> 
                        <?php echo $cnp_exists_result === $should_exist ? '✅ CORECT' : '❌ INCORECT'; ?>
                    </div>
                </div>
            </div>
            
            <div class="test-actions">
                <a href="<?php echo admin_url('admin.php?page=clinica-patients'); ?>" class="test-btn">
                    📊 Pagina Pacienți (Admin)
                </a>
                
                <a href="<?php echo home_url('/receptionist-dashboard/'); ?>" class="test-btn success">
                    👩‍💼 Dashboard Receptionist
                </a>
                
                <a href="test-cnp-duplicate-check.php" class="test-btn">
                    🔍 Test Formular CNP
                </a>
            </div>
            
            <div class="debug-info">
                <strong>Debug Info:</strong>
                
                Test CNP: <?php echo $test_cnp; ?>
                
                Tabela pacienți: <?php echo $table_name; ?>
                Exists in patients: <?php echo $exists_in_patients; ?>
                
                Tabela utilizatori: <?php echo $wpdb->users; ?>
                Exists in users: <?php echo $exists_in_users; ?>
                
                Funcție cnp_exists result: <?php echo $cnp_exists_result ? 'true' : 'false'; ?>
                
                Query pacienți: <?php echo $patient_query; ?>
                Query utilizatori: <?php echo $user_query; ?>
            </div>
        </div>
    </div>
    
    <script>
    function testAjaxHandler() {
        var resultDiv = document.getElementById('ajax-result');
        resultDiv.innerHTML = '🔄 Testare în curs...';
        
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
                    resultDiv.innerHTML = '<span style="color: green;">✅ AJAX Handler funcționează - CNP există: ' + response.data.exists + '</span>';
                } else {
                    resultDiv.innerHTML = '<span style="color: red;">❌ AJAX Handler eroare: ' + response.data + '</span>';
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error);
                resultDiv.innerHTML = '<span style="color: red;">❌ Eroare AJAX: ' + error + '</span>';
            }
        });
    }
    
    // Funcție helper pentru a obține rolul utilizatorului
    function getUserRole(userId) {
        // Această funcție ar trebui să fie implementată în PHP
        return 'clinica_patient';
    }
    </script>
</body>
</html> 