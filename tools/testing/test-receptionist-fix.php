<?php
/**
 * Test Rapid pentru Receptionist Dashboard Fix
 */

// Include WordPress
require_once('../../../wp-load.php');

echo '<h2>Test Receptionist Dashboard Fix</h2>';

// Test 1: Verifică dacă clasa există
if (class_exists('Clinica_Receptionist_Dashboard')) {
    echo '<p style="color: green;">✓ Clasa Clinica_Receptionist_Dashboard există</p>';
} else {
    echo '<p style="color: red;">✗ Clasa Clinica_Receptionist_Dashboard nu există</p>';
    exit;
}

// Test 2: Verifică dacă metoda statică există
if (method_exists('Clinica_Receptionist_Dashboard', 'get_dashboard_html')) {
    echo '<p style="color: green;">✓ Metoda statică get_dashboard_html există</p>';
} else {
    echo '<p style="color: red;">✗ Metoda statică get_dashboard_html nu există</p>';
    exit;
}

// Test 3: Verifică dacă metoda render_dashboard_shortcode există
$receptionist = new Clinica_Receptionist_Dashboard();
if (method_exists($receptionist, 'render_dashboard_shortcode')) {
    echo '<p style="color: green;">✓ Metoda render_dashboard_shortcode există</p>';
} else {
    echo '<p style="color: red;">✗ Metoda render_dashboard_shortcode nu există</p>';
    exit;
}

// Test 4: Testează shortcode-ul
echo '<h3>Test Shortcode</h3>';
$shortcode_output = do_shortcode('[clinica_receptionist_dashboard]');
if (!empty($shortcode_output)) {
    echo '<p style="color: green;">✓ Shortcode-ul funcționează</p>';
    echo '<p><strong>Lungimea output:</strong> ' . strlen($shortcode_output) . ' caractere</p>';
} else {
    echo '<p style="color: red;">✗ Shortcode-ul nu returnează output</p>';
}

// Test 5: Verifică dacă utilizatorul este autentificat
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo '<p><strong>Utilizator curent:</strong> ' . esc_html($current_user->display_name) . '</p>';
    echo '<p><strong>Roluri:</strong> ' . implode(', ', $current_user->roles) . '</p>';
    
    // Test 6: Verifică permisiunile
    if (in_array('clinica_receptionist', $current_user->roles) || in_array('administrator', $current_user->roles)) {
        echo '<p style="color: green;">✓ Utilizatorul are permisiuni pentru receptionist dashboard</p>';
    } else {
        echo '<p style="color: orange;">⚠ Utilizatorul nu are rolul de receptionist sau administrator</p>';
    }
} else {
    echo '<p style="color: orange;">⚠ Nu sunteți autentificat</p>';
}

// Test 7: Afișează preview-ul
echo '<h3>Preview Dashboard</h3>';
echo '<div style="border: 2px solid #ccc; padding: 20px; margin: 20px 0; background: #f9f9f9;">';
echo '<h4>Output Dashboard:</h4>';
echo '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: white;">';
echo $shortcode_output;
echo '</div>';
echo '</div>';

echo '<hr>';
echo '<p><em>Test finalizat la: ' . current_time('Y-m-d H:i:s') . '</em></p>';
?> 