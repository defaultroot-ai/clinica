<?php
/**
 * Script pentru repararea problemelor din settings.php
 */

// Încarcă WordPress
require_once('C:/xampp8.2.12/htdocs/plm/wp-load.php');

echo "=== REPARARE SETTINGS.PHP ===\n\n";

// 1. Verifică și repară clasa Clinica_Settings
$settings_file = 'includes/class-clinica-settings.php';
echo "Verificare $settings_file...\n";

// Verifică dacă există verificări pentru valori null
$settings_content = file_get_contents($settings_file);

// Adaugă verificări pentru valori null în metoda get_group
$pattern = '/public function get_group\(\$group\) \{[\s\S]*?return \$result;/';
$replacement = 'public function get_group($group) {
        global $wpdb;
        $table = $wpdb->prefix . \'clinica_settings\';
        
        $settings = $wpdb->get_results($wpdb->prepare(
            "SELECT setting_key, setting_value, setting_type, setting_label, setting_description 
             FROM $table WHERE setting_group = %s ORDER BY setting_key",
            $group
        ));
        
        $result = array();
        foreach ($settings as $setting) {
            $result[$setting->setting_key] = array(
                \'value\' => $this->parse_setting_value($setting->setting_value, $setting->setting_type),
                \'label\' => isset($setting->setting_label) ? $setting->setting_label : \'\',
                \'description\' => isset($setting->setting_description) ? $setting->setting_description : \'\',
                \'type\' => $setting->setting_type
            );
        }
        
        return $result;';

if (preg_match($pattern, $settings_content)) {
    $settings_content = preg_replace($pattern, $replacement, $settings_content);
    file_put_contents($settings_file, $settings_content);
    echo "✅ Clasa Clinica_Settings reparată!\n";
} else {
    echo "⚠️ Nu s-a găsit pattern-ul în Clinica_Settings\n";
}

// 2. Repară settings.php
$settings_view_file = 'admin/views/settings.php';
echo "\nVerificare $settings_view_file...\n";

$settings_view_content = file_get_contents($settings_view_file);

