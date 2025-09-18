<?php
/**
 * Adaugă Pacient Test
 * 
 * Acest script adaugă un pacient test în tabelul clinica_patients
 * pentru a testa funcționalitatea admin dashboard-ului
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

// Procesează adăugarea pacientului test
if (isset($_POST['add_test_patient'])) {
    $patients_table = $wpdb->prefix . 'clinica_patients';
    
    // Date pentru pacientul test
    $test_patients = array(
        array(
            'first_name' => 'Ion',
            'last_name' => 'Popescu',
            'cnp' => '1800404080170',
            'email' => 'ion.popescu@example.com',
            'phone' => '0722123456',
            'birth_date' => '1980-04-04',
            'gender' => 'male',
            'address' => 'Strada Exemplu, Nr. 123, București',
            'created_at' => current_time('mysql')
        ),
        array(
            'first_name' => 'Maria',
            'last_name' => 'Ionescu',
            'cnp' => '2850515123456',
            'email' => 'maria.ionescu@example.com',
            'phone' => '0733123456',
            'birth_date' => '1985-05-15',
            'gender' => 'female',
            'address' => 'Strada Test, Nr. 456, Cluj',
            'created_at' => current_time('mysql')
        ),
        array(
            'first_name' => 'Vasile',
            'last_name' => 'Dumitrescu',
            'cnp' => '1900606234567',
            'email' => 'vasile.dumitrescu@example.com',
            'phone' => '0744123456',
            'birth_date' => '1990-06-06',
            'gender' => 'male',
            'address' => 'Strada Demo, Nr. 789, Timișoara',
            'created_at' => current_time('mysql')
        )
    );
    
    $success_count = 0;
    $errors = array();
    
    foreach ($test_patients as $patient) {
        $result = $wpdb->insert($patients_table, $patient);
        
        if ($result !== false) {
            $success_count++;
        } else {
            $errors[] = "Eroare la adăugarea pacientului {$patient['first_name']} {$patient['last_name']}: " . $wpdb->last_error;
        }
    }
    
    $message = '';
    $message_type = '';
    
    if ($success_count > 0) {
        $message = "✅ Au fost adăugați $success_count pacienți test cu succes!";
        $message_type = 'success';
    }
    
    if (!empty($errors)) {
        $message .= "<br>⚠️ Erori: " . implode('<br>', $errors);
        $message_type = $message_type === 'success' ? 'warning' : 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaugă Pacienți Test - Clinica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .section h3 {
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
        .button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .button:hover {
            background: #005a87;
        }
        .button.danger {
            background: #dc3545;
        }
        .button.danger:hover {
            background: #c82333;
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
        .table-container {
            overflow-x: auto;
            margin: 15px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th,
        .data-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .data-table tr:hover {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👥 Adaugă Pacienți Test</h1>
        
        <div class="status info">
            <strong>Scop:</strong> Adaugă pacienți test în tabelul clinica_patients pentru a testa funcționalitatea admin dashboard-ului.
        </div>

        <?php if (isset($message)): ?>
        <div class="status <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Verificare Tabel -->
        <div class="section">
            <h3>📊 Verificare Tabel Pacienți</h3>
            
            <?php
            $patients_table = $wpdb->prefix . 'clinica_patients';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$patients_table'");
            
            if ($table_exists) {
                echo '<div class="status success">✅ Tabelul <code>' . $patients_table . '</code> există</div>';
                
                $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $patients_table");
                echo '<div class="status info">Total pacienți în tabel: <strong>' . $total_patients . '</strong></div>';
                
                if ($total_patients > 0) {
                    echo '<div class="status success">✅ Tabelul conține deja pacienți</div>';
                } else {
                    echo '<div class="status warning">⚠️ Tabelul este gol - poți adăuga pacienți test</div>';
                }
            } else {
                echo '<div class="status error">❌ Tabelul <code>' . $patients_table . '</code> NU există!</div>';
                echo '<div class="status warning">⚠️ Trebuie să activezi plugin-ul pentru a crea tabelele</div>';
            }
            ?>
        </div>

        <!-- Pacienți Test -->
        <div class="section">
            <h3>🧪 Pacienți Test de Adăugat</h3>
            
            <div class="code-block">
                <strong>Pacienții test care vor fi adăugați:</strong><br><br>
                
                1. <strong>Ion Popescu</strong><br>
                - CNP: 1800404080170<br>
                - Email: ion.popescu@example.com<br>
                - Telefon: 0722123456<br>
                - Data nașterii: 1980-04-04<br><br>
                
                2. <strong>Maria Ionescu</strong><br>
                - CNP: 2850515123456<br>
                - Email: maria.ionescu@example.com<br>
                - Telefon: 0733123456<br>
                - Data nașterii: 1985-05-15<br><br>
                
                3. <strong>Vasile Dumitrescu</strong><br>
                - CNP: 1900606234567<br>
                - Email: vasile.dumitrescu@example.com<br>
                - Telefon: 0744123456<br>
                - Data nașterii: 1990-06-06
            </div>
            
            <?php if ($table_exists): ?>
            <form method="post">
                <button type="submit" name="add_test_patient" class="button">
                    ➕ Adaugă Pacienți Test
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Pacienți Existenți -->
        <div class="section">
            <h3>📋 Pacienți Existenți</h3>
            
            <?php
            if ($table_exists) {
                $patients = $wpdb->get_results("SELECT * FROM $patients_table ORDER BY created_at DESC LIMIT 10");
                
                if ($patients) {
                    echo '<div class="table-container">';
                    echo '<table class="data-table">';
                    echo '<thead><tr><th>ID</th><th>Nume</th><th>CNP</th><th>Email</th><th>Telefon</th><th>Data Creării</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($patients as $patient) {
                        echo '<tr>';
                        echo '<td>' . $patient->id . '</td>';
                        echo '<td>' . $patient->first_name . ' ' . $patient->last_name . '</td>';
                        echo '<td>' . $patient->cnp . '</td>';
                        echo '<td>' . $patient->email . '</td>';
                        echo '<td>' . $patient->phone . '</td>';
                        echo '<td>' . $patient->created_at . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div>';
                } else {
                    echo '<div class="status warning">Nu există pacienți în tabel.</div>';
                }
            }
            ?>
        </div>

        <!-- Acțiuni -->
        <div class="section">
            <h3>🔧 Acțiuni</h3>
            
            <a href="<?php echo admin_url('admin.php?page=clinica'); ?>" class="button">
                📊 Vezi Admin Dashboard
            </a>
            
            <a href="debug-patients-table.php" class="button">
                🔍 Debug Tabel Pacienți
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=clinica-patients'); ?>" class="button">
                👥 Vezi Pagina Pacienți
            </a>
            
            <button onclick="location.reload()" class="button">
                🔄 Reîmprospătează Pagina
            </button>
        </div>

        <!-- Informații Tehnice -->
        <div class="section">
            <h3>🔍 Informații Tehnice</h3>
            
            <div class="code-block">
                <strong>Tabel:</strong> <?php echo $patients_table; ?><br>
                <strong>Prefix WordPress:</strong> <?php echo $wpdb->prefix; ?><br>
                <strong>Database:</strong> <?php echo DB_NAME; ?><br>
                <strong>Host:</strong> <?php echo DB_HOST; ?><br>
                <strong>Utilizator:</strong> <?php echo DB_USER; ?>
            </div>
        </div>
    </div>
</body>
</html> 