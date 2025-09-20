<?php
/**
 * Script de reparare rapidă pentru Achten Rodica-Laura
 * URL: http://192.168.1.182/plm/wp-content/plugins/clinica/tools/debug/fix-achten-role.php
 */

// Include WordPress
require_once('../../../../wp-load.php');

echo "<h1>Reparare Rol pentru Achten Rodica-Laura</h1>";

// ID utilizator Achten Rodica-Laura
$user_id = 6;

// Obține utilizatorul
$user = get_userdata($user_id);

if (!$user) {
    echo "<p style='color: red;'>❌ Utilizatorul cu ID $user_id nu a fost găsit!</p>";
    exit;
}

echo "<h2>Status Înainte de Reparare</h2>";
echo "<p><strong>Nume:</strong> " . $user->display_name . "</p>";
echo "<p><strong>Roluri înainte:</strong> " . implode(', ', $user->roles) . "</p>";

// Verifică dacă are deja rolul
$has_clinica_patient = in_array('clinica_patient', $user->roles);

if ($has_clinica_patient) {
    echo "<p style='color: green;'>✅ Utilizatorul are deja rolul clinica_patient!</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Utilizatorul NU are rolul clinica_patient. Adaugă rolul...</p>";
    
    // Adaugă rolul clinica_patient
    $user->add_role('clinica_patient');
    
    // Reîncarcă utilizatorul pentru a vedea modificările
    $user = get_userdata($user_id);
    
    echo "<h2>Status După Reparare</h2>";
    echo "<p><strong>Roluri după:</strong> " . implode(', ', $user->roles) . "</p>";
    
    if (in_array('clinica_patient', $user->roles)) {
        echo "<p style='color: green;'>✅ Rolul clinica_patient a fost adăugat cu succes!</p>";
    } else {
        echo "<p style='color: red;'>❌ Eroare la adăugarea rolului!</p>";
    }
}

// Testează funcțiile Clinica
echo "<h2>Test Funcții Clinica</h2>";

// Include clasele Clinica
require_once('../includes/class-clinica-roles.php');

$has_clinica_role = Clinica_Roles::has_clinica_role($user_id);
$user_role = Clinica_Roles::get_user_role($user_id);

echo "<p><strong>has_clinica_role():</strong> " . ($has_clinica_role ? 'TRUE' : 'FALSE') . "</p>";
echo "<p><strong>get_user_role():</strong> " . ($user_role ? $user_role : 'FALSE') . "</p>";

// Testează redirectul
echo "<h2>Test Redirect</h2>";

require_once('../includes/class-clinica-authentication.php');
$auth = new Clinica_Authentication();
$redirect_url = $auth->custom_login_redirect('', '', $user);

echo "<p><strong>Redirect URL:</strong> $redirect_url</p>";

if ($redirect_url && $redirect_url !== home_url()) {
    echo "<p style='color: green;'>✅ Redirect funcționează corect!</p>";
    echo "<p><strong>Link de test:</strong> <a href='$redirect_url' target='_blank'>$redirect_url</a></p>";
} else {
    echo "<p style='color: red;'>❌ Redirect nu funcționează!</p>";
}

// Verifică pagina dashboard
$dashboard_page = get_page_by_path('clinica-patient-dashboard');
echo "<p><strong>Pagina dashboard există:</strong> " . ($dashboard_page ? 'DA' : 'NU') . "</p>";

if ($dashboard_page) {
    echo "<p><strong>URL pagină:</strong> " . get_permalink($dashboard_page->ID) . "</p>";
}

echo "<h2>Concluzie</h2>";
if (in_array('clinica_patient', $user->roles) && $has_clinica_role) {
    echo "<p style='color: green;'>✅ PROBLEMA REZOLVATĂ! Utilizatorul Achten Rodica-Laura va face acum redirect corect după autentificare.</p>";
} else {
    echo "<p style='color: red;'>❌ Problema persistă. Verifică manual rolurile utilizatorului.</p>";
}
?>
