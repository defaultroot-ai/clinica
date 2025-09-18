<?php
// Test JSON fix
require_once('../../../wp-load.php');

echo "=== TEST JSON FIX ===\n";

// Test cu user ID 1939
$test_user_id = 1939;
wp_set_current_user($test_user_id);

$user = wp_get_current_user();
echo "User ID: " . $user->ID . "\n";
echo "User login: " . $user->user_login . "\n";
echo "Is user logged in: " . (is_user_logged_in() ? 'YES' : 'NO') . "\n";

// Test nonce generation
$nonce = wp_create_nonce('clinica_manager_dashboard');
echo "Generated nonce: $nonce\n";

// Test nonce verification
$verify = wp_verify_nonce($nonce, 'clinica_manager_dashboard');
echo "Nonce verification: $verify\n";

// Test AJAX simulation
$_POST['action'] = 'clinica_manager_get_users';
$_POST['nonce'] = $nonce;
$_POST['page'] = 1;
$_POST['per_page'] = 20;
$_POST['search'] = '';
$_POST['sort_by'] = 'name';
$_POST['sort_order'] = 'ASC';
$_POST['letter_filter'] = '';

echo "\n=== TESTING AJAX HANDLER WITH JSON FIX ===\n";

try {
    // Test nonce verification (new method)
    if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
        echo "Nonce verification: FAILED\n";
        echo "Would return: wp_send_json_error('Invalid nonce')\n";
    } else {
        echo "Nonce verification: SUCCESS\n";
    }
    
    // Test capabilities
    $can_manage = current_user_can('manage_options');
    $can_manager = current_user_can('clinica_manager');
    $can_admin = current_user_can('clinica_administrator');
    
    echo "Can manage_options: " . ($can_manage ? 'YES' : 'NO') . "\n";
    echo "Can clinica_manager: " . ($can_manager ? 'YES' : 'NO') . "\n";
    echo "Can clinica_administrator: " . ($can_admin ? 'YES' : 'NO') . "\n";
    
    if ($can_manage || $can_manager || $can_admin) {
        echo "Authorization: SUCCESS\n";
        echo "Would return: wp_send_json_success(data)\n";
        echo "AJAX call should work completely!\n";
    } else {
        echo "Authorization: FAILED\n";
        echo "Would return: wp_send_json_error('Unauthorized')\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
