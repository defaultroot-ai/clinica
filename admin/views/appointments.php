<?php
/**
 * Pagina pentru gestionarea programărilor
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!Clinica_Patient_Permissions::can_view_appointments()) {
    wp_die(__('Nu aveți permisiunea de a vedea programările.', 'clinica'));
}

global $wpdb;

// Paginare
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filtre
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date_filter = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Construiește query-ul
$table_name = $wpdb->prefix . 'clinica_appointments';
$where_conditions = array();
$where_values = array();

if ($patient_id > 0) {
    $where_conditions[] = "a.patient_id = %d";
    $where_values[] = $patient_id;
}

if ($doctor_id > 0) {
    $where_conditions[] = "a.doctor_id = %d";
    $where_values[] = $doctor_id;
}

if (!empty($date_filter)) {
    $where_conditions[] = "a.appointment_date = %s";
    $where_values[] = $date_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = %s";
    $where_values[] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Numărul total de programări
$total_query = "SELECT COUNT(*) FROM $table_name a $where_clause";

if (!empty($where_values)) {
    $total_query = $wpdb->prepare($total_query, $where_values);
}

$total = $wpdb->get_var($total_query);
$total_pages = ceil($total / $per_page);

// Lista de programări
$query = "SELECT a.*, 
                 COALESCE(CONCAT(um1.meta_value, ' ', um2.meta_value), p.display_name) as patient_name,
                 COALESCE(CONCAT(um3.meta_value, ' ', um4.meta_value), d.display_name) as doctor_name,
                 a.created_by_type,
                 a.last_edited_by_type,
                 a.last_edited_by_user_id,
                 a.last_edited_at
          FROM $table_name a 
          LEFT JOIN {$wpdb->users} p ON a.patient_id = p.ID 
          LEFT JOIN {$wpdb->users} d ON a.doctor_id = d.ID 
          LEFT JOIN {$wpdb->usermeta} um1 ON p.ID = um1.user_id AND um1.meta_key = 'first_name'
          LEFT JOIN {$wpdb->usermeta} um2 ON p.ID = um2.user_id AND um2.meta_key = 'last_name'
          LEFT JOIN {$wpdb->usermeta} um3 ON d.ID = um3.user_id AND um3.meta_key = 'first_name'
          LEFT JOIN {$wpdb->usermeta} um4 ON d.ID = um4.user_id AND um4.meta_key = 'last_name'
          $where_clause 
          ORDER BY a.appointment_date DESC, a.appointment_time DESC 
          LIMIT %d OFFSET %d";

$query_values = array_merge($where_values, array($per_page, $offset));
$appointments = $wpdb->get_results($wpdb->prepare($query, $query_values));

// Obține lista de pacienți pentru filtru - VERSIUNE REPARATĂ
$patients = $wpdb->get_results("
    SELECT u.ID, 
           COALESCE(CONCAT(um1.meta_value, ' ', um2.meta_value), u.display_name) as display_name
    FROM {$wpdb->users} u 
    LEFT JOIN {$wpdb->prefix}clinica_patients p ON u.ID = p.user_id 
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE u.ID > 1
    ORDER BY display_name
");

// Obține lista de doctori pentru filtru
$doctors = get_users(array('role__in' => array('clinica_doctor', 'clinica_manager')));
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Programări', 'clinica'); ?></h1>
    
    <?php if (Clinica_Patient_Permissions::can_create_appointments()): ?>
    <a href="<?php echo admin_url('admin.php?page=clinica-appointments&action=new'); ?>" class="page-title-action">
        <?php _e('Programare Nouă', 'clinica'); ?>
    </a>
    <?php endif; ?>
    
    <hr class="wp-header-end">

    <?php if ($action === 'edit' && Clinica_Patient_Permissions::can_manage_appointments()): ?>
    <?php
        $edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($edit_id <= 0) {
            echo '<div class="clinica-notice error">' . esc_html(__('ID programare invalid.', 'clinica')) . '</div>';
        } else {
            $appt = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}clinica_appointments WHERE id = %d", $edit_id));
            if (!$appt) {
                echo '<div class="clinica-notice error">' . esc_html(__('Programare inexistentă.', 'clinica')) . '</div>';
            } else {
                // Servicii active
                $services = $wpdb->get_results("SELECT id, name, duration FROM {$wpdb->prefix}clinica_services WHERE active = 1 ORDER BY name ASC");
                // Nume și date pacient
                $patient_user = get_user_by('ID', intval($appt->patient_id));
                $patient_name = '';
                if ($patient_user) {
                    $fn = get_user_meta($patient_user->ID, 'first_name', true);
                    $ln = get_user_meta($patient_user->ID, 'last_name', true);
                    $patient_name = trim(($fn . ' ' . $ln));
                    if ($patient_name === '') { $patient_name = $patient_user->display_name; }
                }
                $p_row = $wpdb->get_row($wpdb->prepare("SELECT email, phone_primary, cnp FROM {$wpdb->prefix}clinica_patients WHERE user_id = %d", intval($appt->patient_id)));
                $p_email = $p_row && !empty($p_row->email) ? $p_row->email : ($patient_user ? $patient_user->user_email : '');
                $p_phone = $p_row && !empty($p_row->phone_primary) ? $p_row->phone_primary : '';
                $p_cnp   = $p_row && !empty($p_row->cnp) ? $p_row->cnp : '';
                // Slot label
                $slot_label = '';
                if (!empty($appt->appointment_time)) {
                    $t = DateTime::createFromFormat('H:i:s', $appt->appointment_time);
                    if (!$t) { $t = DateTime::createFromFormat('H:i', substr($appt->appointment_time,0,5)); }
                    if ($t) {
                        $start_label = $t->format('H:i');
                        $dur = intval($appt->duration) ?: 30;
                        $t2 = clone $t; $t2->modify('+' . $dur . ' minutes');
                        $slot_label = $start_label . ' - ' . $t2->format('H:i');
                    }
                }
    ?>
    <div class="clinica-form-page">
    <div class="clinica-card clinica-form" style="flex:1;">
        <div class="form-header">
            <h2 style="margin:0; font-size:18px; line-height:1.4; color:#0a66c2;">
                <?php _e('Editează programare', 'clinica'); ?>
            </h2>
            <div class="hint"><?php _e('Modifică câmpurile necesare și salvează.', 'clinica'); ?></div>
        </div>
        <div class="form-grid">
        <div class="form-group autosuggest-group">
            <label for="ef-patient-search" class="label-required"><?php _e('Pacient', 'clinica'); ?></label>
            <input type="text" id="ef-patient-search" name="ef-patient-search" placeholder="<?php esc_attr_e('Caută pacient (nume, email, CNP, telefon)', 'clinica'); ?>" value="<?php echo esc_attr($patient_name); ?>" />
            <input type="hidden" id="ef-patient" name="ef-patient" value="<?php echo (int)$appt->patient_id; ?>" />
            <div id="ef-patient-suggestions" class="clinica-search-suggestions" style="display:none;"></div>
        </div>
        <div class="form-group">
            <label for="ef-service" class="label-required"><?php _e('Serviciu', 'clinica'); ?></label>
            <select id="ef-service" data-services='<?php echo json_encode($services); ?>'>
                <option value=""><?php _e('Selectează serviciu', 'clinica'); ?></option>
                <?php foreach ($services as $s): ?>
                <option value="<?php echo (int)$s->id; ?>" data-duration="<?php echo (int)$s->duration; ?>" <?php selected((intval($appt->service_id) ?: intval($appt->type)), intval($s->id)); ?>><?php echo esc_html($s->name); ?> (<?php echo (int)$s->duration; ?> min)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="ef-doctor" class="label-required"><?php _e('Doctor', 'clinica'); ?></label>
            <select id="ef-doctor">
                <option value=""><?php _e('Selectează doctor', 'clinica'); ?></option>
            </select>
        </div>
        <div class="form-group">
            <label for="ef-date" class="label-required"><?php _e('Data', 'clinica'); ?></label>
            <input type="text" id="ef-date" placeholder="DD.MM.YYYY" value="<?php echo esc_attr($appt->appointment_date); ?>" />
        </div>
        <div class="form-group">
            <label for="ef-slot" class="label-required"><?php _e('Interval orar', 'clinica'); ?></label>
            <select id="ef-slot">
                <option value=""><?php _e('Selectează interval', 'clinica'); ?></option>
            </select>
        </div>
        <div class="form-group">
            <label for="ef-status"><?php _e('Status', 'clinica'); ?></label>
            <select id="ef-status">
                <?php
                // Verifică dacă programarea ar trebui să fie "Completată" pe baza orei
                $current_time = current_time('mysql');
                $appointment_start = $appt->appointment_date . ' ' . $appt->appointment_time;
                $duration = $appt->duration ?: 30;
                $appointment_end = date('Y-m-d H:i:s', strtotime($appointment_start . " +{$duration} minutes"));
                $should_be_completed = (strtotime($appointment_end . ' +30 minutes') < strtotime($current_time));
                
                // Determină statusul corect
                $correct_status = $appt->status;
                if ($appt->status === 'confirmed' && $should_be_completed) {
                    $correct_status = 'completed';
                } elseif ($appt->status === 'completed' && !$should_be_completed) {
                    $correct_status = 'confirmed';
                }
                ?>
                <option value="confirmed" <?php selected($correct_status, 'confirmed'); ?>><?php _e('Acceptat', 'clinica'); ?></option>
                <option value="scheduled" <?php selected($correct_status, 'scheduled'); ?>><?php _e('Programată', 'clinica'); ?></option>
                <option value="completed" <?php selected($correct_status, 'completed'); ?>><?php _e('Completată', 'clinica'); ?></option>
                <option value="cancelled" <?php selected($correct_status, 'cancelled'); ?>><?php _e('Anulată', 'clinica'); ?></option>
                <option value="no_show" <?php selected($correct_status, 'no_show'); ?>><?php _e('Nu s-a prezentat', 'clinica'); ?></option>
            </select>
        </div>
        <div class="form-group">
            <label for="ef-created-by"><?php _e('Creat de', 'clinica'); ?></label>
            <select id="ef-created-by" name="created_by_type" disabled>
                <option value="receptionist" <?php selected($appt->created_by_type ?? 'receptionist', 'receptionist'); ?>><?php _e('Recepționist', 'clinica'); ?></option>
                <option value="doctor" <?php selected($appt->created_by_type ?? 'receptionist', 'doctor'); ?>><?php _e('Doctor', 'clinica'); ?></option>
                <option value="assistant" <?php selected($appt->created_by_type ?? 'receptionist', 'assistant'); ?>><?php _e('Asistent', 'clinica'); ?></option>
                <option value="patient" <?php selected($appt->created_by_type ?? 'receptionist', 'patient'); ?>><?php _e('Pacient', 'clinica'); ?></option>
                <option value="admin" <?php selected($appt->created_by_type ?? 'receptionist', 'admin'); ?>><?php _e('Admin WordPress', 'clinica'); ?></option>
                <option value="manager" <?php selected($appt->created_by_type ?? 'receptionist', 'manager'); ?>><?php _e('Manager Clinică', 'clinica'); ?></option>
            </select>
            <div class="hint"><?php _e('Informație doar pentru vizualizare', 'clinica'); ?></div>
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <label for="ef-notes"><?php _e('Observații', 'clinica'); ?></label>
            <textarea id="ef-notes" rows="3"><?php echo esc_textarea($appt->notes); ?></textarea>
            <div class="hint"><?php _e('Informații pentru personalul medical (opțional)', 'clinica'); ?></div>
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <div class="toggle-row">
                <span class="toggle-label"><?php _e('Trimite email de confirmare', 'clinica'); ?></span>
                <label class="clinica-toggle-switch" for="ef-send-email">
                    <input type="checkbox" id="ef-send-email" />
                    <span class="clinica-toggle-slider"></span>
                </label>
            </div>
        </div>
        </div>
        <div class="form-actions actions-right">
            <a class="clinica-button" href="<?php echo admin_url('admin.php?page=clinica-appointments'); ?>"><?php _e('Renunță', 'clinica'); ?></a>
            <button type="button" class="clinica-button clinica-button--primary" id="ef-submit"><?php _e('Salvează modificările', 'clinica'); ?></button>
        </div>
        <div id="ef-feedback" style="margin-top:10px;"></div>
    </div>
    <aside class="summary-aside">
        <div class="clinica-card summary-card">
            <div class="summary-header">
                <h3><?php _e('Rezumat', 'clinica'); ?></h3>
                <span class="summary-sub"><?php _e('Previzualizare programare', 'clinica'); ?></span>
            </div>
            <div class="summary-columns">
                <div class="summary-section">
                    <h4><?php _e('Pacient', 'clinica'); ?></h4>
                    <ul class="summary-list" id="ef-summary-patient"></ul>
                </div>
                <div class="summary-section">
                    <h4><?php _e('Programare', 'clinica'); ?></h4>
                    <ul class="summary-list" id="ef-summary-appointment"></ul>
                </div>
            </div>
        </div>
    </aside>
    </div>

    <script>
    // Funcție pentru a determina tipul utilizatorului curent
    function getCurrentUserType() {
        <?php 
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        ?>
        
        <?php if (in_array('administrator', $user_roles)): ?>
            return 'admin';
        <?php elseif (in_array('manager', $user_roles)): ?>
            return 'manager';
        <?php elseif (in_array('doctor', $user_roles)): ?>
            return 'doctor';
        <?php elseif (in_array('assistant', $user_roles)): ?>
            return 'assistant';
        <?php elseif (in_array('receptionist', $user_roles)): ?>
            return 'receptionist';
        <?php else: ?>
            return 'receptionist'; // Default
        <?php endif; ?>
    }
    
    jQuery(function($){
        var services = $('#ef-service').data('services') || [];
        var initial = {
            id: <?php echo (int)$appt->id; ?>,
            patient_id: <?php echo (int)$appt->patient_id; ?>,
            patient: { id: <?php echo (int)$appt->patient_id; ?>, name: <?php echo json_encode($patient_name); ?>, email: <?php echo json_encode($p_email); ?>, phone: <?php echo json_encode($p_phone); ?>, cnp: <?php echo json_encode($p_cnp); ?> },
            service_id: <?php echo (intval($appt->service_id) ?: intval($appt->type)); ?>,
            doctor_id: <?php echo (int)$appt->doctor_id; ?>,
            date: <?php echo json_encode($appt->appointment_date); ?>,
            slotLabel: <?php echo json_encode($slot_label); ?>,
            time: <?php echo json_encode(substr($appt->appointment_time,0,5)); ?>,
            duration: <?php echo (int)$appt->duration; ?>,
            status: <?php echo json_encode($correct_status); ?>,
            notes: <?php echo json_encode($appt->notes); ?>,
            isInitializing: true
        };
        
        // Detectează automat serviciul pe baza duratei dacă nu este setat
        if (!initial.service_id || initial.service_id === 0) {
            var matchingService = services.find(function(s) {
                return parseInt(s.duration) === initial.duration;
            });
            if (matchingService) {
                initial.service_id = parseInt(matchingService.id);
                console.log('Auto-detected service:', matchingService.name, 'for duration:', initial.duration);
            }
        }

        // AUTOSUGGEST PACIENT
        var $search = $('#ef-patient-search');
        var $hidden = $('#ef-patient');
        var $box = $('#ef-patient-suggestions');
        $search.data('lastSuggestion', initial.patient || {});
        // Setează valorile inițiale
        $search.val(initial.patient.name || '');
        $hidden.val(initial.patient_id || '');
        function renderSuggestions(items){
            if (!items || !items.length){ $box.hide().empty(); return; }
            var frag = $(document.createDocumentFragment());
            items.forEach(function(it){
                var $i = $('<div/>').addClass('item').attr('data-id', it.id || '').data('payload', it).append(
                    $('<div/>').addClass('title').text(it.name || '')
                ).append(
                    $('<div/>').addClass('meta').text([it.email, it.phone, it.cnp].filter(Boolean).join(' • '))
                );
                $i.on('click', function(){
                    var payload = $(this).data('payload') || it;
                    $hidden.val(payload.id || '');
                    $search.val(payload.name || '');
                    $search.data('lastSuggestion', payload);
                    $box.hide().empty();
                    markValidity(); updateEditFormSummary();
                });
                frag.append($i);
            });
            $box.empty().append(frag).show();
        }
        var suggestTimer = null;
        $search.on('input focus', function(){
            var q = ($search.val()||'').trim();
            if (q.length < 2){ $box.hide().empty(); return; }
            clearTimeout(suggestTimer);
            suggestTimer = setTimeout(function(){
                $.post((window.clinica_autosuggest && clinica_autosuggest.ajaxurl) ? clinica_autosuggest.ajaxurl : ajaxurl, {
                    action:'clinica_search_patients_suggestions',
                    nonce:(window.clinica_autosuggest && clinica_autosuggest.search_nonce) ? clinica_autosuggest.search_nonce : '<?php echo wp_create_nonce('clinica_search_nonce'); ?>',
                    search_term:q,
                    search_type:'search-input'
                }, function(resp){
                    var items = (resp && resp.success && resp.data && resp.data.suggestions) ? resp.data.suggestions : [];
                    renderSuggestions(items);
                });
            }, 200);
        });
        $(document).on('click', function(e){ if(!$(e.target).closest('#ef-patient-search, #ef-patient-suggestions').length){ $box.hide(); } });

        function loadDoctors(serviceId, cb){
            console.log('Loading doctors for service:', serviceId);
            $('#ef-doctor').html('<option value="">'+<?php echo json_encode(__('Selectează doctor', 'clinica')); ?>+'</option>');
            if (!serviceId) { if (cb) cb(); return; }
            $.post(ajaxurl, { action:'clinica_get_doctors_for_service', service_id: serviceId, nonce:'<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' }, function(resp){
                console.log('Doctors response:', resp);
                if (resp && resp.success && Array.isArray(resp.data)){
                    resp.data.forEach(function(d){ $('#ef-doctor').append($('<option/>').val(d.id).text(d.name)); });
                }
                if (cb) cb();
            });
        }
        function loadDays(doctorId, cb){
            $('#ef-date').val(''); $('#ef-slot').html('<option value="">'+<?php echo json_encode(__('Selectează interval', 'clinica')); ?>+'</option>');
            if (!doctorId) { if (cb) cb(); return; }
            var serviceId = $('#ef-service').val() || 0;
            console.log('Loading days for doctor:', doctorId, 'service:', serviceId);
            $.post(ajaxurl, { action:'clinica_get_doctor_availability_days', doctor_id: doctorId, service_id: serviceId, nonce:'<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>', timestamp: Date.now() }, function(resp){
                var days = (resp && resp.success && Array.isArray(resp.data)) ? resp.data : [];
                console.log('Days returned:', days);
                var allowed = days.filter(function(x){ return x && x.date && !x.full; }).map(function(x){ return x.date; });
                console.log('Allowed dates:', allowed);
                try { if ($('#ef-date')[0]._flatpickr) { $('#ef-date')[0]._flatpickr.destroy(); } } catch(e){}
                function loadScript(src, cb){ var s=document.createElement('script'); s.src=src; s.onload=cb; document.head.appendChild(s); }
                function loadCss(href){ var l=document.createElement('link'); l.rel='stylesheet'; l.href=href; document.head.appendChild(l); }
                function initPicker(){
                    $('#ef-date').flatpickr({
                        dateFormat:'Y-m-d',
                        altInput:true,
                        altFormat:'d.m.Y',
                        locale: (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.ro) ? flatpickr.l10ns.ro : 'ro',
                        // Permite programări pe ziua curentă în backend
                        // minDate:'today',
                        disable: [function(date){
                            // Formatează data în local (YYYY-MM-DD) pentru a evita deplasări de timezone cu toISOString()
                            var year = date.getFullYear();
                            var month = String(date.getMonth()+1).padStart(2,'0');
                            var day = String(date.getDate()).padStart(2,'0');
                            var s = year + '-' + month + '-' + day;
                            return allowed.indexOf(s) === -1;
                        }]
                    });
                }
                if (typeof flatpickr === 'undefined'){
                    loadCss('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
                    loadScript('https://cdn.jsdelivr.net/npm/flatpickr', function(){
                        loadScript('https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ro.js', function(){ initPicker(); if (cb) cb(); });
                    });
                } else { initPicker(); if (cb) cb(); }
            });
        }
        function loadSlots(cb){
            var doctorId = $('#ef-doctor').val(), day = $('#ef-date').val(), serviceId = $('#ef-service').val();
            $('#ef-slot').html('<option value="">'+<?php echo json_encode(__('Selectează interval', 'clinica')); ?>+'</option>');
            if (!doctorId || !day) { if (cb) cb([]); return; }
            var duration = (function(){ var sId=parseInt(serviceId,10); var m=services.find(function(s){return parseInt(s.id,10)===sId;}); return (m&&m.duration)?m.duration:(initial.duration||30); })();
            $.post(ajaxurl, { action:'clinica_get_doctor_slots', doctor_id: doctorId, day: day, duration: duration, service_id: serviceId, nonce:'<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' }, function(resp){
                var slots = (resp && resp.success && Array.isArray(resp.data))?resp.data:[];
                slots.forEach(function(s){ $('#ef-slot').append($('<option/>').val(s).text(s)); });
                // Ajustează automat statusul în funcție de noul interval selectat
                autoAdjustStatus();
                if (cb) cb(slots);
            });
        }

        // Calculează și setează automat statusul în funcție de data/ora selectate
        function autoAdjustStatus(){
            var date = $('#ef-date').val();
            var slot = $('#ef-slot').val();
            if (!date || !slot) { return; }
            var parts = slot.split(' - ');
            if (parts.length < 2) { return; }
            var start = parts[0];
            var end = parts[1];
            // Determină dacă utilizatorul a schimbat efectiv data/ora față de valorile inițiale
            var timeChanged = String(initial.date || '') !== String(date || '') || String(initial.time || '') !== String(start || '');
            // Determină durata din serviciu (fallback 30)
            var duration = (function(){ var sId=parseInt($('#ef-service').val(),10); var m=services.find(function(s){return parseInt(s.id,10)===sId;}); return (m&&m.duration)?m.duration:(initial.duration||30); })();

            // Prag: 30 min după sfârșit
            var endDt = new Date(date + 'T' + end + ':00');
            if (isNaN(endDt.getTime())) { return; }
            var threshold = new Date(endDt.getTime() + 30*60000);
            var now = new Date();

            var newStatus = (now >= threshold) ? 'completed' : 'confirmed';
            var currentStatus = $('#ef-status').val();

            // Nu forța ieșirea din „Anulată” la inițializare sau dacă nu s-a schimbat data/ora
            if (currentStatus === 'cancelled' && !timeChanged) { return; }

            // Dacă era anulată și s-a schimbat intervalul, sau dacă statusul diferă, actualizează
            if ((currentStatus === 'cancelled' && timeChanged) || currentStatus !== newStatus) {
                $('#ef-status').val(newStatus).trigger('change');
            }
        }

                // Pre-populare inițială - setează serviciul după ce dropdown-ul este populat
        console.log('Initial data:', initial);
        // Dacă statusul este completat, blochează editarea
        if ((initial.status || '').toLowerCase() === 'completed') {
            $('#ef-form :input').prop('disabled', true);
            $('#ef-feedback').html('<div class="clinica-notice info">Această programare este Completată și nu mai poate fi editată.</div>');
        }
        
        // Setează serviciul după ce dropdown-ul este populat
        if (initial.service_id && initial.service_id > 0) {
            // Setează serviciul imediat, fără setTimeout
            $('#ef-service').val(String(initial.service_id));
            console.log('Set service to:', initial.service_id);
            
            // Încarcă doctorii pentru serviciul selectat
            loadDoctors(initial.service_id, function(){
                $('#ef-doctor').val(String(initial.doctor_id||''));
                console.log('Set doctor to:', initial.doctor_id);
                loadDays(initial.doctor_id, function(){
                    $('#ef-date').val(initial.date||'');
                    console.log('Set date to:', initial.date);
                    loadSlots(function(slots){
                        if (initial.slotLabel && slots && slots.length > 0){
                            // Caută slot-ul care conține ora inițială
                            var targetSlot = slots.find(function(slot) {
                                return slot.includes(initial.time);
                            });
                            if (targetSlot) {
                                $('#ef-slot').val(targetSlot);
                                console.log('Set slot to:', targetSlot);
                            }
                        }
                        // Ajustează statusul după pre-populare
                        autoAdjustStatus();
                        updateEditFormSummary();
                        // Marchează că inițializarea s-a terminat
                        initial.isInitializing = false;
                    });
                });
            });
        } else {
            // Dacă nu există serviciu, setează direct valorile disponibile
            $('#ef-date').val(initial.date||'');
            console.log('No service, set date to:', initial.date);
            
            // Încearcă să încarce doctorii direct
            if (initial.doctor_id && initial.doctor_id > 0) {
                // Încarcă să găsească doctorul în toate serviciile
                $.post(ajaxurl, { action:'clinica_get_doctors_for_service', service_id: 0, nonce:'<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' }, function(resp){
                    if (resp && resp.success && Array.isArray(resp.data)){
                        resp.data.forEach(function(d){ $('#ef-doctor').append($('<option/>').val(d.id).text(d.name)); });
                        $('#ef-doctor').val(String(initial.doctor_id));
                        console.log('Set doctor to:', initial.doctor_id);
                        // Încearcă să încarce zilele disponibile
                        if (initial.date) {
                            loadDays(initial.doctor_id, function(){
                                loadSlots(function(slots){
                                    if (initial.slotLabel && slots && slots.length > 0){
                                        var targetSlot = slots.find(function(slot) {
                                            return slot.includes(initial.time);
                                        });
                                        if (targetSlot) {
                                            $('#ef-slot').val(targetSlot);
                                            console.log('Set slot to:', targetSlot);
                                        }
                                    }
                                });
                            });
                        }
                    }
                });
            }
            updateEditFormSummary();
            // Marchează că inițializarea s-a terminat
            initial.isInitializing = false;
        }

        $('#ef-service').on('change', function(){ 
            var serviceId = $(this).val();
            loadDoctors(serviceId); 
            // Reîncarcă zilele cu noul serviciu
            var doctorId = $('#ef-doctor').val();
            if (doctorId) {
                loadDays(doctorId);
            }
            // Nu reseta slot-ul dacă se schimbă serviciul în timpul inițializării
            if (!initial.isInitializing) {
                $('#ef-slot').html('<option value="">'+<?php echo json_encode(__('Selectează interval', 'clinica')); ?>+'</option>'); 
            }
            autoAdjustStatus();
        });
        $('#ef-doctor').on('change', function(){ loadDays($(this).val()); });
        $('#ef-date').on('change', function(){ loadSlots(); autoAdjustStatus(); });
        $('#ef-slot').on('change', function(){ autoAdjustStatus(); });

        function formatDateRo(s){ if(!s) return ''; try{ var d=new Date(s); return d.toLocaleDateString('ro-RO'); }catch(e){ return s; } }
        function updateEditFormSummary(){
            var patientName = $('#ef-patient-search').val();
            var patientData = $('#ef-patient-search').data('lastSuggestion') || {};
            var serviceName = $('#ef-service option:selected').text();
            var doctorName = $('#ef-doctor option:selected').text();
            var appointmentDate = formatDateRo($('#ef-date').val());
            var appointmentSlot = $('#ef-slot').val();
            var appointmentStatus = $('#ef-status option:selected').text();
            var appointmentStatusVal = $('#ef-status').val();

            var patientItems = [];
            if ($('#ef-patient').val()) {
                patientItems.push({label:'<?php echo esc_js(__('Nume', 'clinica')); ?>', value:(patientName||'')});
                if (patientData.cnp) patientItems.push({label:'<?php echo esc_js(__('CNP', 'clinica')); ?>', value:patientData.cnp});
                if (patientData.email) patientItems.push({label:'<?php echo esc_js(__('Email', 'clinica')); ?>', value:patientData.email});
                if (patientData.phone) patientItems.push({label:'<?php echo esc_js(__('Telefon', 'clinica')); ?>', value:patientData.phone});
            }
            $('#ef-summary-patient').html(patientItems.map(function(it){ return '<li><span class="label">'+it.label+':</span> <strong>'+ (it.value||'—') +'</strong></li>'; }).join(''));

            var appointmentItems = [];
            if ($('#ef-service').val()) appointmentItems.push({label:'<?php echo esc_js(__('Serviciu', 'clinica')); ?>', value:(serviceName||'')});
            if ($('#ef-doctor').val()) appointmentItems.push({label:'<?php echo esc_js(__('Doctor', 'clinica')); ?>', value:(doctorName||'')});
            if (appointmentDate) appointmentItems.push({label:'<?php echo esc_js(__('Data', 'clinica')); ?>', value:appointmentDate});
            if (appointmentSlot) appointmentItems.push({label:'<?php echo esc_js(__('Interval', 'clinica')); ?>', value:appointmentSlot});
            if (appointmentStatusVal) appointmentItems.push({label:'<?php echo esc_js(__('Status', 'clinica')); ?>', value:'<span class="badge '+appointmentStatusVal+'">'+ appointmentStatus +'</span>'});
            $('#ef-summary-appointment').html(appointmentItems.map(function(it){ return '<li><span class="label">'+it.label+':</span> <strong>'+ it.value +'</strong></li>'; }).join(''));
        }

        function markValidity(){
            function setState($el, ok){ $el.toggleClass('is-valid', !!ok).toggleClass('is-invalid', !ok); }
            setState($('#ef-patient-search'), !!$('#ef-patient').val());
            setState($('#ef-service'), !!$('#ef-service').val());
            setState($('#ef-doctor'), !!$('#ef-doctor').val());
            setState($('#ef-date'), !!$('#ef-date').val());
            setState($('#ef-slot'), !!$('#ef-slot').val());
        }
        $(document).on('change keyup', '#ef-patient,#ef-patient-search,#ef-service,#ef-doctor,#ef-date,#ef-slot,#ef-status', function(){ markValidity(); updateEditFormSummary(); });
        updateEditFormSummary();

        $('#ef-submit').on('click', function(){
            var patient = $('#ef-patient').val(), service = $('#ef-service').val(), doctor=$('#ef-doctor').val(), date=$('#ef-date').val(), slot=$('#ef-slot').val();
            markValidity();
            updateEditFormSummary();
            if (!patient || !service || !doctor || !date || !slot){ $('#ef-feedback').html('<div class="clinica-notice error"><?php echo esc_js(__('Completează toate câmpurile obligatorii.', 'clinica')); ?></div>'); return; }
            var start = (slot||'').split(' - ')[0];
            var duration = (function(){ var sId=parseInt(service,10); var m=services.find(function(s){return parseInt(s.id,10)===sId;}); return (m&&m.duration)?m.duration:(initial.duration||30); })();
            var payload = {
                action:'clinica_admin_update_appointment',
                nonce:'<?php echo wp_create_nonce('clinica_admin_update_appointment_nonce'); ?>',
                appointment_id: initial.id,
                patient_id: patient,
                service_id: service,
                doctor_id: doctor,
                appointment_date: date,
                appointment_time: start,
                duration: duration,
                status: $('#ef-status').val(),
                notes: $('#ef-notes').val(),
                send_email: $('#ef-send-email').is(':checked') ? 1 : 0,
                last_edited_by_type: getCurrentUserType(),
                last_edited_by_user_id: <?php echo get_current_user_id(); ?>,
                last_edited_at: new Date().toISOString()
            };
            $('#ef-submit').addClass('is-loading').prop('disabled', true);
            $.post(ajaxurl, payload, function(resp){
                $('#ef-submit').removeClass('is-loading').prop('disabled', false);
                if (resp && resp.success){
                    $('#ef-feedback').html('<div class="clinica-notice success" role="status"><?php echo esc_js(__('Programare actualizată cu succes. Redirecționare...', 'clinica')); ?></div>');
                    setTimeout(function(){ window.location = '<?php echo admin_url('admin.php?page=clinica-appointments'); ?>'; }, 800);
                } else {
                    var msg = resp && resp.data ? resp.data : '<?php echo esc_js(__('Eroare la actualizare.', 'clinica')); ?>';
                    $('#ef-feedback').html('<div class="clinica-notice error">'+ msg +'</div>');
                }
            }).fail(function(){
                $('#ef-submit').removeClass('is-loading').prop('disabled', false);
                $('#ef-feedback').html('<div class="clinica-notice error"><?php echo esc_js(__('Eroare la actualizare.', 'clinica')); ?></div>');
            });
        });
    });
    </script>
    <?php }
        }
    ?>
    <?php return; endif; ?>

    <?php if ($action === 'new' && Clinica_Patient_Permissions::can_manage_appointments()): ?>
    <div class="clinica-form-page">
    <div class="clinica-card clinica-form" style="flex:1;">
        <div class="form-header">
            <h2 style="margin:0; font-size:18px; line-height:1.4; color:#0a66c2;">
                <?php _e('Adaugă programare', 'clinica'); ?>
            </h2>
            <div class="hint"><?php _e('Completează câmpurile obligatorii marcate cu *', 'clinica'); ?></div>
        </div>
        <?php
            // Pacienți (users >1)
            $patients = $wpdb->get_results("SELECT ID, COALESCE(CONCAT(um1.meta_value,' ',um2.meta_value), display_name) AS name FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} um1 ON u.ID=um1.user_id AND um1.meta_key='first_name' LEFT JOIN {$wpdb->usermeta} um2 ON u.ID=um2.user_id AND um2.meta_key='last_name' WHERE u.ID>1 ORDER BY name");
            // Servicii
            $services = $wpdb->get_results("SELECT id, name, duration FROM {$wpdb->prefix}clinica_services WHERE active = 1 ORDER BY name ASC");
        ?>
        <div class="form-grid">
        <div class="form-group autosuggest-group">
            <label for="af-patient-search" class="label-required"><?php _e('Pacient', 'clinica'); ?></label>
            <input type="text" id="af-patient-search" name="af-patient-search" placeholder="<?php esc_attr_e('Caută pacient (nume, email, CNP, telefon)', 'clinica'); ?>" />
            <input type="hidden" id="af-patient" name="af-patient" />
            <div id="af-patient-suggestions" class="clinica-search-suggestions" style="display:none;"></div>
        </div>
        <div class="form-group">
            <label for="af-service" class="label-required"><?php _e('Serviciu', 'clinica'); ?></label>
            <select id="af-service" data-services='<?php echo json_encode($services); ?>'>
                <option value=""><?php _e('Selectează serviciu', 'clinica'); ?></option>
                <?php foreach ($services as $s): ?>
                <option value="<?php echo (int)$s->id; ?>" data-duration="<?php echo (int)$s->duration; ?>"><?php echo esc_html($s->name); ?> (<?php echo (int)$s->duration; ?> min)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="af-doctor" class="label-required"><?php _e('Doctor', 'clinica'); ?></label>
            <select id="af-doctor">
                <option value=""><?php _e('Selectează doctor', 'clinica'); ?></option>
            </select>
        </div>
        <div class="form-group">
            <label for="af-date" class="label-required"><?php _e('Data', 'clinica'); ?></label>
            <input type="text" id="af-date" placeholder="DD.MM.YYYY" />
        </div>
        <div class="form-group">
            <label for="af-slot" class="label-required"><?php _e('Interval orar', 'clinica'); ?></label>
            <select id="af-slot">
                <option value=""><?php _e('Selectează interval', 'clinica'); ?></option>
            </select>
        </div>
        <div class="form-group">
            <label for="af-status"><?php _e('Status', 'clinica'); ?></label>
            <select id="af-status">
                <option value="confirmed"><?php _e('Acceptat', 'clinica'); ?></option>
                <option value="scheduled"><?php _e('Programată', 'clinica'); ?></option>
            </select>
        </div>
        <div class="form-group">
            <label for="af-created-by"><?php _e('Creat de', 'clinica'); ?></label>
            <select id="af-created-by" name="created_by_type">
                <option value="receptionist"><?php _e('Recepționist', 'clinica'); ?></option>
                <option value="doctor"><?php _e('Doctor', 'clinica'); ?></option>
                <option value="assistant"><?php _e('Asistent', 'clinica'); ?></option>
                <option value="patient"><?php _e('Pacient', 'clinica'); ?></option>
                <option value="admin"><?php _e('Admin WordPress', 'clinica'); ?></option>
                <option value="manager"><?php _e('Manager Clinică', 'clinica'); ?></option>
            </select>
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <label for="af-notes"><?php _e('Observații', 'clinica'); ?></label>
            <textarea id="af-notes" rows="3"></textarea>
            <div class="hint"><?php _e('Informații pentru personalul medical (opțional)', 'clinica'); ?></div>
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
            <div class="toggle-row">
                <span class="toggle-label"><?php _e('Trimite email de confirmare', 'clinica'); ?></span>
                <label class="clinica-toggle-switch" for="af-send-email">
                    <input type="checkbox" id="af-send-email" />
                    <span class="clinica-toggle-slider"></span>
                </label>
            </div>
        </div>
        </div>
        <div class="form-actions actions-right">
            <a class="clinica-button" href="<?php echo admin_url('admin.php?page=clinica-appointments'); ?>"><?php _e('Renunță', 'clinica'); ?></a>
            <button type="button" class="clinica-button clinica-button--primary" id="af-submit"><?php _e('Salvează', 'clinica'); ?></button>
        </div>
        <div id="af-feedback" style="margin-top:10px;"></div>
        </div>
        <aside class="summary-aside">
            <div class="clinica-card summary-card">
                <div class="summary-header">
                    <h3><?php _e('Rezumat', 'clinica'); ?></h3>
                    <span class="summary-sub"><?php _e('Previzualizare programare', 'clinica'); ?></span>
                </div>
                <div class="summary-columns">
                    <div class="summary-section">
                        <h4><?php _e('Pacient', 'clinica'); ?></h4>
                        <ul class="summary-list" id="af-summary-patient"></ul>
                    </div>
                    <div class="summary-section">
                        <h4><?php _e('Programare', 'clinica'); ?></h4>
                        <ul class="summary-list" id="af-summary-appointment"></ul>
                    </div>
                </div>
                
            </div>
        </aside>
    </div>
    </div>

    <script>
    jQuery(function($){
        var services = $('#af-service').data('services') || [];
        // AUTOSUGGEST PACIENT
        var $search = $('#af-patient-search');
        var $hidden = $('#af-patient');
        var $box = $('#af-patient-suggestions');
        function renderSuggestions(items){
            if (!items || !items.length){ $box.hide().empty(); return; }
            var frag = $(document.createDocumentFragment());
            items.forEach(function(it){
                var $i = $('<div/>').addClass('item').attr('data-id', it.id || '').data('payload', it).append(
                    $('<div/>').addClass('title').text(it.name || '')
                ).append(
                    $('<div/>').addClass('meta').text([it.email, it.phone, it.cnp].filter(Boolean).join(' • '))
                );
                $i.on('click', function(){
                    var payload = $(this).data('payload') || it;
                    $hidden.val(payload.id || '');
                    $search.val(payload.name || '');
                    $search.data('lastSuggestion', payload);
                    $box.hide().empty();
                    markValidity(); updateAddFormSummary();
                });
                frag.append($i);
            });
            $box.empty().append(frag).show();
        }
        var suggestTimer = null;
        $search.on('input focus', function(){
            var q = ($search.val()||'').trim();
            if (q.length < 2){ $box.hide().empty(); return; }
            clearTimeout(suggestTimer);
            suggestTimer = setTimeout(function(){
                $.post(clinica_autosuggest && clinica_autosuggest.ajaxurl ? clinica_autosuggest.ajaxurl : ajaxurl, {
                    action:'clinica_search_patients_suggestions',
                    nonce:(clinica_autosuggest && clinica_autosuggest.search_nonce) ? clinica_autosuggest.search_nonce : '<?php echo wp_create_nonce('clinica_search_nonce'); ?>',
                    search_term:q,
                    search_type:'search-input'
                }, function(resp){
                    var items = (resp && resp.success && resp.data && resp.data.suggestions) ? resp.data.suggestions : [];
                    renderSuggestions(items);
                });
            }, 200);
        });
        $(document).on('click', function(e){ if(!$(e.target).closest('#af-patient-search, #af-patient-suggestions').length){ $box.hide(); } });
        function loadDoctors(serviceId){
            $('#af-doctor').html('<option value="">'+<?php echo json_encode(__('Selectează doctor', 'clinica')); ?>+'</option>');
            if (!serviceId) return;
            $.post(ajaxurl, { action:'clinica_get_doctors_for_service', service_id: serviceId, nonce:'<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' }, function(resp){
                if (resp && resp.success && Array.isArray(resp.data)){
                    resp.data.forEach(function(d){ $('#af-doctor').append($('<option/>').val(d.id).text(d.name)); });
                }
            });
        }
        function loadDays(doctorId){
            $('#af-date').val(''); $('#af-slot').html('<option value="">'+<?php echo json_encode(__('Selectează interval', 'clinica')); ?>+'</option>');
            if (!doctorId) return;
            var serviceId = $('#af-service').val() || 0;
            $.post(ajaxurl, { action:'clinica_get_doctor_availability_days', doctor_id: doctorId, service_id: serviceId, nonce:'<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' }, function(resp){
                var days = (resp && resp.success && Array.isArray(resp.data)) ? resp.data : [];
                // Simple datepicker constraint: allow only returned days
                var allowed = days.filter(function(x){ return x && x.date && !x.full; }).map(function(x){ return x.date; });
                try { if ($('#af-date')[0]._flatpickr) { $('#af-date')[0]._flatpickr.destroy(); } } catch(e){}
                function loadScript(src, cb){ var s=document.createElement('script'); s.src=src; s.onload=cb; document.head.appendChild(s); }
                function loadCss(href){ var l=document.createElement('link'); l.rel='stylesheet'; l.href=href; document.head.appendChild(l); }
                if (typeof flatpickr === 'undefined'){
                    loadCss('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
                    loadScript('https://cdn.jsdelivr.net/npm/flatpickr', function(){
                        loadScript('https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ro.js', function(){ initPicker(); });
                    });
                } else { initPicker(); }
                function initPicker(){
                    $('#af-date').flatpickr({
                        // Păstrăm valoarea internă în format ISO pentru backend,
                        // dar afișăm în format românesc în inputul alternativ
                        dateFormat:'Y-m-d',
                        altInput:true,
                        altFormat:'d.m.Y',
                        locale: (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.ro) ? flatpickr.l10ns.ro : 'ro',
                        // Permite programări pe ziua curentă în backend
                        // minDate:'today',
                        disable: [function(date){
                            // Formatează data în local (YYYY-MM-DD) pentru a evita deplasări de timezone cu toISOString()
                            var year = date.getFullYear();
                            var month = String(date.getMonth()+1).padStart(2,'0');
                            var day = String(date.getDate()).padStart(2,'0');
                            var s = year + '-' + month + '-' + day;
                            return allowed.indexOf(s) === -1;
                        }]
                    });
                }
            });
        }
        function loadSlots(){
            var doctorId = $('#af-doctor').val(), day = $('#af-date').val(), serviceId = $('#af-service').val();
            $('#af-slot').html('<option value="">'+<?php echo json_encode(__('Selectează interval', 'clinica')); ?>+'</option>');
            if (!doctorId || !day) return;
            var duration = (function(){ var sId=parseInt(serviceId,10); var m=services.find(function(s){return parseInt(s.id,10)===sId;}); return (m&&m.duration)?m.duration:30; })();
            $.post(ajaxurl, { action:'clinica_get_doctor_slots', doctor_id: doctorId, day: day, duration: duration, service_id: serviceId, nonce:'<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' }, function(resp){
                var slots = (resp && resp.success && Array.isArray(resp.data))?resp.data:[];
                slots.forEach(function(s){ $('#af-slot').append($('<option/>').val(s).text(s)); });
            });
        }
        $('#af-service').on('change', function(){ 
            var serviceId = $(this).val();
            loadDoctors(serviceId); 
            // Reîncarcă zilele cu noul serviciu
            var doctorId = $('#af-doctor').val();
            if (doctorId) {
                loadDays(doctorId);
            }
            $('#af-slot').html('<option value="">'+<?php echo json_encode(__('Selectează interval', 'clinica')); ?>+'</option>'); 
        });
        $('#af-doctor').on('change', function(){ loadDays($(this).val()); });
        $('#af-date').on('change', loadSlots);

        // Rezumat live
        function formatDateRo(s){ if(!s) return ''; try{ var d=new Date(s); return d.toLocaleDateString('ro-RO'); }catch(e){ return s; } }
        function updateAddFormSummary(){
            var p = $('#af-patient-search').val();
            var s = $('#af-service option:selected').text();
            var d = $('#af-doctor option:selected').text();
            var dt = formatDateRo($('#af-date').val());
            var slot = $('#af-slot').val();

            // Pacient
            var patientItems = [];
            if ($('#af-patient').val()) {
                patientItems.push({label:'<?php echo esc_js(__('Nume', 'clinica')); ?>', value:(p||'')});
                var last = $('#af-patient-search').data('lastSuggestion') || {};
                if (last.cnp) patientItems.push({label:'CNP', value:last.cnp});
                if (last.email) patientItems.push({label:'Email', value:last.email});
                if (last.phone) patientItems.push({label:'Telefon', value:last.phone});
            }
            $('#af-summary-patient').html(patientItems.map(function(it){ return '<li><span class="label">'+it.label+':</span> <strong>'+ (it.value||'—') +'</strong></li>'; }).join(''));

            // Programare
            var apptItems = [];
            if ($('#af-service').val()) apptItems.push({label:'<?php echo esc_js(__('Serviciu', 'clinica')); ?>', value:(s||'')});
            if ($('#af-doctor').val()) apptItems.push({label:'<?php echo esc_js(__('Doctor', 'clinica')); ?>', value:(d||'')});
            if (dt) apptItems.push({label:'<?php echo esc_js(__('Data', 'clinica')); ?>', value:dt});
            if (slot) apptItems.push({label:'<?php echo esc_js(__('Interval', 'clinica')); ?>', value:slot});
            var st = $('#af-status').val();
            if (st) apptItems.push({label:'<?php echo esc_js(__('Status', 'clinica')); ?>', value:'<span class="badge '+st+'">'+ $('#af-status option:selected').text() +'</span>'});
            $('#af-summary-appointment').html(apptItems.map(function(it){ return '<li><span class="label">'+it.label+':</span> <strong>'+ it.value +'</strong></li>'; }).join(''));
        }

        function markValidity(){
            function setState($el, ok){ $el.toggleClass('is-valid', !!ok).toggleClass('is-invalid', !ok); }
            setState($('#af-patient-search'), !!$('#af-patient').val());
            setState($('#af-service'), !!$('#af-service').val());
            setState($('#af-doctor'), !!$('#af-doctor').val());
            // Nu marca câmpul de dată ca invalid la încărcarea inițială
            // setState($('#af-date'), !!$('#af-date').val());
            setState($('#af-slot'), !!$('#af-slot').val());
        }
        $(document).on('change keyup', '#af-patient,#af-patient-search,#af-service,#af-doctor,#af-slot,#af-status', function(){ markValidity(); updateAddFormSummary(); });
        
        // Validează data separat, doar când se schimbă
        $('#af-date').on('change', function() {
            var dateValue = $(this).val();
            if (dateValue) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
            updateAddFormSummary();
            loadSlots();
        });
        // Apel inițial
        updateAddFormSummary();

        $('#af-submit').on('click', function(){
            var patient = $('#af-patient').val(), service = $('#af-service').val(), doctor=$('#af-doctor').val(), date=$('#af-date').val(), slot=$('#af-slot').val();
            markValidity();
            updateAddFormSummary();
            if (!patient || !service || !doctor || !date || !slot){ $('#af-feedback').html('<div class="clinica-notice error"><?php echo esc_js(__('Completează toate câmpurile obligatorii.', 'clinica')); ?></div>'); return; }
            var start = (slot||'').split(' - ')[0];
            var duration = (function(){ var sId=parseInt(service,10); var m=services.find(function(s){return parseInt(s.id,10)===sId;}); return (m&&m.duration)?m.duration:30; })();
            var payload = {
                action:'clinica_admin_create_appointment',
                nonce:'<?php echo wp_create_nonce('clinica_admin_create_nonce'); ?>',
                patient_id: patient,
                service_id: service,
                doctor_id: doctor,
                appointment_date: date,
                appointment_time: start,
                duration: duration,
                status: $('#af-status').val(),
                notes: $('#af-notes').val(),
                send_email: $('#af-send-email').is(':checked') ? 1 : 0,
                created_by_type: $('#af-created-by').val(),
                created_by_user_id: <?php echo get_current_user_id(); ?>
            };
            $('#af-submit').addClass('is-loading').prop('disabled', true);
            $.post(ajaxurl, payload, function(resp){
                $('#af-submit').removeClass('is-loading').prop('disabled', false);
                if (resp && resp.success){
                    $('#af-feedback').html('<div class="clinica-notice success" role="status"><?php echo esc_js(__('Programare creată cu succes. Redirecționare...', 'clinica')); ?></div>');
                    setTimeout(function(){ window.location = '<?php echo admin_url('admin.php?page=clinica-appointments'); ?>'; }, 800);
                } else {
                    var msg = resp && resp.data ? resp.data : '<?php echo esc_js(__('Eroare la creare.', 'clinica')); ?>';
                    $('#af-feedback').html('<div class="clinica-notice error">'+ msg +'</div>');
                }
            }).fail(function(){
                $('#af-submit').removeClass('is-loading').prop('disabled', false);
                $('#af-feedback').html('<div class="clinica-notice error"><?php echo esc_js(__('Eroare la creare.', 'clinica')); ?></div>');
            });
        });
    });
    </script>
    <?php return; endif; ?>
    
    <!-- Filtre -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="clinica-appointments">
            
            <div class="alignleft actions">
                <select name="patient_id">
                    <option value=""><?php _e('Toți pacienții', 'clinica'); ?></option>
                    <?php foreach ($patients as $patient): ?>
                    <option value="<?php echo $patient->ID; ?>" <?php selected($patient_id, $patient->ID); ?>>
                        <?php echo esc_html($patient->display_name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="doctor_id">
                    <option value=""><?php _e('Toți doctorii', 'clinica'); ?></option>
                    <?php foreach ($doctors as $doctor): ?>
                    <option value="<?php echo $doctor->ID; ?>" <?php selected($doctor_id, $doctor->ID); ?>>
                        <?php echo esc_html($doctor->display_name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="date" name="date" value="<?php echo esc_attr($date_filter); ?>" placeholder="<?php _e('Data programării', 'clinica'); ?>">
                
                <select name="status">
                    <option value=""><?php _e('Toate statusurile', 'clinica'); ?></option>
                    <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php _e('Acceptat', 'clinica'); ?></option>
                    <option value="scheduled" <?php selected($status_filter, 'scheduled'); ?>><?php _e('Programată', 'clinica'); ?></option>
                    <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completată', 'clinica'); ?></option>
                    <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>><?php _e('Anulată', 'clinica'); ?></option>
                    <option value="no_show" <?php selected($status_filter, 'no_show'); ?>><?php _e('Nu s-a prezentat', 'clinica'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filtrează', 'clinica'); ?>">
                
                <?php if ($patient_id || $doctor_id || $date_filter || $status_filter): ?>
                <a href="<?php echo admin_url('admin.php?page=clinica-appointments'); ?>" class="button">
                    <?php _e('Resetează', 'clinica'); ?>
                </a>
                <?php endif; ?>
            </div>
        </form>
        
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s programare', '%s programări', $total, 'clinica'), number_format_i18n($total)); ?>
            </span>
        </div>
    </div>
    
    <!-- Tabel programări -->
    <table class="wp-list-table widefat fixed striped" style="table-layout: fixed; width: 100%;">
        <thead>
            <tr>
                <th><?php _e('Pacient', 'clinica'); ?></th>
                <th><?php _e('Doctor', 'clinica'); ?></th>
                <th><?php _e('Data', 'clinica'); ?></th>
                <th><?php _e('Ora început', 'clinica'); ?></th>
                <th><?php _e('Ora sfârșit', 'clinica'); ?></th>
                <th><?php _e('Durată', 'clinica'); ?></th>
                <th><?php _e('Serviciu', 'clinica'); ?></th>
                <th><?php _e('Status', 'clinica'); ?></th>
                <th><?php _e('Creat de', 'clinica'); ?></th>
                <th><?php _e('Editat de', 'clinica'); ?></th>
                <th><?php _e('Acțiuni', 'clinica'); ?></th>
            </tr>
        </thead>
        
        <tbody>
            <?php if (empty($appointments)): ?>
            <tr>
                <td colspan="9" style="text-align: center; padding: 20px;">
                    <?php _e('Nu s-au găsit programări.', 'clinica'); ?>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td>
                        <strong>
                            <?php 
                            if (!empty($appointment->patient_name)) {
                                echo esc_html($appointment->patient_name);
                            } else {
                                echo '<em>Pacient necunoscut</em>';
                            }
                            ?>
                        </strong>
                    </td>
                    <td>
                        <?php 
                        if (!empty($appointment->doctor_name)) {
                            echo esc_html($appointment->doctor_name);
                        } else {
                            echo '<em>Doctor necunoscut</em>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        if (!empty($appointment->appointment_date)) {
                            $date_obj = DateTime::createFromFormat('Y-m-d', $appointment->appointment_date);
                            if ($date_obj) {
                                echo esc_html($date_obj->format('d.m.Y'));
                            } else {
                                echo esc_html($appointment->appointment_date);
                            }
                        } else {
                            echo '<em>N/A</em>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        if (!empty($appointment->appointment_time)) {
                            $time_obj = DateTime::createFromFormat('H:i:s', $appointment->appointment_time);
                            if (!$time_obj) { $time_obj = DateTime::createFromFormat('H:i', substr($appointment->appointment_time, 0, 5)); }
                            if ($time_obj) {
                                echo esc_html($time_obj->format('H:i'));
                            } else {
                                echo esc_html(substr($appointment->appointment_time, 0, 5));
                            }
                        } else {
                            echo '<em>N/A</em>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        if (!empty($appointment->appointment_time)) {
                            $start_obj = DateTime::createFromFormat('H:i:s', $appointment->appointment_time);
                            if (!$start_obj) { $start_obj = DateTime::createFromFormat('H:i', substr($appointment->appointment_time, 0, 5)); }
                            $dur = isset($appointment->duration) ? intval($appointment->duration) : 0;
                            if ($start_obj && $dur > 0) {
                                $end_obj = clone $start_obj; $end_obj->modify('+' . $dur . ' minutes');
                                echo esc_html($end_obj->format('H:i'));
                            } else {
                                echo '<em>N/A</em>';
                            }
                        } else {
                            echo '<em>N/A</em>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        if (!empty($appointment->duration)) {
                            echo esc_html(intval($appointment->duration)) . ' min';
                        } else {
                            echo '<em>N/A</em>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        // Determină denumirea serviciului
                        $serviceLabel = '';

                        // 1) service_id prezent
                        if (!empty($appointment->service_id)) {
                            $serviceLabel = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}clinica_services WHERE id = %d", intval($appointment->service_id)));
                        }

                        // 2) fallback: câmpul type (numeric = id, text = denumire)
                        if (empty($serviceLabel) && !empty($appointment->type)) {
                            if (ctype_digit((string)$appointment->type)) {
                                $serviceLabel = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}clinica_services WHERE id = %d", intval($appointment->type)));
                            } else {
                                $serviceLabel = (string) $appointment->type;
                            }
                        }

                        // 3) fallback: deducere după durată
                        if (empty($serviceLabel) && !empty($appointment->duration)) {
                            $serviceLabel = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}clinica_services WHERE duration = %d AND active = 1 ORDER BY name ASC LIMIT 1", intval($appointment->duration)));
                        }

                        if (empty($serviceLabel)) { $serviceLabel = '—'; }
                        echo esc_html($serviceLabel);
                        ?>
                    </td>
                    <td>
                        <span class="appointment-status status-<?php echo esc_attr($appointment->status); ?>">
                            <?php
                            $statusLabels = array(
                                'scheduled' => 'Programată',
                                'confirmed' => 'Acceptat',
                                'completed' => 'Completată',
                                'cancelled' => 'Anulată',
                                'no_show' => 'Nu s-a prezentat'
                            );
                            $statusText = isset($statusLabels[$appointment->status]) ? $statusLabels[$appointment->status] : $appointment->status;
                            echo esc_html($statusText);
                            ?>
                        </span>
                    </td>
                    <td>
                        <span class="created-by created-by-<?php echo esc_attr($appointment->created_by_type ?? 'receptionist'); ?>">
                            <?php
                            $created_by_type = $appointment->created_by_type ?? 'receptionist';
                            
                            if ($created_by_type === 'patient') {
                                echo 'Pacient';
                            } else {
                                // Pentru staff, afișează numele
                                $created_by_user_id = $appointment->created_by ?? null;
                                if ($created_by_user_id) {
                                    $user = get_userdata($created_by_user_id);
                                    if ($user) {
                                        $first_name = get_user_meta($created_by_user_id, 'first_name', true);
                                        $last_name = get_user_meta($created_by_user_id, 'last_name', true);
                                        $full_name = trim($first_name . ' ' . $last_name);
                                        if (empty($full_name)) {
                                            // Pentru admin, afișează username-ul
                                            if ($created_by_type === 'admin') {
                                                $full_name = $user->user_login;
                                            } elseif ($created_by_type === 'manager') {
                                                $full_name = 'Manager Clinică';
                                            } else {
                                                $full_name = $user->display_name;
                                            }
                                        }
                                        echo esc_html($full_name);
                                    } else {
                                        echo 'Utilizator șters';
                                    }
                                } else {
                                    echo 'Necunoscut';
                                }
                            }
                            ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($appointment->last_edited_by_type) && $appointment->last_edited_by_type !== $appointment->created_by_type): ?>
                        <div class="appointment-editor">
                            <span class="edited-by edited-by-<?php echo esc_attr($appointment->last_edited_by_type); ?>">
                                <?php
                                $edited_by_type = $appointment->last_edited_by_type;
                                
                                if ($edited_by_type === 'patient') {
                                    echo 'Pacient';
                                } else {
                                    // Pentru staff, afișează numele
                                    $edited_by_user_id = $appointment->last_edited_by_user_id ?? null;
                                    if ($edited_by_user_id) {
                                        $user = get_userdata($edited_by_user_id);
                                        if ($user) {
                                            $first_name = get_user_meta($edited_by_user_id, 'first_name', true);
                                            $last_name = get_user_meta($edited_by_user_id, 'last_name', true);
                                            $full_name = trim($first_name . ' ' . $last_name);
                                            if (empty($full_name)) {
                                                // Pentru admin, afișează username-ul
                                                if ($edited_by_type === 'admin') {
                                                    $full_name = $user->user_login;
                                                } elseif ($edited_by_type === 'manager') {
                                                    $full_name = 'Manager Clinică';
                                                } else {
                                                    $full_name = $user->display_name;
                                                }
                                            }
                                            echo esc_html($full_name);
                                        } else {
                                            echo 'Utilizator șters';
                                        }
                                    } else {
                                        echo 'Necunoscut';
                                    }
                                }
                                ?>
                                <?php if (!empty($appointment->last_edited_at)): ?>
                                - <?php echo date('d.m.Y H:i', strtotime($appointment->last_edited_at)); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php else: ?>
                        <span class="no-edit">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="clinica-actions">
                            <?php if (Clinica_Patient_Permissions::can_view_appointments()): ?>
                            <span class="view">
                                <a class="clinica-button clinica-button--secondary js-view-appointment" href="#" data-id="<?php echo (int)$appointment->id; ?>">
                                    <?php _e('Vezi', 'clinica'); ?>
                                </a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (Clinica_Patient_Permissions::can_manage_appointments() && $appointment->status !== 'completed'): ?>
                            <span class="edit">
                                <a class="clinica-button clinica-button--secondary" href="<?php echo admin_url('admin.php?page=clinica-appointments&action=edit&id=' . $appointment->id); ?>">
                                    <?php _e('Editează', 'clinica'); ?>
                                </a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (Clinica_Patient_Permissions::can_manage_appointments() && in_array($appointment->status, array('scheduled', 'confirmed'))): ?>
                            <span class="transfer">
                                <a href="#" class="clinica-button clinica-button--secondary js-transfer-appointment" data-id="<?php echo esc_attr($appointment->id); ?>" data-doctor-id="<?php echo esc_attr($appointment->doctor_id); ?>" data-patient-id="<?php echo esc_attr($appointment->patient_id); ?>" data-service-id="<?php echo esc_attr($appointment->service_id ?: $appointment->type); ?>" data-date="<?php echo esc_attr($appointment->appointment_date); ?>" data-time="<?php echo esc_attr(substr($appointment->appointment_time, 0, 5)); ?>" data-duration="<?php echo esc_attr($appointment->duration); ?>">
                                    <?php _e('Mută doctor', 'clinica'); ?>
                                </a>
                            </span>
                            <span class="cancel">
                                <a href="#" class="clinica-button clinica-button--danger js-cancel-appointment" data-id="<?php echo esc_attr($appointment->id); ?>">
                                    <?php _e('Anulează', 'clinica'); ?>
                                </a>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Paginare -->
    <?php if ($total_pages > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $page
            ));
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.appointment-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-scheduled {
    background-color: #e7f3ff;
    color: #0073aa;
}

.status-confirmed {
    background-color: #d4edda;
    color: #155724;
}

.status-completed {
    background-color: #d1ecf1;
    color: #0c5460;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
}

.status-no_show {
    background-color: #fff3cd;
    color: #856404;
}

        /* Acțiuni Clinica - izolate de CSS WordPress */
        .clinica-actions {
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }
        /* Stil pentru buton roșu (anulare) în stil WP */
        .button.button-danger {
            background: #dc3232;
            border-color: #dc3232;
            color: #fff;
        }
        .button.button-danger:hover {
            background: #b91d1d;
            border-color: #b91d1d;
            color: #fff;
        }
