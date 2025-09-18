<?php
/**
 * Resetare și rulare migrare roluri duble
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "=== RESETARE ȘI RULARE MIGRARE ROLURI DUBLE ===\n\n";

// 1. Resetează migrarea
echo "1. RESETARE MIGRARE:\n";
Clinica_Database::reset_dual_roles_migration();
echo "Migrarea a fost resetată.\n\n";

// 2. Verifică status înainte de migrare
echo "2. STATUS ÎNAINTE DE MIGRARE:\n";
$migration_done = get_option('clinica_dual_roles_migration_done', false);
$migrated_count = get_option('clinica_dual_roles_migrated_count', 0);
echo "Migration done: " . ($migration_done ? 'DA' : 'NU') . "\n";
echo "Migrated count: $migrated_count\n\n";

// 3. Rulează migrarea
echo "3. RULARE MIGRARE:\n";
try {
    $result = Clinica_Database::migrate_to_dual_roles();
    echo "Rezultat migrare: $result utilizatori migrați\n";
} catch (Exception $e) {
    echo "Eroare la migrare: " . $e->getMessage() . "\n";
}

// 4. Verifică status după migrare
echo "\n4. STATUS DUPĂ MIGRARE:\n";
$migration_done_after = get_option('clinica_dual_roles_migration_done', false);
$migrated_count_after = get_option('clinica_dual_roles_migrated_count', 0);
echo "Migration done: " . ($migration_done_after ? 'DA' : 'NU') . "\n";
echo "Migrated count: $migrated_count_after\n";

// 5. Testează roluri duble după migrare
echo "\n5. TESTARE ROLURI DUBLE DUPĂ MIGRARE:\n";
$staff_roles = array(
    'clinica_administrator',
    'clinica_manager', 
    'clinica_doctor',
    'clinica_assistant',
    'clinica_receptionist'
);

$staff_users = array();
foreach ($staff_roles as $role) {
    $users = get_users(array('role' => $role));
    foreach ($users as $user) {
        $staff_users[] = $user;
    }
}

foreach ($staff_users as $user) {
    $has_dual_role = Clinica_Roles::has_dual_role($user->ID);
    $user_obj = get_userdata($user->ID);
    $roles = $user_obj ? $user_obj->roles : array();
    echo "User {$user->display_name} (ID: {$user->ID}): " . ($has_dual_role ? 'DA' : 'NU') . " - Roluri: " . implode(', ', $roles) . "\n";
}

echo "\n=== MIGRAREA COMPLETĂ ===\n";
?>
