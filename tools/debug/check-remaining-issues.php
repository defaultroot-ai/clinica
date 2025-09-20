<?php
/**
 * Script de verificare probleme rămase
 * URL: http://192.168.1.182/plm/wp-content/plugins/clinica/tools/debug/check-remaining-issues.php
 */

// Include WordPress
require_once('../../../../wp-load.php');

// Include clasele Clinica
require_once('../includes/class-clinica-roles.php');

echo "<h1>Verificare Probleme Rămase</h1>";

// Obține toți utilizatorii (excluzând administratorii WordPress)
$users = get_users(array(
    'exclude' => array(1), // Exclude administratorul principal WordPress
    'orderby' => 'display_name',
    'order' => 'ASC'
));

// Verifică utilizatorii din tabela pacienți
global $wpdb;
$patients_in_table = $wpdb->get_results("
    SELECT user_id, cnp, email, family_role, family_name 
    FROM {$wpdb->prefix}clinica_patients 
    WHERE user_id > 0 
    ORDER BY family_name, cnp
");

// Creează array cu pacienții din tabelă
$patients_user_ids = array();
foreach ($patients_in_table as $patient) {
    $patients_user_ids[] = $patient->user_id;
}

echo "<h2>Analiză Detaliată Probleme</h2>";

$issues_found = 0;
$users_without_clinica_role = array();
$users_with_wrong_role = array();
$users_not_in_patients_table = array();
$other_issues = array();

foreach ($users as $user) {
    $user_id = $user->ID;
    $display_name = $user->display_name;
    $user_roles = $user->roles;
    
    // Verifică dacă este în tabela pacienți
    $is_patient = in_array($user_id, $patients_user_ids);
    
    // Verifică dacă are rol Clinica
    $has_clinica_role = Clinica_Roles::has_clinica_role($user_id);
    $clinica_role = Clinica_Roles::get_user_role($user_id);
    
    // Logica de identificare probleme
    if ($is_patient) {
        // Este pacient - trebuie să aibă rolul clinica_patient
        if (!$has_clinica_role) {
            $users_without_clinica_role[] = $user;
            $issues_found++;
        } elseif ($clinica_role !== 'clinica_patient') {
            $users_with_wrong_role[] = $user;
            $issues_found++;
        }
    } else {
        // Nu este pacient - verifică dacă are rol Clinica
        if ($has_clinica_role) {
            // Verifică dacă este staff
            $is_staff = in_array($clinica_role, array('clinica_doctor', 'clinica_assistant', 'clinica_receptionist', 'clinica_manager', 'clinica_administrator'));
            if (!$is_staff) {
                $other_issues[] = array(
                    'user' => $user,
                    'issue' => 'Rol Clinica necunoscut: ' . $clinica_role,
                    'type' => 'unknown_role'
                );
                $issues_found++;
            }
        }
    }
}

// Verifică utilizatori cu rol Clinica dar nu sunt pacienți
$users_with_clinica_but_not_patients = array();
foreach ($users as $user) {
    $user_id = $user->ID;
    $is_patient = in_array($user_id, $patients_user_ids);
    $has_clinica_role = Clinica_Roles::has_clinica_role($user_id);
    
    if ($has_clinica_role && !$is_patient) {
        $clinica_role = Clinica_Roles::get_user_role($user_id);
        $is_staff = in_array($clinica_role, array('clinica_doctor', 'clinica_assistant', 'clinica_receptionist', 'clinica_manager', 'clinica_administrator'));
        
        if (!$is_staff) {
            $users_with_clinica_but_not_patients[] = $user;
        }
    }
}

echo "<h2>Rezumat Probleme Identificate</h2>";
echo "<p><strong>Total probleme găsite:</strong> $issues_found</p>";
echo "<p><strong>Pacienți fără rol Clinica:</strong> " . count($users_without_clinica_role) . "</p>";
echo "<p><strong>Utilizatori cu rol greșit:</strong> " . count($users_with_wrong_role) . "</p>";
echo "<p><strong>Utilizatori cu rol necunoscut:</strong> " . count($other_issues) . "</p>";
echo "<p><strong>Utilizatori cu rol Clinica dar nu sunt pacienți:</strong> " . count($users_with_clinica_but_not_patients) . "</p>";

// Afișează detaliile problemelor
if (!empty($users_without_clinica_role)) {
    echo "<h3>Pacienți fără rol Clinica</h3>";
    foreach ($users_without_clinica_role as $user) {
        echo "<p style='color: red;'>❌ " . $user->display_name . " (ID: " . $user->ID . ") - " . $user->user_email . "</p>";
    }
}

if (!empty($users_with_wrong_role)) {
    echo "<h3>Utilizatori cu rol greșit</h3>";
    foreach ($users_with_wrong_role as $user) {
        $clinica_role = Clinica_Roles::get_user_role($user->ID);
        echo "<p style='color: orange;'>⚠️ " . $user->display_name . " (ID: " . $user->ID . ") - Rol: " . $clinica_role . "</p>";
    }
}

if (!empty($other_issues)) {
    echo "<h3>Utilizatori cu rol necunoscut</h3>";
    foreach ($other_issues as $issue) {
        echo "<p style='color: red;'>❌ " . $issue['user']->display_name . " (ID: " . $issue['user']->ID . ") - " . $issue['issue'] . "</p>";
    }
}

if (!empty($users_with_clinica_but_not_patients)) {
    echo "<h3>Utilizatori cu rol Clinica dar nu sunt pacienți</h3>";
    foreach ($users_with_clinica_but_not_patients as $user) {
        $clinica_role = Clinica_Roles::get_user_role($user->ID);
        echo "<p style='color: blue;'>ℹ️ " . $user->display_name . " (ID: " . $user->ID . ") - Rol: " . $clinica_role . "</p>";
    }
}

// Verificare suplimentară - utilizatori cu roluri multiple
echo "<h2>Verificare Roluri Multiple</h2>";
$users_with_multiple_roles = array();
foreach ($users as $user) {
    $clinica_roles = array();
    foreach ($user->roles as $role) {
        if (strpos($role, 'clinica_') === 0) {
            $clinica_roles[] = $role;
        }
    }
    
    if (count($clinica_roles) > 1) {
        $users_with_multiple_roles[] = array(
            'user' => $user,
            'roles' => $clinica_roles
        );
    }
}

if (!empty($users_with_multiple_roles)) {
    echo "<p><strong>Utilizatori cu roluri Clinica multiple:</strong> " . count($users_with_multiple_roles) . "</p>";
    foreach ($users_with_multiple_roles as $item) {
        echo "<p style='color: orange;'>⚠️ " . $item['user']->display_name . " (ID: " . $item['user']->ID . ") - Roluri: " . implode(', ', $item['roles']) . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Nu există utilizatori cu roluri Clinica multiple</p>";
}

// Verificare finală
echo "<h2>Verificare Finală</h2>";
$final_issues = 0;
foreach ($users as $user) {
    $user_id = $user->ID;
    $is_patient = in_array($user_id, $patients_user_ids);
    $has_clinica_role = Clinica_Roles::has_clinica_role($user_id);
    $clinica_role = Clinica_Roles::get_user_role($user_id);
    
    if ($is_patient && (!$has_clinica_role || $clinica_role !== 'clinica_patient')) {
        $final_issues++;
    }
}

echo "<p><strong>Probleme rămase după verificare:</strong> $final_issues</p>";

if ($final_issues === 0) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 TOATE PROBLEMELE AU FOST REZOLVATE!</p>";
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ Au rămas $final_issues probleme nerezolvate.</p>";
}

echo "<h2>Concluzie</h2>";
echo "<p><strong>Probleme identificate în această verificare:</strong> $issues_found</p>";
echo "<p><strong>Probleme rămase:</strong> $final_issues</p>";

if ($issues_found > $final_issues) {
    echo "<p style='color: green;'>✅ " . ($issues_found - $final_issues) . " probleme au fost rezolvate automat</p>";
}
?>