</style>

<div class="clinica-modal-backdrop" id="clinica-appointment-modal" style="display:none;">
    <div class="clinica-modal">
        <div class="clinica-modal__header">
            <h2 class="clinica-modal__title"><?php _e('Detalii programare', 'clinica'); ?></h2>
            <button type="button" class="clinica-modal__close" aria-label="<?php esc_attr_e('Închide', 'clinica'); ?>">×</button>
        </div>
        <div class="clinica-modal__body" id="clinica-appointment-modal-body">
            <p style="margin:0; color:#6b7280;"><?php _e('Se încarcă...', 'clinica'); ?></p>
        </div>
        <div class="clinica-modal__footer">
            <button type="button" class="clinica-button" id="clinica-appointment-modal-close"><?php _e('Închide', 'clinica'); ?></button>
        </div>
    </div>
</div>

<!-- Modal pentru mutarea programărilor -->
<div class="clinica-modal-backdrop" id="clinica-transfer-modal" style="display:none;">
    <div class="clinica-modal">
        <div class="clinica-modal__header">
            <h2 class="clinica-modal__title"><?php _e('Mută programarea la alt doctor', 'clinica'); ?></h2>
            <button type="button" class="clinica-modal__close" aria-label="<?php esc_attr_e('Închide', 'clinica'); ?>">×</button>
        </div>
        <div class="clinica-modal__body">
            <div class="transfer-info">
                <h3><?php _e('Programare curentă', 'clinica'); ?></h3>
                <div class="current-appointment-info" id="current-appointment-info">
                    <p><strong><?php _e('Pacient:', 'clinica'); ?></strong> <span id="transfer-patient-name">—</span></p>
                    <p><strong><?php _e('Serviciu:', 'clinica'); ?></strong> <span id="transfer-service-name">—</span></p>
                    <p><strong><?php _e('Data:', 'clinica'); ?></strong> <span id="transfer-date">—</span></p>
                    <p><strong><?php _e('Ora:', 'clinica'); ?></strong> <span id="transfer-time">—</span></p>
                    <p><strong><?php _e('Doctor curent:', 'clinica'); ?></strong> <span id="transfer-current-doctor">—</span></p>
                </div>
            </div>
            
            <div class="transfer-form">
                <h3><?php _e('Selectează noul doctor', 'clinica'); ?></h3>
                <div class="form-group">
                    <label><?php _e('Doctor nou', 'clinica'); ?> <span class="required">*</span></label>
                    <div id="transfer-doctors" class="doctors-grid">
                        <!-- doctor buttons rendered here -->
                    </div>
                </div>
                
                <!-- Layout cu 2 coloane pentru calendar și sloturi -->
                <div class="transfer-layout">
                    <div class="transfer-left-column">
                <div class="form-group">
                            <label for="transfer-date-picker"><?php _e('Data programării', 'clinica'); ?> <span class="required">*</span></label>
                            <div id="transfer-calendar">
                                <input type="text" id="transfer-date-picker" readonly />
                            </div>
                        </div>
                </div>
                
                    <div class="transfer-right-column">
                <div class="form-group">
                            <label><?php _e('Interval orar', 'clinica'); ?> <span class="required">*</span></label>
                            <div id="transfer-slots" class="slots-grid">
                                <!-- slot buttons rendered here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="transfer-notes"><?php _e('Observații (opțional)', 'clinica'); ?></label>
                    <textarea id="transfer-notes" rows="3" placeholder="<?php esc_attr_e('Motivul mutării...', 'clinica'); ?>"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="transfer-send-email" checked />
                        <span class="checkmark"></span>
                        <?php _e('Trimite email de notificare către pacient', 'clinica'); ?>
                    </label>
                </div>
            </div>
        </div>
        <div class="clinica-modal__footer">
            <button type="button" class="clinica-button" id="transfer-modal-cancel"><?php _e('Anulează', 'clinica'); ?></button>
            <button type="button" class="clinica-button clinica-button--primary" id="transfer-modal-confirm" disabled><?php _e('Mută programarea', 'clinica'); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handler pentru anularea programărilor din admin
    $(document).on('click', '.js-cancel-appointment', function(e) {
        e.preventDefault();
        
        var appointmentId = $(this).data('id');
        var row = $(this).closest('tr');
        
        if (!confirm('Sigur doriți să anulați această programare?')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_cancel_appointment',
                appointment_id: appointmentId,
                nonce: '<?php echo wp_create_nonce('clinica_admin_cancel_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Actualizează statusul în tabel
                    var statusCell = row.find('.appointment-status');
                    statusCell.removeClass('status-scheduled status-confirmed').addClass('status-cancelled');
                    statusCell.text('Anulată');
                    
                    // Actualizează coloana "Editat de"
                    var editedByCell = row.find('td:nth-child(10)'); // Coloana "Editat de"
                    var now = new Date();
                    var dateStr = now.toLocaleDateString('ro-RO') + ' ' + now.toLocaleTimeString('ro-RO', {hour: '2-digit', minute: '2-digit'});
                    editedByCell.html('<div class="appointment-editor"><span class="edited-by edited-by-admin">default - ' + dateStr + '</span></div>');
                    
                    // Elimină butonul de anulare
                    row.find('.js-cancel-appointment').parent().remove();
                    
                    // Afișează mesaj de succes
                    alert('Programarea a fost anulată cu succes!');
                } else {
                    alert('Eroare la anulare: ' + (response.data || 'Eroare necunoscută'));
                }
            },
            error: function() {
                alert('Eroare la anulare. Vă rugăm să încercați din nou.');
            }
        });
    });

    // Vezi programare în modal
    function openAppointmentModal() {
        $('#clinica-appointment-modal').addClass('is-visible').show();
    }
    function closeAppointmentModal() {
        $('#clinica-appointment-modal').removeClass('is-visible').hide();
    }
    $(document).on('click', '.clinica-modal__close, #clinica-appointment-modal-close', function() {
        closeAppointmentModal();
    });
    $(document).on('click', '#clinica-appointment-modal', function(e) {
        if (e.target === this) { closeAppointmentModal(); }
    });

    $(document).on('click', '.js-view-appointment', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#clinica-appointment-modal-body').html('<p style="margin:0; color:#6b7280;"><?php echo esc_js(__('Se încarcă...', 'clinica')); ?></p>');
        openAppointmentModal();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_get_appointment',
                id: id,
                nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#clinica-appointment-modal-body').html(response.data);
                } else {
                    $('#clinica-appointment-modal-body').html('<p style="color:#b91c1c;">' + (response.data || '<?php echo esc_js(__('Eroare la încărcare.', 'clinica')); ?>') + '</p>');
                }
            },
            error: function() {
                $('#clinica-appointment-modal-body').html('<p style="color:#b91c1c;"><?php echo esc_js(__('Eroare la încărcare.', 'clinica')); ?></p>');
            }
        });
    });

    // Rezumat live pentru formular Adaugă
    function formatDateRo(s){ if(!s) return ''; try{ var d=new Date(s); return d.toLocaleDateString('ro-RO'); }catch(e){ return s; } }
    function updateAddFormSummary(){
        var p = jQuery('#af-patient-search').val();
        var s = jQuery('#af-service option:selected').text();
        var d = jQuery('#af-doctor option:selected').text();
        var dt = formatDateRo(jQuery('#af-date').val());
        var slot = jQuery('#af-slot').val();
        var parts = [];
        if (jQuery('#af-patient').val()) parts.push('<?php echo esc_js(__('Pacient', 'clinica')); ?>: <strong>'+ (p||'') +'</strong>');
        if (s && jQuery('#af-service').val()) parts.push('<?php echo esc_js(__('Serviciu', 'clinica')); ?>: <strong>'+s+'</strong>');
        if (d && jQuery('#af-doctor').val()) parts.push('<?php echo esc_js(__('Doctor', 'clinica')); ?>: <strong>'+d+'</strong>');
        if (dt) parts.push('<?php echo esc_js(__('Data', 'clinica')); ?>: <strong>'+dt+'</strong>');
        if (slot) parts.push('<?php echo esc_js(__('Interval', 'clinica')); ?>: <strong>'+slot+'</strong>');
        jQuery('#af-summary').html(parts.join(' • '));
    }
    jQuery(document).on('change keyup', '#af-patient,#af-patient-search,#af-service,#af-doctor,#af-date,#af-slot', updateAddFormSummary);
    updateAddFormSummary();

    // Funcționalitatea de mutare programări
    var transferData = {};
    
    // Deschide modalul de mutare
    $(document).on('click', '.js-transfer-appointment', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        transferData = {
            id: $btn.data('id'),
            doctorId: $btn.data('doctor-id'),
            patientId: $btn.data('patient-id'),
            serviceId: $btn.data('service-id'),
            date: $btn.data('date'),
            time: $btn.data('time'),
            duration: $btn.data('duration')
        };
        
        // Populează informațiile curente
        var row = $btn.closest('tr');
        var patientName = row.find('td:first strong').text();
        var currentDoctor = row.find('td:nth-child(2)').text();
        var appointmentDate = row.find('td:nth-child(3)').text();
        var appointmentTime = row.find('td:nth-child(4)').text();
        var serviceName = row.find('td:nth-child(7)').text();
        
        $('#transfer-patient-name').text(patientName);
        $('#transfer-current-doctor').text(currentDoctor);
        $('#transfer-date').text(appointmentDate);
        $('#transfer-time').text(appointmentTime);
        $('#transfer-service-name').text(serviceName);
        
        // Setează data în input (convertește din Y-m-d la formatul input-ului date)
        var dateInput = transferData.date;
        $('#transfer-date-picker').val(dateInput);
        
        // Încarcă doctorii disponibili
        loadTransferDoctors();
        
        // Deschide modalul
        $('#clinica-transfer-modal').addClass('is-visible').show();
    });
    
    // Încarcă doctorii pentru serviciul selectat
    function loadTransferDoctors() {
        console.log('🔍 DEBUG: loadTransferDoctors() - START');
        console.log('🔍 DEBUG: transferData =', transferData);
        
        var grid = $('#transfer-doctors');
        grid.html('<div class="doctor-btn disabled">Se încarcă...</div>');
        
        $.post(ajaxurl, {
            action: 'clinica_get_doctors_for_service',
            service_id: transferData.serviceId,
            nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>'
        }, function(resp) {
            console.log('🔍 DEBUG: loadTransferDoctors() - AJAX SUCCESS');
            console.log('🔍 DEBUG: resp =', resp);
            
            grid.empty();
            
            if (resp && resp.success && Array.isArray(resp.data) && resp.data.length > 0) {
                console.log('🔍 DEBUG: Found doctors:', resp.data.length);
                
                resp.data.forEach(function(doctor) {
                    // Exclude doctorul curent
                    if (parseInt(doctor.id) !== parseInt(transferData.doctorId)) {
                        console.log('🔍 DEBUG: Creating button for doctor:', doctor.name, 'ID:', doctor.id);
                        
                        var btn = $('<div/>').addClass('doctor-btn').text(doctor.name).attr('data-doctor-id', doctor.id);
                        btn.on('click', function() {
                            console.log('🔍 DEBUG: Doctor button clicked:', doctor.name, 'ID:', doctor.id);
                            
                            $('.doctor-btn').removeClass('selected');
                            $(this).addClass('selected');
                            transferData.selectedDoctorId = doctor.id;
                            
                            console.log('🔍 DEBUG: transferData.selectedDoctorId =', transferData.selectedDoctorId);
                            
                            // Încarcă calendarul și sloturile pentru noul doctor
                            loadTransferAvailableDays(doctor.id, transferData.serviceId);
                            if (transferData.date) {
                                loadTransferSlots(doctor.id);
                            }
                            
                            validateTransferForm();
                        });
                        grid.append(btn);
                    }
                });
                
                // Nu selecta automat primul doctor - lasă utilizatorul să aleagă
            } else {
                console.log('🔍 DEBUG: No doctors available');
                grid.append('<div class="doctor-btn disabled">Nu există doctori disponibili</div>');
            }
        }).fail(function(xhr, status, error) {
            console.error('🔍 DEBUG: loadTransferDoctors() - AJAX ERROR');
            console.error('🔍 DEBUG: xhr =', xhr);
            console.error('🔍 DEBUG: status =', status);
            console.error('🔍 DEBUG: error =', error);
            
            grid.html('<div class="doctor-btn disabled">Eroare la încărcare</div>');
        });
    }
    
    // Când se schimbă data, resetează doctorul și sloturile
    $('#transfer-date-picker').on('change', function() {
        var newDate = $(this).val();
        if (newDate) {
            transferData.date = newDate;
            // Resetează selecțiile
            $('#transfer-doctors').html('');
            $('#transfer-slots').html('');
            $('#transfer-modal-confirm').prop('disabled', true);
            transferData.selectedSlot = '';
            transferData.selectedDoctorId = '';
            // Reîncarcă doctorii pentru noua dată
            loadTransferDoctors();
        }
    });
    
    // Funcția de schimbare a doctorului este acum gestionată în loadTransferDoctors()
    
    // Încarcă zilele disponibile pentru doctorul selectat
    function loadTransferAvailableDays(doctorId, serviceId) {
        console.log('🔍 DEBUG: loadTransferAvailableDays() - START');
        console.log('🔍 DEBUG: doctorId =', doctorId);
        console.log('🔍 DEBUG: serviceId =', serviceId);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: { 
                action: 'clinica_get_doctor_availability_days', 
                doctor_id: doctorId, 
                service_id: serviceId || 0, 
                nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>' 
            },
            success: function(resp){
                console.log('🔍 DEBUG: loadTransferAvailableDays() - AJAX SUCCESS');
                console.log('🔍 DEBUG: resp =', resp);
                
                var days = (resp && resp.success && Array.isArray(resp.data)) ? resp.data : [];
                console.log('🔍 DEBUG: Available days =', days);
                
                renderTransferCalendar(days);
            },
            error: function(xhr, status, error){
                console.error('🔍 DEBUG: loadTransferAvailableDays() - AJAX ERROR');
                console.error('🔍 DEBUG: xhr =', xhr);
                console.error('🔍 DEBUG: status =', status);
                console.error('🔍 DEBUG: error =', error);
                
                renderTransferCalendar([]); 
            }
        });
    }
    
    // Render calendarul de transfer cu zilele disponibile
    function renderTransferCalendar(days) {
        console.log('🔍 DEBUG: renderTransferCalendar() - START');
        console.log('🔍 DEBUG: days =', days);
        
        var container = document.getElementById('transfer-calendar');
        if (!container) {
            console.error('🔍 DEBUG: transfer-calendar container not found!');
            return;
        }
        
        // Dacă nu există zile disponibile, afișează mesaj
        if (!days || days.length === 0) {
            console.log('🔍 DEBUG: No days available, showing message');
            container.innerHTML = '<div class="no-availability">Nu există zile disponibile pentru acest doctor și serviciu</div>';
            return;
        }
        
        console.log('🔍 DEBUG: Rendering calendar with', days.length, 'days');
        
        // Creează input-ul pentru Flatpickr dacă nu există
        var input = document.getElementById('transfer-date-picker');
        if (!input) {
            container.innerHTML = '<input type="text" id="transfer-date-picker" readonly />';
            input = document.getElementById('transfer-date-picker');
        }
        
        // Distruge instanța existentă
        if (input._flatpickr) {
            try { 
                input._flatpickr.destroy(); 
            } catch(e) {}
        }
        
        // Pregătește zilele disponibile
        var available = {};
        (days || []).forEach(function(rec) { 
            var d = (typeof rec === 'string') ? rec : rec.date; 
            available[d] = rec; 
        });
        
        var keys = Object.keys(available);
        var minDate = keys.length ? keys[0] : 'today';
        var defaultDate = keys.length ? keys[0] : 'today';
        
        // Verifică dacă Flatpickr este disponibil
        if (typeof flatpickr === 'undefined') {
            loadFlatpickrForTransfer();
            return;
        }
        
        initTransferFlatpickr(input, available, keys, minDate, defaultDate);
    }
    
    // Încarcă Flatpickr pentru transfer
    function loadFlatpickrForTransfer() {
        function loadScript(src, cb) { 
            var s = document.createElement('script'); 
            s.src = src; 
            s.onload = cb; 
            document.head.appendChild(s); 
        }
        function loadCSS(href) { 
            var l = document.createElement('link'); 
            l.rel = 'stylesheet'; 
            l.href = href; 
            document.head.appendChild(l); 
        }
        
        loadCSS('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
        loadCSS('https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css');
        loadScript('https://cdn.jsdelivr.net/npm/flatpickr', function(){
            loadScript('https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ro.js', function(){
                loadScript('<?php echo CLINICA_PLUGIN_URL; ?>assets/js/romanian-holidays.js', function(){
                    // Re-render calendarul după ce Flatpickr s-a încărcat
                    var doctorId = transferData.selectedDoctorId;
                    var serviceId = transferData.serviceId;
                    if (doctorId) {
                        loadTransferAvailableDays(doctorId, serviceId);
                    }
                });
            });
        });
    }
    
    // Inițializează Flatpickr pentru transfer
    function initTransferFlatpickr(input, available, keys, minDate, defaultDate) {
        input._flatpickr = flatpickr(input, {
            locale: (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.ro) ? 'ro' : undefined,
            dateFormat: 'Y-m-d',
            minDate: minDate,
            maxDate: null,
            defaultDate: defaultDate,
            inline: true,
            allowInput: false,
            appendTo: document.getElementById('transfer-calendar'),
            showMonths: 1,
            static: true,
            disable: [function(date){
                // Dezactivează weekend-urile și datele indisponibile
                if (date.getDay() === 0 || date.getDay() === 6) return true;
                
                var s = date.getFullYear() + '-' + 
                       String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(date.getDate()).padStart(2, '0');
                var isAvailable = available[s] && !available[s].full;
                return !isAvailable;
            }],
            onDayCreate: function(dObj, dStr, fp, dayElem){
                if (!dayElem || !dayElem.dateObj) return;
                
                var s = dayElem.dateObj.getFullYear() + '-' + 
                       String(dayElem.dateObj.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(dayElem.dateObj.getDate()).padStart(2, '0');
                
                // Verifică sărbătorile legale românești
                if (window.ClinicaRomanianHolidays && window.ClinicaRomanianHolidays.isHoliday) {
                    if (window.ClinicaRomanianHolidays.isHoliday(s)) {
                        dayElem.classList.add('legal-holiday');
                        dayElem.title = window.ClinicaRomanianHolidays.getHolidayName ? window.ClinicaRomanianHolidays.getHolidayName(s) : 'Sărbătoare';
                    }
                }
                
                if (dayElem.dateObj.getDay() === 0 || dayElem.dateObj.getDay() === 6) { 
                    dayElem.classList.add('weekend'); 
                }
                if (available[s] && available[s].full) { 
                    dayElem.classList.add('full'); 
                    dayElem.title = 'Zi plină'; 
                }
            },
            onChange: function(selectedDates, dateStr, fp){
                if (!dateStr) return;
                $('#transfer-date-picker').val(dateStr);
                transferData.date = dateStr;
                validateTransferForm();
            }
        });
        
        // Setează prima zi disponibilă
        if (keys.length && input._flatpickr) { 
            input._flatpickr.setDate(keys[0], true); 
        }
    }
    
    // Resetează calendarul de transfer
    function resetTransferCalendar() {
        var input = document.getElementById('transfer-date-picker');
        if (input && input._flatpickr) {
            try {
                input._flatpickr.destroy();
                input._flatpickr = null;
            } catch(e) {}
        }
        var container = document.getElementById('transfer-calendar');
        if (container) {
            container.innerHTML = '<input type="text" id="transfer-date-picker" readonly />';
        }
    }
    
    // Încarcă sloturile disponibile pentru doctorul selectat
    function loadTransferSlots(doctorId) {
        console.log('🔍 DEBUG: loadTransferSlots() - START');
        console.log('🔍 DEBUG: doctorId =', doctorId);
        console.log('🔍 DEBUG: transferData.date =', transferData.date);
        console.log('🔍 DEBUG: transferData.duration =', transferData.duration);
        
        var grid = $('#transfer-slots');
        grid.html('<div class="slot-btn disabled">Se încarcă...</div>');
        
        $.post(ajaxurl, {
            action: 'clinica_get_doctor_slots',
            doctor_id: doctorId,
            day: transferData.date,
            duration: transferData.duration,
            service_id: transferData.serviceId,
            nonce: '<?php echo wp_create_nonce('clinica_dashboard_nonce'); ?>'
        }, function(resp) {
            console.log('🔍 DEBUG: loadTransferSlots() - AJAX SUCCESS');
            console.log('🔍 DEBUG: resp =', resp);
            
            grid.empty();
            
            if (resp && resp.success && Array.isArray(resp.data) && resp.data.length > 0) {
                console.log('🔍 DEBUG: Found slots:', resp.data.length);
                
                resp.data.forEach(function(slot) {
                    var btn = $('<div/>').addClass('slot-btn').text(slot).attr('data-slot', slot);
                    btn.on('click', function() {
                        console.log('🔍 DEBUG: Slot clicked:', slot);
                        $('.slot-btn').removeClass('selected');
                        $(this).addClass('selected');
                        transferData.selectedSlot = slot;
                        validateTransferForm();
                    });
                    grid.append(btn);
                });
                
                // Încearcă să selecteze slotul original
                var originalSlot = transferData.time + ' - ' + getEndTime(transferData.time, transferData.duration);
                if (resp.data.includes(originalSlot)) {
                    grid.find('[data-slot="' + originalSlot + '"]').addClass('selected');
                    transferData.selectedSlot = originalSlot;
                }
            } else {
                console.log('🔍 DEBUG: No slots available');
                grid.append('<div class="slot-btn disabled">Nu există sloturi disponibile</div>');
            }
            
            validateTransferForm();
        }).fail(function(xhr, status, error) {
            console.error('🔍 DEBUG: loadTransferSlots() - AJAX ERROR');
            console.error('🔍 DEBUG: xhr =', xhr);
            console.error('🔍 DEBUG: status =', status);
            console.error('🔍 DEBUG: error =', error);
            
            grid.html('<div class="slot-btn disabled">Eroare la încărcare</div>');
        });
    }
    
    // Calculează ora de sfârșit
    function getEndTime(startTime, duration) {
        var start = new Date('2000-01-01T' + startTime + ':00');
        var end = new Date(start.getTime() + (duration * 60000));
        return end.toTimeString().substr(0, 5);
    }
    
    // Validează formularul de mutare
    function validateTransferForm() {
        var date = $('#transfer-date-picker').val();
        var doctor = transferData.selectedDoctorId || '';
        var slot = transferData.selectedSlot || '';
        var isValid = date && doctor && slot;
        
        console.log('🔍 DEBUG: validateTransferForm()');
        console.log('🔍 DEBUG: date =', date);
        console.log('🔍 DEBUG: doctor =', doctor);
        console.log('🔍 DEBUG: slot =', slot);
        console.log('🔍 DEBUG: isValid =', isValid);
        
        $('#transfer-modal-confirm').prop('disabled', !isValid);
    }
    
    // Validează la schimbarea câmpurilor
    $('#transfer-date-picker').on('change', validateTransferForm);
    
    // Confirmă mutarea
    $('#transfer-modal-confirm').on('click', function() {
        var newDate = $('#transfer-date-picker').val();
        var doctorId = transferData.selectedDoctorId || '';
        var slot = transferData.selectedSlot || '';
        var notes = $('#transfer-notes').val();
        var sendEmail = $('#transfer-send-email').is(':checked');
        
        if (!newDate || !doctorId || !slot) {
            alert('<?php echo esc_js(__('Completează toate câmpurile obligatorii.', 'clinica')); ?>');
            return;
        }
        
        var startTime = slot.split(' - ')[0];
        
        // Confirmă mutarea
        if (!confirm('<?php echo esc_js(__('Sigur doriți să mutați această programare la noul doctor?', 'clinica')); ?>')) {
            return;
        }
        
        $(this).prop('disabled', true).addClass('is-loading').text('<?php echo esc_js(__('Se mută...', 'clinica')); ?>');
        
        // Folosește noua funcție dedicată de transfer
        $.post(ajaxurl, {
            action: 'clinica_admin_transfer_appointment',
            nonce: '<?php echo wp_create_nonce('clinica_admin_transfer_appointment_nonce'); ?>',
            appointment_id: transferData.id,
            new_doctor_id: doctorId,
            new_date: newDate,
            new_time: startTime,
            transfer_notes: notes,
            send_email: sendEmail ? 1 : 0
        }, function(resp) {
            $('#transfer-modal-confirm').prop('disabled', false).removeClass('is-loading').text('<?php echo esc_js(__('Mută programarea', 'clinica')); ?>');
            
            if (resp && resp.success) {
                // Afișează mesaj de succes
                if (resp.data && resp.data.message) {
                    alert(resp.data.message);
                }
                
                // Închide modalul
                closeTransferModal();
                
                // Reîncarcă pagina pentru a actualiza tabelul
                window.location.reload();
            } else {
                var msg = resp && resp.data ? resp.data : '<?php echo esc_js(__('Eroare la mutarea programării.', 'clinica')); ?>';
                alert(msg);
            }
        }).fail(function() {
            $('#transfer-modal-confirm').prop('disabled', false).removeClass('is-loading').text('<?php echo esc_js(__('Mută programarea', 'clinica')); ?>');
            alert('<?php echo esc_js(__('Eroare la mutarea programării.', 'clinica')); ?>');
        });
    });
    
    // Funcții pentru modalul de mutare
    function openTransferModal() {
        $('#clinica-transfer-modal').addClass('is-visible').show();
    }
    
    function closeTransferModal() {
        $('#clinica-transfer-modal').removeClass('is-visible').hide();
        // Resetează formularul
        $('#transfer-date-picker').val('');
        $('#transfer-doctors').html('');
        $('#transfer-slots').html('');
        $('#transfer-notes').val('');
        $('#transfer-send-email').prop('checked', true);
        $('#transfer-modal-confirm').prop('disabled', true);
        transferData.selectedSlot = '';
        transferData.selectedDoctorId = '';
        // Resetează calendarul
        resetTransferCalendar();
    }
    
    // Event handlers pentru modalul de mutare
    $(document).on('click', '#transfer-modal-cancel, #clinica-transfer-modal .clinica-modal__close', function() {
        closeTransferModal();
    });
    
    // Nu se închide modalul când dai click pe backdrop - doar pe butoanele de închidere
});
</script>

