<?php
/**
 * Script pentru a verifica toate tabelele care ar putea conține familii
 */

echo "<h1>Verificare Toate Tabelele Familii - Clinica Plugin</h1>";

// Conectare directă la baza de date
$host = 'localhost';
$dbname = 'plm';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Conectare la baza de date reușită!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la conectarea la baza de date: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>1. Toate tabelele din baza de date:</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($tables)) {
        echo "<p>Numărul total de tabele: <strong>" . count($tables) . "</strong></p>";
        
        // Filtrează tabelele care ar putea conține familii
        $family_related_tables = array_filter($tables, function($table) {
            return strpos(strtolower($table), 'family') !== false || 
                   strpos(strtolower($table), 'patient') !== false ||
                   strpos(strtolower($table), 'clinica') !== false;
        });
        
        if (!empty($family_related_tables)) {
            echo "<h3>🎯 Tabele care ar putea conține familii:</h3>";
            echo "<ul>";
            foreach ($family_related_tables as $table) {
                echo "<li><strong>" . htmlspecialchars($table) . "</strong></li>";
            }
            echo "</ul>";
        }
        
        // Verifică fiecare tabel relevant pentru familii
        foreach ($family_related_tables as $table) {
            echo "<h3>📊 Tabel: " . htmlspecialchars($table) . "</h3>";
            
            try {
                // Verifică câte rânduri are tabelul
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<p>Numărul de rânduri: <strong>" . $count . "</strong></p>";
                
                if ($count > 0) {
                    // Verifică structura tabelului
                    $stmt = $pdo->query("DESCRIBE `$table`");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<p>Coloanele tabelului:</p>";
                    echo "<ul>";
                    foreach ($columns as $column) {
                        echo "<li><strong>" . $column['Field'] . "</strong> - " . $column['Type'] . "</li>";
                    }
                    echo "</ul>";
                    
                    // Verifică dacă tabelul conține date despre familii
                    if (strpos(strtolower($table), 'family') !== false) {
                        echo "<h4>🔍 Verificare date familii în tabelul $table:</h4>";
                        
                        // Încearcă să găsească coloane relevante
                        $family_columns = array_filter($columns, function($col) {
                            return strpos(strtolower($col['Field']), 'family') !== false ||
                                   strpos(strtolower($col['Field']), 'name') !== false ||
                                   strpos(strtolower($col['Field']), 'role') !== false;
                        });
                        
                        if (!empty($family_columns)) {
                            echo "<p>Coloane relevante pentru familii găsite:</p>";
                            echo "<ul>";
                            foreach ($family_columns as $col) {
                                echo "<li><strong>" . $col['Field'] . "</strong></li>";
                            }
                            echo "</ul>";
                            
                            // Încearcă să obțină primele 5 rânduri
                            try {
                                $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 5");
                                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (!empty($rows)) {
                                    echo "<h4>Primele 5 rânduri din $table:</h4>";
                                    echo "<table border='1' style='border-collapse: collapse;'>";
                                    
                                    // Header
                                    echo "<tr>";
                                    foreach (array_keys($rows[0]) as $header) {
                                        echo "<th>" . htmlspecialchars($header) . "</th>";
                                    }
                                    echo "</tr>";
                                    
                                    // Rânduri
                                    foreach ($rows as $row) {
                                        echo "<tr>";
                                        foreach ($row as $value) {
                                            echo "<td>" . htmlspecialchars($value ?: 'NULL') . "</td>";
                                        }
                                        echo "</tr>";
                                    }
                                    echo "</table>";
                                }
                            } catch (PDOException $e) {
                                echo "<p style='color: orange;'>⚠️ Nu s-au putut citi datele din tabel: " . $e->getMessage() . "</p>";
                            }
                        }
                    }
                }
                
            } catch (PDOException $e) {
                echo "<p style='color: red;'>❌ Eroare la verificarea tabelului $table: " . $e->getMessage() . "</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ Nu există tabele în baza de date!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la obținerea tabelelor: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificare WordPress options (cache):</h2>";

try {
    // Verifică dacă există opțiuni WordPress care ar putea conține familii
    $stmt = $pdo->query("SELECT option_name, option_value FROM wp_options WHERE option_name LIKE '%family%' OR option_name LIKE '%clinica%' LIMIT 20");
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($options)) {
        echo "<h3>🔧 Opțiuni WordPress relevante:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Option Name</th><th>Option Value</th></tr>";
        
        foreach ($options as $option) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($option['option_name']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($option['option_value'], 0, 200)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nu există opțiuni WordPress relevante pentru familii</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Eroare la verificarea opțiunilor WordPress: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Rezumat și Recomandări</h2>";

echo "<p><strong>Status:</strong> Scriptul de verificare a rulat cu succes!</p>";
echo "<p><strong>Problema identificată:</strong> Contradicția între interfață (463 familii) și baza de date (0 familii)</p>";

echo "<p><strong>Următorul pas:</strong> Verifică ce tabele conțin date despre familii și spune-mi rezultatele!</p>";
?>
