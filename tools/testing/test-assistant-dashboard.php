<?php
/**
 * Test Script pentru Dashboard-ul Asistent/Recepționer
 * 
 * Acest script testează funcționalitatea dashboard-ului pentru asistenți și recepționeri
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    echo '<h2>Test Dashboard Asistent/Recepționer</h2>';
    echo '<p style="color: red;">Trebuie să fiți autentificat pentru a testa dashboard-ul.</p>';
    echo '<p><a href="' . wp_login_url() . '">Autentificare</a></p>';
    exit;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

echo '<h2>Test Dashboard Asistent/Recepționer</h2>';
echo '<h3>Informații Utilizator Curent</h3>';
echo '<ul>';
echo '<li><strong>ID:</strong> ' . $current_user->ID . '</li>';
echo '<li><strong>Username:</strong> ' . $current_user->user_login . '</li>';
echo '<li><strong>Email:</strong> ' . $current_user->user_email . '</li>';
echo '<li><strong>Roluri:</strong> ' . implode(', ', $user_roles) . '</li>';
echo '</ul>';

// Verifică dacă utilizatorul are rolul de asistent sau recepționer
if (!in_array('assistant', $user_roles) && !in_array('receptionist', $user_roles)) {
    echo '<h3>Test Rol Asistent/Recepționer</h3>';
    echo '<p style="color: orange;">Utilizatorul curent nu are rolul de asistent sau recepționer.</p>';
    echo '<p>Pentru a testa dashboard-ul, trebuie să adăugați rolul "assistant" sau "receptionist" utilizatorului.</p>';
    
    // Adaugă rolul de asistent temporar pentru test
    $user = new WP_User($current_user->ID);
    $user->add_role('assistant');
    
    echo '<p style="color: green;">Rolul de asistent a fost adăugat temporar pentru test.</p>';
    echo '<p><a href="' . $_SERVER['REQUEST_URI'] . '">Reîncarcă pagina</a></p>';
    exit;
}

echo '<h3>Test Dashboard Asistent/Recepționer</h3>';
echo '<p style="color: green;">Utilizatorul are rolul de asistent/recepționer. Dashboard-ul ar trebui să funcționeze.</p>';

// Testează shortcode-ul
echo '<h3>Test Shortcode Dashboard Asistent</h3>';
echo '<div style="border: 2px solid #fd7e14; padding: 20px; margin: 20px 0; background: #f8f9fa;">';
echo do_shortcode('[clinica_assistant_dashboard]');
echo '</div>';

// Testează AJAX handlers
echo '<h3>Test AJAX Handlers</h3>';
echo '<div style="border: 2px solid #28a745; padding: 20px; margin: 20px 0; background: #f8f9fa;">';

// Test pentru overview
echo '<h4>Test Overview</h4>';
$overview_url = admin_url('admin-ajax.php');
$overview_data = array(
    'action' => 'clinica_get_assistant_overview',
    'nonce' => wp_create_nonce('clinica_assistant_dashboard_nonce')
);

echo '<p><strong>URL:</strong> ' . $overview_url . '</p>';
echo '<p><strong>Action:</strong> ' . $overview_data['action'] . '</p>';
echo '<p><strong>Nonce:</strong> ' . $overview_data['nonce'] . '</p>';

// Test pentru appointments
echo '<h4>Test Appointments</h4>';
$appointments_data = array(
    'action' => 'clinica_get_assistant_appointments',
    'nonce' => wp_create_nonce('clinica_assistant_dashboard_nonce'),
    'date_filter' => 'today',
    'doctor_filter' => '',
    'status_filter' => ''
);

echo '<p><strong>Action:</strong> ' . $appointments_data['action'] . '</p>';
echo '<p><strong>Date Filter:</strong> ' . $appointments_data['date_filter'] . '</p>';

// Test pentru patients
echo '<h4>Test Patients</h4>';
$patients_data = array(
    'action' => 'clinica_get_assistant_patients',
    'nonce' => wp_create_nonce('clinica_assistant_dashboard_nonce'),
    'search' => '',
    'sort' => 'name'
);

echo '<p><strong>Action:</strong> ' . $patients_data['action'] . '</p>';
echo '<p><strong>Sort:</strong> ' . $patients_data['sort'] . '</p>';

// Test pentru create appointment
echo '<h4>Test Create Appointment</h4>';
$create_appointment_data = array(
    'action' => 'clinica_create_appointment',
    'nonce' => wp_create_nonce('clinica_assistant_dashboard_nonce'),
    'appointment_data' => array(
        'patient_id' => 1,
        'doctor_id' => 1,
        'date' => '2024-01-15',
        'time' => '09:00',
        'type' => 'consultatie',
        'notes' => 'Test programare'
    )
);

echo '<p><strong>Action:</strong> ' . $create_appointment_data['action'] . '</p>';

echo '</div>';

// Testează CSS și JS
echo '<h3>Test Assets</h3>';
echo '<div style="border: 2px solid #ffc107; padding: 20px; margin: 20px 0; background: #f8f9fa;">';

$css_file = plugin_dir_url(__FILE__) . 'assets/css/assistant-dashboard.css';
$js_file = plugin_dir_url(__FILE__) . 'assets/js/assistant-dashboard.js';

echo '<h4>CSS File</h4>';
echo '<p><strong>URL:</strong> <a href="' . $css_file . '" target="_blank">' . $css_file . '</a></p>';

$css_path = plugin_dir_path(__FILE__) . 'assets/css/assistant-dashboard.css';
if (file_exists($css_path)) {
    echo '<p style="color: green;">✓ Fișierul CSS există</p>';
    echo '<p><strong>Size:</strong> ' . filesize($css_path) . ' bytes</p>';
} else {
    echo '<p style="color: red;">✗ Fișierul CSS nu există</p>';
}

echo '<h4>JS File</h4>';
echo '<p><strong>URL:</strong> <a href="' . $js_file . '" target="_blank">' . $js_file . '</a></p>';

$js_path = plugin_dir_path(__FILE__) . 'assets/js/assistant-dashboard.js';
if (file_exists($js_path)) {
    echo '<p style="color: green;">✓ Fișierul JS există</p>';
    echo '<p><strong>Size:</strong> ' . filesize($js_path) . ' bytes</p>';
} else {
    echo '<p style="color: red;">✗ Fișierul JS nu există</p>';
}

echo '</div>';

// Testează datele asistentului
echo '<h3>Test Date Asistent</h3>';
echo '<div style="border: 2px solid #6f42c1; padding: 20px; margin: 20px 0; background: #f8f9fa;">';

$assistant_phone = get_user_meta($current_user->ID, 'phone', true);
$assistant_role = in_array('receptionist', $user_roles) ? 'Recepționer' : 'Asistent';

echo '<p><strong>Funcție:</strong> ' . $assistant_role . '</p>';
echo '<p><strong>Telefon:</strong> ' . ($assistant_phone ?: 'N/A') . '</p>';

if (!$assistant_phone) {
    echo '<p style="color: orange;">Pentru un test complet, adăugați metadatele asistentului:</p>';
    echo '<pre>';
    echo "update_user_meta({$current_user->ID}, 'phone', '0722123456');";
    echo '</pre>';
}

echo '</div>';

// Instrucțiuni de testare
echo '<h3>Instrucțiuni de Testare</h3>';
echo '<div style="border: 2px solid #17a2b8; padding: 20px; margin: 20px 0; background: #f8f9fa;">';

echo '<h4>1. Testare Shortcode</h4>';
echo '<p>Creați o pagină nouă și adăugați shortcode-ul: <code>[clinica_assistant_dashboard]</code></p>';

echo '<h4>2. Testare Funcționalități</h4>';
echo '<ul>';
echo '<li>Schimbarea între tab-uri (Prezentare Generală, Programări, Pacienți, Calendar)</li>';
echo '<li>Filtrarea programărilor după dată, doctor și status</li>';
echo '<li>Căutarea pacienților</li>';
echo '<li>Crearea de programări noi (butonul "Programare Nouă")</li>';
echo '<li>Crearea de pacienți noi (butonul "Pacient Nou")</li>';
echo '<li>Vizualizarea calendarului de programări</li>';
echo '</ul>';

echo '<h4>3. Testare Modale</h4>';
echo '<ul>';
echo '<li>Deschiderea modalului pentru crearea programărilor</li>';
echo '<li>Deschiderea modalului pentru crearea pacienților</li>';
echo '<li>Validarea formularelor</li>';
echo '<li>Închiderea modalelor cu X sau Escape</li>';
echo '</ul>';

echo '<h4>4. Testare AJAX</h4>';
echo '<p>Deschideți Developer Tools (F12) și verificați tab-ul Network pentru a vedea cererile AJAX.</p>';

echo '<h4>5. Testare Responsive</h4>';
echo '<p>Testați dashboard-ul pe diferite dimensiuni de ecran.</p>';

echo '</div>';

// Funcționalități specifice asistenților
echo '<h3>Funcționalități Specifice Asistenților</h3>';
echo '<div style="border: 2px solid #fd7e14; padding: 20px; margin: 20px 0; background: #f8f9fa;">';

echo '<h4>1. Gestionare Programări</h4>';
echo '<ul>';
echo '<li>Vizualizarea tuturor programărilor (nu doar ale doctorului)</li>';
echo '<li>Filtrarea după doctor</li>';
echo '<li>Crearea de programări noi</li>';
echo '<li>Actualizarea statusului programărilor</li>';
echo '<li>Ștergerea programărilor</li>';
echo '</ul>';

echo '<h4>2. Gestionare Pacienți</h4>';
echo '<ul>';
echo '<li>Vizualizarea tuturor pacienților</li>';
echo '<li>Crearea de pacienți noi</li>';
echo '<li>Autocompletarea datelor din CNP</li>';
echo '<li>Căutarea și sortarea pacienților</li>';
echo '</ul>';

echo '<h4>3. Calendar de Programări</h4>';
echo '<ul>';
echo '<li>Vizualizarea programărilor în format calendar</li>';
echo '<li>Navigarea între luni</li>';
echo '<li>Indicatori vizuali pentru zilele cu programări</li>';
echo '</ul>';

echo '<h4>4. Statistici Generale</h4>';
echo '<ul>';
echo '<li>Programări zilnice (total, confirmate, în așteptare)</li>';
echo '<li>Pacienți noi (astăzi, săptămâna aceasta, luna aceasta)</li>';
echo '<li>Doctori activi</li>';
echo '<li>Activități recente</li>';
echo '</ul>';

echo '</div>';

// Link-uri utile
echo '<h3>Link-uri Utile</h3>';
echo '<div style="border: 2px solid #6c757d; padding: 20px; margin: 20px 0; background: #f8f9fa;">';
echo '<ul>';
echo '<li><a href="' . admin_url('admin.php?page=clinica-dashboard') . '">Dashboard Admin</a></li>';
echo '<li><a href="' . admin_url('admin.php?page=clinica-patients') . '">Gestionare Pacienți</a></li>';
echo '<li><a href="' . admin_url('admin.php?page=clinica-appointments') . '">Gestionare Programări</a></li>';
echo '<li><a href="' . home_url('clinica-patient-dashboard') . '">Dashboard Pacient</a></li>';
echo '<li><a href="' . home_url('clinica-doctor-dashboard') . '">Dashboard Doctor</a></li>';
echo '</ul>';
echo '</div>';

echo '<hr>';
echo '<p><em>Test creat pentru verificarea dashboard-ului asistenților/recepționerilor - Clinica Plugin</em></p>';
?> 