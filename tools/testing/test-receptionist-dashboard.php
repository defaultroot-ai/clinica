<?php
/**
 * Test Script pentru Receptionist Dashboard
 * Verifică funcționalitatea dashboard-ului receptionist
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    echo '<h2>Test Receptionist Dashboard</h2>';
    echo '<p style="color: red;">Trebuie să fiți autentificat pentru a testa dashboard-ul receptionist.</p>';
    echo '<p><a href="' . wp_login_url() . '">Autentificare</a></p>';
    exit;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

echo '<h2>Test Receptionist Dashboard</h2>';
echo '<p><strong>Utilizator curent:</strong> ' . esc_html($current_user->display_name) . '</p>';
echo '<p><strong>Roluri:</strong> ' . implode(', ', $user_roles) . '</p>';

// Verifică dacă utilizatorul are permisiunea de receptionist sau administrator
if (!in_array('clinica_receptionist', $user_roles) && !in_array('administrator', $user_roles)) {
    echo '<p style="color: red;">Nu aveți permisiunea de a accesa dashboard-ul receptionist.</p>';
    echo '<p>Roluri necesare: clinica_receptionist sau administrator</p>';
    exit;
}

echo '<p style="color: green;">✓ Permisiuni OK - Puteți accesa dashboard-ul receptionist</p>';

// Testează clasa receptionist dashboard
echo '<h3>Test Clasă Receptionist Dashboard</h3>';

if (class_exists('Clinica_Receptionist_Dashboard')) {
    echo '<p style="color: green;">✓ Clasa Clinica_Receptionist_Dashboard există</p>';
    
    try {
        $receptionist_dashboard = new Clinica_Receptionist_Dashboard();
        echo '<p style="color: green;">✓ Instanța receptionist dashboard a fost creată cu succes</p>';
        
        // Testează metoda render_dashboard
        if (method_exists($receptionist_dashboard, 'render_dashboard')) {
            echo '<p style="color: green;">✓ Metoda render_dashboard există</p>';
            
            // Testează renderizarea
            $dashboard_html = $receptionist_dashboard->render_dashboard(array());
            if (!empty($dashboard_html)) {
                echo '<p style="color: green;">✓ Dashboard-ul se renderizează cu succes</p>';
                echo '<p><strong>Lungimea HTML:</strong> ' . strlen($dashboard_html) . ' caractere</p>';
            } else {
                echo '<p style="color: red;">✗ Dashboard-ul nu returnează HTML</p>';
            }
        } else {
            echo '<p style="color: red;">✗ Metoda render_dashboard nu există</p>';
        }
        
        // Testează metoda get_dashboard_html
        if (method_exists('Clinica_Receptionist_Dashboard', 'get_dashboard_html')) {
            echo '<p style="color: green;">✓ Metoda statică get_dashboard_html există</p>';
            
            $static_html = Clinica_Receptionist_Dashboard::get_dashboard_html($current_user->ID);
            if (!empty($static_html)) {
                echo '<p style="color: green;">✓ Metoda statică returnează HTML</p>';
            } else {
                echo '<p style="color: red;">✗ Metoda statică nu returnează HTML</p>';
            }
        } else {
            echo '<p style="color: red;">✗ Metoda statică get_dashboard_html nu există</p>';
        }
        
    } catch (Exception $e) {
        echo '<p style="color: red;">✗ Eroare la crearea instanței: ' . esc_html($e->getMessage()) . '</p>';
    }
} else {
    echo '<p style="color: red;">✗ Clasa Clinica_Receptionist_Dashboard nu există</p>';
}

// Testează shortcode-ul
echo '<h3>Test Shortcode</h3>';

$shortcode_output = do_shortcode('[clinica_receptionist_dashboard]');
if (!empty($shortcode_output)) {
    echo '<p style="color: green;">✓ Shortcode-ul funcționează</p>';
    echo '<p><strong>Lungimea output:</strong> ' . strlen($shortcode_output) . ' caractere</p>';
} else {
    echo '<p style="color: red;">✗ Shortcode-ul nu returnează output</p>';
}

// Testează fișierele CSS și JS
echo '<h3>Test Fișiere CSS și JS</h3>';

$css_file = CLINICA_PLUGIN_PATH . 'assets/css/receptionist-dashboard.css';
$js_file = CLINICA_PLUGIN_PATH . 'assets/js/receptionist-dashboard.js';

if (file_exists($css_file)) {
    echo '<p style="color: green;">✓ Fișierul CSS există: ' . basename($css_file) . '</p>';
    echo '<p><strong>Dimensiune:</strong> ' . filesize($css_file) . ' bytes</p>';
} else {
    echo '<p style="color: red;">✗ Fișierul CSS nu există: ' . basename($css_file) . '</p>';
}

if (file_exists($js_file)) {
    echo '<p style="color: green;">✓ Fișierul JS există: ' . basename($js_file) . '</p>';
    echo '<p><strong>Dimensiune:</strong> ' . filesize($js_file) . ' bytes</p>';
} else {
    echo '<p style="color: red;">✗ Fișierul JS nu există: ' . basename($js_file) . '</p>';
}

// Testează înregistrarea scripturilor
echo '<h3>Test Înregistrare Scripturi</h3>';

$enqueued_styles = wp_styles()->queue;
$enqueued_scripts = wp_scripts()->queue;

if (in_array('clinica-receptionist-dashboard', $enqueued_styles)) {
    echo '<p style="color: green;">✓ CSS-ul receptionist dashboard este înregistrat</p>';
} else {
    echo '<p style="color: orange;">⚠ CSS-ul receptionist dashboard nu este înregistrat (poate fi normal dacă nu este pe pagina corectă)</p>';
}

if (in_array('clinica-receptionist-dashboard', $enqueued_scripts)) {
    echo '<p style="color: green;">✓ JS-ul receptionist dashboard este înregistrat</p>';
} else {
    echo '<p style="color: orange;">⚠ JS-ul receptionist dashboard nu este înregistrat (poate fi normal dacă nu este pe pagina corectă)</p>';
}

// Testează pagina creată automat
echo '<h3>Test Pagină Creată Automat</h3>';

$receptionist_page = get_page_by_path('clinica-receptionist-dashboard');
if ($receptionist_page) {
    echo '<p style="color: green;">✓ Pagina receptionist dashboard există</p>';
    echo '<p><strong>ID:</strong> ' . $receptionist_page->ID . '</p>';
    echo '<p><strong>Status:</strong> ' . $receptionist_page->post_status . '</p>';
    echo '<p><strong>URL:</strong> <a href="' . get_permalink($receptionist_page->ID) . '" target="_blank">Vezi pagina</a></p>';
} else {
    echo '<p style="color: red;">✗ Pagina receptionist dashboard nu există</p>';
}

// Testează AJAX handlers
echo '<h3>Test AJAX Handlers</h3>';

$ajax_actions = array(
    'clinica_receptionist_overview',
    'clinica_receptionist_appointments',
    'clinica_receptionist_patients',
    'clinica_receptionist_calendar',
    'clinica_receptionist_reports'
);

foreach ($ajax_actions as $action) {
    if (has_action('wp_ajax_' . $action)) {
        echo '<p style="color: green;">✓ AJAX handler există: ' . $action . '</p>';
    } else {
        echo '<p style="color: orange;">⚠ AJAX handler nu există: ' . $action . '</p>';
    }
}

// Afișează preview-ul dashboard-ului
echo '<h3>Preview Dashboard Receptionist</h3>';
echo '<div style="border: 2px solid #ccc; padding: 20px; margin: 20px 0; background: #f9f9f9;">';
echo '<h4>Output Dashboard:</h4>';
echo '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: white;">';
echo $shortcode_output;
echo '</div>';
echo '</div>';

// Link-uri utile
echo '<h3>Link-uri Utile</h3>';
echo '<ul>';
echo '<li><a href="' . get_permalink($receptionist_page->ID) . '" target="_blank">Vezi pagina receptionist dashboard</a></li>';
echo '<li><a href="' . admin_url('admin.php?page=clinica-shortcodes') . '" target="_blank">Pagina shortcode-uri în admin</a></li>';
echo '<li><a href="' . admin_url('admin.php?page=clinica') . '" target="_blank">Dashboard admin Clinica</a></li>';
echo '</ul>';

echo '<h3>Informații Debug</h3>';
echo '<p><strong>Plugin URL:</strong> ' . CLINICA_PLUGIN_URL . '</p>';
echo '<p><strong>Plugin Path:</strong> ' . CLINICA_PLUGIN_PATH . '</p>';
echo '<p><strong>Versiune Plugin:</strong> ' . CLINICA_VERSION . '</p>';
echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';

echo '<hr>';
echo '<p><em>Test finalizat la: ' . current_time('Y-m-d H:i:s') . '</em></p>';
?> 