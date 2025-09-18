<?php
$root = dirname(__DIR__, 5); // pleacă din clinica/tools/check până la plm
$wp_load = $root . '/wp-load.php';
if (!file_exists($wp_load)) {
    // fallback: încearcă două niveluri în sus (în caz de structură atipică)
    $alt = dirname(__DIR__, 6) . '/wp-load.php';
    if (file_exists($alt)) {
        $wp_load = $alt;
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Nu pot găsi wp-load.php. Verifică ruta: \n - " . $wp_load . "\n - " . $alt . "\n";
        exit;
    }
}
require_once($wp_load);

if (!is_user_logged_in() || !current_user_can('list_users')) {
    wp_die('Acces restricționat. Trebuie să fii autentificat cu drepturi de administrare.');
}

$doctors = get_users(array('role__in' => array('clinica_doctor', 'clinica_manager')));
$count = count($doctors);

?><!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8" />
<title>Verificare Medici - Clinica</title>
<style>
body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; padding: 20px; }
.container { max-width: 900px; margin: 0 auto; }
.hint { color: #555; margin-bottom: 16px; }
.table { border-collapse: collapse; width: 100%; background: #fff; }
.table th, .table td { border: 1px solid #e1e5e9; padding: 8px; text-align: left; font-size: 14px; }
.table th { background: #f8f9fa; }
.badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; }
.badge-ok { background: #d4edda; color: #155724; }
.badge-warn { background: #fff3cd; color: #856404; }
.actions a { margin-right: 10px; }
</style>
</head>
<body>
<div class="container">
    <h1>Verificare Medici</h1>
    <p class="hint">Acest utilitar verifică dacă există utilizatori cu rol <strong>Doctor</strong> sau <strong>Manager</strong>.</p>
    <p>
        Status: <?php if ($count > 0): ?><span class="badge badge-ok">Găsiți <?php echo (int)$count; ?> medici/manageri</span><?php else: ?><span class="badge badge-warn">Nu există medici în sistem</span><?php endif; ?>
    </p>

    <?php if ($count > 0): ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nume</th>
                <th>Email</th>
                <th>Roluri</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($doctors as $u): ?>
            <tr>
                <td><?php echo (int)$u->ID; ?></td>
                <td><?php echo esc_html($u->display_name); ?></td>
                <td><?php echo esc_html($u->user_email); ?></td>
                <td><?php echo esc_html(implode(', ', $u->roles)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Nu există niciun utilizator cu rol <strong>clinica_doctor</strong>. Creează cel puțin un medic pentru a permite programări pe doctor.</p>
    <?php endif; ?>

    <p class="actions">
        <a class="button" href="<?php echo esc_url(admin_url('user-new.php')); ?>">Adaugă utilizator</a>
        <a class="button" href="<?php echo esc_url(admin_url('users.php')); ?>">Vezi utilizatori</a>
    </p>
</div>
</body>
</html>