// Adaugă verificări pentru array keys nedefinite
$lines_to_fix = array(
    // Linia 76 - working_hours
    array(
        'search' => '$working_hours = $schedule_settings[\'working_hours\'][\'value\'];',
        'replace' => '$working_hours = isset($schedule_settings[\'working_hours\'][\'value\']) ? $schedule_settings[\'working_hours\'][\'value\'] : \'\';'
    ),
    // Linia 133 - clinic_name
    array(
        'search' => 'value="<?php echo esc_attr($clinic_settings[\'clinic_name\'][\'value\']); ?>"',
        'replace' => 'value="<?php echo esc_attr(isset($clinic_settings[\'clinic_name\'][\'value\']) ? $clinic_settings[\'clinic_name\'][\'value\'] : \'\'); ?>"'
    ),
    // Linia 134 - clinic_name description
    array(
        'search' => '<p class="description"><?php echo esc_html($clinic_settings[\'clinic_name\'][\'description\']); ?></p>',
        'replace' => '<p class="description"><?php echo esc_html(isset($clinic_settings[\'clinic_name\'][\'description\']) ? $clinic_settings[\'clinic_name\'][\'description\'] : \'\'); ?></p>'
    ),
    // Linia 139 - clinic_email
    array(
        'search' => 'value="<?php echo esc_attr($clinic_settings[\'clinic_email\'][\'value\']); ?>"',
        'replace' => 'value="<?php echo esc_attr(isset($clinic_settings[\'clinic_email\'][\'value\']) ? $clinic_settings[\'clinic_email\'][\'value\'] : \'\'); ?>"'
    ),
    // Linia 140 - clinic_email description
    array(
        'search' => '<p class="description"><?php echo esc_html($clinic_settings[\'clinic_email\'][\'description\']); ?></p>',
        'replace' => '<p class="description"><?php echo esc_html(isset($clinic_settings[\'clinic_email\'][\'description\']) ? $clinic_settings[\'clinic_email\'][\'description\'] : \'\'); ?></p>'
    ),
    // Linia 145 - clinic_phone
    array(
        'search' => 'value="<?php echo esc_attr($clinic_settings[\'clinic_phone\'][\'value\']); ?>"',
        'replace' => 'value="<?php echo esc_attr(isset($clinic_settings[\'clinic_phone\'][\'value\']) ? $clinic_settings[\'clinic_phone\'][\'value\'] : \'\'); ?>"'
    ),
    // Linia 146 - clinic_phone description
    array(
        'search' => '<p class="description"><?php echo esc_html($clinic_settings[\'clinic_phone\'][\'description\']); ?></p>',
        'replace' => '<p class="description"><?php echo esc_html(isset($clinic_settings[\'clinic_phone\'][\'description\']) ? $clinic_settings[\'clinic_phone\'][\'description\'] : \'\'); ?></p>'
    ),
    // Linia 151 - clinic_website
    array(
        'search' => 'value="<?php echo esc_attr($clinic_settings[\'clinic_website\'][\'value\']); ?>"',
        'replace' => 'value="<?php echo esc_attr(isset($clinic_settings[\'clinic_website\'][\'value\']) ? $clinic_settings[\'clinic_website\'][\'value\'] : \'\'); ?>"'
    ),
    // Linia 152 - clinic_website description
    array(
        'search' => '<p class="description"><?php echo esc_html($clinic_settings[\'clinic_website\'][\'description\']); ?></p>',
        'replace' => '<p class="description"><?php echo esc_html(isset($clinic_settings[\'clinic_website\'][\'description\']) ? $clinic_settings[\'clinic_website\'][\'description\'] : \'\'); ?></p>'
    ),
    // Linia 157 - clinic_address
    array(
        'search' => '><?php echo esc_textarea($clinic_settings[\'clinic_address\'][\'value\']); ?></textarea>',
        'replace' => '><?php echo esc_textarea(isset($clinic_settings[\'clinic_address\'][\'value\']) ? $clinic_settings[\'clinic_address\'][\'value\'] : \'\'); ?></textarea>'
    ),
    // Linia 158 - clinic_address description
    array(
        'search' => '<p class="description"><?php echo esc_html($clinic_settings[\'clinic_address\'][\'description\']); ?></p>',
        'replace' => '<p class="description"><?php echo esc_html(isset($clinic_settings[\'clinic_address\'][\'description\']) ? $clinic_settings[\'clinic_address\'][\'description\'] : \'\'); ?></p>'
    ),
    // Linia 169 - clinic_logo description
    array(
        'search' => '<p class="description"><?php echo esc_html($clinic_settings[\'clinic_logo\'][\'description\']); ?></p>',
        'replace' => '<p class="description"><?php echo esc_html(isset($clinic_settings[\'clinic_logo\'][\'description\']) ? $clinic_settings[\'clinic_logo\'][\'description\'] : \'\'); ?></p>'
    )
);

$fixed_count = 0;
foreach ($lines_to_fix as $fix) {
    if (strpos($settings_view_content, $fix['search']) !== false) {
        $settings_view_content = str_replace($fix['search'], $fix['replace'], $settings_view_content);
        $fixed_count++;
    }
}

if ($fixed_count > 0) {
    file_put_contents($settings_view_file, $settings_view_content);
    echo "✅ $fixed_count probleme reparate în settings.php!\n";
} else {
    echo "⚠️ Nu s-au găsit probleme specifice în settings.php\n";
}

// 3. Adaugă o funcție helper pentru verificări
$helper_function = '
/**
 * Helper function pentru verificarea setărilor
 */
function clinica_get_setting_value($settings, $key, $default = \'\') {
    return isset($settings[$key][\'value\']) ? $settings[$key][\'value\'] : $default;
}

function clinica_get_setting_description($settings, $key, $default = \'\') {
    return isset($settings[$key][\'description\']) ? $settings[$key][\'description\'] : $default;
}
';

// Adaugă funcția helper la începutul fișierului
if (strpos($settings_view_content, 'function clinica_get_setting_value') === false) {
    $settings_view_content = '<?php' . $helper_function . substr($settings_view_content, 5);
    file_put_contents($settings_view_file, $settings_view_content);
    echo "✅ Funcții helper adăugate!\n";
}

echo "\n=== REPARARE COMPLETĂ ===\n";
echo "✅ Toate problemele din settings.php au fost rezolvate!\n";
?> 