<style>
/* Stilizare pentru coloana "Creat de" */
.appointment-creator {
    display: flex;
    flex-direction: column;
    gap: 2px;
    width: fit-content;
    max-width: 120px;
}

.created-by, .edited-by {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    display: inline-block;
    line-height: 1;
    width: fit-content;
    white-space: nowrap;
    box-sizing: border-box;
}

.created-by-patient { background: #e3f2fd; color: #1976d2; }
.created-by-doctor { background: #f3e5f5; color: #7b1fa2; }
.created-by-assistant { background: #e8f5e8; color: #388e3c; }
.created-by-receptionist { background: #e0f7fa; color: #00695c; }
.created-by-admin { background: #ffebee; color: #c62828; }
.created-by-manager { background: #fff8e1; color: #f57f17; }

.edited-by-patient { background: #e3f2fd; color: #1976d2; }
.edited-by-doctor { background: #f3e5f5; color: #7b1fa2; }
.edited-by-assistant { background: #e8f5e8; color: #388e3c; }
.edited-by-receptionist { background: #e0f7fa; color: #00695c; }
.edited-by-admin { background: #ffebee; color: #c62828; }
.edited-by-manager { background: #fff8e1; color: #f57f17; }

.last-edited {
    color: #666;
    font-size: 10px;
    margin-top: 2px;
}

.edit-date {
    color: #999;
    font-size: 9px;
}

.last-edited i {
    margin-right: 4px;
}

/* Stilizare pentru coloana "Editat de" */
.appointment-editor {
    display: flex;
    flex-direction: column;
    gap: 2px;
    width: fit-content;
    max-width: 120px;
}

.no-edit {
    color: #999;
    font-style: italic;
}

.edit-date-inline {
    color: #000 !important;
    font-weight: normal;
    font-size: 11px;
    background: none !important;
    padding: 0 !important;
    border-radius: 0 !important;
    border: none !important;
}

/* Lățimi coloane pentru tabelul de programări */
.wp-list-table th:nth-child(1), .wp-list-table td:nth-child(1) { width: 12%; } /* Pacient */
.wp-list-table th:nth-child(2), .wp-list-table td:nth-child(2) { width: 10%; } /* Doctor */
.wp-list-table th:nth-child(3), .wp-list-table td:nth-child(3) { width: 6%; }  /* Data */
.wp-list-table th:nth-child(4), .wp-list-table td:nth-child(4) { width: 5%; }  /* Ora început */
.wp-list-table th:nth-child(5), .wp-list-table td:nth-child(5) { width: 5%; }  /* Ora sfârșit */
.wp-list-table th:nth-child(6), .wp-list-table td:nth-child(6) { width: 4%; }  /* Durată */
.wp-list-table th:nth-child(7), .wp-list-table td:nth-child(7) { width: 12%; } /* Serviciu */
.wp-list-table th:nth-child(8), .wp-list-table td:nth-child(8) { width: 6%; }  /* Status */
.wp-list-table th:nth-child(9), .wp-list-table td:nth-child(9) { width: 12%; } /* Creat de */
.wp-list-table th:nth-child(10), .wp-list-table td:nth-child(10) { width: 12%; } /* Editat de */
.wp-list-table th:nth-child(11), .wp-list-table td:nth-child(11) { width: 16%; } /* Acțiuni */

/* Stilizare pentru coloanele restrânse */
.wp-list-table td:nth-child(3), .wp-list-table td:nth-child(4), .wp-list-table td:nth-child(5), .wp-list-table td:nth-child(6) {
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Stilizare pentru coloanele "Creat de" și "Editat de" */
.wp-list-table td:nth-child(9), .wp-list-table td:nth-child(10) {
    font-size: 11px;
    line-height: 1.3;
    padding: 8px 6px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 0;
}

/* Stilizare pentru textul din coloanele "Creat de" și "Editat de" */
.wp-list-table td:nth-child(9) .created-by,
.wp-list-table td:nth-child(10) .edited-by {
    display: block;
    white-space: normal;
    word-break: break-word;
    max-width: 100%;
}

/* Stilizare pentru timestamp-urile din "Editat de" */
.wp-list-table td:nth-child(10) .edited-by {
    font-size: 10px;
    color: #666;
    margin-top: 2px;
}

/* Îmbunătățire spațiere tabel */
.wp-list-table {
    table-layout: fixed;
    width: 100%;
}

.wp-list-table th,
.wp-list-table td {
    padding: 8px 6px;
    vertical-align: top;
}

.wp-list-table th {
    font-weight: 600;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

/* Butoanele de acțiuni normale */
.clinica-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

/* Stiluri pentru modalul de mutare */
.transfer-info {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.transfer-info h3 {
    margin: 0 0 12px 0;
    color: #495057;
    font-size: 16px;
}

.current-appointment-info p {
    margin: 8px 0;
    font-size: 14px;
}

.current-appointment-info strong {
    color: #495057;
    font-weight: 600;
}

.transfer-form h3 {
    margin: 0 0 16px 0;
    color: #495057;
    font-size: 16px;
}

.transfer-form .form-group {
    margin-bottom: 16px;
}

.transfer-form label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #495057;
}

.transfer-form .required {
    color: #dc3545;
}

.transfer-form select,
.transfer-form input,
.transfer-form textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.transfer-form select:focus,
.transfer-form input:focus,
.transfer-form textarea:focus {
    outline: none;
    border-color: #0a66c2;
    box-shadow: 0 0 0 2px rgba(10, 102, 194, 0.25);
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
}

.checkmark {
    margin-left: 4px;
}

/* Stiluri pentru butonul de mutare */
.js-transfer-appointment {
    background: #6c757d;
    border-color: #6c757d;
    color: #fff;
}

.js-transfer-appointment:hover {
    background: #5a6268;
    border-color: #545b62;
    color: #fff;
}

/* Loading states pentru transfer */
#transfer-modal-confirm.is-loading {
    position: relative;
    color: transparent !important;
}

#transfer-modal-confirm.is-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid #ffffff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Stiluri pentru formularul de transfer */
.transfer-form .form-group {
    margin-bottom: 15px;
}

.transfer-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.transfer-form .required {
    color: #dc3545;
}

.transfer-form input[type="date"],
.transfer-form select,
.transfer-form textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.transfer-form input[type="date"]:focus,
.transfer-form select:focus,
.transfer-form textarea:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.2);
}

/* Stiluri pentru informațiile programării curente */
.transfer-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.transfer-info h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.current-appointment-info p {
    margin: 5px 0;
    color: #666;
}

/* Stiluri pentru calendarul de transfer */
#transfer-calendar {
    min-height: 350px;
    max-height: 400px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background: #fff;
    overflow: visible;
    width: 100%;
    box-sizing: border-box;
}

#transfer-calendar .no-availability {
    text-align: center;
    color: #666;
    padding: 20px;
    font-style: italic;
}

#transfer-calendar .flatpickr-calendar {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #ddd;
    width: 100% !important;
    max-width: 100% !important;
    font-size: 13px;
}

#transfer-calendar .flatpickr-innerContainer {
    width: 100% !important;
    max-width: 100% !important;
}

#transfer-calendar .flatpickr-days {
    width: 100% !important;
    max-width: 100% !important;
}

#transfer-calendar .flatpickr-day {
    width: calc(100% / 7) !important;
    height: 32px !important;
    line-height: 32px !important;
    font-size: 13px !important;
    margin: 0 !important;
    padding: 0 !important;
}

#transfer-calendar .flatpickr-weekday {
    width: calc(100% / 7) !important;
    height: 28px !important;
    line-height: 28px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    margin: 0 !important;
    padding: 0 !important;
}

#transfer-calendar .flatpickr-months {
    margin-bottom: 8px !important;
}

#transfer-calendar .flatpickr-month {
    height: 32px !important;
    line-height: 32px !important;
    font-size: 14px !important;
}

#transfer-calendar .flatpickr-prev-month,
#transfer-calendar .flatpickr-next-month {
    height: 32px !important;
    line-height: 32px !important;
    width: 32px !important;
}

#transfer-calendar .flatpickr-day.legal-holiday {
    background-color: #ffebee;
    color: #c62828;
    font-weight: bold;
}

