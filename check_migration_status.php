<?php
/**
 * Verificare status migrare roluri duble
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "=== VERIFICARE STATUS MIGRARE ===\n\n";

// Verifică dacă migrarea s-a marcat ca completă
$migration_done = get_option('clinica_dual_roles_migration_done', false);
echo "Migration done: " . ($migration_done ? 'DA' : 'NU') . "\n";

// Verifică data migrării
$migration_date = get_option('clinica_dual_roles_migration_date', 'N/A');
echo "Migration date: $migration_date\n";

// Verifică numărul de utilizatori migrați
$migrated_count = get_option('clinica_dual_roles_migrated_count', 0);
echo "Migrated count: $migrated_count\n";

// Verifică dacă funcția de migrare există
if (method_exists('Clinica_Database', 'migrate_to_dual_roles')) {
    echo "Funcția migrate_to_dual_roles există: DA\n";
} else {
    echo "Funcția migrate_to_dual_roles există: NU\n";
}

// Verifică dacă funcția is_dual_roles_migrated există
if (method_exists('Clinica_Database', 'is_dual_roles_migrated')) {
    echo "Funcția is_dual_roles_migrated există: DA\n";
    $is_migrated = Clinica_Database::is_dual_roles_migrated();
    echo "is_dual_roles_migrated() returnează: " . ($is_migrated ? 'DA' : 'NU') . "\n";
} else {
    echo "Funcția is_dual_roles_migrated există: NU\n";
}

// Testează migrarea manuală
echo "\n=== TEST MIGRARE MANUALĂ ===\n";

if (method_exists('Clinica_Database', 'migrate_to_dual_roles')) {
    echo "Încercare migrare manuală...\n";
    try {
        $result = Clinica_Database::migrate_to_dual_roles();
        echo "Rezultat migrare: $result utilizatori migrați\n";
    } catch (Exception $e) {
        echo "Eroare la migrare: " . $e->getMessage() . "\n";
    }
} else {
    echo "Nu se poate testa migrarea - funcția nu există\n";
}

echo "\n=== CONCLUZIE ===\n";
if ($migrated_count == 0) {
    echo "PROBLEMĂ: Migrarea nu a funcționat!\n";
    echo "Cauze posibile:\n";
    echo "- Funcția de migrare nu a fost apelată\n";
    echo "- Eroare în funcția de migrare\n";
    echo "- Utilizatorii nu au fost identificați corect\n";
} else {
    echo "Migrarea a funcționat - $migrated_count utilizatori migrați\n";
}
?>
