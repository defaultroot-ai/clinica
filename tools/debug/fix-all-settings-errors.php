<?php
/**
 * Script pentru repararea tuturor erorilor din settings.php
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🔧 Repararea tuturor erorilor din settings.php...\n\n";

// Calea către fișierul settings.php
$settings_file = __DIR__ . '/../../admin/views/settings.php';

if (!file_exists($settings_file)) {
    echo "❌ Fișierul settings.php nu a fost găsit!\n";
    exit(1);
}

// Citește conținutul fișierului
$content = file_get_contents($settings_file);

// 1. Repară problemele cu working_hours (liniile 276-281)
echo "📝 Repararea problemelor cu working_hours...\n";

// Găsește și repară liniile cu $day_hours = $working_hours[$day_key];
$pattern = '/\$day_hours = \$working_hours\[\$day_key\];/';
$replacement = '$day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);';
$content = preg_replace($pattern, $replacement, $content);

// 2. Repară problemele cu setările care nu există în grupul performance
echo "📝 Repararea problemelor cu setările performance...\n";

// items_per_page
$pattern = '/\$performance_settings\[\'items_per_page\'\]\[\'value\'\]/';
$replacement = 'isset($performance_settings[\'items_per_page\'][\'value\']) ? $performance_settings[\'items_per_page\'][\'value\'] : \'20\'';
$content = preg_replace($pattern, $replacement, $content);

$pattern = '/\$performance_settings\[\'items_per_page\'\]\[\'description\'\]/';
$replacement = 'isset($performance_settings[\'items_per_page\'][\'description\']) ? $performance_settings[\'items_per_page\'][\'description\'] : \'Numărul de elemente afișate pe pagină\'';
$content = preg_replace($pattern, $replacement, $content);

// cache_enabled
$pattern = '/\$performance_settings\[\'cache_enabled\'\]\[\'value\'\]/';
$replacement = 'isset($performance_settings[\'cache_enabled\'][\'value\']) ? $performance_settings[\'cache_enabled\'][\'value\'] : false';
$content = preg_replace($pattern, $replacement, $content);

$pattern = '/\$performance_settings\[\'cache_enabled\'\]\[\'description\'\]/';
$replacement = 'isset($performance_settings[\'cache_enabled\'][\'description\']) ? $performance_settings[\'cache_enabled\'][\'description\'] : \'Activează cache-ul pentru performanță îmbunătățită\'';
$content = preg_replace($pattern, $replacement, $content);

// auto_refresh
$pattern = '/\$performance_settings\[\'auto_refresh\'\]\[\'value\'\]/';
$replacement = 'isset($performance_settings[\'auto_refresh\'][\'value\']) ? $performance_settings[\'auto_refresh\'][\'value\'] : \'0\'';
$content = preg_replace($pattern, $replacement, $content);

$pattern = '/\$performance_settings\[\'auto_refresh\'\]\[\'description\'\]/';
$replacement = 'isset($performance_settings[\'auto_refresh\'][\'description\']) ? $performance_settings[\'auto_refresh\'][\'description\'] : \'Intervalul de auto-refresh în secunde (0 = dezactivat)\'';
$content = preg_replace($pattern, $replacement, $content);

// 3. Adaugă verificări pentru strtotime() pentru a evita deprecation warnings
echo "📝 Repararea problemelor cu strtotime()...\n";

// Găsește și repară liniile cu strtotime($day_hours['start']) și strtotime($day_hours['end'])
$pattern = '/\$start_time = strtotime\(\$day_hours\[\'start\'\]\);/';
$replacement = '$start_time = !empty($day_hours[\'start\']) ? strtotime($day_hours[\'start\']) : 0;';
$content = preg_replace($pattern, $replacement, $content);

$pattern = '/\$end_time = strtotime\(\$day_hours\[\'end\'\]\);/';
$replacement = '$end_time = !empty($day_hours[\'end\']) ? strtotime($day_hours[\'end\']) : 0;';
$content = preg_replace($pattern, $replacement, $content);

// Scrie conținutul înapoi
if (file_put_contents($settings_file, $content)) {
    echo "✅ Toate erorile au fost reparate cu succes!\n";
} else {
    echo "❌ Eroare la scrierea fișierului!\n";
    exit(1);
}

echo "\n🎯 Repararea completă! Verifică pagina de setări.\n";
?> 