#transfer-calendar .flatpickr-day.weekend {
    background-color: #f5f5f5;
    color: #999;
}

#transfer-calendar .flatpickr-day.full {
    background-color: #ffcdd2;
    color: #d32f2f;
    text-decoration: line-through;
}

#transfer-calendar .flatpickr-day:hover:not(.disabled) {
    background-color: #e3f2fd;
    color: #1976d2;
}

#transfer-calendar .flatpickr-day.selected {
    background-color: #2196f3;
    color: #fff;
}

/* Ajustări suplimentare pentru potrivirea în modal */
#transfer-calendar .flatpickr-calendar {
    position: relative !important;
    top: auto !important;
    left: auto !important;
    right: auto !important;
    transform: none !important;
    margin: 0 !important;
}

#transfer-calendar .flatpickr-wrapper {
    width: 100% !important;
    max-width: 100% !important;
}

#transfer-calendar .flatpickr-input {
    display: none !important;
}

/* Responsive pentru modal */
@media (max-width: 768px) {
    #transfer-calendar {
        min-height: 300px;
        max-height: 350px;
        padding: 8px;
    }
    
    #transfer-calendar .flatpickr-day {
        height: 28px !important;
        line-height: 28px !important;
        font-size: 12px !important;
    }
    
    #transfer-calendar .flatpickr-weekday {
        height: 24px !important;
        line-height: 24px !important;
        font-size: 11px !important;
    }
}

