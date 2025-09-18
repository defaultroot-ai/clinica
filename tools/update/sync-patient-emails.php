<?php
/**
 * Script de sincronizare email pacienti <-> utilizatori
 * - Rulat din admin: wp-admin/plugins.php?run=clinica-sync-emails (sau direct din CLI)
 * - Reguli:
 *   1) Dacă clinica_patients.email este NULL/invalid și wp_users.user_email e valid -> copiază din users în patients
 *   2) Dacă clinica_patients.email este valid și user_email e NULL/invalid -> copiază din patients în users
 *   3) Nu suprascrie email-uri valide existente (conservativ)
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script.');
}

global $wpdb;

$patients_table = $wpdb->prefix . 'clinica_patients';

echo '<h1>🔄 Sincronizare email pacienți</h1>';

// 1) Completează email în clinica_patients din wp_users
$rows = $wpdb->get_results("SELECT p.id, p.user_id, p.email as p_email, u.user_email as u_email FROM $patients_table p LEFT JOIN {$wpdb->users} u ON u.ID = p.user_id");

$updated_patients = 0;
$updated_users = 0;
$errors = [];

foreach ($rows as $r) {
    $p_email = trim((string)$r->p_email);
    $u_email = trim((string)$r->u_email);

    $p_valid = !empty($p_email) && is_email($p_email);
    $u_valid = !empty($u_email) && is_email($u_email);

    // Copiază din users -> patients
    if (!$p_valid && $u_valid) {
        $ok = $wpdb->update($patients_table, ['email' => $u_email, 'updated_at' => current_time('mysql')], ['id' => (int)$r->id]);
        if ($ok !== false) { $updated_patients++; } else { $errors[] = "Eroare update patients ID {$r->id}"; }
        continue;
    }

    // Copiază din patients -> users
    if ($p_valid && !$u_valid && (int)$r->user_id > 0) {
        $ok = $wpdb->update($wpdb->users, ['user_email' => $p_email], ['ID' => (int)$r->user_id]);
        if ($ok !== false) { $updated_users++; } else { $errors[] = "Eroare update users ID {$r->user_id}"; }
        continue;
    }
}

echo '<div style="background:#f8fbff;border:1px solid #e1effe;padding:12px;border-radius:6px;">';
echo '<p><strong>Pacienți actualizați (email din users -> patients):</strong> ' . (int)$updated_patients . '</p>';
echo '<p><strong>Utilizatori actualizați (email din patients -> users):</strong> ' . (int)$updated_users . '</p>';
echo '<p><strong>Erori:</strong> ' . count($errors) . '</p>';
if (!empty($errors)) { echo '<ul><li>' . implode('</li><li>', array_map('esc_html', $errors)) . '</li></ul>'; }
echo '<p><em>Rulat la: ' . esc_html(current_time('mysql')) . '</em></p>';
echo '</div>';

?>


