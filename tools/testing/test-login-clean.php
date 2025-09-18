<?php
/**
 * Test curat pentru autentificare fără conflicte JavaScript
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Test Curat Autentificare</h1>";

// Verifică dacă clasele sunt încărcate
if (!class_exists('Clinica_Authentication')) {
    echo "<p style='color: red;'>❌ Clasa Clinica_Authentication nu este încărcată!</p>";
    exit;
}

echo "<p style='color: green;'>✅ Clasa Clinica_Authentication este încărcată</p>";

$auth = new Clinica_Authentication();

// Testează un pacient
echo "<h2>1. Test Pacient</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';

$patient = $wpdb->get_row("
    SELECT p.user_id, p.cnp, p.password_method, u.user_login, u.user_email, u.display_name
    FROM $table_name p
    JOIN {$wpdb->users} u ON p.user_id = u.ID
    LIMIT 1
");

if (!$patient) {
    echo "<p style='color: red;'>❌ Nu există pacienți în baza de date!</p>";
    exit;
}

echo "<h3>Pacient: {$patient->display_name}</h3>";
echo "<p><strong>CNP:</strong> {$patient->cnp}</p>";
echo "<p><strong>Metoda parolă:</strong> {$patient->password_method}</p>";

// Generează parola corectă
$correct_password = '';
if ($patient->password_method === 'cnp') {
    $correct_password = substr($patient->cnp, 0, 6);
} elseif ($patient->password_method === 'birth_date') {
    $birth_date = substr($patient->cnp, 1, 6);
    $correct_password = $birth_date;
}

echo "<p><strong>Parola corectă:</strong> <span style='background: yellow; padding: 5px; font-size: 18px; font-weight: bold;'>{$correct_password}</span></p>";

// Testează autentificarea
echo "<h2>2. Test Autentificare</h2>";

// Simulează AJAX login
$_POST = array(
    'action' => 'clinica_login',
    'clinica_frontend_nonce' => wp_create_nonce('clinica_login'),
    'identifier' => $patient->cnp,
    'password' => $correct_password,
    'remember' => '0'
);

echo "<h3>Datele trimise:</h3>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

// Simulează AJAX call
ob_start();
try {
    $auth->ajax_login();
} catch (Exception $e) {
    echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
}
$output = ob_get_clean();

echo "<h3>Răspuns AJAX:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Decodifică JSON
$json_start = strpos($output, '{');
if ($json_start !== false) {
    $json_part = substr($output, $json_start);
    $response = json_decode($json_part, true);
    
    if ($response) {
        echo "<h3>Răspuns decodificat:</h3>";
        echo "<pre>" . print_r($response, true) . "</pre>";
        
        if (isset($response['success']) && $response['success']) {
            echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h2 style='color: green;'>🎉 LOGIN REUȘIT!</h2>";
            echo "<p><strong>Mesaj:</strong> " . $response['data']['message'] . "</p>";
            if (isset($response['data']['redirect_url'])) {
                echo "<p><strong>Redirect URL:</strong> <a href='{$response['data']['redirect_url']}' target='_blank'>{$response['data']['redirect_url']}</a></p>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h2 style='color: red;'>❌ LOGIN EȘUAT!</h2>";
            echo "<p><strong>Eroare:</strong> " . (isset($response['data']) ? $response['data'] : 'Eroare necunoscută') . "</p>";
            echo "</div>";
        }
    }
}

// Verifică paginile
echo "<h2>3. Verificare Pagini</h2>";

$login_page = get_page_by_path('clinica-login');
if ($login_page) {
    echo "<p style='color: green;'>✅ Pagina login există: <a href='" . home_url('/clinica-login/') . "' target='_blank'>/clinica-login/</a></p>";
} else {
    echo "<p style='color: red;'>❌ Pagina login nu există!</p>";
}

$dashboard_page = get_page_by_path('clinica-patient-dashboard');
if ($dashboard_page) {
    echo "<p style='color: green;'>✅ Pagina dashboard pacient există: <a href='" . home_url('/clinica-patient-dashboard/') . "' target='_blank'>/clinica-patient-dashboard/</a></p>";
} else {
    echo "<p style='color: red;'>❌ Pagina dashboard pacient nu există!</p>";
}

// Testează și cu email
echo "<h2>4. Test cu Email</h2>";
if (!empty($patient->user_email)) {
    echo "<p><strong>Email:</strong> {$patient->user_email}</p>";
    
    // Testează find_user_by_identifier cu email
    $user = $auth->find_user_by_identifier($patient->user_email);
    if ($user) {
        echo "<p style='color: green;'>✅ Găsit utilizator cu email: {$user->display_name}</p>";
        
        // Testează login cu email
        $_POST['identifier'] = $patient->user_email;
        
        ob_start();
        try {
            $auth->ajax_login();
        } catch (Exception $e) {
            echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
        }
        $output = ob_get_clean();
        
        $json_start = strpos($output, '{');
        if ($json_start !== false) {
            $json_part = substr($output, $json_start);
            $response = json_decode($json_part, true);
            
            if ($response && isset($response['success']) && $response['success']) {
                echo "<p style='color: green;'>✅ Login cu email reușit!</p>";
            } else {
                echo "<p style='color: red;'>❌ Login cu email eșuat: " . (isset($response['data']) ? $response['data'] : 'Eroare necunoscută') . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Nu s-a găsit utilizator cu email: {$patient->user_email}</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Pacientul nu are email setat</p>";
}

echo "<h2>5. Linkuri pentru Testare Finală</h2>";
echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 5px;'>";
echo "<h3>🎯 Testează acum autentificarea în browser:</h3>";
echo "<ol>";
echo "<li><a href='" . home_url('/clinica-login/') . "' target='_blank' style='font-size: 18px; font-weight: bold;'>Accesează pagina de login</a></li>";
echo "<li>Introduceți CNP-ul: <strong>{$patient->cnp}</strong></li>";
echo "<li>Introduceți parola: <strong>{$correct_password}</strong></li>";
echo "<li>Apăsați 'Autentificare'</li>";
echo "<li>Ar trebui să fiți redirecționați către dashboard</li>";
echo "</ol>";
echo "</div>";

echo "<h2>6. Verificare JavaScript</h2>";
echo "<p>Pentru a verifica că nu mai sunt erori JavaScript:</p>";
echo "<ol>";
echo "<li>Deschideți Developer Tools (F12)</li>";
echo "<li>Mergeți la tab-ul Console</li>";
echo "<li>Accesați pagina de login</li>";
echo "<li>Verificați că nu apar erori</li>";
echo "<li>Încercați să vă autentificați</li>";
echo "<li>Verificați că redirect-ul funcționează</li>";
echo "</ol>";

echo "<h2>7. Rezumat</h2>";
echo "<p>Dacă totul funcționează corect:</p>";
echo "<ul>";
echo "<li>✅ Login-ul se procesează prin AJAX</li>";
echo "<li>✅ Nu apar erori JavaScript</li>";
echo "<li>✅ Utilizatorul este redirecționat către dashboard</li>";
echo "<li>✅ Autentificarea funcționează cu CNP și email</li>";
echo "</ul>";
?> 