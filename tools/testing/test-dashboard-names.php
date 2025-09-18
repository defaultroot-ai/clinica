<?php
/**
 * Test pentru numele pacienților în dashboard
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este logat
if (!is_user_logged_in()) {
    echo '<h2>Test Nume Dashboard</h2>';
    echo '<p>Trebuie să fiți autentificat pentru a testa dashboard-ul.</p>';
    echo '<p><a href="' . wp_login_url() . '">Autentificare</a></p>';
    exit;
}

$current_user = wp_get_current_user();

// Verifică dacă utilizatorul este pacient
if (!Clinica_Roles::has_clinica_role($current_user->ID) || 
    Clinica_Roles::get_user_role($current_user->ID) !== 'clinica_patient') {
    echo '<h2>Test Nume Dashboard</h2>';
    echo '<p>Acest test este destinat doar pacienților.</p>';
    echo '<p>Utilizatorul curent: ' . esc_html($current_user->user_login) . '</p>';
    echo '<p>Rol: ' . esc_html(Clinica_Roles::get_user_role($current_user->ID)) . '</p>';
    exit;
}

global $wpdb;

echo '<h2>Test Nume Dashboard - Pacient</h2>';
echo '<p>Testarea încărcării numelui pacientului în dashboard</p>';

// Test 1: Datele brute din baza de date
echo '<h3>1. Datele brute din baza de date</h3>';
$table_name = $wpdb->prefix . 'clinica_patients';
$raw_data = $wpdb->get_row($wpdb->prepare(
    "SELECT p.*, u.user_email, u.display_name,
     um1.meta_value as first_name, um2.meta_value as last_name
     FROM $table_name p 
     LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
     LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
     LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
     WHERE p.user_id = %d",
    $current_user->ID
));

if ($raw_data) {
    echo '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 10px 0;">';
    echo '<h4>Datele pacientului:</h4>';
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<tr><td style="padding: 5px; font-weight: bold;">User ID:</td><td style="padding: 5px;">' . esc_html($raw_data->user_id) . '</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">CNP:</td><td style="padding: 5px;">' . esc_html($raw_data->cnp) . '</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">First Name:</td><td style="padding: 5px;">' . esc_html($raw_data->first_name) . '</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Last Name:</td><td style="padding: 5px;">' . esc_html($raw_data->last_name) . '</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Display Name:</td><td style="padding: 5px;">' . esc_html($raw_data->display_name) . '</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Email:</td><td style="padding: 5px;">' . esc_html($raw_data->user_email) . '</td></tr>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<div style="color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff9999; margin: 10px 0;">';
    echo '<strong>Eroare:</strong> Nu s-au găsit date pentru pacientul cu ID: ' . $current_user->ID;
    echo '</div>';
}

// Test 2: Metoda get_patient_data din clasa dashboard
echo '<h3>2. Test metoda get_patient_data()</h3>';
$dashboard = new Clinica_Patient_Dashboard();
$patient_data = $dashboard->get_patient_data($current_user->ID);

if ($patient_data) {
    echo '<div style="background: #e6ffe6; padding: 15px; border-radius: 6px; margin: 10px 0;">';
    echo '<h4>Datele procesate de dashboard:</h4>';
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<tr><td style="padding: 5px; font-weight: bold;">User ID:</td><td style="padding: 5px;">' . esc_html($patient_data->user_id) . '</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">CNP:</td><td style="padding: 5px;">' . esc_html($patient_data->cnp) . '</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">First Name:</td><td style="padding: 5px;">' . esc_html($patient_data->first_name) . '</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Last Name:</td><td style="padding: 5px;">' . esc_html($patient_data->last_name) . '</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Display Name:</td><td style="padding: 5px;">' . esc_html($patient_data->display_name) . '</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Email:</td><td style="padding: 5px;">' . esc_html($patient_data->user_email) . '</td></tr>';
    echo '</table>';
    echo '</div>';
    
    // Test 3: Calcularea numelui complet
    echo '<h3>3. Calcularea numelui complet</h3>';
    $full_name = trim($patient_data->first_name . ' ' . $patient_data->last_name);
    $display_name = !empty($full_name) ? $full_name : $patient_data->display_name;
    
    echo '<div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin: 10px 0;">';
    echo '<h4>Rezultatul calculării:</h4>';
    echo '<p><strong>First Name:</strong> "' . esc_html($patient_data->first_name) . '"</p>';
    echo '<p><strong>Last Name:</strong> "' . esc_html($patient_data->last_name) . '"</p>';
    echo '<p><strong>Full Name (calculat):</strong> "' . esc_html($full_name) . '"</p>';
    echo '<p><strong>Display Name:</strong> "' . esc_html($patient_data->display_name) . '"</p>';
    echo '<p><strong>Nume final afișat:</strong> "' . esc_html($display_name) . '"</p>';
    echo '</div>';
    
    // Test 4: Avatar placeholder
    echo '<h3>4. Avatar placeholder</h3>';
    if (!empty($full_name)) {
        $avatar_text = strtoupper(substr($patient_data->first_name, 0, 1) . substr($patient_data->last_name, 0, 1));
    } else {
        $avatar_text = strtoupper(substr($patient_data->display_name, 0, 2));
    }
    
    echo '<div style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin: 10px 0;">';
    echo '<h4>Avatar placeholder:</h4>';
    echo '<p><strong>Text avatar:</strong> "' . esc_html($avatar_text) . '"</p>';
    echo '<div style="width: 60px; height: 60px; background: #0073aa; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; margin: 10px 0;">';
    echo esc_html($avatar_text);
    echo '</div>';
    echo '</div>';
    
} else {
    echo '<div style="color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff9999; margin: 10px 0;">';
    echo '<strong>Eroare:</strong> Metoda get_patient_data() nu a returnat date';
    echo '</div>';
}

// Test 5: Verificare usermeta
echo '<h3>5. Verificare usermeta</h3>';
$first_name_meta = get_user_meta($current_user->ID, 'first_name', true);
$last_name_meta = get_user_meta($current_user->ID, 'last_name', true);

echo '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 10px 0;">';
echo '<h4>User meta pentru nume:</h4>';
echo '<p><strong>first_name (usermeta):</strong> "' . esc_html($first_name_meta) . '"</p>';
echo '<p><strong>last_name (usermeta):</strong> "' . esc_html($last_name_meta) . '"</p>';
echo '</div>';

// Test 6: Render dashboard complet
echo '<h3>6. Render dashboard complet</h3>';
echo '<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 10px 0;">';
echo $dashboard->render_dashboard_shortcode(array());
echo '</div>';

echo '<hr>';
echo '<p><a href="../" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">← Înapoi la plugin</a></p>';
?> 