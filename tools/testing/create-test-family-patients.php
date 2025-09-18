<?php
/**
 * Creează pacienți de test cu email-uri similare pentru a demonstra detectarea familiilor
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

echo "<h1>👨‍👩‍👧‍👦 Creează Pacienți de Test pentru Familii</h1>";

// Datele pentru familiile de test
$test_families = array(
    array(
        'name' => 'Familia Popescu',
        'members' => array(
            array('first_name' => 'Ion', 'last_name' => 'Popescu', 'email' => 'ion.popescu@gmail.com', 'cnp' => '1850103045379'),
            array('first_name' => 'Maria', 'last_name' => 'Popescu', 'email' => 'ion.popescu+maria@gmail.com', 'cnp' => '2900103045379'),
            array('first_name' => 'Vasile', 'last_name' => 'Popescu', 'email' => 'ion.popescu+vasile@gmail.com', 'cnp' => '1850103045378')
        )
    ),
    array(
        'name' => 'Familia Ionescu',
        'members' => array(
            array('first_name' => 'Vasile', 'last_name' => 'Ionescu', 'email' => 'vasile.ionescu@yahoo.com', 'cnp' => '1850103045377'),
            array('first_name' => 'Ana', 'last_name' => 'Ionescu', 'email' => 'vasile.ionescu+ana@yahoo.com', 'cnp' => '2900103045376')
        )
    ),
    array(
        'name' => 'Familia Dumitrescu',
        'members' => array(
            array('first_name' => 'Gheorghe', 'last_name' => 'Dumitrescu', 'email' => 'gheorghe.dumitrescu@hotmail.com', 'cnp' => '1850103045375'),
            array('first_name' => 'Elena', 'last_name' => 'Dumitrescu', 'email' => 'gheorghe.dumitrescu+elena@hotmail.com', 'cnp' => '2900103045374'),
            array('first_name' => 'Mihai', 'last_name' => 'Dumitrescu', 'email' => 'gheorghe.dumitrescu+mihai@hotmail.com', 'cnp' => '1850103045373'),
            array('first_name' => 'Ioana', 'last_name' => 'Dumitrescu', 'email' => 'gheorghe.dumitrescu+ioana@hotmail.com', 'cnp' => '2900103045372')
        )
    )
);

$created_count = 0;

foreach ($test_families as $family) {
    echo "<h2>Creare {$family['name']}</h2>";
    
    foreach ($family['members'] as $member) {
        try {
            // Creează utilizatorul WordPress
            $username = $member['cnp'];
            $email = $member['email'];
            $display_name = $member['first_name'] . ' ' . $member['last_name'];
            
            // Verifică dacă utilizatorul există deja
            $stmt = $pdo->prepare("SELECT ID FROM wp_users WHERE user_login = ?");
            $stmt->execute([$username]);
            $existing_user = $stmt->fetch();
            
            if ($existing_user) {
                echo "<p>⚠️ Utilizatorul $username există deja</p>";
                continue;
            }
            
            // Creează utilizatorul WordPress
            $stmt = $pdo->prepare("INSERT INTO wp_users (user_login, user_pass, user_email, display_name, user_registered) VALUES (?, ?, ?, ?, NOW())");
            $password_hash = wp_hash_password($member['cnp']); // Parolă bazată pe CNP
            $stmt->execute([$username, $password_hash, $email, $display_name]);
            $user_id = $pdo->lastInsertId();
            
            // Adaugă meta datele utilizatorului
            $stmt = $pdo->prepare("INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, 'first_name', $member['first_name']]);
            $stmt->execute([$user_id, 'last_name', $member['last_name']]);
            $stmt->execute([$user_id, 'nickname', $member['cnp']]);
            
            // Creează pacientul în tabelul clinica
            $stmt = $pdo->prepare("INSERT INTO wp_clinica_patients (user_id, cnp, cnp_type, created_at, updated_at) VALUES (?, ?, 'romanian', NOW(), NOW())");
            $stmt->execute([$user_id, $member['cnp']]);
            
            echo "<p>✅ Creat: {$member['first_name']} {$member['last_name']} ({$member['email']})</p>";
            $created_count++;
            
        } catch (Exception $e) {
            echo "<p>❌ Eroare la crearea {$member['first_name']} {$member['last_name']}: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h2>🎉 Rezumat</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px;'>";
echo "<p><strong>✅ S-au creat $created_count pacienți de test</strong></p>";
echo "<p>Acum poți testa funcționalitatea de detectare familii în admin:</p>";
echo "<ol>";
echo "<li>Mergi la Admin → Clinica → Familii</li>";
echo "<li>Click pe \"Creează Familii Automat\"</li>";
echo "<li>Click pe \"Detectează Familii\" pentru a vedea familiile detectate</li>";
echo "<li>Click pe \"Creează Familiile Detectate\" pentru a le crea</li>";
echo "</ol>";
echo "</div>";

// Funcție helper pentru hash password
function wp_hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
} 