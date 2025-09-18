<?php
/**
 * Test Afișare Pacienți - Clinica
 * 
 * Acest script testează și corectează problema cu afișarea pacienților în backend
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

global $wpdb;

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Afișare Pacienți - Clinica</title>
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
        .code-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin: 10px 0;
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
        .test-button.danger {
            background: #dc3545;
        }
        .test-button.danger:hover {
            background: #c82333;
        }
        .test-button.success {
            background: #28a745;
        }
        .test-button.success:hover {
            background: #218838;
        }
        .fix-result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 5px;
            background: #f8f9fa;
            border-left: 4px solid #0073aa;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🔧 Test Afișare Pacienți - Clinica</h1>
        
        <div class="status info">
            <strong>Scop:</strong> Testează și corectează problema cu afișarea pacienților în backend.
        </div>

        <!-- Test 1: Verificare Tabelă -->
        <div class="test-section">
            <h3>📋 Test 1: Verificare Tabelă clinica_patients</h3>
            
            <?php
            $table_name = $wpdb->prefix . 'clinica_patients';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            if ($table_exists) {
                echo '<div class="status success">✅ Tabela clinica_patients există</div>';
                
                $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                echo '<div class="status info">Total pacienți în tabelă: <strong>' . $total_patients . '</strong></div>';
                
                if ($total_patients == 0) {
                    echo '<div class="status warning">⚠️ Nu există pacienți în baza de date!</div>';
                    echo '<button class="test-button success" onclick="createSamplePatients()">➕ Creează Pacienți Test</button>';
                }
            } else {
                echo '<div class="status error">❌ Tabela clinica_patients NU există!</div>';
                echo '<button class="test-button danger" onclick="createTable()">🏗️ Creează Tabelă</button>';
            }
            ?>
        </div>

        <!-- Test 2: Verificare Permisiuni -->
        <div class="test-section">
            <h3>🔐 Test 2: Verificare Permisiuni</h3>
            
            <?php
            $current_user_id = get_current_user_id();
            $current_user = wp_get_current_user();
            
            echo '<div class="code-block">';
            echo '<strong>Utilizator curent:</strong><br>';
            echo 'ID: ' . $current_user_id . '<br>';
            echo 'Username: ' . $current_user->user_login . '<br>';
            echo 'Email: ' . $current_user->user_email . '<br>';
            echo 'Roluri: ' . implode(', ', $current_user->roles) . '<br>';
            echo '</div>';
            
            // Verifică permisiunile specifice
            $permissions_to_check = [
                'clinica_view_patients',
                'clinica_create_patients',
                'clinica_edit_patients',
                'clinica_manage_patients'
            ];
            
            echo '<div class="code-block">';
            echo '<strong>Verificare permisiuni:</strong><br>';
            
            foreach ($permissions_to_check as $permission) {
                $has_permission = user_can($current_user_id, $permission);
                $status_class = $has_permission ? 'success' : 'error';
                $status_icon = $has_permission ? '✅' : '❌';
                echo '<div class="status ' . $status_class . '">' . $status_icon . ' ' . $permission . ': ' . ($has_permission ? 'Da' : 'Nu') . '</div>';
            }
            echo '</div>';
            
            // Verifică dacă utilizatorul are permisiuni de administrator
            if (user_can($current_user_id, 'manage_options')) {
                echo '<div class="status success">✅ Utilizatorul are permisiuni de administrator WordPress</div>';
            } else {
                echo '<div class="status warning">⚠️ Utilizatorul nu are permisiuni de administrator WordPress</div>';
            }
            ?>
        </div>

        <!-- Test 3: Simulare Query Pagină Pacienți -->
        <div class="test-section">
            <h3>🔍 Test 3: Simulare Query Pagină Pacienți</h3>
            
            <?php
            if ($table_exists) {
                // Simulează exact query-ul din pagina pacienți
                $test_query = "SELECT p.*, u.user_email, u.display_name,
                              um1.meta_value as first_name, um2.meta_value as last_name
                              FROM $table_name p 
                              LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                              LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
                              LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
                              ORDER BY p.created_at DESC 
                              LIMIT 10";
                
                echo '<div class="code-block">';
                echo '<strong>Query test:</strong><br>';
                echo '<code>' . $test_query . '</code><br><br>';
                echo '</div>';
                
                $test_results = $wpdb->get_results($test_query);
                
                if ($test_results) {
                    echo '<div class="status success">✅ Query-ul returnează ' . count($test_results) . ' rezultate</div>';
                    
                    echo '<div class="code-block">';
                    echo '<strong>Rezultate:</strong><br>';
                    echo '<table class="test-table">';
                    echo '<tr><th>User ID</th><th>CNP</th><th>Email</th><th>Display Name</th><th>First Name</th><th>Last Name</th></tr>';
                    
                    foreach ($test_results as $result) {
                        echo '<tr>';
                        echo '<td>' . $result->user_id . '</td>';
                        echo '<td>' . $result->cnp . '</td>';
                        echo '<td>' . ($result->user_email ?: 'N/A') . '</td>';
                        echo '<td>' . ($result->display_name ?: 'N/A') . '</td>';
                        echo '<td>' . ($result->first_name ?: 'N/A') . '</td>';
                        echo '<td>' . ($result->last_name ?: 'N/A') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    echo '</div>';
                } else {
                    echo '<div class="status warning">⚠️ Query-ul nu returnează rezultate</div>';
                    
                    if ($wpdb->last_error) {
                        echo '<div class="status error">❌ Eroare SQL: ' . $wpdb->last_error . '</div>';
                    }
                }
            }
            ?>
        </div>

        <!-- Test 4: Verificare Utilizatori WordPress -->
        <div class="test-section">
            <h3>👤 Test 4: Verificare Utilizatori WordPress</h3>
            
            <?php
            // Verifică utilizatorii cu rol clinica_patient
            $patients_users = $wpdb->get_results("
                SELECT u.ID, u.user_login, u.user_email, u.display_name, um.meta_value as role
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities'
                WHERE um.meta_value LIKE '%clinica_patient%'
                ORDER BY u.ID DESC
                LIMIT 10
            ");
            
            echo '<div class="status info">Utilizatori cu rol clinica_patient: <strong>' . count($patients_users) . '</strong></div>';
            
            if ($patients_users) {
                echo '<div class="code-block">';
                echo '<table class="test-table">';
                echo '<tr><th>ID</th><th>Login</th><th>Email</th><th>Display Name</th><th>Rol</th></tr>';
                
                foreach ($patients_users as $user) {
                    echo '<tr>';
                    echo '<td>' . $user->ID . '</td>';
                    echo '<td>' . $user->user_login . '</td>';
                    echo '<td>' . $user->user_email . '</td>';
                    echo '<td>' . $user->display_name . '</td>';
                    echo '<td>' . $user->role . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            } else {
                echo '<div class="status warning">⚠️ Nu există utilizatori cu rol clinica_patient</div>';
            }
            ?>
        </div>

        <!-- Test 5: Corectare Probleme -->
        <div class="test-section">
            <h3>🔧 Test 5: Corectare Probleme Identificate</h3>
            
            <button class="test-button" onclick="fixPermissions()">🔐 Corectează Permisiuni</button>
            <button class="test-button" onclick="syncPatients()">🔄 Sincronizează Pacienți</button>
            <button class="test-button" onclick="createMissingUsers()">👤 Creează Utilizatori Lipsă</button>
            <button class="test-button" onclick="testPageAccess()">📄 Test Acces Pagină</button>
            
            <div id="fix-results" class="fix-result" style="display: none;"></div>
        </div>

        <!-- Test 6: Verificare Finală -->
        <div class="test-section">
            <h3>✅ Test 6: Verificare Finală</h3>
            
            <button class="test-button success" onclick="finalCheck()">🔍 Verificare Completă</button>
            <button class="test-button" onclick="window.open('admin.php?page=clinica-patients', '_blank')">👥 Deschide Pagina Pacienți</button>
            
            <div id="final-results" class="fix-result" style="display: none;"></div>
        </div>
    </div>

    <script>
    function createSamplePatients() {
        var resultsDiv = document.getElementById('fix-results');
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = '<div class="status info">Se creează pacienți test...</div>';
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_create_sample_patients',
                nonce: '<?php echo wp_create_nonce('clinica_test_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<div class="status success">✅ ' + response.data.count + ' pacienți test au fost creați cu succes!</div>';
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    resultsDiv.innerHTML = '<div class="status error">❌ Eroare la crearea pacienților: ' + response.data + '</div>';
                }
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="status error">❌ Eroare la crearea pacienților</div>';
            }
        });
    }
    
    function createTable() {
        var resultsDiv = document.getElementById('fix-results');
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = '<div class="status info">Se creează tabela...</div>';
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_create_patients_table',
                nonce: '<?php echo wp_create_nonce('clinica_test_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<div class="status success">✅ Tabela a fost creată cu succes!</div>';
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    resultsDiv.innerHTML = '<div class="status error">❌ Eroare la crearea tabelei: ' + response.data + '</div>';
                }
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="status error">❌ Eroare la crearea tabelei</div>';
            }
        });
    }
    
    function fixPermissions() {
        var resultsDiv = document.getElementById('fix-results');
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = '<div class="status info">Se corectează permisiunile...</div>';
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_fix_permissions',
                nonce: '<?php echo wp_create_nonce('clinica_test_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<div class="status success">✅ Permisiunile au fost corectate!</div>';
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    resultsDiv.innerHTML = '<div class="status error">❌ Eroare la corectarea permisiunilor: ' + response.data + '</div>';
                }
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="status error">❌ Eroare la corectarea permisiunilor</div>';
            }
        });
    }
    
    function syncPatients() {
        var resultsDiv = document.getElementById('fix-results');
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = '<div class="status info">Se sincronizează pacienții...</div>';
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_sync_patients',
                nonce: '<?php echo wp_create_nonce('clinica_test_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<div class="status success">✅ Sincronizarea completă! ' + response.data.message + '</div>';
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    resultsDiv.innerHTML = '<div class="status error">❌ Eroare la sincronizare: ' + response.data + '</div>';
                }
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="status error">❌ Eroare la sincronizare</div>';
            }
        });
    }
    
    function createMissingUsers() {
        var resultsDiv = document.getElementById('fix-results');
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = '<div class="status info">Se creează utilizatorii lipsă...</div>';
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_create_missing_users',
                nonce: '<?php echo wp_create_nonce('clinica_test_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<div class="status success">✅ ' + response.data.count + ' utilizatori au fost creați!</div>';
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    resultsDiv.innerHTML = '<div class="status error">❌ Eroare la crearea utilizatorilor: ' + response.data + '</div>';
                }
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="status error">❌ Eroare la crearea utilizatorilor</div>';
            }
        });
    }
    
    function testPageAccess() {
        var resultsDiv = document.getElementById('fix-results');
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = '<div class="status info">Se testează accesul la pagină...</div>';
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_test_page_access',
                nonce: '<?php echo wp_create_nonce('clinica_test_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<div class="status success">✅ Accesul la pagină funcționează! ' + response.data.message + '</div>';
                } else {
                    resultsDiv.innerHTML = '<div class="status error">❌ Problema cu accesul la pagină: ' + response.data + '</div>';
                }
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="status error">❌ Eroare la testarea accesului</div>';
            }
        });
    }
    
    function finalCheck() {
        var resultsDiv = document.getElementById('final-results');
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = '<div class="status info">Se efectuează verificarea finală...</div>';
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_final_check',
                nonce: '<?php echo wp_create_nonce('clinica_test_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<div class="status success">✅ Verificarea finală completă! ' + response.data.message + '</div>';
                } else {
                    resultsDiv.innerHTML = '<div class="status error">❌ Probleme identificate: ' + response.data + '</div>';
                }
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="status error">❌ Eroare la verificarea finală</div>';
            }
        });
    }
    </script>
</body>
</html> 