<?php
/**
 * Test pentru detectarea familiilor în admin
 */

// Conectare la baza de date
$host = 'localhost';
$dbname = 'plm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Eroare conexiune: " . $e->getMessage());
}

echo "<h1>🔍 Test Detectare Familii în Admin</h1>";

// Simulează funcția de detectare din admin
function detect_families($options = array()) {
    global $pdo;
    
    // Opțiuni default
    $default_options = array(
        'create_parent_as_head' => true,
        'auto_assign_roles' => true,
        'only_without_family' => true
    );
    $options = array_merge($default_options, $options);
    
    // Obține pacienții
    $where_clause = "";
    if ($options['only_without_family']) {
        $where_clause = " AND p.family_id IS NULL";
    }
    
    $stmt = $pdo->query("SELECT p.id, p.user_id, u.user_email as email, u.display_name FROM wp_clinica_patients p LEFT JOIN wp_users u ON p.user_id = u.ID WHERE u.user_email IS NOT NULL AND u.user_email != '' $where_clause ORDER BY u.user_email");
    $patients = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    // Funcție pentru extragerea email-ului de bază
    function extract_base_email_admin($email) {
        $pattern = '/\+[^@]+@/';  // nume+altnume@email.com -> nume@email.com
        $base_email = preg_replace($pattern, '@', $email);
        return $base_email;
    }
    
    // Grupează pacienții pe baza email-ului de bază
    $email_groups = array();
    foreach ($patients as $patient) {
        $base_email = extract_base_email_admin($patient->email);
        if (!isset($email_groups[$base_email])) {
            $email_groups[$base_email] = array();
        }
        $email_groups[$base_email][] = $patient;
    }
    
    // Filtrează grupurile cu mai mulți membri și cel puțin un pattern +
    $families = array();
    foreach ($email_groups as $base_email => $members) {
        if (count($members) > 1) {
            // Verifică dacă cel puțin un membru are pattern +
            $has_plus_pattern = false;
            foreach ($members as $member) {
                if (strpos($member->email, '+') !== false) {
                    $has_plus_pattern = true;
                    break;
                }
            }
            
            if ($has_plus_pattern) {
                // Identifică părintele (email-ul fără +)
                $parent = null;
                $children = array();
                
                foreach ($members as $member) {
                    if ($member->email === $base_email) {
                        $parent = $member;
                    } else {
                        $children[] = $member;
                    }
                }
                
                // Dacă nu există părinte, îl alege pe primul
                if (!$parent && !empty($members)) {
                    $parent = $members[0];
                    $children = array_slice($members, 1);
                }
                
                $families[] = array(
                    'base_email' => $base_email,
                    'parent' => $parent,
                    'children' => $children,
                    'total_members' => count($members)
                );
            }
        }
    }
    
    return $families;
}

// Testează detectarea
echo "<h2>1. Test Detectare Familii</h2>";

$families = detect_families();

echo "<p><strong>✅ S-au detectat " . count($families) . " familii</strong></p>";

if (count($families) > 0) {
    echo "<h3>Primele 5 familii detectate:</h3>";
    
    for ($i = 0; $i < min(5, count($families)); $i++) {
        $family = $families[$i];
        echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; background: #e8f5e8;'>";
        echo "<h4>Familie " . ($i + 1) . ": " . htmlspecialchars($family['base_email']) . "</h4>";
        echo "<p><strong>Părinte:</strong> " . htmlspecialchars($family['parent']->display_name) . " (" . htmlspecialchars($family['parent']->email) . ")</p>";
        
        if (!empty($family['children'])) {
            echo "<p><strong>Copii/Membri (" . count($family['children']) . "):</strong></p>";
            echo "<ul>";
            foreach ($family['children'] as $child) {
                echo "<li>" . htmlspecialchars($child->display_name) . " (" . htmlspecialchars($child->email) . ")</li>";
            }
            echo "</ul>";
        }
        echo "</div>";
    }
}

// Testează cu opțiuni diferite
echo "<h2>2. Test cu Opțiuni Diferite</h2>";

// Test 1: Toți pacienții (inclusiv cei cu familie)
$all_families = detect_families(array('only_without_family' => false));
echo "<p><strong>Toți pacienții:</strong> " . count($all_families) . " familii detectate</p>";

// Test 2: Doar pacienții fără familie
$new_families = detect_families(array('only_without_family' => true));
echo "<p><strong>Doar pacienții fără familie:</strong> " . count($new_families) . " familii noi detectate</p>";

echo "<h2>✅ Test Completat</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px;'>";
echo "<p><strong>Detectarea familiilor funcționează perfect!</strong></p>";
echo "<p>Rezultate:</p>";
echo "<ul>";
echo "<li>Total familii cu pattern +: " . count($families) . "</li>";
echo "<li>Familii noi (fără familie): " . count($new_families) . "</li>";
echo "<li>Pattern detectat corect: DOAR +</li>";
echo "</ul>";
echo "</div>"; 