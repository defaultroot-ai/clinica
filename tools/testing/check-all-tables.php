<?php
/**
 * Script pentru a verifica toate tabelele din baza de date
 */

echo "<h1>Verificare Toate Tabelele - Baza de Date PLM</h1>";

// Conectare directă la baza de date
$host = 'localhost';
$dbname = 'plm'; // Numele bazei de date
$username = 'root'; // Utilizatorul default XAMPP
$password = ''; // Fără parolă pentru XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Conectare la baza de date reușită!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la conectarea la baza de date: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>1. Toate tabelele din baza de date PLM:</h2>";

try {
    // Obține toate tabelele
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($tables)) {
        echo "<p>Numărul total de tabele: <strong>" . count($tables) . "</strong></p>";
        
        echo "<h3>Lista tabelelor:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>#</th><th>Nume Tabel</th><th>Prefix</th></tr>";
        
        foreach ($tables as $index => $table) {
            $prefix = '';
            if (strpos($table, 'wp_') === 0) {
                $prefix = 'WordPress Standard';
            } elseif (strpos($table, 'clinica_') === 0) {
                $prefix = 'Clinica Plugin';
            } else {
                $prefix = 'Alt tip';
            }
            
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td><strong>" . htmlspecialchars($table) . "</strong></td>";
            echo "<td>" . $prefix . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verifică dacă există tabele cu "clinica" în nume
        $clinica_tables = array_filter($tables, function($table) {
            return strpos($table, 'clinica') !== false;
        });
        
        if (!empty($clinica_tables)) {
            echo "<h3>🎯 Tabele cu 'clinica' în nume:</h3>";
            echo "<ul>";
            foreach ($clinica_tables as $table) {
                echo "<li><strong>" . htmlspecialchars($table) . "</strong></li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠️ Nu există tabele cu 'clinica' în nume!</p>";
        }
        
        // Verifică dacă există tabele cu "patient" în nume
        $patient_tables = array_filter($tables, function($table) {
            return strpos(strtolower($table), 'patient') !== false;
        });
        
        if (!empty($patient_tables)) {
            echo "<h3>🏥 Tabele cu 'patient' în nume:</h3>";
            echo "<ul>";
            foreach ($patient_tables as $table) {
                echo "<li><strong>" . htmlspecialchars($table) . "</strong></li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠️ Nu există tabele cu 'patient' în nume!</p>";
        }
        
        // Verifică dacă există tabele cu "family" în nume
        $family_tables = array_filter($tables, function($table) {
            return strpos(strtolower($table), 'family') !== false;
        });
        
        if (!empty($family_tables)) {
            echo "<h3>👨‍👩‍👧‍👦 Tabele cu 'family' în nume:</h3>";
            echo "<ul>";
            foreach ($family_tables as $table) {
                echo "<li><strong>" . htmlspecialchars($table) . "</strong></li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠️ Nu există tabele cu 'family' în nume!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Nu există tabele în baza de date!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la obținerea tabelelor: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificare structură tabel WordPress (dacă există):</h2>";

// Verifică dacă există wp_users
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'wp_users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabelul wp_users există</p>";
        
        // Verifică câți utilizatori sunt
        $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM wp_users");
        $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['user_count'];
        echo "<p>Numărul de utilizatori: <strong>" . $user_count . "</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Tabelul wp_users NU există!</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea wp_users: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Rezumat și Recomandări</h2>";

echo "<p><strong>Status:</strong> Scriptul de verificare a rulat cu succes!</p>";

if (empty($clinica_tables)) {
    echo "<p style='color: red;'>🚨 PROBLEMĂ IDENTIFICATĂ: Nu există tabele Clinica în baza de date!</p>";
    echo "<p>Acest lucru explică de ce familiile nu se afișează!</p>";
    echo "<p><strong>Posibile cauze:</strong></p>";
    echo "<ul>";
    echo "<li>Plugin-ul Clinica nu a fost activat niciodată</li>";
    echo "<li>Tabelele nu au fost create la activarea plugin-ului</li>";
    echo "<li>Plugin-ul a fost dezactivat și tabelele au fost șterse</li>";
    echo "<li>Probleme la instalarea/activarea plugin-ului</li>";
    echo "</ul>";
    
    echo "<p><strong>Recomandare:</strong></p>";
    echo "<ol>";
    echo "<li>Activează din nou plugin-ul Clinica</li>";
    echo "<li>Verifică dacă tabelele sunt create</li>";
    echo "<li>Dacă nu, reinstalează plugin-ul</li>";
    echo "</ol>";
} else {
    echo "<p style='color: green;'>✅ Tabelele Clinica există în baza de date</p>";
    echo "<p>Problema poate fi în altă parte (interfață, AJAX, etc.)</p>";
}

echo "<p><strong>Următorul pas:</strong> Verifică ce tabele Clinica există și spune-mi rezultatele!</p>";
?>
