<?php
/**
 * Script de test pentru redirectul utilizatorului Achten Rodica-Laura
 * URL: http://192.168.1.182/plm/wp-content/plugins/clinica/tools/debug/test-redirect-achten.php
 */

// Include WordPress
require_once('../../../../wp-load.php');

// Include clasele Clinica
require_once('../includes/class-clinica-roles.php');
require_once('../includes/class-clinica-authentication.php');

echo "<h1>Test Redirect pentru Achten Rodica-Laura</h1>";

// Testează utilizatorul ID 6 (Achten Rodica-Laura)
$user_id = 6;
$user = get_userdata($user_id);

if (!$user) {
    echo "<p style='color: red;'>❌ Utilizatorul cu ID $user_id nu a fost găsit!</p>";
    exit;
}

echo "<h2>Informații Utilizator</h2>";
echo "<p><strong>ID:</strong> " . $user->ID . "</p>";
echo "<p><strong>Login:</strong> " . $user->user_login . "</p>";
echo "<p><strong>Email:</strong> " . $user->user_email . "</p>";
echo "<p><strong>Display Name:</strong> " . $user->display_name . "</p>";
echo "<p><strong>Roles:</strong> " . implode(', ', $user->roles) . "</p>";

echo "<h2>Test Funcții Clinica</h2>";

// Test 1: has_clinica_role
$has_clinica_role = Clinica_Roles::has_clinica_role($user_id);
echo "<p><strong>has_clinica_role():</strong> " . ($has_clinica_role ? 'TRUE' : 'FALSE') . "</p>";

// Test 2: get_user_role
$user_role = Clinica_Roles::get_user_role($user_id);
echo "<p><strong>get_user_role():</strong> " . ($user_role ? $user_role : 'FALSE') . "</p>";

// Test 3: get_clinica_roles
$clinica_roles = Clinica_Roles::get_clinica_roles();
echo "<p><strong>Roluri Clinica disponibile:</strong></p>";
echo "<ul>";
foreach ($clinica_roles as $role_key => $role_name) {
    echo "<li>$role_key: $role_name</li>";
}
echo "</ul>";

// Test 4: Verificare roluri utilizator
echo "<p><strong>Roluri utilizator:</strong></p>";
echo "<ul>";
foreach ($user->roles as $role) {
    $is_clinica_role = in_array($role, array_keys($clinica_roles));
    echo "<li>$role " . ($is_clinica_role ? "✅ (Clinica)" : "❌ (NU Clinica)") . "</li>";
}
echo "</ul>";

// Test 5: Simulare redirect
echo "<h2>Simulare Redirect</h2>";

$auth = new Clinica_Authentication();
$redirect_url = $auth->custom_login_redirect('', '', $user);

echo "<p><strong>Redirect URL:</strong> $redirect_url</p>";

// Test 6: Verificare pagină dashboard
$dashboard_page = get_page_by_path('clinica-patient-dashboard');
echo "<p><strong>Pagina dashboard există:</strong> " . ($dashboard_page ? 'DA (ID: ' . $dashboard_page->ID . ')' : 'NU') . "</p>";

if ($dashboard_page) {
    echo "<p><strong>URL pagină:</strong> " . get_permalink($dashboard_page->ID) . "</p>";
    echo "<p><strong>Status pagină:</strong> " . $dashboard_page->post_status . "</p>";
}

// Test 7: Verificare în tabela pacienți
global $wpdb;
$patient_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}clinica_patients WHERE user_id = %d",
    $user_id
));

echo "<h2>Date Pacient</h2>";
if ($patient_data) {
    echo "<p><strong>Pacient găsit în tabela:</strong> DA</p>";
    echo "<p><strong>CNP:</strong> " . $patient_data->cnp . "</p>";
    echo "<p><strong>Email pacient:</strong> " . $patient_data->email . "</p>";
    echo "<p><strong>Family ID:</strong> " . $patient_data->family_id . "</p>";
    echo "<p><strong>Family Role:</strong> " . $patient_data->family_role . "</p>";
} else {
    echo "<p style='color: red;'><strong>Pacient NU găsit în tabela!</strong></p>";
}

// Test 8: Verificare hook-uri WordPress
echo "<h2>Hook-uri WordPress</h2>";
$login_redirect_hooks = $GLOBALS['wp_filter']['login_redirect'] ?? null;
if ($login_redirect_hooks) {
    echo "<p><strong>Hook-uri login_redirect active:</strong> DA</p>";
    foreach ($login_redirect_hooks->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && is_object($callback['function'][0])) {
                $class_name = get_class($callback['function'][0]);
                $method_name = $callback['function'][1];
                echo "<p>Prioritate $priority: $class_name::$method_name</p>";
            }
        }
    }
} else {
    echo "<p style='color: red;'><strong>NU există hook-uri login_redirect active!</strong></p>";
}

echo "<h2>Concluzie</h2>";
if (!$has_clinica_role) {
    echo "<p style='color: red;'>❌ PROBLEMA: Utilizatorul nu are rol Clinica!</p>";
    echo "<p><strong>Soluție:</strong> Adaugă rolul 'clinica_patient' utilizatorului.</p>";
} else {
    echo "<p style='color: green;'>✅ Utilizatorul are rol Clinica: $user_role</p>";
    if ($redirect_url && $redirect_url !== home_url()) {
        echo "<p style='color: green;'>✅ Redirect funcționează: $redirect_url</p>";
    } else {
        echo "<p style='color: red;'>❌ PROBLEMA: Redirect nu funcționează corect!</p>";
    }
}
?>
