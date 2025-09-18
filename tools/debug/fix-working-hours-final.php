<?php
/**
 * Script pentru repararea finală a working_hours în settings.php
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🔧 Repararea finală a working_hours în settings.php...\n\n";

// Calea către fișierul settings.php
$settings_file = __DIR__ . '/../../admin/views/settings.php';

if (!file_exists($settings_file)) {
    echo "❌ Fișierul settings.php nu a fost găsit!\n";
    exit(1);
}

// Citește conținutul fișierului
$content = file_get_contents($settings_file);

// Verifică dacă linia problematică există
if (strpos($content, '$working_hours = isset($schedule_settings[\'working_hours\'])') !== false) {
    echo "📝 Găsită linia problematică...\n";
    
    // Înlocuiește linia problematică
    $old_line = '$working_hours = isset($schedule_settings[\'working_hours\']) ? $schedule_settings[\'working_hours\'] : array();';
    $new_line = '$working_hours = isset($schedule_settings[\'working_hours\'][\'value\']) ? $schedule_settings[\'working_hours\'][\'value\'] : array();';
    
    $content = str_replace($old_line, $new_line, $content);
    
    // Scrie conținutul înapoi
    if (file_put_contents($settings_file, $content)) {
        echo "✅ Linia a fost reparată cu succes!\n";
        echo "📝 Schimbare: '$old_line' -> '$new_line'\n";
    } else {
        echo "❌ Eroare la scrierea fișierului!\n";
        exit(1);
    }
} else {
    echo "ℹ️ Linia problematică nu a fost găsită. Verifică manual...\n";
}

echo "\n🎯 Repararea completă! Verifică pagina de setări.\n";
?> 