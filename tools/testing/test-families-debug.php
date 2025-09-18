<?php
/**
 * Script de test pentru a verifica familiile din baza de date
 */

// Încarcă WordPress - corectez calea pentru structura ta de foldere
require_once('../../../wp-load.php');

// Verifică dacă suntem admin
if (!current_user_can('manage_options')) {
    die('Nu aveți permisiunea de a accesa acest script');
}

echo "<h1>Debug Familii - Clinica Plugin</h1>";

// Verifică dacă clasa există
if (!class_exists('Clinica_Family_Manager')) {
    echo "<p style='color: red;'>❌ Clasa Clinica_Family_Manager nu există!</p>";
    exit;
}

$family_manager = new Clinica_Family_Manager();

echo "<h2>1. Test get_all_families()</h2>";
try {
    $families = $family_manager->get_all_families();
    echo "<p>✅ get_all_families() executat cu succes</p>";
    echo "<p>Numărul de familii returnate: " . count($families) . "</p>";
    
    if (!empty($families)) {
        echo "<h3>Primele 5 familii:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Family ID</th><th>Family Name</th><th>Member Count</th></tr>";
        
        $count = 0;
        foreach ($families as $family) {
            if ($count >= 5) break;
            
            echo "<tr>";
            echo "<td>" . $family->family_id . "</td>";
            echo "<td>" . htmlspecialchars($family->family_name) . "</td>";
            echo "<td>" . $family->member_count . "</td>";
            echo "</tr>";
            
            $count++;
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nu există familii în baza de date</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Eroare la get_all_families(): " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificare directă în baza de date</h2>";
global $wpdb;

$table_patients = $wpdb->prefix . 'clinica_patients';

// Verifică câte familii există
$family_count = $wpdb->get_var("SELECT COUNT(DISTINCT family_id) FROM $table_patients WHERE family_id IS NOT NULL");
echo "<p>Numărul total de familii în baza de date: " . ($family_count ?: 0) . "</p>";

// Verifică câți membri au familii
$member_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients WHERE family_id IS NOT NULL");
echo "<p>Numărul total de membri cu familie: " . ($member_count ?: 0) . "</p>";

// Verifică câți capi de familie există
$head_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients WHERE family_role = 'head'");
echo "<p>Numărul de capi de familie: " . ($head_count ?: 0) . "</p>";

// Verifică primele 5 familii cu detalii
echo "<h3>Primele 5 familii cu detalii:</h3>";
$sample_families = $wpdb->get_results("
    SELECT family_id, family_name, family_role, 
           COUNT(*) as member_count
    FROM $table_patients 
    WHERE family_id IS NOT NULL 
    GROUP BY family_id, family_name, family_role
    ORDER BY family_id 
    LIMIT 5
");

if (!empty($sample_families)) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Family ID</th><th>Family Name</th><th>Role</th><th>Member Count</th></tr>";
    
    foreach ($sample_families as $family) {
        echo "<tr>";
        echo "<td>" . $family->family_id . "</td>";
        echo "<td>" . htmlspecialchars($family->family_name ?: 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($family->family_role ?: 'NULL') . "</td>";
        echo "<td>" . $family->member_count . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ Nu există familii în baza de date</p>";
}

echo "<h2>3. Test get_family_members()</h2>";
if (!empty($families)) {
    $first_family = $families[0];
    try {
        $members = $family_manager->get_family_members($first_family->family_id);
        echo "<p>✅ get_family_members() executat cu succes pentru familia " . $first_family->family_id . "</p>";
        echo "<p>Numărul de membri: " . count($members) . "</p>";
        
        if (!empty($members)) {
            echo "<h4>Membrii familiei " . $first_family->family_id . ":</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Role</th><th>Email</th></tr>";
            
            foreach ($members as $member) {
                echo "<tr>";
                echo "<td>" . $member->id . "</td>";
                echo "<td>" . htmlspecialchars($member->display_name ?: 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($member->family_role ?: 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($member->user_email ?: 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Eroare la get_family_members(): " . $e->getMessage() . "</p>";
    }
}

echo "<h2>4. Rezumat</h2>";
echo "<p>Plugin-ul pare să funcționeze corect din punct de vedere tehnic.</p>";
echo "<p>Dacă familiile nu se afișează în interfață, problema poate fi:</p>";
echo "<ul>";
echo "<li>JavaScript-ul AJAX nu se execută</li>";
echo "<li>Există o eroare în consola browser-ului</li>";
echo "<li>Funcția AJAX returnează o eroare</li>";
echo "</ul>";

echo "<p><strong>Recomandare:</strong> Verifică consola browser-ului pentru erori JavaScript!</p>";
?>
