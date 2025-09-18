<?php
/**
 * Script pentru repararea problemei cu working_hours în settings.php
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🔧 Repararea problemei cu working_hours în settings.php...\n\n";

// Calea către fișierul settings.php
$settings_file = __DIR__ . '/../../admin/views/settings.php';

if (!file_exists($settings_file)) {
    echo "❌ Fișierul settings.php nu a fost găsit!\n";
    exit(1);
}

// Citește conținutul fișierului
$content = file_get_contents($settings_file);

// Verifică dacă linia problematică există
if (strpos($content, '$working_hours = isset($schedule_settings[\'working_hours\'][\'value\'])') !== false) {
    echo "📝 Găsită linia problematică...\n";
    
    // Înlocuiește linia problematică
    $old_line = '$working_hours = isset($schedule_settings[\'working_hours\'][\'value\']) ? $schedule_settings[\'working_hours\'][\'value\'] : \'\';';
    $new_line = '$working_hours = isset($schedule_settings[\'working_hours\']) ? $schedule_settings[\'working_hours\'] : array();';
    
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

// Verifică dacă există și alte probleme similare
$problematic_patterns = [
    'working_hours[\'value\']',
    'schedule_settings[\'working_hours\'][\'value\']'
];

foreach ($problematic_patterns as $pattern) {
    if (strpos($content, $pattern) !== false) {
        echo "⚠️ Avertisment: Găsit pattern problematic: $pattern\n";
    }
}

echo "\n🎯 Repararea completă! Verifică pagina de setări.\n";
?> 