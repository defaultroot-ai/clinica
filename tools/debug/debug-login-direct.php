<?php
/**
 * Debug direct pentru autentificare
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Debug Direct Autentificare</h1>";

// Verifică dacă clasele sunt încărcate
if (!class_exists('Clinica_Authentication')) {
    echo "<p style='color: red;'>❌ Clasa Clinica_Authentication nu este încărcată!</p>";
    exit;
}

if (!class_exists('Clinica_Roles')) {
    echo "<p style='color: red;'>❌ Clasa Clinica_Roles nu este încărcată!</p>";
    exit;
}

echo "<p style='color: green;'>✅ Clasele sunt încărcate</p>";

$auth = new Clinica_Authentication();

// Testează un pacient existent
echo "<h2>1. Test Pacient Existente</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';

$patients = $wpdb->get_results("
    SELECT p.user_id, p.cnp, u.user_login, u.user_email, u.display_name
    FROM $table_name p
    JOIN {$wpdb->users} u ON p.user_id = u.ID
    LIMIT 3
");

if (!$patients) {
    echo "<p style='color: red;'>❌ Nu există pacienți în baza de date!</p>";
    exit;
}

foreach ($patients as $patient) {
    echo "<h3>Pacient: {$patient->display_name} (ID: {$patient->user_id})</h3>";
    
    // Testează find_user_by_identifier cu CNP
    echo "<h4>Test cu CNP: {$patient->cnp}</h4>";
    $user = $auth->find_user_by_identifier($patient->cnp);
    if ($user) {
        echo "<p style='color: green;'>✅ Găsit utilizator: {$user->display_name}</p>";
        
        // Verifică rolul
        $role = Clinica_Roles::get_user_role($user->ID);
        echo "<p>Rol: " . ($role ? $role : 'N/A') . "</p>";
        
        // Testează parola
        echo "<h4>Test parolă</h4>";
        $password_works = wp_check_password('test123', $user->user_pass, $user->ID);
        echo "<p>Parola 'test123' funcționează: " . ($password_works ? '✅ Da' : '❌ Nu') . "</p>";
        
        // Testează redirect-ul
        echo "<h4>Test redirect</h4>";
        if (Clinica_Roles::has_clinica_role($user->ID)) {
            $role = Clinica_Roles::get_user_role($user->ID);
            
            switch ($role) {
                case 'clinica_patient':
                    $redirect_url = home_url('/clinica-patient-dashboard/');
                    echo "<p style='color: blue;'>Redirect: {$redirect_url}</p>";
                    break;
                default:
                    $redirect_url = admin_url('admin.php?page=clinica-dashboard');
                    echo "<p style='color: blue;'>Redirect: {$redirect_url}</p>";
                    break;
            }
        } else {
            echo "<p style='color: red;'>❌ Utilizatorul nu are rol Clinica!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Nu s-a găsit utilizator cu CNP: {$patient->cnp}</p>";
    }
    
    echo "<hr>";
}

// Testează AJAX login direct
echo "<h2>2. Test AJAX Login Direct</h2>";

if ($patients) {
    $test_patient = $patients[0];
    echo "<h3>Test cu pacientul: {$test_patient->display_name}</h3>";
    
    // Simulează datele AJAX
    $_POST = array(
        'action' => 'clinica_login',
        'clinica_frontend_nonce' => wp_create_nonce('clinica_login'),
        'identifier' => $test_patient->cnp,
        'password' => 'test123',
        'remember' => '0'
    );
    
    echo "<h4>Datele trimise:</h4>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Simulează AJAX call
    ob_start();
    try {
        $auth->ajax_login();
    } catch (Exception $e) {
        echo "<p style='color: red;'>Eroare: " . $e->getMessage() . "</p>";
    }
    $output = ob_get_clean();
    
    echo "<h4>Răspuns AJAX:</h4>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Decodifică JSON pentru a vedea răspunsul
    $json_start = strpos($output, '{');
    if ($json_start !== false) {
        $json_part = substr($output, $json_start);
        $response = json_decode($json_part, true);
        
        if ($response) {
            echo "<h4>Răspuns decodificat:</h4>";
            echo "<pre>" . print_r($response, true) . "</pre>";
            
            if (isset($response['success']) && $response['success']) {
                echo "<p style='color: green;'>✅ Login reușit!</p>";
                if (isset($response['data']['redirect_url'])) {
                    echo "<p style='color: blue;'>Redirect URL: {$response['data']['redirect_url']}</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Login eșuat: " . (isset($response['data']) ? $response['data'] : 'Eroare necunoscută') . "</p>";
            }
        }
    }
}

// Testează și cu email
echo "<h2>3. Test cu Email</h2>";
if ($patients) {
    $test_patient = $patients[0];
    if (!empty($test_patient->user_email)) {
        echo "<h3>Test cu email: {$test_patient->user_email}</h3>";
        
        // Testează find_user_by_identifier cu email
        $user = $auth->find_user_by_identifier($test_patient->user_email);
        if ($user) {
            echo "<p style='color: green;'>✅ Găsit utilizator cu email: {$user->display_name}</p>";
        } else {
            echo "<p style='color: red;'>❌ Nu s-a găsit utilizator cu email: {$test_patient->user_email}</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Pacientul nu are email setat</p>";
    }
}

// Verifică paginile dashboard
echo "<h2>4. Verificare Pagini Dashboard</h2>";
$dashboard_pages = array(
    'clinica-patient-dashboard' => 'Dashboard Pacient',
    'clinica-doctor-dashboard' => 'Dashboard Doctor',
    'clinica-assistant-dashboard' => 'Dashboard Asistent',
    'clinica-manager-dashboard' => 'Dashboard Manager',
    'clinica-receptionist-dashboard' => 'Dashboard Receptionist'
);

foreach ($dashboard_pages as $slug => $name) {
    $page = get_page_by_path($slug);
    if ($page) {
        echo "<p style='color: green;'>✅ {$name}: /{$slug}/ (ID: {$page->ID})</p>";
    } else {
        echo "<p style='color: red;'>❌ {$name}: /{$slug}/ - NU EXISTĂ</p>";
    }
}

// Verifică meniul admin
echo "<h2>5. Verificare Meniu Admin</h2>";
$admin_url = admin_url('admin.php?page=clinica-dashboard');
echo "<p>URL Admin: <a href='{$admin_url}' target='_blank'>{$admin_url}</a></p>";

// Testează JavaScript
echo "<h2>6. Test JavaScript</h2>";
echo "<p>Pentru a testa JavaScript-ul:</p>";
echo "<ol>";
echo "<li>Deschideți pagina de login</li>";
echo "<li>Deschideți Developer Tools (F12)</li>";
echo "<li>Mergeți la tab-ul Console</li>";
echo "<li>Încercați să vă autentificați</li>";
echo "<li>Verificați erorile în console</li>";
echo "</ol>";

echo "<h3>Cod JavaScript așteptat:</h3>";
echo "<pre>";
echo "success: function(response) {
    if (response.success) {
        messages.html('<div class=\"success\">' + response.data.message + '</div>');
        setTimeout(function() {
            window.location.href = response.data.redirect_url;
        }, 2000);
    } else {
        messages.html('<div class=\"error\">' + response.data + '</div>');
    }
}";
echo "</pre>";

echo "<h2>7. Linkuri Directe pentru Testare</h2>";
echo "<ul>";
echo "<li><a href='" . home_url('/clinica-login/') . "' target='_blank'>Pagina Login</a></li>";
echo "<li><a href='" . home_url('/clinica-patient-dashboard/') . "' target='_blank'>Dashboard Pacient</a></li>";
echo "<li><a href='" . admin_url('admin.php?page=clinica-dashboard') . "' target='_blank'>Dashboard Admin</a></li>";
echo "</ul>";

echo "<h2>8. Rezumat Probleme Posibile</h2>";
echo "<ul>";
echo "<li><strong>Roluri lipsă:</strong> Utilizatorii nu au rolurile Clinica atribuite</li>";
echo "<li><strong>Parole incorecte:</strong> Parolele nu corespund cu cele din baza de date</li>";
echo "<li><strong>Paginile nu există:</strong> Paginile dashboard nu au fost create</li>";
echo "<li><strong>Erori JavaScript:</strong> Probleme în procesarea răspunsului AJAX</li>";
echo "<li><strong>Permisiuni:</strong> Utilizatorii nu au acces la paginile respective</li>";
echo "</ul>";
?> 