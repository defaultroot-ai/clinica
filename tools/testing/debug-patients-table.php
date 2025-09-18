<?php
/**
 * Debug Tabela Pacienți - Clinica
 * 
 * Acest script verifică tabela pacienților și datele din baza de date
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
    <title>Debug Tabela Pacienți - Clinica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .debug-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .debug-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .debug-section h3 {
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
        .debug-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .debug-table th,
        .debug-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .debug-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .debug-button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .debug-button:hover {
            background: #005a87;
        }
        .debug-button.danger {
            background: #dc3545;
        }
        .debug-button.danger:hover {
            background: #c82333;
        }
        .debug-button.success {
            background: #28a745;
        }
        .debug-button.success:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1>🔍 Debug Tabela Pacienți - Clinica</h1>
        
        <div class="status info">
            <strong>Scop:</strong> Verifică tabela pacienților și datele din baza de date pentru a identifica problemele cu afișarea.
        </div>

        <!-- Verificare Tabelă -->
        <div class="debug-section">
            <h3>📋 Verificare Tabelă clinica_patients</h3>
            
            <?php
            $table_name = $wpdb->prefix . 'clinica_patients';
            
            // Verifică dacă tabela există
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            if ($table_exists) {
                echo '<div class="status success">✅ Tabela clinica_patients există</div>';
                
                // Verifică structura tabelului
                $columns = $wpdb->get_results("DESCRIBE $table_name");
                echo '<div class="code-block">';
                echo '<strong>Structura tabelului:</strong><br>';
                echo '<table class="debug-table">';
                echo '<tr><th>Câmp</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
                
                foreach ($columns as $column) {
                    echo '<tr>';
                    echo '<td>' . $column->Field . '</td>';
                    echo '<td>' . $column->Type . '</td>';
                    echo '<td>' . $column->Null . '</td>';
                    echo '<td>' . $column->Key . '</td>';
                    echo '<td>' . $column->Default . '</td>';
                    echo '<td>' . $column->Extra . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
                
            } else {
                echo '<div class="status error">❌ Tabela clinica_patients NU există!</div>';
                echo '<div class="status warning">Tabela trebuie creată prin activarea plugin-ului.</div>';
            }
            ?>
        </div>

        <!-- Numărul de Pacienți -->
        <div class="debug-section">
            <h3>👥 Numărul de Pacienți</h3>
            
            <?php
            if ($table_exists) {
                $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                echo '<div class="status info">Total pacienți în tabelă: <strong>' . $total_patients . '</strong></div>';
                
                if ($total_patients == 0) {
                    echo '<div class="status warning">⚠️ Nu există pacienți în baza de date!</div>';
                    echo '<div class="status info">Cauze posibile:</div>';
                    echo '<ul>';
                    echo '<li>Nu s-au creat pacienți prin formularul de creare</li>';
                    echo '<li>Probleme la salvarea datelor</li>';
                    echo '<li>Tabela a fost ștearsă</li>';
                    echo '</ul>';
                } else {
                    echo '<div class="status success">✅ Există pacienți în baza de date</div>';
                }
            }
            ?>
        </div>

        <!-- Lista Pacienților -->
        <div class="debug-section">
            <h3>📝 Lista Pacienților</h3>
            
            <?php
            if ($table_exists && $total_patients > 0) {
                $patients = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10");
                
                echo '<div class="code-block">';
                echo '<strong>Ultimii 10 pacienți:</strong><br>';
                echo '<table class="debug-table">';
                echo '<tr><th>ID</th><th>User ID</th><th>CNP</th><th>Email</th><th>Telefon</th><th>Data Creării</th></tr>';
                
                foreach ($patients as $patient) {
                    echo '<tr>';
                    echo '<td>' . $patient->id . '</td>';
                    echo '<td>' . $patient->user_id . '</td>';
                    echo '<td>' . $patient->cnp . '</td>';
                    echo '<td>' . (isset($patient->user_email) ? $patient->user_email : 'N/A') . '</td>';
                    echo '<td>' . ($patient->phone_primary ?: 'N/A') . '</td>';
                    echo '<td>' . $patient->created_at . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            }
            ?>
        </div>

        <!-- Verificare Query Pagină Pacienți -->
        <div class="debug-section">
            <h3>🔍 Test Query Pagină Pacienți</h3>
            
            <?php
            if ($table_exists) {
                // Simulează query-ul din pagina pacienți
                $test_query = "SELECT p.*, u.user_email, u.display_name,
                              um1.meta_value as first_name, um2.meta_value as last_name
                              FROM $table_name p 
                              LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                              LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
                              LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
                              ORDER BY p.created_at DESC 
                              LIMIT 5";
                
                $test_results = $wpdb->get_results($test_query);
                
                echo '<div class="code-block">';
                echo '<strong>Query test:</strong><br>';
                echo '<code>' . $test_query . '</code><br><br>';
                
                if ($test_results) {
                    echo '<strong>Rezultate query test:</strong><br>';
                    echo '<table class="debug-table">';
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
                } else {
                    echo '<div class="status warning">Query-ul nu returnează rezultate</div>';
                }
                echo '</div>';
            }
            ?>
        </div>

        <!-- Verificare Utilizatori WordPress -->
        <div class="debug-section">
            <h3>👤 Verificare Utilizatori WordPress</h3>
            
            <?php
            if ($table_exists) {
                // Verifică utilizatorii cu rol clinica_patient
                $patients_users = $wpdb->get_results("
                    SELECT u.ID, u.user_login, u.user_email, u.display_name, um.meta_value as role
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities'
                    WHERE um.meta_value LIKE '%clinica_patient%'
                    ORDER BY u.ID DESC
                    LIMIT 10
                ");
                
                echo '<div class="code-block">';
                echo '<strong>Utilizatori cu rol clinica_patient:</strong><br>';
                
                if ($patients_users) {
                    echo '<table class="debug-table">';
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
                } else {
                    echo '<div class="status warning">Nu există utilizatori cu rol clinica_patient</div>';
                }
                echo '</div>';
            }
            ?>
        </div>

        <!-- Acțiuni de Debug -->
        <div class="debug-section">
            <h3>🛠️ Acțiuni de Debug</h3>
            
            <button class="debug-button" onclick="location.reload()">🔄 Reîncarcă Debug</button>
            <button class="debug-button success" onclick="window.open('admin.php?page=clinica-patients', '_blank')">👥 Verifică Pagina Pacienți</button>
            <button class="debug-button" onclick="window.open('admin.php?page=clinica-create-patient', '_blank')">➕ Creează Pacient Test</button>
            <button class="debug-button" onclick="window.open('admin.php?page=clinica-dashboard', '_blank')">📊 Dashboard</button>
            
            <hr style="margin: 20px 0;">
            
            <h4>Teste Rapide:</h4>
            <button class="debug-button" onclick="testDatabaseConnection()">🔗 Test Conexiune DB</button>
            <button class="debug-button" onclick="testPatientQuery()">🔍 Test Query Pacienți</button>
            <button class="debug-button" onclick="createTestPatient()">➕ Creează Pacient Test AJAX</button>
            
            <div id="test-results" style="margin-top: 15px;"></div>
        </div>

        <!-- Informații Sistem -->
        <div class="debug-section">
            <h3>ℹ️ Informații Sistem</h3>
            
            <div class="code-block">
                <strong>Versiune WordPress:</strong> <?php echo get_bloginfo('version'); ?><br>
                <strong>Versiune PHP:</strong> <?php echo PHP_VERSION; ?><br>
                <strong>Prefix tabel:</strong> <?php echo $wpdb->prefix; ?><br>
                <strong>Database name:</strong> <?php echo DB_NAME; ?><br>
                <strong>Plugin activat:</strong> <?php echo is_plugin_active('clinica/clinica.php') ? 'Da' : 'Nu'; ?><br>
                <strong>User ID curent:</strong> <?php echo get_current_user_id(); ?><br>
                <strong>User roles:</strong> <?php echo implode(', ', wp_get_current_user()->roles); ?><br>
            </div>
        </div>
    </div>

    <script>
    function testDatabaseConnection() {
        var resultsDiv = document.getElementById('test-results');
        resultsDiv.innerHTML = '<div class="status info">Se testează conexiunea la baza de date...</div>';
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_test_db_connection',
                nonce: '<?php echo wp_create_nonce('clinica_debug_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<div class="status success">✅ Conexiunea la baza de date funcționează</div>';
                } else {
                    resultsDiv.innerHTML = '<div class="status error">❌ Eroare la conexiunea cu baza de date: ' + response.data + '</div>';
                }
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="status error">❌ Eroare la testarea conexiunii</div>';
            }
        });
    }
    
    function testPatientQuery() {
        var resultsDiv = document.getElementById('test-results');
        resultsDiv.innerHTML = '<div class="status info">Se testează query-ul pacienților...</div>';
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_test_patient_query',
                nonce: '<?php echo wp_create_nonce('clinica_debug_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<div class="status success">✅ Query-ul pacienților funcționează. Găsiți ' + response.data.count + ' pacienți.</div>';
                } else {
                    resultsDiv.innerHTML = '<div class="status error">❌ Eroare la query-ul pacienților: ' + response.data + '</div>';
                }
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="status error">❌ Eroare la testarea query-ului</div>';
            }
        });
    }
    
    function createTestPatient() {
        var resultsDiv = document.getElementById('test-results');
        resultsDiv.innerHTML = '<div class="status info">Se creează pacient test...</div>';
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_create_test_patient',
                nonce: '<?php echo wp_create_nonce('clinica_debug_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<div class="status success">✅ Pacient test creat cu succes! ID: ' + response.data.user_id + '</div>';
                } else {
                    resultsDiv.innerHTML = '<div class="status error">❌ Eroare la crearea pacientului test: ' + response.data + '</div>';
                }
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="status error">❌ Eroare la crearea pacientului test</div>';
            }
        });
    }
    </script>
</body>
</html> 