/* Layout cu 2 coloane pentru modalul de transfer */
.transfer-layout {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.transfer-left-column {
    flex: 1;
    min-width: 0;
}

.transfer-right-column {
    flex: 1;
    min-width: 0;
}

/* Ajustări pentru calendarul în coloana stângă */
.transfer-left-column #transfer-calendar {
    min-height: 320px;
    max-height: 380px;
}

/* Stiluri pentru grid-ul de doctori */
#transfer-doctors.doctors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

#transfer-doctors .doctor-btn {
    padding: 12px 16px;
    text-align: center;
    background: #fff;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    min-height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333;
}

#transfer-doctors .doctor-btn:hover:not(.disabled) {
    background: #f0f8ff;
    border-color: #0073aa;
    color: #0073aa;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 115, 170, 0.15);
}

#transfer-doctors .doctor-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: #f5f5f5;
    color: #999;
}

#transfer-doctors .doctor-btn.selected {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 115, 170, 0.3);
}

#transfer-doctors .doctor-btn.selected:hover {
    background: #005a87;
    border-color: #005a87;
    box-shadow: 0 4px 12px rgba(0, 90, 135, 0.4);
}

/* Ajustări pentru sloturile în coloana dreaptă */
.transfer-right-column .form-group {
    margin-bottom: 20px;
}

