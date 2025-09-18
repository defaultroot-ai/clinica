<?php
/**
 * Test script pentru verificarea redirect-ului după autentificare
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Test Redirect După Autentificare</h1>";

// Verifică dacă clasele sunt încărcate
if (!class_exists('Clinica_Authentication')) {
    echo "<p style='color: red;'>Clasa Clinica_Authentication nu este încărcată!</p>";
    exit;
}

if (!class_exists('Clinica_Roles')) {
    echo "<p style='color: red;'>Clasa Clinica_Roles nu este încărcată!</p>";
    exit;
}

$auth = new Clinica_Authentication();

// Verifică utilizatorii cu roluri Clinica
echo "<h2>1. Utilizatori cu roluri Clinica</h2>";
global $wpdb;

$users_with_roles = $wpdb->get_results("
    SELECT u.ID, u.user_login, u.user_email, u.display_name, um.meta_value as roles
    FROM {$wpdb->users} u
    JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = '{$wpdb->prefix}capabilities'
    ORDER BY u.ID
    LIMIT 10
");

if (!$users_with_roles) {
    echo "<p style='color: red;'>Nu există utilizatori în baza de date.</p>";
    exit;
}

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Nume</th><th>Roluri</th><th>Test Redirect</th></tr>";

foreach ($users_with_roles as $user_data) {
    $user_roles = maybe_unserialize($user_data->roles);
    $roles_list = is_array($user_roles) ? implode(', ', array_keys($user_roles)) : 'N/A';
    
    echo "<tr>";
    echo "<td>{$user_data->ID}</td>";
    echo "<td>{$user_data->user_login}</td>";
    echo "<td>{$user_data->user_email}</td>";
    echo "<td>{$user_data->display_name}</td>";
    echo "<td>{$roles_list}</td>";
    
    // Testează redirect-ul pentru acest utilizator
    $redirect_url = '';
    if (Clinica_Roles::has_clinica_role($user_data->ID)) {
        $role = Clinica_Roles::get_user_role($user_data->ID);
        
        switch ($role) {
            case 'clinica_patient':
                $redirect_url = home_url('/clinica-patient-dashboard/');
                break;
            case 'clinica_doctor':
            case 'clinica_assistant':
            case 'clinica_receptionist':
            case 'clinica_manager':
            case 'clinica_administrator':
                $redirect_url = admin_url('admin.php?page=clinica-dashboard');
                break;
            default:
                $redirect_url = home_url();
        }
        
        echo "<td style='color: green;'>✅ {$redirect_url}</td>";
    } else {
        $redirect_url = home_url();
        echo "<td style='color: orange;'>⚠️ {$redirect_url} (nu are rol Clinica)</td>";
    }
    
    echo "</tr>";
}
echo "</table>";

// Testează metoda ajax_login (simulat)
echo "<h2>2. Test AJAX Login (simulat)</h2>";

// Alege primul utilizator pentru test
$test_user = $users_with_roles[0];
echo "<h3>Test cu utilizatorul: {$test_user->display_name} (ID: {$test_user->ID})</h3>";

// Simulează datele AJAX pentru login
$_POST = array(
    'action' => 'clinica_login',
    'clinica_frontend_nonce' => wp_create_nonce('clinica_login'),
    'identifier' => $test_user->user_login,
    'password' => 'test123', // Parolă test
    'remember' => '0'
);

echo "<h4>Datele simulate:</h4>";
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

// Testează logica de redirect direct
echo "<h2>3. Test Logică Redirect Direct</h2>";

foreach ($users_with_roles as $user_data) {
    if (Clinica_Roles::has_clinica_role($user_data->ID)) {
        $role = Clinica_Roles::get_user_role($user_data->ID);
        
        echo "<h4>Utilizator: {$user_data->display_name} - Rol: {$role}</h4>";
        
        $redirect_url = '';
        switch ($role) {
            case 'clinica_patient':
                $redirect_url = home_url('/clinica-patient-dashboard/');
                echo "<p style='color: blue;'>Pacient → {$redirect_url}</p>";
                break;
            case 'clinica_doctor':
                $redirect_url = admin_url('admin.php?page=clinica-dashboard');
                echo "<p style='color: blue;'>Doctor → {$redirect_url}</p>";
                break;
            case 'clinica_assistant':
                $redirect_url = admin_url('admin.php?page=clinica-dashboard');
                echo "<p style='color: blue;'>Asistent → {$redirect_url}</p>";
                break;
            case 'clinica_receptionist':
                $redirect_url = admin_url('admin.php?page=clinica-dashboard');
                echo "<p style='color: blue;'>Receptionist → {$redirect_url}</p>";
                break;
            case 'clinica_manager':
                $redirect_url = admin_url('admin.php?page=clinica-dashboard');
                echo "<p style='color: blue;'>Manager → {$redirect_url}</p>";
                break;
            case 'clinica_administrator':
                $redirect_url = admin_url('admin.php?page=clinica-dashboard');
                echo "<p style='color: blue;'>Administrator → {$redirect_url}</p>";
                break;
            default:
                $redirect_url = home_url();
                echo "<p style='color: orange;'>Rol necunoscut → {$redirect_url}</p>";
                break;
        }
        
        // Verifică dacă URL-ul este valid
        if (filter_var($redirect_url, FILTER_VALIDATE_URL)) {
            echo "<p style='color: green;'>✅ URL valid</p>";
        } else {
            echo "<p style='color: red;'>❌ URL invalid</p>";
        }
    }
}

// Verifică paginile dashboard
echo "<h2>4. Verificare Pagini Dashboard</h2>";

$dashboard_pages = array(
    'clinica-patient-dashboard' => home_url('/clinica-patient-dashboard/'),
    'clinica-doctor-dashboard' => home_url('/clinica-doctor-dashboard/'),
    'clinica-assistant-dashboard' => home_url('/clinica-assistant-dashboard/'),
    'clinica-manager-dashboard' => home_url('/clinica-manager-dashboard/'),
    'clinica-receptionist-dashboard' => home_url('/clinica-receptionist-dashboard/')
);

foreach ($dashboard_pages as $slug => $url) {
    $page = get_page_by_path($slug);
    if ($page) {
        echo "<p style='color: green;'>✅ Pagina {$slug} există (ID: {$page->ID})</p>";
    } else {
        echo "<p style='color: red;'>❌ Pagina {$slug} nu există</p>";
    }
}

// Verifică meniul admin
echo "<h2>5. Verificare Meniu Admin</h2>";
$admin_page_url = admin_url('admin.php?page=clinica-dashboard');
echo "<p>URL meniu admin: <a href='{$admin_page_url}' target='_blank'>{$admin_page_url}</a></p>";

// Testează JavaScript redirect
echo "<h2>6. Test JavaScript Redirect</h2>";
echo "<p>JavaScript-ul din formularul de login ar trebui să:</p>";
echo "<ol>";
echo "<li>Prima răspunsul AJAX cu success</li>";
echo "<li>Extragă redirect_url din response.data.redirect_url</li>";
echo "<li>Aștepte 2 secunde</li>";
echo "<li>Redirecționeze cu window.location.href</li>";
echo "</ol>";

echo "<h3>Cod JavaScript testat:</h3>";
echo "<pre>";
echo "success: function(response) {
    if (response.success) {
        messages.html('<div class=\"success\">' + response.data.message + '</div>');
        // Redirect după 2 secunde
        setTimeout(function() {
            window.location.href = response.data.redirect_url;
        }, 2000);
    } else {
        messages.html('<div class=\"error\">' + response.data + '</div>');
    }
}";
echo "</pre>";

echo "<h2>7. Rezumat</h2>";
echo "<p>Pentru ca redirect-ul să funcționeze:</p>";
echo "<ul>";
echo "<li>✅ Utilizatorul trebuie să aibă un rol Clinica valid</li>";
echo "<li>✅ Metoda get_user_role() trebuie să returneze rolul corect</li>";
echo "<li>✅ URL-ul de redirect trebuie să fie valid</li>";
echo "<li>✅ JavaScript-ul trebuie să proceseze corect răspunsul AJAX</li>";
echo "<li>✅ Paginile dashboard trebuie să existe</li>";
echo "</ul>";

echo "<p><strong>Notă:</strong> Dacă redirect-ul nu funcționează, verificați console-ul browser-ului pentru erori JavaScript.</p>";
?> 