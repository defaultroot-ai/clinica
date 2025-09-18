<?php
/**
 * Script pentru repararea bazei de date Clinica
 * Rulează acest script pentru a rezolva problemele cu cheile primare multiple
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script.');
}

echo '<h1>Reparare Baza de Date Clinica</h1>';

// Verifică dacă clasa există
if (!class_exists('Clinica_Database')) {
    echo '<p style="color: red;">Eroare: Clasa Clinica_Database nu a fost găsită. Asigurați-vă că plugin-ul este activat.</p>';
    exit;
}

try {
    echo '<p>Începe repararea bazei de date...</p>';
    
    // Forțează recrearea tabelelor
    Clinica_Database::force_recreate_tables();
    
    echo '<p style="color: green;">✓ Baza de date a fost reparată cu succes!</p>';
    echo '<p>Tabelele au fost recreate corect și foreign key-urile au fost adăugate.</p>';
    
    // Verifică dacă tabelele există
    if (Clinica_Database::tables_exist()) {
        echo '<p style="color: green;">✓ Toate tabelele există și sunt funcționale.</p>';
    } else {
        echo '<p style="color: red;">✗ Probleme cu tabelele. Verificați log-urile.</p>';
    }
    
} catch (Exception $e) {
    echo '<p style="color: red;">Eroare: ' . $e->getMessage() . '</p>';
}

echo '<p><a href="' . admin_url() . '">← Înapoi la Admin</a></p>';
?> 