/* Stiluri pentru sloturile ca în dashboard-ul pacient */
#transfer-slots.slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
    gap: 8px;
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    background: #f9f9f9;
}

#transfer-slots .slot-btn {
    padding: 8px;
    text-align: center;
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s ease;
    min-height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

#transfer-slots .slot-btn:hover:not(.disabled) {
    background: #f0f8ff;
    border-color: #0073aa;
    color: #0073aa;
}

#transfer-slots .slot-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: #f5f5f5;
    color: #999;
}

#transfer-slots .slot-btn.selected {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

#transfer-slots .slot-btn.selected:hover {
    background: #005a87;
    border-color: #005a87;
}

/* Scrollbar personalizat pentru grid-ul de sloturi */
#transfer-slots.slots-grid::-webkit-scrollbar {
    width: 6px;
}

#transfer-slots.slots-grid::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#transfer-slots.slots-grid::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#transfer-slots.slots-grid::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive pentru layout-ul cu 2 coloane */
@media (max-width: 768px) {
    .transfer-layout {
        flex-direction: column;
        gap: 15px;
    }
    
    .transfer-left-column #transfer-calendar {
        min-height: 280px;
        max-height: 320px;
    }
}

/* Ajustări pentru modalul de transfer */
.clinica-modal .modal-body {
    max-height: 80vh;
    overflow-y: auto;
}

#transfer-modal .modal-dialog {
    max-width: 800px;
    margin: 30px auto;
}

#transfer-modal .modal-content {
    max-height: 90vh;
    overflow: hidden;
}

/* Modal visibility */
.clinica-modal-backdrop.is-visible {
    display: flex !important;
    align-items: center;
    justify-content: center;
}

.clinica-modal-backdrop.is-visible .clinica-modal {
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style> 