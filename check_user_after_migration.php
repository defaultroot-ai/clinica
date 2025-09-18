<?php
/**
 * Verificare detaliată utilizator după migrare
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "=== VERIFICARE DETALIATĂ UTILIZATOR DUPĂ MIGRARE ===\n\n";

// Verifică un utilizator specific
$user_id = 1939; // Ulieru Ionut-Bogdan
$user = get_userdata($user_id);
echo "User ID: $user_id\n";
echo "Display Name: " . $user->display_name . "\n";
echo "Roles: " . implode(', ', $user->roles) . "\n";

// Verifică dacă are rol de pacient
$has_patient_role = in_array('clinica_patient', $user->roles);
echo "Has clinica_patient role: " . ($has_patient_role ? 'DA' : 'NU') . "\n";

// Verifică funcția has_dual_role
$has_dual_role = Clinica_Roles::has_dual_role($user_id);
echo "Has dual role: " . ($has_dual_role ? 'DA' : 'NU') . "\n";

// Verifică tabela roluri active
global $wpdb;
$active_role = $wpdb->get_var($wpdb->prepare("SELECT active_role FROM {$wpdb->prefix}clinica_user_active_roles WHERE user_id = %d", $user_id));
echo "Active role in table: " . ($active_role ?: 'N/A') . "\n";

// Verifică toți utilizatorii din tabela roluri active
echo "\n=== TOȚI UTILIZATORII DIN TABELA ROLURI ACTIVE ===\n";
$active_roles = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}clinica_user_active_roles");
foreach ($active_roles as $role) {
    echo "User ID: {$role->user_id} | Active Role: {$role->active_role} | Last Switched: {$role->last_switched}\n";
}

echo "\n=== CONCLUZIE ===\n";
if (!$has_patient_role) {
    echo "PROBLEMĂ: Rolul clinica_patient nu a fost adăugat!\n";
    echo "Migrarea a raportat 8 utilizatori migrați, dar rolul nu a fost adăugat.\n";
} else {
    echo "Rolul clinica_patient a fost adăugat cu succes.\n";
}
?>
