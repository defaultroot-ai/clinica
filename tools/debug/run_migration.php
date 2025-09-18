<?php
/**
 * Rulare migrare manuală roluri duble
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "=== RULARE MIGRARE MANUALĂ ROLURI DUBLE ===\n\n";

// Verifică status înainte de migrare
echo "1. STATUS ÎNAINTE DE MIGRARE:\n";
$migration_done = get_option('clinica_dual_roles_migration_done', false);
$migrated_count = get_option('clinica_dual_roles_migrated_count', 0);
echo "Migration done: " . ($migration_done ? 'DA' : 'NU') . "\n";
echo "Migrated count: $migrated_count\n\n";

// Obține utilizatorii cu roluri de staff
echo "2. IDENTIFICARE UTILIZATORI STAFF:\n";
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
        echo "Staff user: {$user->display_name} (ID: {$user->ID}) - Rol: $role\n";
    }
}

echo "Total utilizatori staff: " . count($staff_users) . "\n\n";

// Rulează migrarea
echo "3. RULARE MIGRARE:\n";
if (method_exists('Clinica_Database', 'migrate_to_dual_roles')) {
    try {
        $result = Clinica_Database::migrate_to_dual_roles();
        echo "Rezultat migrare: $result utilizatori migrați\n";
    } catch (Exception $e) {
        echo "Eroare la migrare: " . $e->getMessage() . "\n";
    }
} else {
    echo "Funcția migrate_to_dual_roles nu există!\n";
}

// Verifică status după migrare
echo "\n4. STATUS DUPĂ MIGRARE:\n";
$migration_done_after = get_option('clinica_dual_roles_migration_done', false);
$migrated_count_after = get_option('clinica_dual_roles_migration_done', 0);
echo "Migration done: " . ($migration_done_after ? 'DA' : 'NU') . "\n";
echo "Migrated count: $migrated_count_after\n";

// Testează roluri duble după migrare
echo "\n5. TESTARE ROLURI DUBLE DUPĂ MIGRARE:\n";
foreach ($staff_users as $user) {
    $has_dual_role = Clinica_Roles::has_dual_role($user->ID);
    echo "User {$user->display_name} (ID: {$user->ID}): " . ($has_dual_role ? 'DA' : 'NU') . "\n";
}

echo "\n=== MIGRAREA COMPLETĂ ===\n";
?>
