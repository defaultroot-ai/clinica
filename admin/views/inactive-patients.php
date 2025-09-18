<?php
/**
 * Pagina pentru gestionarea pacienților inactivi
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!Clinica_Patient_Permissions::can_view_patients()) {
    wp_die(__('Nu aveți permisiunea de a vedea pacienții.', 'clinica'));
}

global $wpdb;

// Paginare
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Căutare
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$cnp_filter = isset($_GET['cnp']) ? sanitize_text_field($_GET['cnp']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Construiește query-ul pentru pacienții inactivi
$table_name = $wpdb->prefix . 'clinica_patients';
$where_conditions = array();
$where_values = array();

// Filtru pentru pacienții inactivi sau blocați
// Include doar pacienții cu status explicit 'inactive' sau 'blocked'
$where_conditions[] = "(um_status.meta_value IN ('inactive', 'blocked'))";

if (!empty($search)) {
    $where_conditions[] = "(p.cnp LIKE %s OR um1.meta_value LIKE %s OR um2.meta_value LIKE %s OR u.user_email LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $where_values[] = $search_term;
    $where_values[] = $search_term;
    $where_values[] = $search_term;
    $where_values[] = $search_term;
}

if (!empty($cnp_filter)) {
    $where_conditions[] = "p.cnp = %s";
    $where_values[] = $cnp_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "um_status.meta_value = %s";
    $where_values[] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Numărul total de pacienți inactivi
$total_query = "SELECT COUNT(*) FROM $table_name p 
               LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
               LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
               LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
               LEFT JOIN {$wpdb->usermeta} um_status ON p.user_id = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
               $where_clause";

if (!empty($where_values)) {
    $total_query = $wpdb->prepare($total_query, $where_values);
}

$total = $wpdb->get_var($total_query);
$total_pages = ceil($total / $per_page);

// Lista de pacienți inactivi
$query = "SELECT p.*, u.user_email, u.display_name,
          um1.meta_value as first_name, um2.meta_value as last_name,
          um_status.meta_value as patient_status
          FROM $table_name p 
          LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
          LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
          LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
          LEFT JOIN {$wpdb->usermeta} um_status ON p.user_id = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
          $where_clause 
          ORDER BY p.created_at DESC 
          LIMIT %d OFFSET %d";

$query_values = array_merge($where_values, array($per_page, $offset));
$patients = $wpdb->get_results($wpdb->prepare($query, $query_values));
?>

<div class="wrap clinica-inactive-patients">
    <!-- Header cu statistici -->
    <div class="clinica-patients-header">
        <div class="clinica-header-main">
            <div class="clinica-header-left">
                <h1 class="wp-heading-inline">
                    <span class="dashicons dashicons-groups" style="margin-right: 15px; color: white; font-size: 32px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);"></span>
                    <?php _e('Pacienți Inactivi', 'clinica'); ?>
                </h1>
                <div class="clinica-stats">
                    <div class="stat-item" title="<?php _e('Numărul total de pacienți inactivi în sistem.', 'clinica'); ?>">
                        <span class="dashicons dashicons-admin-users" style="margin-right: 12px; color: white; font-size: 24px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);"></span>
                        <span class="stat-number"><?php echo number_format_i18n($total); ?></span>
                        <span class="stat-label"><?php _e('Pacienți Inactivi', 'clinica'); ?></span>
                    </div>
                    <div class="stat-item" title="<?php _e('Pacienții blocați definitiv.', 'clinica'); ?>">
                        <span class="dashicons dashicons-lock" style="margin-right: 12px; color: white; font-size: 24px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);"></span>
                        <span class="stat-number"><?php 
                            $blocked_count = $wpdb->get_var("
                                SELECT COUNT(*) FROM $table_name p 
                                LEFT JOIN {$wpdb->usermeta} um_status ON p.user_id = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
                                WHERE um_status.meta_value = 'blocked'
                            ");
                            echo number_format_i18n($blocked_count);
                        ?></span>
                        <span class="stat-label"><?php _e('Blocați', 'clinica'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="clinica-header-right">
                <div class="clinica-actions">
                    <a href="<?php echo admin_url('admin.php?page=clinica-patients'); ?>" class="button">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Înapoi la Pacienți Activi', 'clinica'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <hr class="wp-header-end">
    
    <!-- Filtre -->
    <div class="clinica-filters-container">
        <form method="get" action="" class="clinica-filters-form">
            <input type="hidden" name="page" value="clinica-inactive-patients">
            
            <div class="clinica-filters-row">
                <div class="clinica-filter-group">
                    <label for="search-input"><?php _e('Căutare', 'clinica'); ?></label>
                    <input type="text" id="search-input" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php _e('Nume, email, telefon...', 'clinica'); ?>">
                </div>
                
                <div class="clinica-filter-group">
                    <label for="cnp-filter"><?php _e('CNP specific', 'clinica'); ?></label>
                    <input type="text" id="cnp-filter" name="cnp" value="<?php echo esc_attr($cnp_filter); ?>" 
                           placeholder="<?php _e('CNP...', 'clinica'); ?>">
                </div>
                
                <div class="clinica-filter-group">
                    <label for="status-filter"><?php _e('Status', 'clinica'); ?></label>
                    <select id="status-filter" name="status">
                        <option value=""><?php _e('Toate', 'clinica'); ?></option>
                        <option value="inactive" <?php selected($status_filter === 'inactive'); ?>>
                            <?php _e('Inactivi', 'clinica'); ?>
                        </option>
                        <option value="blocked" <?php selected($status_filter === 'blocked'); ?>>
                            <?php _e('Blocați', 'clinica'); ?>
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="clinica-filters-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Filtrează', 'clinica'); ?>
                </button>
                
                <?php if (!empty($search) || !empty($cnp_filter) || !empty($status_filter)): ?>
                <a href="<?php echo admin_url('admin.php?page=clinica-inactive-patients'); ?>" class="button">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Resetează', 'clinica'); ?>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Rezultate -->
    <div class="clinica-results-info">
        <div class="clinica-results-left">
            <span class="displaying-num">
                <?php printf(_n('%s pacient inactiv găsit', '%s pacienți inactivi găsiți', $total, 'clinica'), number_format_i18n($total)); ?>
            </span>
        </div>
    </div>
    
    <!-- Tabel pacienți inactivi -->
    <div class="clinica-patients-view">
        <table class="wp-list-table widefat fixed striped clinica-patients-table">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="select-all-inactive-patients">
                    </th>
                    <th class="column-name"><?php _e('Pacient', 'clinica'); ?></th>
                    <th class="column-cnp"><?php _e('CNP', 'clinica'); ?></th>
                    <th class="column-email"><?php _e('Email', 'clinica'); ?></th>
                    <th class="column-gender"><?php _e('Sex', 'clinica'); ?></th>
                    <th class="column-status"><?php _e('Status', 'clinica'); ?></th>
                    <th class="column-reason"><?php _e('Motiv', 'clinica'); ?></th>
                    <th class="column-actions"><?php _e('Acțiuni', 'clinica'); ?></th>
                </tr>
            </thead>
            
            <tbody>
                <?php if (empty($patients)): ?>
                <tr>
                    <td colspan="8" class="clinica-empty-state">
                        <div class="clinica-empty-content">
                            <span class="dashicons dashicons-groups"></span>
                            <h3><?php _e('Nu s-au găsit pacienți inactivi', 'clinica'); ?></h3>
                            <p><?php _e('Nu există pacienți inactivi care să corespundă criteriilor de căutare.', 'clinica'); ?></p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($patients as $patient): ?>
                    <tr class="clinica-patient-row" data-patient-id="<?php echo $patient->user_id; ?>">
                        <td class="check-column">
                            <input type="checkbox" name="selected_inactive_patients[]" value="<?php echo $patient->user_id; ?>">
                        </td>
                        
                        <td class="column-name">
                            <div class="clinica-patient-info">
                                <div class="clinica-patient-avatar">
                                    <?php 
                                    $avatar = get_avatar($patient->user_id, 40);
                                    echo $avatar ? $avatar : '<div class="clinica-avatar-placeholder">' . substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1) . '</div>';
                                    ?>
                                </div>
                                <div class="clinica-patient-details">
                                    <strong class="clinica-patient-name">
                                        <?php 
                                        $full_name = trim($patient->first_name . ' ' . $patient->last_name);
                                        echo esc_html(!empty($full_name) ? $full_name : $patient->display_name); 
                                        ?>
                                    </strong>
                                    <span class="clinica-patient-id">ID: <?php echo $patient->user_id; ?></span>
                                </div>
                            </div>
                        </td>
                        
                        <td class="column-cnp">
                            <code class="clinica-cnp"><?php echo esc_html($patient->cnp); ?></code>
                        </td>
                        
                        <td class="column-email">
                            <?php if ($patient->user_email): ?>
                            <a href="mailto:<?php echo esc_attr($patient->user_email); ?>"><?php echo esc_html($patient->user_email); ?></a>
                            <?php else: ?>
                            <span class="clinica-no-email"><?php _e('Fără email', 'clinica'); ?></span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="column-gender">
                            <?php 
                            $gender = null;
                            if (strlen($patient->cnp) === 13 && ctype_digit($patient->cnp)) {
                                $sex_digit = substr($patient->cnp, 0, 1);
                                if ($sex_digit == 1 || $sex_digit == 3 || $sex_digit == 5 || $sex_digit == 7) {
                                    $gender = 'M';
                                } elseif ($sex_digit == 2 || $sex_digit == 4 || $sex_digit == 6 || $sex_digit == 8) {
                                    $gender = 'F';
                                }
                            }
                            
                            if ($gender) {
                                echo '<span class="clinica-gender-simple clinica-gender-' . strtolower($gender) . '">' . $gender . '</span>';
                            } else {
                                echo '<span class="clinica-no-gender">-</span>';
                            }
                            ?>
                        </td>
                        
                        <td class="column-status">
                            <div class="clinica-patient-status">
                                <?php 
                                $is_blocked = $patient->patient_status === 'blocked';
                                ?>
                                
                                <div class="clinica-status-toggle">
                                    <label class="clinica-toggle-switch">
                                        <input type="checkbox" 
                                               class="clinica-status-checkbox" 
                                               data-patient-id="<?php echo $patient->user_id; ?>"
                                               <?php echo $is_blocked ? '' : 'checked'; ?>
                                               <?php echo $is_blocked ? 'disabled' : ''; ?>>
                                        <span class="clinica-toggle-slider"></span>
                                    </label>
                                    
                                    <span class="clinica-status-label">
                                        <?php if ($is_blocked): ?>
                                            <span class="clinica-status-badge clinica-status-blocked">
                                                <span class="dashicons dashicons-lock"></span>
                                                <?php _e('Blocat', 'clinica'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="clinica-status-badge clinica-status-inactive">
                                                <span class="dashicons dashicons-no-alt"></span>
                                                <?php _e('Inactiv', 'clinica'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <?php if (Clinica_Patient_Permissions::can_edit_patient_profile($patient->user_id)): ?>
                                <div class="clinica-status-actions">
                                    <button type="button" class="clinica-action-btn clinica-action-small" 
                                            onclick="togglePatientBlock(<?php echo $patient->user_id; ?>, <?php echo $is_blocked ? 'false' : 'true'; ?>)" 
                                            title="<?php echo $is_blocked ? __('Deblochează pacientul', 'clinica') : __('Blochează pacientul', 'clinica'); ?>">
                                        <span class="dashicons dashicons-<?php echo $is_blocked ? 'unlock' : 'lock'; ?>"></span>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <td class="column-reason">
                            <?php 
                            $reason = get_user_meta($patient->user_id, 'clinica_inactive_reason', true);
                            if ($reason) {
                                $reason_display = '';
                                switch ($reason) {
                                    case 'deces':
                                        $reason_display = '<span class="clinica-reason-badge clinica-reason-deces"><span class="dashicons dashicons-heart"></span> Deces</span>';
                                        break;
                                    case 'transfer':
                                        $reason_display = '<span class="clinica-reason-badge clinica-reason-transfer"><span class="dashicons dashicons-migrate"></span> Transfer</span>';
                                        break;
                                    default:
                                        $reason_display = '<span class="clinica-reason-badge clinica-reason-other">' . esc_html($reason) . '</span>';
                                }
                                echo $reason_display;
                            } else {
                                echo '<span class="clinica-no-reason">-</span>';
                            }
                            ?>
                        </td>
                        
                        <td class="column-actions">
                            <div class="clinica-actions">
                                <div class="clinica-action-buttons">
                                    <?php if (Clinica_Patient_Permissions::can_view_patient($patient->user_id)): ?>
                                    <button type="button" class="clinica-action-btn" onclick="viewPatient(<?php echo $patient->user_id; ?>)" title="<?php _e('Vezi detalii', 'clinica'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                    <?php endif; ?>
                                    
                                                                         <?php 
                                     $reason = get_user_meta($patient->user_id, 'clinica_inactive_reason', true);
                                     $is_deceased = ($reason === 'deces');
                                     ?>
                                     <?php if (Clinica_Patient_Permissions::can_edit_patient_profile($patient->user_id) && !$is_deceased): ?>
                                     <button type="button" class="clinica-action-btn clinica-reactivate-btn" onclick="reactivatePatient(<?php echo $patient->user_id; ?>)" title="<?php _e('Reactivează pacientul', 'clinica'); ?>">
                                         <span class="dashicons dashicons-update"></span>
                                         <?php _e('Reactivează', 'clinica'); ?>
                                     </button>
                                     <?php elseif ($is_deceased): ?>
                                     <button type="button" class="clinica-action-btn clinica-reactivate-btn clinica-reactivate-disabled" disabled title="<?php _e('Pacientul este decedat și nu poate fi reactivat', 'clinica'); ?>">
                                         <span class="dashicons dashicons-lock"></span>
                                         <?php _e('Decedat', 'clinica'); ?>
                                     </button>
                                     <?php endif; ?>
                                    
                                    <div class="clinica-action-dropdown">
                                        <button type="button" class="clinica-action-btn" onclick="toggleActionMenu(<?php echo $patient->user_id; ?>)" title="<?php _e('Mai multe acțiuni', 'clinica'); ?>">
                                            <span class="dashicons dashicons-menu"></span>
                                        </button>
                                        <div class="clinica-action-menu" id="action-menu-<?php echo $patient->user_id; ?>">
                                            <a href="#" onclick="viewPatientHistory(<?php echo $patient->user_id; ?>); return false;">
                                                <span class="dashicons dashicons-clock"></span>
                                                <?php _e('Istoric', 'clinica'); ?>
                                            </a>
                                            <a href="#" onclick="exportPatientData(<?php echo $patient->user_id; ?>); return false;">
                                                <span class="dashicons dashicons-download"></span>
                                                <?php _e('Export date', 'clinica'); ?>
                                            </a>
                                            <a href="#" onclick="setInactiveReason(<?php echo $patient->user_id; ?>); return false;">
                                                <span class="dashicons dashicons-edit"></span>
                                                <?php _e('Editează motivul', 'clinica'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginare -->
    <?php if ($total_pages > 1): ?>
    <div class="tablenav-pages">
        <span class="displaying-num"><?php printf(__('%s elemente', 'clinica'), number_format_i18n($total)); ?></span>
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
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Funcționalitate pentru toggle-ul de status
    $(document).on('change', '.clinica-status-checkbox', function() {
        var patientId = $(this).data('patient-id');
        var isActive = $(this).is(':checked');
        var status = isActive ? 'active' : 'inactive';
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_update_patient_status',
                patient_id: patientId,
                status: status,
                nonce: '<?php echo wp_create_nonce('clinica_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Actualizează badge-ul de status
                    var statusLabel = $(this).closest('.clinica-status-toggle').find('.clinica-status-label');
                    if (isActive) {
                        statusLabel.html('<span class="clinica-status-badge clinica-status-active"><span class="dashicons dashicons-yes-alt"></span> Activ</span>');
                    } else {
                        statusLabel.html('<span class="clinica-status-badge clinica-status-inactive"><span class="dashicons dashicons-no-alt"></span> Inactiv</span>');
                    }
                } else {
                    alert('Eroare la actualizarea statusului: ' + response.data);
                    // Revenire la starea anterioară
                    $(this).prop('checked', !isActive);
                }
            }.bind(this),
            error: function() {
                alert('Eroare la actualizarea statusului.');
                // Revenire la starea anterioară
                $(this).prop('checked', !isActive);
            }
        });
    });
    
    // Funcționalitate pentru blocarea/deblocarea pacienților
    window.togglePatientBlock = function(patientId, block) {
        var action = block ? 'clinica_block_patient' : 'clinica_unblock_patient';
        var message = block ? 'Blochezi' : 'Deblochezi';
        
        if (confirm('Ești sigur că vrei să ' + message.toLowerCase() + ' acest pacient?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    patient_id: patientId,
                    nonce: '<?php echo wp_create_nonce('clinica_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Eroare la ' + message.toLowerCase() + ' pacientul: ' + response.data);
                    }
                },
                error: function() {
                    alert('Eroare la ' + message.toLowerCase() + ' pacientul.');
                }
            });
        }
    };
    
    // Funcționalitate pentru reactivarea pacienților
    window.reactivatePatient = function(patientId) {
        // Verifică dacă pacientul este marcat ca decedat prin AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_get_inactive_reason',
                patient_id: patientId,
                nonce: '<?php echo wp_create_nonce('clinica_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var reason = response.data.reason;
                    if (reason === 'deces') {
                        alert('Nu se poate reactiva un pacient marcat ca decedat!');
                        return;
                    }
                    
                    if (confirm('Ești sigur că vrei să reactivezi acest pacient?')) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'clinica_reactivate_patient',
                                patient_id: patientId,
                                nonce: '<?php echo wp_create_nonce('clinica_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('Pacientul a fost reactivat cu succes!');
                                    location.reload();
                                } else {
                                    alert('Eroare la reactivarea pacientului: ' + response.data);
                                }
                            },
                            error: function() {
                                alert('Eroare la reactivarea pacientului.');
                            }
                        });
                    }
                } else {
                    alert('Eroare la verificarea statusului pacientului.');
                }
            },
            error: function() {
                alert('Eroare la verificarea statusului pacientului.');
            }
        });
    };
    
    // Funcționalitate pentru setarea motivului de inactivitate
    window.setInactiveReason = function(patientId) {
        var reason = prompt('Introduceți motivul pentru inactivitate:\n\n1. deces - pentru pacienți decedați\n2. transfer - pentru pacienți transferați\n\nNotă: Pacienții marcați ca "deces" nu pot fi reactivați!');
        if (reason !== null && reason.trim() !== '') {
            var reasonLower = reason.toLowerCase().trim();
            if (reasonLower === 'deces' || reasonLower === 'transfer') {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clinica_set_inactive_reason',
                        patient_id: patientId,
                        reason: reasonLower,
                        nonce: '<?php echo wp_create_nonce('clinica_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Eroare la setarea motivului: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Eroare la setarea motivului.');
                    }
                });
            } else {
                alert('Motivul trebuie să fie "deces" sau "transfer".');
            }
        }
    };
});
</script> 