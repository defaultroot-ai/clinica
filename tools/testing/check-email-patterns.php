<?php
/**
 * Verifică pattern-urile de email pentru detectarea familiilor
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

echo "<h1>🔍 Verificare Pattern-uri Email pentru Familii</h1>";

// Funcție pentru extragerea email-ului de bază
function extract_base_email($email) {
    // DOAR pattern-ul + este valid pentru familii
    // Părinte: nume@email.com
    // Copil/Membru: nume+altnume@email.com
    $pattern = '/\+[^@]+@/';  // nume+altnume@email.com -> nume@email.com
    
    $base_email = preg_replace($pattern, '@', $email);
    
    return $base_email;
}

// 1. Obține toți pacienții cu email
echo "<h2>1. Analiză Email-uri Pacienți</h2>";

$stmt = $pdo->query("SELECT p.id, p.user_id, u.user_email as email, u.display_name FROM wp_clinica_patients p LEFT JOIN wp_users u ON p.user_id = u.ID WHERE u.user_email IS NOT NULL AND u.user_email != '' ORDER BY u.user_email LIMIT 50");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Primii 50 pacienți cu email:</strong></p>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>ID</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Nume</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Email Original</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Email de Bază</th>";
echo "</tr>";

foreach ($patients as $patient) {
    $base_email = extract_base_email($patient['email']);
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $patient['id'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($patient['display_name']) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($patient['email']) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($base_email) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Grupează pacienții pe baza email-ului de bază
echo "<h2>2. Grupuri Email-uri de Bază</h2>";

$email_groups = array();
foreach ($patients as $patient) {
    $base_email = extract_base_email($patient['email']);
    if (!isset($email_groups[$base_email])) {
        $email_groups[$base_email] = array();
    }
    $email_groups[$base_email][] = $patient;
}

// Afișează grupurile cu mai mulți membri
$families_found = 0;
foreach ($email_groups as $base_email => $members) {
    if (count($members) > 1) {
        $families_found++;
        echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; background: #e8f5e8;'>";
        echo "<h3>Familie detectată: $base_email</h3>";
        echo "<p><strong>Membri (" . count($members) . "):</strong></p>";
        echo "<ul>";
        foreach ($members as $member) {
            echo "<li>" . htmlspecialchars($member['display_name']) . " (" . htmlspecialchars($member['email']) . ")</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

if ($families_found > 0) {
    echo "<p><strong>✅ S-au detectat $families_found familii potențiale</strong></p>";
} else {
    echo "<p><strong>ℹ️ Nu s-au detectat familii cu email-uri similare în primii 50 pacienți</strong></p>";
}

// 3. Verifică toți pacienții pentru pattern-uri
echo "<h2>3. Verificare Completă Pattern-uri</h2>";

$stmt = $pdo->query("SELECT p.id, p.user_id, u.user_email as email, u.display_name FROM wp_clinica_patients p LEFT JOIN wp_users u ON p.user_id = u.ID WHERE u.user_email IS NOT NULL AND u.user_email != ''");
$all_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$all_email_groups = array();
foreach ($all_patients as $patient) {
    $base_email = extract_base_email($patient['email']);
    if (!isset($all_email_groups[$base_email])) {
        $all_email_groups[$base_email] = array();
    }
    $all_email_groups[$base_email][] = $patient;
}

$total_families = 0;
$total_potential_members = 0;

foreach ($all_email_groups as $base_email => $members) {
    if (count($members) > 1) {
        $total_families++;
        $total_potential_members += count($members);
    }
}

echo "<p><strong>📊 Statistici Complete:</strong></p>";
echo "<ul>";
echo "<li>Total pacienți cu email: " . count($all_patients) . "</li>";
echo "<li>Email-uri de bază unice: " . count($all_email_groups) . "</li>";
echo "<li>Familii potențiale detectate: $total_families</li>";
echo "<li>Membri potențiali în familii: $total_potential_members</li>";
echo "</ul>";

if ($total_families > 0) {
    echo "<p><strong>✅ S-au detectat $total_families familii potențiale cu $total_potential_members membri</strong></p>";
    
    // Afișează primele 5 familii
    $count = 0;
    foreach ($all_email_groups as $base_email => $members) {
        if (count($members) > 1 && $count < 5) {
            $count++;
            echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; background: #fff3cd;'>";
            echo "<h4>Familie $count: $base_email</h4>";
            echo "<p><strong>Membri (" . count($members) . "):</strong></p>";
            echo "<ul>";
            foreach ($members as $member) {
                echo "<li>" . htmlspecialchars($member['display_name']) . " (" . htmlspecialchars($member['email']) . ")</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
    }
} else {
    echo "<p><strong>ℹ️ Nu s-au detectat familii cu email-uri similare</strong></p>";
    echo "<p>Toate adresele de email sunt unice sau nu au pattern-uri de familie.</p>";
}

// 4. Sfaturi pentru testare
echo "<h2>4. Sfaturi pentru Testare</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>💡 Pentru a testa funcționalitatea:</strong></p>";
echo "<ol>";
echo "<li>Creează manual câteva pacienți cu email-uri similare (ex: test@email.com, test+maria@email.com)</li>";
echo "<li>Sau importă date de test cu pattern-uri de familie</li>";
echo "<li>Apoi testează din nou funcția de detectare familii</li>";
echo "</ol>";
echo "</div>";

echo "<h2>✅ Verificare Completă</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px;'>";
echo "<p><strong>Analiza pattern-urilor de email a fost completată!</strong></p>";
echo "<p>Rezultat: $total_families familii potențiale detectate din " . count($all_patients) . " pacienți.</p>";
echo "</div>"; 