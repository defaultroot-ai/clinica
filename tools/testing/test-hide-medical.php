<?php
/**
 * Test pentru ascunderea secțiunii medicale din dashboard-ul pacient
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Test Ascundere Secțiune Medicală</h1>";

// Verifică dacă clasa este încărcată
if (!class_exists('Clinica_Patient_Dashboard')) {
    echo "<p style='color: red;'>❌ Clasa Clinica_Patient_Dashboard nu este încărcată!</p>";
    exit;
}

echo "<p style='color: green;'>✅ Clasa Clinica_Patient_Dashboard este încărcată</p>";

$dashboard = new Clinica_Patient_Dashboard();

// Testează un pacient
echo "<h2>1. Test Pacient</h2>";
global $wpdb;
$table_name = $wpdb->prefix . 'clinica_patients';

$patient = $wpdb->get_row("
    SELECT p.user_id, p.cnp, u.user_login, u.user_email, u.display_name
    FROM $table_name p
    JOIN {$wpdb->users} u ON p.user_id = u.ID
    LIMIT 1
");

if (!$patient) {
    echo "<p style='color: red;'>❌ Nu există pacienți în baza de date!</p>";
    exit;
}

echo "<h3>Pacient: {$patient->display_name}</h3>";
echo "<p><strong>ID:</strong> {$patient->user_id}</p>";
echo "<p><strong>CNP:</strong> {$patient->cnp}</p>";

// Testează render-ul dashboard-ului
echo "<h2>2. Test Render Dashboard</h2>";

// Simulează un utilizator autentificat
wp_set_current_user($patient->user_id);

// Obține HTML-ul dashboard-ului
ob_start();
$dashboard->render_dashboard_shortcode(array());
$dashboard_html = ob_get_clean();

echo "<h3>Analiză HTML Dashboard:</h3>";

// Verifică dacă tab-ul medical există în navigație
if (strpos($dashboard_html, 'data-tab="medical"') !== false) {
    echo "<p style='color: red;'>❌ Tab-ul medical încă există în navigație!</p>";
} else {
    echo "<p style='color: green;'>✅ Tab-ul medical a fost eliminat din navigație</p>";
}

// Verifică dacă card-ul cu informații medicale există în overview
if (strpos($dashboard_html, 'Informații medicale') !== false && strpos($dashboard_html, 'dashboard-card') !== false) {
    echo "<p style='color: red;'>❌ Card-ul cu informații medicale încă există în overview!</p>";
} else {
    echo "<p style='color: green;'>✅ Card-ul cu informații medicale a fost ascuns din overview</p>";
}

// Verifică dacă tab-ul medical complet există
if (strpos($dashboard_html, 'id="medical"') !== false) {
    echo "<p style='color: red;'>❌ Tab-ul medical complet încă există!</p>";
} else {
    echo "<p style='color: green;'>✅ Tab-ul medical complet a fost ascuns</p>";
}

// Verifică tab-urile rămase
echo "<h3>Tab-uri rămase:</h3>";
$tabs_found = array();

if (strpos($dashboard_html, 'data-tab="overview"') !== false) {
    $tabs_found[] = 'overview';
    echo "<p style='color: green;'>✅ Tab Overview</p>";
}

if (strpos($dashboard_html, 'data-tab="appointments"') !== false) {
    $tabs_found[] = 'appointments';
    echo "<p style='color: green;'>✅ Tab Appointments</p>";
}

if (strpos($dashboard_html, 'data-tab="messages"') !== false) {
    $tabs_found[] = 'messages';
    echo "<p style='color: green;'>✅ Tab Messages</p>";
}

echo "<p><strong>Total tab-uri active:</strong> " . count($tabs_found) . "</p>";

// Verifică conținutul tab-urilor
echo "<h3>Conținut tab-uri:</h3>";

if (strpos($dashboard_html, 'id="overview"') !== false) {
    echo "<p style='color: green;'>✅ Conținut tab Overview</p>";
} else {
    echo "<p style='color: red;'>❌ Conținut tab Overview lipsește</p>";
}

if (strpos($dashboard_html, 'id="appointments"') !== false) {
    echo "<p style='color: green;'>✅ Conținut tab Appointments</p>";
} else {
    echo "<p style='color: red;'>❌ Conținut tab Appointments lipsește</p>";
}

if (strpos($dashboard_html, 'id="messages"') !== false) {
    echo "<p style='color: green;'>✅ Conținut tab Messages</p>";
} else {
    echo "<p style='color: red;'>❌ Conținut tab Messages lipsește</p>";
}

// Testează în browser
echo "<h2>3. Testare în Browser</h2>";
echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 5px;'>";
echo "<h3>🎯 Testează acum dashboard-ul în browser:</h3>";
echo "<ol>";
echo "<li><a href='" . home_url('/clinica-patient-dashboard/') . "' target='_blank' style='font-size: 18px; font-weight: bold;'>Accesează dashboard-ul pacient</a></li>";
echo "<li>Verificați că nu mai există tab-ul 'Informații medicale' în navigație</li>";
echo "<li>Verificați că nu mai există card-ul 'Informații medicale' în overview</li>";
echo "<li>Testați că tab-urile rămase funcționează corect</li>";
echo "<li>Verificați că nu apar erori JavaScript</li>";
echo "</ol>";
echo "</div>";

// Afișează un fragment din HTML pentru verificare
echo "<h2>4. Fragment HTML Dashboard</h2>";
echo "<p><strong>Primii 1000 de caractere din HTML:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
echo htmlspecialchars(substr($dashboard_html, 0, 1000));
echo "</pre>";

echo "<h2>5. Rezumat</h2>";
echo "<p>Dacă toate verificările sunt corecte:</p>";
echo "<ul>";
echo "<li>✅ Tab-ul 'Informații medicale' nu mai apare în navigație</li>";
echo "<li>✅ Card-ul 'Informații medicale' nu mai apare în overview</li>";
echo "<li>✅ Tab-ul medical complet nu mai există</li>";
echo "<li>✅ Tab-urile rămase (Overview, Programări, Mesaje) funcționează</li>";
echo "<li>✅ Dashboard-ul se încarcă fără erori</li>";
echo "</ul>";

echo "<p><strong>Notă:</strong> Secțiunea medicală a fost ascunsă temporar prin comentarii. Pentru a o reactiva în viitor, eliminați comentariile din cod.</p>";
?> 