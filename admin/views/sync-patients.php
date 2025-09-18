<?php
/**
 * Pagina de sincronizare pacienți
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die(__('Nu aveți permisiunea de a accesa această pagină.', 'clinica'));
}

global $wpdb;

// Procesează sincronizarea dacă este trimis formularul
if (isset($_POST['sync_patients']) && wp_verify_nonce($_POST['_wpnonce'], 'sync_patients_nonce')) {
    $sync_results = array(
        'total_users' => 0,
        'existing_patients' => 0,
        'synced_count' => 0,
        'errors' => array()
    );
    
    // 1. Găsește doar utilizatorii cu rolul "subscriber" (pacienții)
    $users_query = "
        SELECT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered,
               um1.meta_value as first_name,
               um2.meta_value as last_name,
               um3.meta_value as phone_primary,
               um4.meta_value as phone_secondary
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'phone_primary'
        LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'phone_secondary'
        WHERE u.ID IN (
            SELECT user_id 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = '{$wpdb->prefix}capabilities' 
            AND meta_value LIKE '%subscriber%'
        )
        ORDER BY u.user_registered DESC
    ";
    
    $users = $wpdb->get_results($users_query);
    $sync_results['total_users'] = count($users);
    
    // 2. Verifică pacienții existenți în clinica_patients
    $clinica_table = $wpdb->prefix . 'clinica_patients';
    $existing_patients = $wpdb->get_results("SELECT user_id FROM $clinica_table");
    $existing_user_ids = array_column($existing_patients, 'user_id');
    $sync_results['existing_patients'] = count($existing_user_ids);
    
    // 3. Găsește subscriberii care trebuie sincronizați
    $users_to_sync = array();
    foreach ($users as $user) {
        if (!in_array($user->ID, $existing_user_ids)) {
            $users_to_sync[] = $user;
        }
    }
    
    // 4. Sincronizează utilizatorii
    foreach ($users_to_sync as $user) {
        // Parsează CNP-ul din username (presupunem că username-ul este CNP-ul)
        $cnp = $user->user_login;
        
        // Verifică dacă CNP-ul este valid (acceptă și CNP-uri străine)
        $cnp_length = strlen($cnp);
        $is_numeric = ctype_digit($cnp);
        
        // CNP-uri valide: românești (13 cifre) sau străine (12-14 cifre)
        $is_valid_cnp = ($is_numeric && $cnp_length >= 12 && $cnp_length <= 14);
        
        if (!$is_valid_cnp) {
            $sync_results['errors'][] = "CNP invalid pentru {$user->display_name}: {$cnp} (lungime: {$cnp_length}, numeric: " . ($is_numeric ? 'DA' : 'NU') . ")";
            continue;
        }
        
        // Determină tipul CNP-ului
        $cnp_type = ($cnp_length === 13) ? 'romanian' : 'foreign';
        
        // Pregătește datele pentru inserare
        $patient_data = array(
            'user_id' => $user->ID,
            'cnp' => $cnp,
            'cnp_type' => $cnp_type,
            'email' => (is_email($user->user_email) ? $user->user_email : null),
            'phone_primary' => $user->phone_primary ?? '',
            'phone_secondary' => $user->phone_secondary ?? '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // Inserează în tabela clinica_patients
        $result = $wpdb->insert($clinica_table, $patient_data);
        
        if ($result) {
            $sync_results['synced_count']++;
            // Asigură rolul clinica_patient
            $wp_user = get_userdata($user->ID);
            if ($wp_user && !in_array('clinica_patient', (array)$wp_user->roles, true)) {
                $wp_user->add_role('clinica_patient');
            }
        } else {
            $sync_results['errors'][] = "Eroare pentru {$user->display_name}: " . $wpdb->last_error;
        }
    }
}

// Sincronizare EMAILURI: users <-> patients (unificat în pagina aceasta)
if (isset($_POST['sync_emails']) && wp_verify_nonce($_POST['_wpnonce'], 'sync_emails_nonce')) {
    $email_sync_results = array(
        'patients_updated' => 0,
        'users_updated' => 0,
        'differences_fixed' => 0,
        'roles_updated' => 0,
        'errors' => array()
    );
    $clinica_table = $wpdb->prefix . 'clinica_patients';
    $rows = $wpdb->get_results("SELECT p.id, p.user_id, p.email AS p_email, u.user_email AS u_email FROM $clinica_table p LEFT JOIN {$wpdb->users} u ON u.ID = p.user_id");
    foreach ($rows as $r) {
        $p_email = trim((string)$r->p_email);
        $u_email = trim((string)$r->u_email);
        $p_valid = !empty($p_email) && is_email($p_email);
        $u_valid = !empty($u_email) && is_email($u_email);
        // Rule 1: patients.email <- users.user_email dacă lipsă/invalid în patients și valid în users
        if (!$p_valid && $u_valid) {
            $ok = $wpdb->update($clinica_table, array('email' => $u_email, 'updated_at' => current_time('mysql')), array('id' => (int)$r->id));
            if ($ok !== false) { $email_sync_results['patients_updated']++; } else { $email_sync_results['errors'][] = 'Eroare update patients ID '.$r->id; }
            continue;
        }
        // Rule 2: users.user_email <- patients.email dacă lipsă/invalid în users și valid în patients
        if ($p_valid && !$u_valid && (int)$r->user_id > 0) {
            $ok = $wpdb->update($wpdb->users, array('user_email' => $p_email), array('ID' => (int)$r->user_id));
            if ($ok !== false) { $email_sync_results['users_updated']++; } else { $email_sync_results['errors'][] = 'Eroare update users ID '.$r->user_id; }
            continue;
        }
        // Rule 3: ambele valide, dar diferite -> preferă emailul din users și aliniază patients
        if ($p_valid && $u_valid && strcasecmp($p_email, $u_email) !== 0) {
            $ok = $wpdb->update($clinica_table, array('email' => $u_email, 'updated_at' => current_time('mysql')), array('id' => (int)$r->id));
            if ($ok !== false) { $email_sync_results['differences_fixed']++; } else { $email_sync_results['errors'][] = 'Eroare aliniere patients ID '.$r->id; }
        }
        // Rule 4: asigură rolul clinica_patient
        if ((int)$r->user_id > 0) {
            $wp_user = get_userdata((int)$r->user_id);
            if ($wp_user && !in_array('clinica_patient', (array)$wp_user->roles, true)) {
                $wp_user->add_role('clinica_patient');
                $email_sync_results['roles_updated']++;
            }
        }
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="dashicons dashicons-update"></i>
        <?php _e('Sincronizare Pacienți', 'clinica'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <?php if (isset($sync_results)): ?>
    <div class="notice notice-success is-dismissible">
        <h3><?php _e('Rezultate Sincronizare:', 'clinica'); ?></h3>
        <ul>
            <li><strong><?php _e('Subscriberi găsiți:', 'clinica'); ?></strong> <?php echo $sync_results['total_users']; ?></li>
            <li><strong><?php _e('Pacienți existenți:', 'clinica'); ?></strong> <?php echo $sync_results['existing_patients']; ?></li>
            <li><strong><?php _e('Sincronizați cu succes:', 'clinica'); ?></strong> <?php echo $sync_results['synced_count']; ?></li>
            <?php if (!empty($sync_results['errors'])): ?>
            <li><strong><?php _e('Erori:', 'clinica'); ?></strong> <?php echo count($sync_results['errors']); ?></li>
            <?php endif; ?>
        </ul>
        
        <?php if (!empty($sync_results['errors'])): ?>
        <div class="error-details" style="margin-top: 15px;">
            <h4><?php _e('Detalii erori:', 'clinica'); ?></h4>
            <ul>
                <?php foreach ($sync_results['errors'] as $error): ?>
                <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($email_sync_results)): ?>
    <div class="notice notice-success is-dismissible">
        <h3><?php _e('Rezultate Sincronizare Emailuri:', 'clinica'); ?></h3>
        <ul>
            <li><strong><?php _e('Pacienți actualizați (users → patients):', 'clinica'); ?></strong> <?php echo (int)$email_sync_results['patients_updated']; ?></li>
            <li><strong><?php _e('Utilizatori actualizați (patients → users):', 'clinica'); ?></strong> <?php echo (int)$email_sync_results['users_updated']; ?></li>
            <li><strong><?php _e('Diferențe aliniate:', 'clinica'); ?></strong> <?php echo (int)$email_sync_results['differences_fixed']; ?></li>
            <li><strong><?php _e('Roluri setate:', 'clinica'); ?></strong> <?php echo (int)$email_sync_results['roles_updated']; ?></li>
            <?php if (!empty($email_sync_results['errors'])): ?>
            <li><strong><?php _e('Erori:', 'clinica'); ?></strong> <?php echo count($email_sync_results['errors']); ?></li>
            <?php endif; ?>
        </ul>
        <?php if (!empty($email_sync_results['errors'])): ?>
        <div class="error-details" style="margin-top: 15px;">
            <h4><?php _e('Detalii erori:', 'clinica'); ?></h4>
            <ul>
                <?php foreach ($email_sync_results['errors'] as $error): ?>
                <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="clinica-sync-container">
        <div class="clinica-sync-stats">
            <h2><?php _e('Statistici Curente', 'clinica'); ?></h2>
            
            <?php
            // Numărul de utilizatori cu rolul "subscriber" (pacienții)
            $subscribers_count = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->users} u
                WHERE u.ID IN (
                    SELECT user_id 
                    FROM {$wpdb->usermeta} 
                    WHERE meta_key = '{$wpdb->prefix}capabilities' 
                    AND meta_value LIKE '%subscriber%'
                )
            ");
            
            // Numărul de pacienți în clinica_patients
            $clinica_table = $wpdb->prefix . 'clinica_patients';
            $patients_count = $wpdb->get_var("SELECT COUNT(*) FROM $clinica_table");
            
            // Calculați câți subscriberi trebuie sincronizați
            $existing_patients = $wpdb->get_results("SELECT user_id FROM $clinica_table");
            $existing_user_ids = array_column($existing_patients, 'user_id');
            
            // Găsește subscriberii care nu sunt în clinica_patients
            $subscribers_to_sync = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->users} u
                WHERE u.ID IN (
                    SELECT user_id 
                    FROM {$wpdb->usermeta} 
                    WHERE meta_key = '{$wpdb->prefix}capabilities' 
                    AND meta_value LIKE '%subscriber%'
                )
                AND u.ID NOT IN (
                    SELECT user_id FROM $clinica_table
                )
            ");
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($subscribers_count); ?></div>
                    <div class="stat-label"><?php _e('Subscriberi (Pacienți)', 'clinica'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($patients_count); ?></div>
                    <div class="stat-label"><?php _e('Pacienți în Clinica', 'clinica'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($subscribers_to_sync); ?></div>
                    <div class="stat-label"><?php _e('De Sincronizat', 'clinica'); ?></div>
                </div>
                <?php
                // Erori recente din log
                $log_file = CLINICA_PLUGIN_PATH . 'logs/sync-errors.log';
                $invalid_cnp_count = 0;
                if (file_exists($log_file)) {
                    $lines = @file($log_file);
                    if ($lines !== false) {
                        $slice = array_slice($lines, -200);
                        foreach ($slice as $ln) { if (strpos($ln, 'invalid CNP birth date') !== false) { $invalid_cnp_count++; } }
                    }
                }
                ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($invalid_cnp_count); ?></div>
                    <div class="stat-label"><?php _e('Erori CNP invalide (recente)', 'clinica'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="clinica-sync-actions">
            <h2><?php _e('Acțiuni', 'clinica'); ?></h2>
            
            <?php if ($subscribers_to_sync > 0): ?>
            <form method="post" action="" id="clinica-sync-patients-form">
                <?php wp_nonce_field('sync_patients_nonce'); ?>
                <p><?php _e('Această acțiune va sincroniza toți utilizatorii cu rolul "subscriber" (pacienții) cu tabela de pacienți.', 'clinica'); ?></p>
                <p><strong><?php _e('Atenție:', 'clinica'); ?></strong> <?php _e('Această operațiune poate dura câteva minute pentru baze de date mari.', 'clinica'); ?></p>
                
                <button type="submit" name="sync_patients" class="button button-primary button-hero">
                    <i class="dashicons dashicons-update"></i>
                    <?php _e('Sincronizează Pacienții', 'clinica'); ?>
                </button>
            </form>
            <div id="clinica-sync-patients-progress" style="display:none; margin-top:10px;">
                <div style="background:#f1f1f1; border-radius:6px; overflow:hidden; height:16px;">
                    <div id="clinica-sync-patients-bar" style="width:0; height:16px; background:#46b450;"></div>
                </div>
                <p id="clinica-sync-patients-stats" style="margin-top:8px; color:#555;"></p>
            </div>
            <?php else: ?>
            <div class="notice notice-info">
                <p><?php _e('✅ Toți subscriberii sunt deja sincronizați!', 'clinica'); ?></p>
            </div>
            <?php endif; ?>

            <hr>

            <form method="post" action="" id="clinica-sync-emails-form">
                <?php wp_nonce_field('sync_emails_nonce'); ?>
                <p><?php _e('Sincronizează emailurile dintre utilizatori și pacienți (completează lipsurile, aliniează diferențele, validează și setează rolul clinica_patient unde lipsește).', 'clinica'); ?></p>
                <button type="submit" name="sync_emails" class="button">
                    <i class="dashicons dashicons-email"></i>
                    <?php _e('Sincronizează Emailuri', 'clinica'); ?>
                </button>
            </form>

            <div id="clinica-sync-progress" style="display:none; margin-top:15px;">
                <div style="background:#f1f1f1; border-radius:6px; overflow:hidden; height:16px;">
                    <div id="clinica-sync-bar" style="width:0; height:16px; background:#0073aa;"></div>
                </div>
                <p id="clinica-sync-stats" style="margin-top:8px; color:#555;"></p>
            </div>

            <hr>

            <h3><?php _e('Sincronizare completă', 'clinica'); ?></h3>
            <p><?php _e('Rulează ambele procese (Pacienți + Emailuri) cu un singur click.', 'clinica'); ?></p>
            <button type="button" id="clinica-sync-all-btn" class="button button-primary">
                <i class="dashicons dashicons-update"></i> <?php _e('Rulează Sincronizare Completă', 'clinica'); ?>
            </button>
            <div id="clinica-sync-all-progress" style="display:none; margin-top:10px;">
                <div style="background:#f1f1f1; border-radius:6px; overflow:hidden; height:16px;">
                    <div id="clinica-sync-all-bar" style="width:0; height:16px; background:#6f42c1;"></div>
                </div>
                <p id="clinica-sync-all-stats" style="margin-top:8px; color:#555;"></p>
            </div>

            <button type="button" id="clinica-view-errors-btn" class="button" style="margin-top:10px;">
                <i class="dashicons dashicons-warning"></i> <?php _e('Vezi erorile recente', 'clinica'); ?>
            </button>
            <a href="#" id="clinica-download-log" class="button" style="margin-top:10px;">
                <i class="dashicons dashicons-download"></i> <?php _e('Descarcă log complet', 'clinica'); ?>
            </a>
            <button type="button" id="clinica-archive-log" class="button" style="margin-top:10px;">
                <i class="dashicons dashicons-archive"></i> <?php _e('Arhivează și golește log', 'clinica'); ?>
            </button>

            <div id="clinica-errors-modal" class="notice" style="display:none; background:#fff; border:1px solid #ccd0d4; padding:10px; max-height:300px; overflow:auto; margin-top:10px;"></div>
        </div>
        
        <div class="clinica-sync-info">
            <h2><?php _e('Informații', 'clinica'); ?></h2>
            <div class="info-content">
                <h3><?php _e('Ce face sincronizarea?', 'clinica'); ?></h3>
                <ul>
                    <li><?php _e('Găsește toți utilizatorii cu rolul "subscriber" (pacienții)', 'clinica'); ?></li>
                    <li><?php _e('Verifică care nu sunt în tabela clinica_patients', 'clinica'); ?></li>
                    <li><?php _e('Adaugă pacienții lipsă în tabela clinica_patients', 'clinica'); ?></li>
                    <li><?php _e('Folosește CNP-ul din username ca CNP', 'clinica'); ?></li>
                    <li><?php _e('Acceptă CNP-uri românești (13 cifre) și străine (12-14 cifre)', 'clinica'); ?></li>
                    <li><?php _e('Copiază informațiile de contact din usermeta', 'clinica'); ?></li>
                </ul>
                
                <h3><?php _e('Rezultatul:', 'clinica'); ?></h3>
                <ul>
                    <li><?php _e('Toți utilizatorii vor apărea în dropdown-ul de filtrare din programări', 'clinica'); ?></li>
                    <li><?php _e('Numele complete vor fi afișate corect', 'clinica'); ?></li>
                    <li><?php _e('Filtrarea va funcționa pentru toți pacienții', 'clinica'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.clinica-sync-container {
    max-width: 1200px;
    margin: 20px 0;
}

.clinica-sync-stats {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #0073aa;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 10px;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

.clinica-sync-actions {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.clinica-sync-info {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.info-content h3 {
    margin-top: 20px;
    margin-bottom: 10px;
    color: #333;
}

.info-content ul {
    margin-left: 20px;
}

.info-content li {
    margin-bottom: 5px;
    color: #666;
}

.error-details {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    padding: 15px;
    margin-top: 15px;
}

.error-details h4 {
    color: #721c24;
    margin-top: 0;
}

.error-details ul {
    margin: 10px 0;
    color: #721c24;
}
</style> 

<script>
document.addEventListener('DOMContentLoaded', function(){
  // Pacienți – progres AJAX
  var pf = document.getElementById('clinica-sync-patients-form');
  if (pf) {
    pf.addEventListener('submit', function(e){
      e.preventDefault();
      var box = document.getElementById('clinica-sync-patients-progress');
      var bar = document.getElementById('clinica-sync-patients-bar');
      var stats = document.getElementById('clinica-sync-patients-stats');
      box.style.display = 'block'; bar.style.width = '0%'; stats.textContent = 'Pornesc sincronizarea pacienților...';
      var step = 0, batch = 250, total = 0, inserted = 0, roles = 0;
      function tick(){
        var data = new FormData(); data.append('action','clinica_sync_patients_progress'); data.append('nonce','<?php echo wp_create_nonce('clinica_test_nonce'); ?>'); data.append('step', step); data.append('batch', batch);
        fetch(ajaxurl, {method:'POST', body:data}).then(r=>r.json()).then(function(resp){
          if(!resp || !resp.success){ stats.textContent = 'Eroare la sincronizare.'; return; }
          var d = resp.data; total = d.total; step += batch; inserted += d.inserted; roles += d.roles_set; var pct = total ? Math.min(100, Math.round((d.processed/total)*100)) : 100; bar.style.width = pct+'%';
          stats.textContent = 'Procesat: '+(d.processed)+' / '+total+' • Inserări: '+inserted+' • Roluri setate: '+roles;
          if(!d.done){ tick(); } else { stats.textContent += ' • Gata.'; pf.submit(); }
        }).catch(function(){ stats.textContent = 'Eroare la rețea.'; });
      }
      tick();
    });
  }
  var form = document.getElementById('clinica-sync-emails-form');
  if (!form) return;
  form.addEventListener('submit', function(e){
    e.preventDefault();
    var progress = document.getElementById('clinica-sync-progress');
    var bar = document.getElementById('clinica-sync-bar');
    var stats = document.getElementById('clinica-sync-stats');
    progress.style.display = 'block'; bar.style.width = '0%'; stats.textContent = 'Pregătesc sincronizarea...';
    // Rulare AJAX cu batch-uri
    var step = 0; var batch = 250; var total = 0; var updatedP=0, updatedU=0, diff=0, roles=0;
    function tick(){
      var data = new FormData();
      data.append('action','clinica_sync_emails_progress');
      data.append('nonce','<?php echo wp_create_nonce('clinica_test_nonce'); ?>');
      data.append('step', step);
      data.append('batch', batch);
      fetch(ajaxurl, {method:'POST', body:data}).then(r=>r.json()).then(function(resp){
        if(!resp || !resp.success){ stats.textContent = 'Eroare la sincronizare.'; return; }
        var d = resp.data; total = d.total; step += batch; updatedP += d.updated_patients; updatedU += d.updated_users; diff += d.differences_fixed; roles += d.roles_set;
        var pct = total ? Math.min(100, Math.round((d.processed/total)*100)) : 100; bar.style.width = pct+'%';
        stats.textContent = 'Procesat: '+(d.processed)+' / '+total+' • Pacienți: '+updatedP+' • Utilizatori: '+updatedU+' • Diferențe: '+diff+' • Roluri: '+roles;
        if(!d.done){ tick(); } else { stats.textContent += ' • Gata.'; }
      }).catch(function(){ stats.textContent = 'Eroare la rețea.'; });
    }
    tick();
  });
});

// Sincronizare completă (Pacienți -> Emailuri)
document.addEventListener('DOMContentLoaded', function(){
  var btn = document.getElementById('clinica-sync-all-btn');
  if (!btn) return;
  btn.addEventListener('click', function(){
    var box = document.getElementById('clinica-sync-all-progress');
    var bar = document.getElementById('clinica-sync-all-bar');
    var stats = document.getElementById('clinica-sync-all-stats');
    box.style.display = 'block'; bar.style.width = '0%'; stats.textContent = 'Pasul 1/2: sincronizez pacienții lipsă...';
    // 1) Run patients progress to 50%
    var step = 0, batch = 250, total=0, inserted=0, roles=0;
    function tickPatients(){
      var fd = new FormData(); fd.append('action','clinica_sync_patients_progress'); fd.append('nonce','<?php echo wp_create_nonce('clinica_test_nonce'); ?>'); fd.append('step', step); fd.append('batch', batch);
      fetch(ajaxurl,{method:'POST', body:fd}).then(r=>r.json()).then(function(resp){
        if(!resp || !resp.success){ stats.textContent='Eroare la sincronizarea pacienților.'; return; }
        var d=resp.data; total=d.total; step+=batch; inserted+=d.inserted; roles+=d.roles_set; var pct = total? Math.min(100, Math.round((d.processed/total)*50)) : 50; bar.style.width=pct+'%';
        stats.textContent = 'Pasul 1/2: '+(d.processed)+' / '+total+' • Inserări: '+inserted+' • Roluri: '+roles;
        if(!d.done){ tickPatients(); } else { stats.textContent='Pasul 2/2: sincronizez emailurile...'; tickEmails(); }
      }).catch(function(){ stats.textContent='Eroare rețea la pasul 1.'; });
    }
    // 2) Run emails progress to 100%
    var estTotal=0, eStep=0, updP=0, updU=0, dif=0, rset=0;
    function tickEmails(){
      var fd = new FormData(); fd.append('action','clinica_sync_emails_progress'); fd.append('nonce','<?php echo wp_create_nonce('clinica_test_nonce'); ?>'); fd.append('step', eStep); fd.append('batch', batch);
      fetch(ajaxurl,{method:'POST', body:fd}).then(r=>r.json()).then(function(resp){
        if(!resp || !resp.success){ stats.textContent='Eroare la sincronizarea emailurilor.'; return; }
        var d=resp.data; estTotal=d.total; eStep+=batch; updP+=d.updated_patients; updU+=d.updated_users; dif+=d.differences_fixed; rset+=d.roles_set; var pct = estTotal? Math.min(100, 50+Math.round((d.processed/estTotal)*50)) : 100; bar.style.width=pct+'%';
        stats.textContent = 'Pasul 2/2: '+(d.processed)+' / '+estTotal+' • Pacienți: '+updP+' • Utilizatori: '+updU+' • Diferențe: '+dif+' • Roluri: '+rset;
        if(!d.done){ tickEmails(); } else { stats.textContent += ' • Gata.'; }
      }).catch(function(){ stats.textContent='Eroare rețea la pasul 2.'; });
    }
    tickPatients();
  });
  // Erori recente
  var bErr = document.getElementById('clinica-view-errors-btn');
  var mErr = document.getElementById('clinica-errors-modal');
  var dwn = document.getElementById('clinica-download-log');
  if (bErr && mErr && dwn) {
    bErr.addEventListener('click', function(){
      var fd = new FormData(); fd.append('action','clinica_get_sync_errors'); fd.append('nonce','<?php echo wp_create_nonce('clinica_test_nonce'); ?>');
      fetch(ajaxurl,{method:'POST', body:fd}).then(r=>r.json()).then(function(resp){
        if(!resp || !resp.success){ mErr.style.display='block'; mErr.innerHTML='<p style="color:#a00;">Eroare la citirea logului.</p>'; return; }
        var lines = resp.data.lines || []; mErr.style.display='block';
        if(lines.length===0){ mErr.innerHTML='<p><?php echo esc_js(__('Nu există erori recente.', 'clinica')); ?></p>'; return; }
        mErr.innerHTML='<pre style="white-space:pre-wrap; margin:0;">'+lines.join('\n')+'</pre>';
      }).catch(function(){ mErr.style.display='block'; mErr.innerHTML='<p style="color:#a00;">Eroare la rețea.</p>'; });
    });
    dwn.addEventListener('click', function(e){ e.preventDefault(); var url = ajaxurl + '&action=clinica_download_sync_log&nonce=<?php echo wp_create_nonce('clinica_test_nonce'); ?>'; window.location.href = url; });
    var arch = document.getElementById('clinica-archive-log');
    arch.addEventListener('click', function(){
      var fd = new FormData(); fd.append('action','clinica_archive_sync_log'); fd.append('nonce','<?php echo wp_create_nonce('clinica_test_nonce'); ?>');
      fetch(ajaxurl,{method:'POST', body:fd}).then(r=>r.json()).then(function(resp){
        if(!resp || !resp.success){ alert('Arhivarea a eșuat.'); return; }
        alert('Log arhivat în: '+ resp.data.archive);
        mErr.style.display='none';
      }).catch(function(){ alert('Eroare rețea.'); });
    });
  }
});
</script>