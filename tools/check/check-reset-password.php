<?php
/**
 * Script pentru verificarea și resetarea parolelor pacienților
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Verificare și Resetare Parole Pacienți</h1>";

// Verifică dacă clasele sunt încărcate
if (!class_exists('Clinica_Password_Generator')) {
    echo "<p style='color: red;'>❌ Clasa Clinica_Password_Generator nu este încărcată!</p>";
    exit;
}

$password_generator = new Clinica_Password_Generator();

// Verifică pacienții
echo "<h2>1. Pacienți și Parolele Lor</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';

$patients = $wpdb->get_results("
    SELECT p.user_id, p.cnp, p.password_method, u.user_login, u.user_email, u.display_name, u.user_pass
    FROM $table_name p
    JOIN {$wpdb->users} u ON p.user_id = u.ID
    LIMIT 5
");

if (!$patients) {
    echo "<p style='color: red;'>❌ Nu există pacienți în baza de date!</p>";
    exit;
}

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Nume</th><th>CNP</th><th>Metoda Parolă</th><th>Parola Generată</th><th>Test Parolă</th><th>Acțiune</th></tr>";

foreach ($patients as $patient) {
    echo "<tr>";
    echo "<td>{$patient->user_id}</td>";
    echo "<td>{$patient->display_name}</td>";
    echo "<td>{$patient->cnp}</td>";
    echo "<td>{$patient->password_method}</td>";
    
    // Generează parola conform metodei
    $generated_password = '';
    if ($patient->password_method === 'cnp') {
        $generated_password = substr($patient->cnp, 0, 6);
    } elseif ($patient->password_method === 'birth_date') {
        // Extrage data nașterii din CNP
        $birth_date = substr($patient->cnp, 1, 6);
        $generated_password = $birth_date;
    }
    
    echo "<td><strong>{$generated_password}</strong></td>";
    
    // Testează parola
    $password_works = wp_check_password($generated_password, $patient->user_pass, $patient->user_id);
    echo "<td>" . ($password_works ? '✅ Da' : '❌ Nu') . "</td>";
    
    // Buton pentru resetare
    echo "<td>";
    if (!$password_works) {
        echo "<button onclick='resetPassword({$patient->user_id}, \"{$generated_password}\")' style='background: #0073aa; color: white; border: none; padding: 5px 10px; cursor: pointer;'>Resetează</button>";
    } else {
        echo "<span style='color: green;'>✅ OK</span>";
    }
    echo "</td>";
    
    echo "</tr>";
}
echo "</table>";

// Testează autentificarea cu parola corectă
echo "<h2>2. Test Autentificare cu Parola Corectă</h2>";

if ($patients) {
    $test_patient = $patients[0];
    
    // Generează parola corectă
    $correct_password = '';
    if ($test_patient->password_method === 'cnp') {
        $correct_password = substr($test_patient->cnp, 0, 6);
    } elseif ($test_patient->password_method === 'birth_date') {
        $birth_date = substr($test_patient->cnp, 1, 6);
        $correct_password = $birth_date;
    }
    
    echo "<h3>Test cu pacientul: {$test_patient->display_name}</h3>";
    echo "<p><strong>CNP:</strong> {$test_patient->cnp}</p>";
    echo "<p><strong>Metoda parolă:</strong> {$test_patient->password_method}</p>";
    echo "<p><strong>Parola corectă:</strong> <span style='background: yellow; padding: 2px;'>{$correct_password}</span></p>";
    
    // Testează parola
    $password_works = wp_check_password($correct_password, $test_patient->user_pass, $test_patient->user_id);
    echo "<p><strong>Parola funcționează:</strong> " . ($password_works ? '✅ Da' : '❌ Nu') . "</p>";
    
    if ($password_works) {
        echo "<h4>Test AJAX Login cu parola corectă:</h4>";
        
        // Simulează AJAX login
        $_POST = array(
            'action' => 'clinica_login',
            'clinica_frontend_nonce' => wp_create_nonce('clinica_login'),
            'identifier' => $test_patient->cnp,
            'password' => $correct_password,
            'remember' => '0'
        );
        
        echo "<p><strong>Datele trimise:</strong></p>";
        echo "<pre>" . print_r($_POST, true) . "</pre>";
        
        // Simulează AJAX call
        if (class_exists('Clinica_Authentication')) {
            $auth = new Clinica_Authentication();
            
            ob_start();
            try {
                $auth->ajax_login();
            } catch (Exception $e) {
                echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
            }
            $output = ob_get_clean();
            
            echo "<p><strong>Răspuns AJAX:</strong></p>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
            
            // Decodifică JSON
            $json_start = strpos($output, '{');
            if ($json_start !== false) {
                $json_part = substr($output, $json_start);
                $response = json_decode($json_part, true);
                
                if ($response) {
                    if (isset($response['success']) && $response['success']) {
                        echo "<p style='color: green; font-size: 18px;'>🎉 LOGIN REUȘIT!</p>";
                        if (isset($response['data']['redirect_url'])) {
                            echo "<p style='color: blue;'>Redirect: {$response['data']['redirect_url']}</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>❌ Login eșuat: " . (isset($response['data']) ? $response['data'] : 'Eroare necunoscută') . "</p>";
                    }
                }
            }
        }
    }
}

// Formular pentru resetare manuală
echo "<h2>3. Resetare Manuală Parolă</h2>";
echo "<form method='post' style='border: 1px solid #ccc; padding: 20px; border-radius: 5px;'>";
echo "<h3>Resetează parola pentru un pacient specific:</h3>";
echo "<p><label>ID Pacient: <input type='number' name='patient_id' required></label></p>";
echo "<p><label>Metoda parolă: <select name='password_method'>";
echo "<option value='cnp'>Primele 6 cifre CNP</option>";
echo "<option value='birth_date'>Data nașterii (DDMMYY)</option>";
echo "</select></label></p>";
echo "<p><input type='submit' name='reset_password' value='Resetează Parola' style='background: #0073aa; color: white; border: none; padding: 10px 20px; cursor: pointer;'></p>";
echo "</form>";

// Procesează resetarea
if (isset($_POST['reset_password']) && isset($_POST['patient_id'])) {
    $patient_id = intval($_POST['patient_id']);
    $password_method = sanitize_text_field($_POST['password_method']);
    
    $patient = $wpdb->get_row($wpdb->prepare(
        "SELECT p.cnp, u.user_login, u.display_name FROM $table_name p 
         JOIN {$wpdb->users} u ON p.user_id = u.ID 
         WHERE p.user_id = %d",
        $patient_id
    ));
    
    if ($patient) {
        // Generează parola nouă
        $new_password = '';
        if ($password_method === 'cnp') {
            $new_password = substr($patient->cnp, 0, 6);
        } elseif ($password_method === 'birth_date') {
            $birth_date = substr($patient->cnp, 1, 6);
            $new_password = $birth_date;
        }
        
        // Resetează parola
        wp_set_password($new_password, $patient_id);
        
        // Actualizează metoda în baza de date
        $wpdb->update($table_name, array('password_method' => $password_method), array('user_id' => $patient_id));
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ Parola resetată cu succes!</h3>";
        echo "<p><strong>Pacient:</strong> {$patient->display_name}</p>";
        echo "<p><strong>CNP:</strong> {$patient->cnp}</p>";
        echo "<p><strong>Metoda parolă:</strong> {$password_method}</p>";
        echo "<p><strong>Parola nouă:</strong> <span style='background: yellow; padding: 5px; font-size: 18px; font-weight: bold;'>{$new_password}</span></p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Pacientul nu a fost găsit!</p>";
    }
}

echo "<h2>4. Linkuri pentru Testare</h2>";
echo "<ul>";
echo "<li><a href='" . home_url('/clinica-login/') . "' target='_blank'>Pagina Login</a></li>";
echo "<li><a href='" . home_url('/clinica-patient-dashboard/') . "' target='_blank'>Dashboard Pacient</a></li>";
echo "</ul>";

echo "<h2>5. Instrucțiuni pentru Testare</h2>";
echo "<ol>";
echo "<li>Găsiți parola corectă din tabelul de mai sus</li>";
echo "<li>Mergeți la pagina de login</li>";
echo "<li>Introduceți CNP-ul ca identificator</li>";
echo "<li>Introduceți parola corectă (primele 6 cifre CNP sau data nașterii)</li>";
echo "<li>Ar trebui să fiți redirecționați către dashboard</li>";
echo "</ol>";

echo "<script>";
echo "function resetPassword(userId, password) {
    if (confirm('Sigur vrei să resetezi parola pentru utilizatorul ' + userId + '?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type=\"hidden\" name=\"patient_id\" value=\"' + userId + '\">' +
                        '<input type=\"hidden\" name=\"password_method\" value=\"cnp\">' +
                        '<input type=\"hidden\" name=\"reset_password\" value=\"1\">';
        document.body.appendChild(form);
        form.submit();
    }
}";
echo "</script>";
?> 