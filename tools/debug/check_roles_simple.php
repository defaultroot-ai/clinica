<?php
/**
 * Verificare simplă a rolurilor utilizatorilor
 */

// Include WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "=== VERIFICARE ROLURI UTILIZATORI CLINICA ===\n\n";

// 1. Verifică rolurile definite în plugin
echo "1. ROLURI DEFINITE IN PLUGIN:\n";
echo "- clinica_administrator\n";
echo "- clinica_manager\n";
echo "- clinica_doctor\n";
echo "- clinica_assistant\n";
echo "- clinica_receptionist\n";
echo "- clinica_patient\n\n";

// 2. Obține primii 10 utilizatori cu roluri Clinica
echo "2. PRIMII 10 UTILIZATORI CU ROLURI CLINICA:\n";
$users = $wpdb->get_results("
    SELECT u.ID, u.display_name, u.user_email, um.meta_value as roles
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = '{$wpdb->prefix}capabilities'
    AND um.meta_value LIKE '%clinica_%'
    ORDER BY u.display_name
    LIMIT 10
");

foreach ($users as $user) {
    // Obține rolurile corecte din WordPress
    $user_obj = get_userdata($user->ID);
    $roles = $user_obj ? $user_obj->roles : array();
    
    echo "ID: {$user->ID} | Nume: {$user->display_name} | Email: {$user->user_email}\n";
    echo "Roluri: " . implode(', ', $roles) . "\n";
    echo "---\n";
}

// 3. Verifică statistici
echo "\n3. STATISTICI ROLURI:\n";

$all_users = $wpdb->get_results("
    SELECT u.ID, um.meta_value as roles
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = '{$wpdb->prefix}capabilities'
    AND um.meta_value LIKE '%clinica_%'
");

$total_users = count($all_users);
$dual_role_count = 0;
$staff_only_count = 0;
$patient_only_count = 0;
$other_count = 0;

$staff_roles = array(
    'clinica_administrator',
    'clinica_manager', 
    'clinica_doctor',
    'clinica_assistant',
    'clinica_receptionist'
);

foreach ($all_users as $user) {
    // Obține rolurile corecte din WordPress
    $user_obj = get_userdata($user->ID);
    $roles = $user_obj ? $user_obj->roles : array();
    
    $has_staff_role = false;
    $has_patient_role = false;
    
    foreach ($roles as $role) {
        if (in_array($role, $staff_roles)) {
            $has_staff_role = true;
        }
        if ($role === 'clinica_patient') {
            $has_patient_role = true;
        }
    }
    
    if ($has_staff_role && $has_patient_role) {
        $dual_role_count++;
    } elseif ($has_staff_role && !$has_patient_role) {
        $staff_only_count++;
    } elseif (!$has_staff_role && $has_patient_role) {
        $patient_only_count++;
    } else {
        $other_count++;
    }
}

echo "Total utilizatori: $total_users\n";
echo "Roluri duble: $dual_role_count\n";
echo "Doar staff: $staff_only_count\n";
echo "Doar pacienti: $patient_only_count\n";
echo "Altele: $other_count\n\n";

// 4. Verifică migrarea
echo "4. STATUS MIGRARE:\n";
$migration_done = get_option('clinica_dual_roles_migration_done', false);
$migration_date = get_option('clinica_dual_roles_migration_date', 'N/A');
$migrated_count = get_option('clinica_dual_roles_migrated_count', 0);

echo "Migrare completă: " . ($migration_done ? 'DA' : 'NU') . "\n";
echo "Data migrare: $migration_date\n";
echo "Utilizatori migrați: $migrated_count\n\n";

// 5. Verifică tabela roluri active
echo "5. TABELA ROLURI ACTIVE:\n";
$active_roles_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}clinica_user_active_roles");
echo "Înregistrări în tabela roluri active: $active_roles_count\n";

if ($active_roles_count > 0) {
    $active_roles = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}clinica_user_active_roles LIMIT 5");
    echo "Primele 5 înregistrări:\n";
    foreach ($active_roles as $role) {
        echo "ID: {$role->id} | User ID: {$role->user_id} | Active Role: {$role->active_role} | Last Switched: {$role->last_switched}\n";
    }
}

echo "\n=== CONCLUZIE ===\n";
if ($dual_role_count == 0 && $staff_only_count == $total_users) {
    echo "PROBLEMĂ: Toți utilizatorii sunt considerați 'Doar Staff'!\n";
    echo "Cauze posibile:\n";
    echo "- Migrarea nu a funcționat corect\n";
    echo "- Utilizatorii nu au rolul 'clinica_patient'\n";
    echo "- Logica de detectare a rolurilor este greșită\n";
} else {
    echo "Statisticile par corecte.\n";
}
?>
