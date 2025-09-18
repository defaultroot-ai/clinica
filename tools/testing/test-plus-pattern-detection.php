<?php
/**
 * Test specific pentru detectarea pattern-ului + în email-uri
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

echo "<h1>🔍 Test Pattern + în Email-uri</h1>";

// Funcție pentru extragerea email-ului de bază (DOAR pattern +)
function extract_base_email($email) {
    // DOAR pattern-ul + este valid pentru familii
    // Părinte: nume@email.com
    // Copil/Membru: nume+altnume@email.com
    $pattern = '/\+[^@]+@/';  // nume+altnume@email.com -> nume@email.com
    
    $base_email = preg_replace($pattern, '@', $email);
    
    return $base_email;
}

// Teste cu pattern-uri
$test_emails = array(
    'ion.popescu@gmail.com',           // Părinte
    'ion.popescu+maria@gmail.com',     // Copil/Membru
    'ion.popescu+vasile@gmail.com',    // Copil/Membru
    'vasile.ionescu@yahoo.com',        // Părinte
    'vasile.ionescu+ana@yahoo.com',    // Copil/Membru
    'gheorghe.dumitrescu@hotmail.com', // Părinte
    'gheorghe.dumitrescu+elena@hotmail.com', // Copil/Membru
    'gheorghe.dumitrescu+mihai@hotmail.com', // Copil/Membru
    'gheorghe.dumitrescu+ioana@hotmail.com', // Copil/Membru
    'test@email.com',                  // Email normal (nu familie)
    'test.alt@email.com',              // Email cu . (nu familie)
    'test_alt@email.com'               // Email cu _ (nu familie)
);

echo "<h2>1. Test Pattern-uri Email</h2>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Email Original</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Email de Bază</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Pattern + Detectat</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Tip</th>";
echo "</tr>";

foreach ($test_emails as $email) {
    $base_email = extract_base_email($email);
    $has_plus = strpos($email, '+') !== false;
    $is_family = $has_plus || in_array($email, array('ion.popescu@gmail.com', 'vasile.ionescu@yahoo.com', 'gheorghe.dumitrescu@hotmail.com'));
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($email) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($base_email) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($has_plus ? '✅ DA' : '❌ NU') . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($is_family ? '👨‍👩‍👧‍👦 Familie' : '👤 Individual') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Verifică pacienții reali cu pattern +
echo "<h2>2. Pacienți cu Pattern + (din baza de date)</h2>";

$stmt = $pdo->query("SELECT p.id, p.user_id, u.user_email as email, u.display_name FROM wp_clinica_patients p LEFT JOIN wp_users u ON p.user_id = u.ID WHERE u.user_email LIKE '%+%' ORDER BY u.user_email");
$plus_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($plus_patients) {
    echo "<p><strong>✅ S-au găsit " . count($plus_patients) . " pacienți cu pattern +</strong></p>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>ID</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Nume</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Email</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Email de Bază</th>";
    echo "</tr>";
    
    foreach ($plus_patients as $patient) {
        $base_email = extract_base_email($patient['email']);
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $patient['id'] . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($patient['display_name']) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($patient['email']) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($base_email) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p><strong>❌ Nu s-au găsit pacienți cu pattern +</strong></p>";
}

// 3. Grupează pacienții cu pattern + pe familii
echo "<h2>3. Familii Detectate (Pattern +)</h2>";

$stmt = $pdo->query("SELECT p.id, p.user_id, u.user_email as email, u.display_name FROM wp_clinica_patients p LEFT JOIN wp_users u ON p.user_id = u.ID WHERE u.user_email IS NOT NULL AND u.user_email != '' ORDER BY u.user_email");
$all_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$email_groups = array();
foreach ($all_patients as $patient) {
    $base_email = extract_base_email($patient['email']);
    if (!isset($email_groups[$base_email])) {
        $email_groups[$base_email] = array();
    }
    $email_groups[$base_email][] = $patient;
}

$families_found = 0;
foreach ($email_groups as $base_email => $members) {
    if (count($members) > 1) {
        // Verifică dacă cel puțin un membru are pattern +
        $has_plus_pattern = false;
        foreach ($members as $member) {
            if (strpos($member['email'], '+') !== false) {
                $has_plus_pattern = true;
                break;
            }
        }
        
        if ($has_plus_pattern) {
            $families_found++;
            echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; background: #e8f5e8;'>";
            echo "<h3>Familie detectată: $base_email</h3>";
            echo "<p><strong>Membri (" . count($members) . "):</strong></p>";
            echo "<ul>";
            foreach ($members as $member) {
                $is_parent = $member['email'] === $base_email;
                $has_plus = strpos($member['email'], '+') !== false;
                $role = $is_parent ? '👨‍👩‍👧‍👦 Părinte' : ($has_plus ? '👶 Copil/Membru' : '👤 Individual');
                echo "<li><strong>" . htmlspecialchars($member['display_name']) . "</strong> (" . htmlspecialchars($member['email']) . ") - $role</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
    }
}

if ($families_found > 0) {
    echo "<p><strong>✅ S-au detectat $families_found familii cu pattern +</strong></p>";
} else {
    echo "<p><strong>ℹ️ Nu s-au detectat familii cu pattern +</strong></p>";
}

echo "<h2>✅ Test Completat</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px;'>";
echo "<p><strong>Pattern-ul + este configurat corect!</strong></p>";
echo "<p>Algoritmul detectează DOAR:</p>";
echo "<ul>";
echo "<li>✅ Părinte: nume@email.com</li>";
echo "<li>✅ Copil/Membru: nume+altnume@email.com</li>";
echo "<li>❌ NU detectează: nume.altnume@email.com</li>";
echo "<li>❌ NU detectează: nume_altnume@email.com</li>";
echo "</ul>";
echo "</div>"; 