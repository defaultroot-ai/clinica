<?php
/**
 * Test pentru query-ul pacienților
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    echo '<h2>Test Query Pacienți</h2>';
    echo '<p>Trebuie să fiți administrator pentru a rula acest test.</p>';
    exit;
}

global $wpdb;

echo '<h2>Test Query Pacienți</h2>';
echo '<p>Testarea query-ului pentru lista de pacienți</p>';

// Test query simplu
echo '<h3>1. Test query simplu</h3>';
$table_name = $wpdb->prefix . 'clinica_patients';
$simple_query = "SELECT p.*, u.user_email, u.display_name,
                 um1.meta_value as first_name, um2.meta_value as last_name
                 FROM $table_name p 
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                 LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
                 LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
                 ORDER BY p.created_at DESC 
                 LIMIT 5";

$patients = $wpdb->get_results($simple_query);

if ($wpdb->last_error) {
    echo '<div style="color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff9999; margin: 10px 0;">';
    echo '<strong>Eroare query:</strong> ' . $wpdb->last_error;
    echo '</div>';
} else {
    echo '<div style="color: green; background: #e6ffe6; padding: 10px; border: 1px solid #99ff99; margin: 10px 0;">';
    echo '<strong>Query executat cu succes!</strong> Găsiți ' . count($patients) . ' pacienți.';
    echo '</div>';
}

// Afișează rezultatele
if (!empty($patients)) {
    echo '<h3>2. Rezultate (primele 5 pacienți)</h3>';
    echo '<table border="1" style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
    echo '<tr style="background: #f0f0f0;">';
    echo '<th style="padding: 8px;">ID</th>';
    echo '<th style="padding: 8px;">User ID</th>';
    echo '<th style="padding: 8px;">CNP</th>';
    echo '<th style="padding: 8px;">First Name</th>';
    echo '<th style="padding: 8px;">Last Name</th>';
    echo '<th style="padding: 8px;">Display Name</th>';
    echo '<th style="padding: 8px;">Email</th>';
    echo '<th style="padding: 8px;">Phone</th>';
    echo '</tr>';
    
    foreach ($patients as $patient) {
        $full_name = trim($patient->first_name . ' ' . $patient->last_name);
        $display_name = !empty($full_name) ? $full_name : $patient->display_name;
        
        echo '<tr>';
        echo '<td style="padding: 8px;">' . esc_html($patient->id) . '</td>';
        echo '<td style="padding: 8px;">' . esc_html($patient->user_id) . '</td>';
        echo '<td style="padding: 8px;">' . esc_html($patient->cnp) . '</td>';
        echo '<td style="padding: 8px;">' . esc_html($patient->first_name) . '</td>';
        echo '<td style="padding: 8px;">' . esc_html($patient->last_name) . '</td>';
        echo '<td style="padding: 8px;"><strong>' . esc_html($display_name) . '</strong></td>';
        echo '<td style="padding: 8px;">' . esc_html($patient->user_email) . '</td>';
        echo '<td style="padding: 8px;">' . esc_html($patient->phone_primary) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<div style="color: orange; background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; margin: 10px 0;">';
    echo '<strong>Nu s-au găsit pacienți în baza de date.</strong>';
    echo '</div>';
}

// Test query cu căutare
echo '<h3>3. Test query cu căutare</h3>';
$search = 'test'; // Caută după "test"
$where_conditions = array();
$where_values = array();

if (!empty($search)) {
    $where_conditions[] = "(p.cnp LIKE %s OR um1.meta_value LIKE %s OR um2.meta_value LIKE %s OR u.user_email LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $where_values[] = $search_term;
    $where_values[] = $search_term;
    $where_values[] = $search_term;
    $where_values[] = $search_term;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$search_query = "SELECT p.*, u.user_email, u.display_name,
                 um1.meta_value as first_name, um2.meta_value as last_name
                 FROM $table_name p 
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                 LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
                 LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
                 $where_clause 
                 ORDER BY p.created_at DESC 
                 LIMIT 5";

if (!empty($where_values)) {
    $search_query = $wpdb->prepare($search_query, $where_values);
}

$search_patients = $wpdb->get_results($search_query);

echo '<p><strong>Căutare pentru:</strong> "' . esc_html($search) . '"</p>';
echo '<p><strong>Rezultate găsite:</strong> ' . count($search_patients) . ' pacienți</p>';

if ($wpdb->last_error) {
    echo '<div style="color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff9999; margin: 10px 0;">';
    echo '<strong>Eroare query căutare:</strong> ' . $wpdb->last_error;
    echo '</div>';
} else {
    echo '<div style="color: green; background: #e6ffe6; padding: 10px; border: 1px solid #99ff99; margin: 10px 0;">';
    echo '<strong>Query căutare executat cu succes!</strong>';
    echo '</div>';
}

// Test numărul total
echo '<h3>4. Test numărul total de pacienți</h3>';
$total_query = "SELECT COUNT(*) FROM $table_name p 
               LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
               LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
               LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'";

$total = $wpdb->get_var($total_query);

if ($wpdb->last_error) {
    echo '<div style="color: red; background: #ffe6e6; padding: 10px; border: 1px solid #ff9999; margin: 10px 0;">';
    echo '<strong>Eroare query total:</strong> ' . $wpdb->last_error;
    echo '</div>';
} else {
    echo '<div style="color: green; background: #e6ffe6; padding: 10px; border: 1px solid #99ff99; margin: 10px 0;">';
    echo '<strong>Total pacienți în baza de date:</strong> ' . $total;
    echo '</div>';
}

// Informații despre tabele
echo '<h3>5. Informații despre tabele</h3>';
echo '<table border="1" style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
echo '<tr style="background: #f0f0f0;">';
echo '<th style="padding: 8px;">Tabelă</th>';
echo '<th style="padding: 8px;">Număr înregistrări</th>';
echo '</tr>';

$tables = array(
    'clinica_patients' => $wpdb->prefix . 'clinica_patients',
    'wp_users' => $wpdb->users,
    'wp_usermeta' => $wpdb->usermeta
);

foreach ($tables as $table_name => $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo '<tr>';
    echo '<td style="padding: 8px;">' . esc_html($table_name) . '</td>';
    echo '<td style="padding: 8px;">' . $count . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '<h3>6. Structura tabelă clinica_patients</h3>';
$columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}clinica_patients");
if ($columns) {
    echo '<table border="1" style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
    echo '<tr style="background: #f0f0f0;">';
    echo '<th style="padding: 8px;">Coloană</th>';
    echo '<th style="padding: 8px;">Tip</th>';
    echo '<th style="padding: 8px;">Null</th>';
    echo '<th style="padding: 8px;">Key</th>';
    echo '<th style="padding: 8px;">Default</th>';
    echo '</tr>';
    
    foreach ($columns as $column) {
        echo '<tr>';
        echo '<td style="padding: 8px;">' . esc_html($column->Field) . '</td>';
        echo '<td style="padding: 8px;">' . esc_html($column->Type) . '</td>';
        echo '<td style="padding: 8px;">' . esc_html($column->Null) . '</td>';
        echo '<td style="padding: 8px;">' . esc_html($column->Key) . '</td>';
        echo '<td style="padding: 8px;">' . esc_html($column->Default) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '<hr>';
echo '<p><a href="../" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">← Înapoi la plugin</a></p>';
?> 