<?php
require_once('wp-config.php');
require_once('wp-load.php');

echo "=== TEST PERMISIUNI BACKEND ===\n";
echo "Utilizator curent: " . wp_get_current_user()->user_login . "\n";
echo "ID utilizator: " . get_current_user_id() . "\n";
echo "Roluri: " . implode(', ', wp_get_current_user()->roles) . "\n\n";

echo "=== VERIFICĂRI PERMISIUNI ===\n";
echo "Can create appointments: " . (Clinica_Patient_Permissions::can_create_appointments() ? 'DA' : 'NU') . "\n";
echo "User can clinica_create_appointments: " . (user_can(get_current_user_id(), 'clinica_create_appointments') ? 'DA' : 'NU') . "\n";
echo "User can manage_options: " . (user_can(get_current_user_id(), 'manage_options') ? 'DA' : 'NU') . "\n";

echo "\n=== CAPACITĂȚI UTILIZATOR ===\n";
$user = wp_get_current_user();
$all_caps = $user->allcaps;
$clinica_caps = array_filter($all_caps, function($key) {
    return strpos($key, 'clinica_') === 0;
}, ARRAY_FILTER_USE_KEY);

foreach ($clinica_caps as $cap => $value) {
    echo "$cap: " . ($value ? 'DA' : 'NU') . "\n";
}

echo "\n=== VERIFICARE ROLURI ===\n";
$roles = wp_roles();
foreach ($roles->roles as $role_name => $role_data) {
    if (strpos($role_name, 'clinica_') === 0) {
        echo "Rol: $role_name\n";
        if (isset($role_data['capabilities']['clinica_create_appointments'])) {
            echo "  - clinica_create_appointments: " . ($role_data['capabilities']['clinica_create_appointments'] ? 'DA' : 'NU') . "\n";
        } else {
            echo "  - clinica_create_appointments: LIPSEȘTE\n";
        }
    }
}

echo "\n=== TEST DIRECT ===\n";
$test_user_id = get_current_user_id();
$test_cap = 'clinica_create_appointments';
echo "Test direct user_can($test_user_id, '$test_cap'): " . (user_can($test_user_id, $test_cap) ? 'DA' : 'NU') . "\n";

// Test cu get_user_meta
$user_meta = get_user_meta($test_user_id, 'wp_capabilities', true);
echo "User meta capabilities: " . print_r($user_meta, true) . "\n";
?>
