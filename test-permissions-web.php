<?php
/**
 * Test pentru verificarea permisiunilor - accesibil prin browser
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fii autentificat pentru a accesa această pagină.');
}

echo '<h2>Test Permisiuni Backend</h2>';
echo '<p><strong>Utilizator curent:</strong> ' . wp_get_current_user()->user_login . '</p>';
echo '<p><strong>ID utilizator:</strong> ' . get_current_user_id() . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', wp_get_current_user()->roles) . '</p>';

echo '<h3>Verificări Permisiuni</h3>';
echo '<p><strong>Can create appointments:</strong> ' . (Clinica_Patient_Permissions::can_create_appointments() ? 'DA' : 'NU') . '</p>';
echo '<p><strong>User can clinica_create_appointments:</strong> ' . (user_can(get_current_user_id(), 'clinica_create_appointments') ? 'DA' : 'NU') . '</p>';
echo '<p><strong>User can manage_options:</strong> ' . (user_can(get_current_user_id(), 'manage_options') ? 'DA' : 'NU') . '</p>';

echo '<h3>Capacități Utilizator</h3>';
$user = wp_get_current_user();
$all_caps = $user->allcaps;
$clinica_caps = array_filter($all_caps, function($key) {
    return strpos($key, 'clinica_') === 0;
}, ARRAY_FILTER_USE_KEY);

echo '<ul>';
foreach ($clinica_caps as $cap => $value) {
    echo '<li>' . $cap . ': ' . ($value ? 'DA' : 'NU') . '</li>';
}
echo '</ul>';

echo '<h3>Verificare Roluri</h3>';
$roles = wp_roles();
echo '<ul>';
foreach ($roles->roles as $role_name => $role_data) {
    if (strpos($role_name, 'clinica_') === 0) {
        echo '<li><strong>Rol: ' . $role_name . '</strong>';
        if (isset($role_data['capabilities']['clinica_create_appointments'])) {
            echo ' - clinica_create_appointments: ' . ($role_data['capabilities']['clinica_create_appointments'] ? 'DA' : 'NU');
        } else {
            echo ' - clinica_create_appointments: LIPSEȘTE';
        }
        echo '</li>';
    }
}
echo '</ul>';

echo '<h3>Test Direct</h3>';
$test_user_id = get_current_user_id();
$test_cap = 'clinica_create_appointments';
echo '<p><strong>Test direct user_can(' . $test_user_id . ', \'' . $test_cap . '\'):</strong> ' . (user_can($test_user_id, $test_cap) ? 'DA' : 'NU') . '</p>';

// Test cu get_user_meta
$user_meta = get_user_meta($test_user_id, 'wp_capabilities', true);
echo '<p><strong>User meta capabilities:</strong></p>';
echo '<pre>' . print_r($user_meta, true) . '</pre>';

echo '<h3>Test Buton Programare Nouă</h3>';
echo '<p><strong>Condiția din appointments.php:</strong></p>';
echo '<pre>';
echo 'Clinica_Patient_Permissions::can_create_appointments() = ' . (Clinica_Patient_Permissions::can_create_appointments() ? 'true' : 'false') . "\n";
echo '</pre>';

if (Clinica_Patient_Permissions::can_create_appointments()) {
    echo '<p style="color: green;">✅ Butonul "Programare Nouă" AR TREBUI să fie vizibil!</p>';
} else {
    echo '<p style="color: red;">❌ Butonul "Programare Nouă" NU ar trebui să fie vizibil!</p>';
}
?>
