<?php
/**
 * Script de verificare și reparare roluri pentru toți utilizatorii WordPress
 * URL: http://192.168.1.182/plm/wp-content/plugins/clinica/tools/debug/check-all-users-roles.php
 */

// Include WordPress
require_once('../../../../wp-load.php');

// Include clasele Clinica
require_once('../includes/class-clinica-roles.php');

echo "<h1>Verificare și Reparare Roluri - Toți Utilizatorii</h1>";

// Obține toți utilizatorii (excluzând administratorii WordPress)
$users = get_users(array(
    'exclude' => array(1), // Exclude administratorul principal WordPress
    'orderby' => 'display_name',
    'order' => 'ASC'
));

echo "<h2>Analiză Utilizatori WordPress</h2>";
echo "<p><strong>Total utilizatori găsiți:</strong> " . count($users) . "</p>";

// Verifică rolurile Clinica disponibile
$clinica_roles = Clinica_Roles::get_clinica_roles();
echo "<p><strong>Roluri Clinica disponibile:</strong> " . implode(', ', array_keys($clinica_roles)) . "</p>";

// Verifică utilizatorii din tabela pacienți
global $wpdb;
$patients_in_table = $wpdb->get_results("
    SELECT user_id, cnp, email, family_role, family_name 
    FROM {$wpdb->prefix}clinica_patients 
    WHERE user_id > 0 
    ORDER BY family_name, cnp
");

echo "<h2>Analiză Pacienți din Tabela Clinica</h2>";
echo "<p><strong>Total pacienți în tabelă:</strong> " . count($patients_in_table) . "</p>";

// Creează array cu pacienții din tabelă
$patients_user_ids = array();
foreach ($patients_in_table as $patient) {
    $patients_user_ids[] = $patient->user_id;
}

echo "<h2>Verificare Roluri Utilizatori</h2>";

$issues_found = 0;
$fixed_users = 0;
$users_without_clinica_role = array();
$users_with_wrong_role = array();

foreach ($users as $user) {
    $user_id = $user->ID;
    $display_name = $user->display_name;
    $user_roles = $user->roles;
    
    // Verifică dacă este în tabela pacienți
    $is_patient = in_array($user_id, $patients_user_ids);
    
    // Verifică dacă are rol Clinica
    $has_clinica_role = Clinica_Roles::has_clinica_role($user_id);
    $clinica_role = Clinica_Roles::get_user_role($user_id);
    
    echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
    echo "<h3>Utilizator: $display_name (ID: $user_id)</h3>";
    echo "<p><strong>Email:</strong> " . $user->user_email . "</p>";
    echo "<p><strong>Roluri WordPress:</strong> " . implode(', ', $user_roles) . "</p>";
    echo "<p><strong>Este pacient în tabelă:</strong> " . ($is_patient ? 'DA' : 'NU') . "</p>";
    echo "<p><strong>Are rol Clinica:</strong> " . ($has_clinica_role ? 'DA' : 'NU') . "</p>";
    echo "<p><strong>Rol Clinica:</strong> " . ($clinica_role ? $clinica_role : 'NICIUNUL') . "</p>";
    
    // Logica de reparare
    if ($is_patient) {
        // Este pacient - trebuie să aibă rolul clinica_patient
        if (!$has_clinica_role) {
            echo "<p style='color: red;'>❌ PROBLEMĂ: Pacient fără rol Clinica!</p>";
            $users_without_clinica_role[] = $user;
            $issues_found++;
        } elseif ($clinica_role !== 'clinica_patient') {
            echo "<p style='color: orange;'>⚠️ ATENȚIE: Pacient cu rol Clinica greșit: $clinica_role</p>";
            $users_with_wrong_role[] = $user;
            $issues_found++;
        } else {
            echo "<p style='color: green;'>✅ OK: Pacient cu rol corect</p>";
        }
    } else {
        // Nu este pacient - verifică dacă are rol Clinica
        if ($has_clinica_role) {
            echo "<p style='color: blue;'>ℹ️ INFO: Utilizator cu rol Clinica dar nu este pacient</p>";
            // Verifică dacă este staff
            $is_staff = in_array($clinica_role, array('clinica_doctor', 'clinica_assistant', 'clinica_receptionist', 'clinica_manager', 'clinica_administrator'));
            if (!$is_staff) {
                echo "<p style='color: red;'>❌ PROBLEMĂ: Rol Clinica necunoscut: $clinica_role</p>";
                $issues_found++;
            } else {
                echo "<p style='color: green;'>✅ OK: Staff cu rol corect</p>";
            }
        } else {
            echo "<p style='color: gray;'>ℹ️ INFO: Utilizator normal WordPress (fără rol Clinica)</p>";
        }
    }
    echo "</div>";
}

echo "<h2>Rezumat Probleme</h2>";
echo "<p><strong>Probleme găsite:</strong> $issues_found</p>";
echo "<p><strong>Pacienți fără rol Clinica:</strong> " . count($users_without_clinica_role) . "</p>";
echo "<p><strong>Utilizatori cu rol greșit:</strong> " . count($users_with_wrong_role) . "</p>";

// Reparare automată
if ($issues_found > 0) {
    echo "<h2>Reparare Automată</h2>";
    
    // Repară pacienții fără rol Clinica
    if (!empty($users_without_clinica_role)) {
        echo "<h3>Reparare Pacienți fără rol Clinica</h3>";
        foreach ($users_without_clinica_role as $user) {
            $user->add_role('clinica_patient');
            echo "<p style='color: green;'>✅ Adăugat rol clinica_patient pentru: " . $user->display_name . "</p>";
            $fixed_users++;
        }
    }
    
    // Repară utilizatorii cu rol greșit
    if (!empty($users_with_wrong_role)) {
        echo "<h3>Reparare Utilizatori cu rol greșit</h3>";
        foreach ($users_with_wrong_role as $user) {
            // Șterge rolul greșit și adaugă cel corect
            $user->remove_role(Clinica_Roles::get_user_role($user->ID));
            $user->add_role('clinica_patient');
            echo "<p style='color: green;'>✅ Corectat rol pentru: " . $user->display_name . "</p>";
            $fixed_users++;
        }
    }
    
    echo "<p><strong>Utilizatori reparați:</strong> $fixed_users</p>";
} else {
    echo "<p style='color: green;'>✅ Nu s-au găsit probleme! Toți utilizatorii au rolurile corecte.</p>";
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

if ($final_issues === 0) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 TOATE PROBLEMELE AU FOST REZOLVATE! Toți utilizatorii au rolurile corecte.</p>";
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ Au rămas $final_issues probleme nerezolvate.</p>";
}

// Statistici finale
echo "<h2>Statistici Finale</h2>";
echo "<p><strong>Total utilizatori WordPress:</strong> " . count($users) . "</p>";
echo "<p><strong>Total pacienți în tabelă:</strong> " . count($patients_in_table) . "</p>";
echo "<p><strong>Probleme găsite:</strong> $issues_found</p>";
echo "<p><strong>Probleme rezolvate:</strong> $fixed_users</p>";
echo "<p><strong>Probleme rămase:</strong> $final_issues</p>";

// Verificare hook-uri
echo "<h2>Verificare Hook-uri WordPress</h2>";
$login_redirect_hooks = $GLOBALS['wp_filter']['login_redirect'] ?? null;
if ($login_redirect_hooks) {
    echo "<p style='color: green;'>✅ Hook-uri login_redirect active</p>";
} else {
    echo "<p style='color: red;'>❌ NU există hook-uri login_redirect active!</p>";
}

echo "<h2>Concluzie</h2>";
if ($final_issues === 0) {
    echo "<p style='color: green; font-size: 20px; font-weight: bold;'>✅ VERIFICAREA COMPLETĂ FINALIZATĂ! Toți utilizatorii au rolurile potrivite și sistemul de redirect va funcționa corect.</p>";
} else {
    echo "<p style='color: red; font-size: 20px; font-weight: bold;'>⚠️ VERIFICAREA COMPLETĂ FINALIZATĂ! Au rămas probleme care necesită atenție manuală.</p>";
}
?>
