<?php
/**
 * Script pentru repararea calculului duratei în settings.php
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🔧 Repararea calculului duratei în settings.php...\n\n";

// Calea către fișierul settings.php
$settings_file = __DIR__ . '/../../admin/views/settings.php';

if (!file_exists($settings_file)) {
    echo "❌ Fișierul settings.php nu a fost găsit!\n";
    exit(1);
}

// Citește conținutul fișierului
$content = file_get_contents($settings_file);

// Repară calculul duratei - linia 275
echo "📝 Repararea calculului duratei...\n";

// Găsește și repară linia cu calculul duratei
$old_pattern = '/\$start_time = !empty\(\$day_hours\[\'start\'\]\) \? strtotime\(\$day_hours\[\'start\'\]\) : 0;\s*\$end_time = !empty\(\$day_hours\[\'end\'\]\) \? strtotime\(\$day_hours\[\'end\'\]\) : 0;\s*\$duration = \$day_hours\[\'active\'\] \? round\(\(\$end_time - \$start_time\) \/ 3600, 1\) \. \'h\' : \'-\';/';
$new_pattern = '$start_time = !empty($day_hours[\'start\']) ? strtotime($day_hours[\'start\']) : 0;
                                        $end_time = !empty($day_hours[\'end\']) ? strtotime($day_hours[\'end\']) : 0;
                                        $duration = ($day_hours[\'active\'] && !empty($day_hours[\'start\']) && !empty($day_hours[\'end\']) && $end_time > $start_time) ? round(($end_time - $start_time) / 3600, 1) . \'h\' : \'-\';';

$content = preg_replace($old_pattern, $new_pattern, $content);

// Verifică dacă s-a făcut schimbarea
if (strpos($content, '$duration = ($day_hours[\'active\'] && !empty($day_hours[\'start\']) && !empty($day_hours[\'end\']) && $end_time > $start_time)') !== false) {
    echo "✅ Calculul duratei reparat cu succes!\n";
} else {
    echo "⚠️ Calculul duratei nu s-a modificat. Verifică manual...\n";
}

// Repară și input-urile pentru a se încărca corect
echo "📝 Repararea input-urilor pentru ore...\n";

// Găsește și repară input-urile pentru start time
$old_start_pattern = '/<input type="time" name="working_hours\[<?php echo \$day_key; ?>\]\[start\]" value="<?php echo esc_attr\(\$day_hours\[\'start\'\]\); ?>" <?php echo !\$day_hours\[\'active\'\] \? \'disabled\' : \'\'; ?>>/';
$new_start_pattern = '<input type="time" name="working_hours[<?php echo $day_key; ?>][start]" value="<?php echo esc_attr(!empty($day_hours[\'start\']) ? $day_hours[\'start\'] : \'\'); ?>" <?php echo !$day_hours[\'active\'] ? \'disabled\' : \'\'; ?>>';

$content = preg_replace($old_start_pattern, $new_start_pattern, $content);

// Găsește și repară input-urile pentru end time
$old_end_pattern = '/<input type="time" name="working_hours\[<?php echo \$day_key; ?>\]\[end\]" value="<?php echo esc_attr\(\$day_hours\[\'end\'\]\); ?>" <?php echo !\$day_hours\[\'active\'\] \? \'disabled\' : \'\'; ?>>/';
$new_end_pattern = '<input type="time" name="working_hours[<?php echo $day_key; ?>][end]" value="<?php echo esc_attr(!empty($day_hours[\'end\']) ? $day_hours[\'end\'] : \'\'); ?>" <?php echo !$day_hours[\'active\'] ? \'disabled\' : \'\'; ?>>';

$content = preg_replace($old_end_pattern, $new_end_pattern, $content);

// Scrie conținutul înapoi
if (file_put_contents($settings_file, $content)) {
    echo "✅ Toate reparațiile au fost aplicate cu succes!\n";
} else {
    echo "❌ Eroare la scrierea fișierului!\n";
    exit(1);
}

// Testează calculul duratei
echo "\n🧪 Testarea calculului duratei...\n";

// Testează cu ore valide
$start_time = strtotime('08:00');
$end_time = strtotime('17:00');
$duration = ($end_time > $start_time) ? round(($end_time - $start_time) / 3600, 1) . 'h' : '-';
echo "  - Test 1 (08:00-17:00): $duration\n";

// Testează cu ore invalide
$start_time = 0;
$end_time = strtotime('17:00');
$duration = ($end_time > $start_time) ? round(($end_time - $start_time) / 3600, 1) . 'h' : '-';
echo "  - Test 2 (ora goală): $duration\n";

// Testează cu ore goale
$start_time = 0;
$end_time = 0;
$duration = ($end_time > $start_time) ? round(($end_time - $start_time) / 3600, 1) . 'h' : '-';
echo "  - Test 3 (ambele goale): $duration\n";

echo "\n🎯 Repararea completă! Verifică pagina de setări.\n";
?> 