<?php
/**
 * Script pentru repararea setării working_hours în baza de date
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🔧 Repararea setării working_hours în baza de date...\n\n";

global $wpdb;

$table = $wpdb->prefix . 'clinica_settings';

// Verifică dacă working_hours există în grupul clinic
$existing = $wpdb->get_row("SELECT * FROM $table WHERE setting_key = 'working_hours'");

if ($existing) {
    echo "📝 Găsită setarea working_hours în grupul: {$existing->setting_group}\n";
    echo "📝 Tipul actual: {$existing->setting_type}\n";
    
    // Verifică dacă trebuie mutată în grupul schedule
    if ($existing->setting_group !== 'schedule') {
        echo "🔄 Mutarea din grupul '{$existing->setting_group}' în 'schedule'...\n";
        
        $result = $wpdb->update(
            $table,
            array('setting_group' => 'schedule'),
            array('setting_key' => 'working_hours'),
            array('%s'),
            array('%s')
        );
        
        if ($result !== false) {
            echo "✅ Grupul actualizat cu succes!\n";
        } else {
            echo "❌ Eroare la actualizarea grupului!\n";
        }
    }
    
    // Verifică dacă tipul trebuie schimbat în json
    if ($existing->setting_type !== 'json') {
        echo "🔄 Schimbarea tipului din '{$existing->setting_type}' în 'json'...\n";
        
        // Creează valoarea JSON corectă
        $default_working_hours = array(
            'monday' => array('start' => '', 'end' => '', 'active' => false),
            'tuesday' => array('start' => '', 'end' => '', 'active' => false),
            'wednesday' => array('start' => '', 'end' => '', 'active' => false),
            'thursday' => array('start' => '', 'end' => '', 'active' => false),
            'friday' => array('start' => '', 'end' => '', 'active' => false),
            'saturday' => array('start' => '', 'end' => '', 'active' => false),
            'sunday' => array('start' => '', 'end' => '', 'active' => false)
        );
        
        $json_value = json_encode($default_working_hours);
        
        $result = $wpdb->update(
            $table,
            array(
                'setting_type' => 'json',
                'setting_value' => $json_value,
                'setting_label' => 'Program funcționare',
                'setting_description' => 'Programul de funcționare al clinicii'
            ),
            array('setting_key' => 'working_hours'),
            array('%s', '%s', '%s', '%s'),
            array('%s')
        );
        
        if ($result !== false) {
            echo "✅ Tipul și valoarea actualizate cu succes!\n";
        } else {
            echo "❌ Eroare la actualizarea tipului!\n";
        }
    }
    
} else {
    echo "❌ Setarea working_hours nu există în baza de date!\n";
    echo "🔄 Creez setarea working_hours...\n";
    
    $default_working_hours = array(
        'monday' => array('start' => '', 'end' => '', 'active' => false),
        'tuesday' => array('start' => '', 'end' => '', 'active' => false),
        'wednesday' => array('start' => '', 'end' => '', 'active' => false),
        'thursday' => array('start' => '', 'end' => '', 'active' => false),
        'friday' => array('start' => '', 'end' => '', 'active' => false),
        'saturday' => array('start' => '', 'end' => '', 'active' => false),
        'sunday' => array('start' => '', 'end' => '', 'active' => false)
    );
    
    $result = $wpdb->insert(
        $table,
        array(
            'setting_key' => 'working_hours',
            'setting_value' => json_encode($default_working_hours),
            'setting_type' => 'json',
            'setting_group' => 'schedule',
            'setting_label' => 'Program funcționare',
            'setting_description' => 'Programul de funcționare al clinicii',
            'is_public' => 0
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%d')
    );
    
    if ($result !== false) {
        echo "✅ Setarea working_hours creată cu succes!\n";
    } else {
        echo "❌ Eroare la crearea setării!\n";
    }
}

// Verifică rezultatul
$updated = $wpdb->get_row("SELECT * FROM $table WHERE setting_key = 'working_hours'");
if ($updated) {
    echo "\n✅ Setarea working_hours actualizată:\n";
    echo "  - Grup: {$updated->setting_group}\n";
    echo "  - Tip: {$updated->setting_type}\n";
    echo "  - Valoare: " . substr($updated->setting_value, 0, 100) . "...\n";
    
    if ($updated->setting_type === 'json') {
        $decoded = json_decode($updated->setting_value, true);
        if (is_array($decoded)) {
            echo "  - Decodat: array cu " . count($decoded) . " elemente\n";
        } else {
            echo "  - Decodat: JSON invalid\n";
        }
    }
} else {
    echo "\n❌ Setarea working_hours nu a fost găsită după actualizare!\n";
}

echo "\n🎯 Repararea completă! Verifică pagina de setări.\n";
?> 