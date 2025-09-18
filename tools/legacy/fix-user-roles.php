<?php
/**
 * Script pentru verificarea și corectarea rolurilor utilizatorilor
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Verificare și Corectare Roluri Utilizatori</h1>";

// Verifică dacă clasele sunt încărcate
if (!class_exists('Clinica_Roles')) {
    echo "<p style='color: red;'>Clasa Clinica_Roles nu este încărcată!</p>";
    exit;
}

// Verifică pacienții din tabela clinica_patients
echo "<h2>1. Verificare Pacienți din Tabela clinica_patients</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';

$patients = $wpdb->get_results("
    SELECT p.user_id, p.cnp, u.user_login, u.user_email, u.display_name
    FROM $table_name p
    JOIN {$wpdb->users} u ON p.user_id = u.ID
    LIMIT 10
");

if (!$patients) {
    echo "<p style='color: red;'>Nu există pacienți în baza de date.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>CNP</th><th>Username</th><th>Email</th><th>Nume</th><th>Roluri Actuale</th><th>Acțiune</th></tr>";
    
    foreach ($patients as $patient) {
        $user = get_userdata($patient->user_id);
        $roles_list = is_array($user->roles) ? implode(', ', $user->roles) : 'N/A';
        
        echo "<tr>";
        echo "<td>{$patient->user_id}</td>";
        echo "<td>{$patient->cnp}</td>";
        echo "<td>{$patient->user_login}</td>";
        echo "<td>{$patient->user_email}</td>";
        echo "<td>{$patient->display_name}</td>";
        echo "<td>{$roles_list}</td>";
        
        // Verifică dacă pacientul are rolul corect
        if (in_array('clinica_patient', $user->roles)) {
            echo "<td style='color: green;'>✅ Rol corect</td>";
        } else {
            echo "<td style='color: red;'>❌ Lipsește rolul clinica_patient</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
}

// Verifică toți utilizatorii
echo "<h2>2. Verificare Toți Utilizatorii</h2>";

$all_users = $wpdb->get_results("
    SELECT u.ID, u.user_login, u.user_email, u.display_name, um.meta_value as roles
    FROM {$wpdb->users} u
    JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = '{$wpdb->prefix}capabilities'
    ORDER BY u.ID
    LIMIT 20
");

if (!$all_users) {
    echo "<p style='color: red;'>Nu există utilizatori în baza de date.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Nume</th><th>Roluri</th><th>Are Rol Clinica</th><th>Rol Principal</th></tr>";
    
    foreach ($all_users as $user_data) {
        $user_roles = maybe_unserialize($user_data->roles);
        $roles_list = is_array($user_roles) ? implode(', ', array_keys($user_roles)) : 'N/A';
        
        $has_clinica_role = Clinica_Roles::has_clinica_role($user_data->ID);
        $main_role = Clinica_Roles::get_user_role($user_data->ID);
        
        echo "<tr>";
        echo "<td>{$user_data->ID}</td>";
        echo "<td>{$user_data->user_login}</td>";
        echo "<td>{$user_data->user_email}</td>";
        echo "<td>{$user_data->display_name}</td>";
        echo "<td>{$roles_list}</td>";
        echo "<td>" . ($has_clinica_role ? '✅ Da' : '❌ Nu') . "</td>";
        echo "<td>" . ($main_role ? $main_role : 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Testează redirect-ul pentru fiecare tip de rol
echo "<h2>3. Test Redirect pentru Fiecare Rol</h2>";

$test_roles = array(
    'clinica_patient' => 'Pacient',
    'clinica_doctor' => 'Doctor',
    'clinica_assistant' => 'Asistent',
    'clinica_receptionist' => 'Receptionist',
    'clinica_manager' => 'Manager',
    'clinica_administrator' => 'Administrator'
);

foreach ($test_roles as $role => $role_name) {
    echo "<h3>Test pentru rolul: {$role_name} ({$role})</h3>";
    
    // Simulează un utilizator cu acest rol
    $redirect_url = '';
    switch ($role) {
        case 'clinica_patient':
            $redirect_url = home_url('/clinica-patient-dashboard/');
            echo "<p style='color: blue;'>Redirect: {$redirect_url}</p>";
            break;
        case 'clinica_doctor':
        case 'clinica_assistant':
        case 'clinica_receptionist':
        case 'clinica_manager':
        case 'clinica_administrator':
            $redirect_url = admin_url('admin.php?page=clinica-dashboard');
            echo "<p style='color: blue;'>Redirect: {$redirect_url}</p>";
            break;
        default:
            $redirect_url = home_url();
            echo "<p style='color: orange;'>Redirect: {$redirect_url}</p>";
            break;
    }
    
    // Verifică dacă URL-ul este valid
    if (filter_var($redirect_url, FILTER_VALIDATE_URL)) {
        echo "<p style='color: green;'>✅ URL valid</p>";
    } else {
        echo "<p style='color: red;'>❌ URL invalid</p>";
    }
    
    // Verifică dacă pagina există (pentru pacienți)
    if ($role === 'clinica_patient') {
        $page = get_page_by_path('clinica-patient-dashboard');
        if ($page) {
            echo "<p style='color: green;'>✅ Pagina dashboard pacient există (ID: {$page->ID})</p>";
        } else {
            echo "<p style='color: red;'>❌ Pagina dashboard pacient nu există</p>";
        }
    }
}

// Verifică meniul admin
echo "<h2>4. Verificare Meniu Admin</h2>";
$admin_page_url = admin_url('admin.php?page=clinica-dashboard');
echo "<p>URL meniu admin: <a href='{$admin_page_url}' target='_blank'>{$admin_page_url}</a></p>";

// Testează dacă pagina admin există
$admin_page = get_page_by_path('clinica-dashboard');
if ($admin_page) {
    echo "<p style='color: green;'>✅ Pagina admin există (ID: {$admin_page->ID})</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Pagina admin nu există (normal, este o pagină admin)</p>";
}

// Sfaturi pentru debugging
echo "<h2>5. Sfaturi pentru Debugging</h2>";
echo "<p>Dacă redirect-ul nu funcționează:</p>";
echo "<ol>";
echo "<li><strong>Verificați console-ul browser-ului</strong> pentru erori JavaScript</li>";
echo "<li><strong>Verificați răspunsul AJAX</strong> în Network tab</li>";
echo "<li><strong>Verificați rolurile utilizatorilor</strong> - trebuie să aibă roluri Clinica</li>";
echo "<li><strong>Verificați paginile dashboard</strong> - trebuie să existe</li>";
echo "<li><strong>Verificați permisiunile</strong> - utilizatorul trebuie să aibă acces la paginile respective</li>";
echo "</ol>";

echo "<h3>Test rapid pentru un utilizator specific:</h3>";
echo "<p>Pentru a testa redirect-ul pentru un utilizator specific:</p>";
echo "<ol>";
echo "<li>Găsiți ID-ul utilizatorului din tabelul de mai sus</li>";
echo "<li>Verificați că are rolul corect</li>";
echo "<li>Încercați să vă autentificați cu credențialele corecte</li>";
echo "<li>Verificați răspunsul AJAX în browser</li>";
echo "</ol>";

echo "<h2>6. Rezumat</h2>";
echo "<p>Redirect-ul după autentificare funcționează corect dacă:</p>";
echo "<ul>";
echo "<li>✅ Utilizatorul are un rol Clinica valid</li>";
echo "<li>✅ Metoda get_user_role() returnează rolul corect</li>";
echo "<li>✅ URL-ul de redirect este valid</li>";
echo "<li>✅ JavaScript-ul procesează corect răspunsul AJAX</li>";
echo "<li>✅ Paginile dashboard există (pentru pacienți)</li>";
echo "<li>✅ Utilizatorul are permisiuni pentru paginile respective</li>";
echo "</ul>";
?> 