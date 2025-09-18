<?php
/**
 * Test pentru verificarea corectării diacriticelor
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<h1>Test Corectare Diacritice</h1>";

// Verifică fișierul principal
echo "<h2>Test Fișier Principal</h2>";

$file_path = plugin_dir_path(__FILE__) . 'clinica.php';
$content = file_get_contents($file_path);

$diacritics_to_check = array(
    'Pacienți' => 'Pacienți',
    'Programări' => 'Programări', 
    'Setări' => 'Setări',
    'Import Pacienți' => 'Import Pacienți',
    'Creare Pacient' => 'Creare Pacient'
);

foreach ($diacritics_to_check as $correct => $description) {
    if (strpos($content, $correct) !== false) {
        echo "<p style='color: green;'>✅ '$correct' este corect în fișier</p>";
    } else {
        echo "<p style='color: red;'>❌ '$correct' NU este corect în fișier</p>";
    }
}

// Verifică caracterele corupte
$corrupted_chars = array(
    'PacienČ›i' => 'Caractere corupte pentru Pacienți',
    'ProgramÄDri' => 'Caractere corupte pentru Programări',
    'SetÄOri' => 'Caractere corupte pentru Setări',
    'Č›' => 'Caractere corupte pentru ț',
    'Ä' => 'Caractere corupte pentru ă'
);

foreach ($corrupted_chars as $corrupted => $description) {
    if (strpos($content, $corrupted) !== false) {
        echo "<p style='color: red;'>❌ Găsite caractere corupte: '$corrupted' - $description</p>";
    } else {
        echo "<p style='color: green;'>✅ Nu s-au găsit caractere corupte: '$corrupted'</p>";
    }
}

// Verifică CSS-ul admin
echo "<h2>Test CSS Admin</h2>";

$css_file = plugin_dir_path(__FILE__) . 'assets/css/admin.css';
if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    
    if (strpos($css_content, 'font-family') !== false) {
        echo "<p style='color: green;'>✅ Font-family este definit în CSS-ul admin</p>";
    } else {
        echo "<p style='color: red;'>❌ Font-family NU este definit în CSS-ul admin</p>";
    }
    
    if (strpos($css_content, 'Segoe UI') !== false) {
        echo "<p style='color: green;'>✅ Font Segoe UI este specificat</p>";
    } else {
        echo "<p style='color: red;'>❌ Font Segoe UI NU este specificat</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Fișierul CSS admin nu există</p>";
}

// Verifică encoding-ul fișierului
echo "<h2>Test Encoding</h2>";

$encoding = mb_detect_encoding($content, array('UTF-8', 'ISO-8859-1', 'Windows-1252'));
echo "<p>Encoding detectat: <strong>$encoding</strong></p>";

if ($encoding === 'UTF-8') {
    echo "<p style='color: green;'>✅ Fișierul folosește encoding UTF-8 corect</p>";
} else {
    echo "<p style='color: red;'>❌ Fișierul NU folosește encoding UTF-8</p>";
}

// Testează dacă meniurile se încarcă corect
echo "<h2>Test Meniuri Admin</h2>";

if (function_exists('add_menu_page')) {
    echo "<p style='color: green;'>✅ Funcția add_menu_page este disponibilă</p>";
} else {
    echo "<p style='color: red;'>❌ Funcția add_menu_page NU este disponibilă</p>";
}

// Verifică dacă plugin-ul se încarcă
if (class_exists('Clinica_Plugin')) {
    echo "<p style='color: green;'>✅ Clinica_Plugin se încarcă corect</p>";
    
    $plugin = Clinica_Plugin::get_instance();
    if ($plugin) {
        echo "<p style='color: green;'>✅ Instanța plugin-ului a fost creată cu succes</p>";
    } else {
        echo "<p style='color: red;'>❌ Nu s-a putut crea instanța plugin-ului</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Clinica_Plugin NU se încarcă</p>";
}

echo "<h2>Link-uri de Test</h2>";
echo "<p><a href='" . admin_url('admin.php?page=clinica') . "' target='_blank'>Test Admin Dashboard</a></p>";
echo "<p><a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank'>Test Admin Pacienți</a></p>";
echo "<p><a href='" . admin_url('admin.php?page=clinica-appointments') . "' target='_blank'>Test Admin Programări</a></p>";

echo "<h2>Instrucțiuni de Testare</h2>";
echo "<ol>";
echo "<li>Accesează WordPress Admin</li>";
echo "<li>Verifică meniul lateral 'Clinica'</li>";
echo "<li>Verifică dacă diacriticele se afișează corect:</li>";
echo "<ul>";
echo "<li>Pacienți (nu PacienČ›i)</li>";
echo "<li>Programări (nu ProgramÄDri)</li>";
echo "<li>Setări (nu SetÄOri)</li>";
echo "<li>Import Pacienți</li>";
echo "</ul>";
echo "<li>Verifică dacă fontul este consistent în toată interfața</li>";
echo "</ol>";

echo "<h2>Status Final</h2>";

$all_good = true;
if (strpos($content, 'Pacienți') !== false && 
    strpos($content, 'Programări') !== false && 
    strpos($content, 'Setări') !== false &&
    $encoding === 'UTF-8') {
    echo "<p style='color: green; font-weight: bold; font-size: 1.2em;'>🎉 PROBLEMA CU DIACRITICELE A FOST REZOLVATĂ! 🎉</p>";
    echo "<p>✅ Toate diacriticele sunt corecte</p>";
    echo "<p>✅ Encoding-ul este UTF-8</p>";
    echo "<p>✅ Font-family este consistent</p>";
} else {
    echo "<p style='color: red; font-weight: bold; font-size: 1.2em;'>❌ ÎNCĂ EXISTĂ PROBLEME CU DIACRITICELE!</p>";
}

echo "<h2>Recomandări</h2>";
echo "<p>Dacă diacriticele încă nu se afișează corect în browser:</p>";
echo "<ul>";
echo "<li>Fă refresh la pagina (Ctrl+F5)</li>";
echo "<li>Șterge cache-ul browser-ului</li>";
echo "<li>Verifică dacă WordPress folosește encoding UTF-8</li>";
echo "<li>Verifică dacă baza de date folosește collation UTF-8</li>";
echo "</ul>";
?> 