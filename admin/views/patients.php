<?php
/**
 * Pagina pentru gestionarea pacienților
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
$age_filter = isset($_GET['age']) ? sanitize_text_field($_GET['age']) : '';

// Construiește query-ul
$table_name = $wpdb->prefix . 'clinica_patients';
$where_conditions = array();
$where_values = array();

// Exclude pacienții inactivi și blocați din lista principală
// Pacienții fără status (NULL) sunt considerați activi
$where_conditions[] = "(um_status.meta_value IS NULL OR um_status.meta_value = 'active')";

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

// Filtrare după vârstă
if (!empty($age_filter)) {
    $current_year = date('Y');
    $current_month = date('m');
    $current_day = date('d');
    
    switch ($age_filter) {
        case '0-18':
            $min_year = $current_year - 18;
            $max_year = $current_year;
            break;
        case '19-30':
            $min_year = $current_year - 30;
            $max_year = $current_year - 19;
            break;
        case '31-50':
            $min_year = $current_year - 50;
            $max_year = $current_year - 31;
            break;
        case '51-65':
            $min_year = $current_year - 65;
            $max_year = $current_year - 51;
            break;
        case '51+':
            $min_year = 1900;
            $max_year = $current_year - 51;
            break;
        case '65+':
            $min_year = 1900;
            $max_year = $current_year - 66;
            break;
        default:
            $min_year = 0;
            $max_year = 9999;
    }
    
    // Construiește condiția pentru vârstă bazată pe CNP
    $age_condition = "(
        CASE 
            WHEN SUBSTRING(p.cnp, 1, 1) IN ('1', '2') THEN 1900 + CAST(SUBSTRING(p.cnp, 2, 2) AS UNSIGNED)
            WHEN SUBSTRING(p.cnp, 1, 1) IN ('3', '4') THEN 1800 + CAST(SUBSTRING(p.cnp, 2, 2) AS UNSIGNED)
            WHEN SUBSTRING(p.cnp, 1, 1) IN ('5', '6') THEN 2000 + CAST(SUBSTRING(p.cnp, 2, 2) AS UNSIGNED)
            ELSE 1900 + CAST(SUBSTRING(p.cnp, 2, 2) AS UNSIGNED)
        END BETWEEN %d AND %d
    )";
    
    $where_conditions[] = $age_condition;
    $where_values[] = $min_year;
    $where_values[] = $max_year;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Numărul total de pacienți
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

// Lista de pacienți
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'created_at';
$sort_order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'desc';

// Construiește clauza ORDER BY
$order_clause = '';
switch ($sort_by) {
    case 'name':
        $order_clause = "ORDER BY um1.meta_value ASC, um2.meta_value ASC";
        break;
    case 'cnp':
        $order_clause = "ORDER BY p.cnp " . strtoupper($sort_order);
        break;
    case 'email':
        $order_clause = "ORDER BY u.user_email " . strtoupper($sort_order);
        break;
    case 'family':
        $order_clause = "ORDER BY p.family_id " . strtoupper($sort_order);
        break;
    case 'gender':
        $order_clause = "ORDER BY SUBSTRING(p.cnp, 1, 1) " . strtoupper($sort_order);
        break;
    case 'birth_date':
        $order_clause = "ORDER BY p.cnp " . strtoupper($sort_order);
        break;
    case 'age':
        $order_clause = "ORDER BY p.cnp " . ($sort_order === 'asc' ? 'DESC' : 'ASC');
        break;
    case 'status':
        $order_clause = "ORDER BY um_status.meta_value " . strtoupper($sort_order);
        break;
    default:
        $order_clause = "ORDER BY p.created_at " . strtoupper($sort_order);
}

$query = "SELECT p.*, u.user_email, u.display_name,
          um1.meta_value as first_name, um2.meta_value as last_name,
          um_status.meta_value as patient_status
          FROM $table_name p 
          LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
          LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
          LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
          LEFT JOIN {$wpdb->usermeta} um_status ON p.user_id = um_status.user_id AND um_status.meta_key = 'clinica_patient_status'
          $where_clause 
          $order_clause 
          LIMIT %d OFFSET %d";

$query_values = array_merge($where_values, array($per_page, $offset));
$patients = $wpdb->get_results($wpdb->prepare($query, $query_values));
?>

<div class="wrap">
    <!-- Header cu statistici -->
    <div class="clinica-patients-header">
        <div class="clinica-header-main">
            <div class="clinica-header-left">
                <h1 class="wp-heading-inline"><?php _e('Pacienți', 'clinica'); ?></h1>
                <div class="clinica-stats">
                    <div class="stat-item" title="<?php _e('Numărul total de pacienți înregistrați în sistem. Include toți pacienții din baza de date, indiferent de status.', 'clinica'); ?>">
                        <span class="stat-number"><?php echo number_format_i18n($total); ?></span>
                        <span class="stat-label"><?php _e('Total Pacienți', 'clinica'); ?></span>
                    </div>
                    <div class="stat-item" title="<?php _e('Numărul de familii cu cel puțin un pacient înregistrat. O familie poate avea mai mulți pacienți.', 'clinica'); ?>">
                        <span class="stat-number"><?php 
                            $families_count = $wpdb->get_var("SELECT COUNT(DISTINCT family_id) FROM $table_name WHERE family_id IS NOT NULL");
                            echo number_format_i18n($families_count);
                        ?></span>
                        <span class="stat-label"><?php _e('Familii', 'clinica'); ?></span>
                    </div>
                    <div class="stat-item" title="<?php _e('Pacienții adăugați astăzi. Numărul de pacienți noi înregistrați în ziua curentă.', 'clinica'); ?>">
                        <span class="stat-number"><?php 
                            $today_patients = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
                                current_time('Y-m-d')
                            ));
                            echo number_format_i18n($today_patients);
                        ?></span>
                        <span class="stat-label"><?php _e('Astăzi', 'clinica'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="clinica-header-right">
                <div class="clinica-actions">
                    <?php if (Clinica_Patient_Permissions::can_create_patient()): ?>
                    <a href="<?php echo admin_url('admin.php?page=clinica-create-patient'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php _e('Adaugă Pacient Nou', 'clinica'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <button type="button" class="button" onclick="exportPatients()">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export', 'clinica'); ?>
                    </button>
                    
                    <button type="button" class="button" onclick="showBulkActions()">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Acțiuni în Masă', 'clinica'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <hr class="wp-header-end">
    
    <!-- Filtre avansate -->
    <div class="clinica-filters-container">
        <form method="get" action="" class="clinica-filters-form">
            <input type="hidden" name="page" value="clinica-patients">
            
            <div class="clinica-filters-row">
                <div class="clinica-filter-group">
                    <label for="search-input"><?php _e('Căutare', 'clinica'); ?></label>
                    <div class="clinica-search-container">
                        <input type="text" id="search-input" name="s" value="<?php echo esc_attr($search); ?>" 
                               placeholder="<?php _e('Nume, email, telefon...', 'clinica'); ?>" 
                               autocomplete="off">
                        <div id="search-suggestions" class="clinica-suggestions"></div>
                    </div>
                </div>
                
                <div class="clinica-filter-group">
                    <label for="cnp-filter"><?php _e('CNP', 'clinica'); ?></label>
                    <div class="clinica-search-container">
                        <input type="text" id="cnp-filter" name="cnp" value="<?php echo esc_attr($cnp_filter); ?>" 
                               placeholder="<?php _e('CNP specific', 'clinica'); ?>" 
                               autocomplete="off">
                        <div id="cnp-suggestions" class="clinica-suggestions"></div>
                    </div>
                </div>
                
                <div class="clinica-filter-group">
                    <label for="family-filter"><?php _e('Familie', 'clinica'); ?></label>
                    <div class="clinica-search-container">
                        <input type="text" id="family-filter" name="family_search" 
                               placeholder="<?php _e('Caută familie...', 'clinica'); ?>" 
                               autocomplete="off">
                        <input type="hidden" id="family-filter-value" name="family" value="<?php echo esc_attr($_GET['family'] ?? ''); ?>">
                        <div id="family-suggestions" class="clinica-suggestions"></div>
                    </div>
                </div>
                
                <div class="clinica-filter-group">
                    <label for="age-filter"><?php _e('Vârsta', 'clinica'); ?></label>
                    <select id="age-filter" name="age">
                        <option value=""><?php _e('Toate vârstele', 'clinica'); ?></option>
                        <option value="0-18" <?php selected(isset($_GET['age']) && $_GET['age'] === '0-18'); ?>>
                            <?php _e('0-18 ani', 'clinica'); ?>
                        </option>
                        <option value="19-30" <?php selected(isset($_GET['age']) && $_GET['age'] === '19-30'); ?>>
                            <?php _e('19-30 ani', 'clinica'); ?>
                        </option>
                        <option value="31-50" <?php selected(isset($_GET['age']) && $_GET['age'] === '31-50'); ?>>
                            <?php _e('31-50 ani', 'clinica'); ?>
                        </option>
                        <option value="51+" <?php selected(isset($_GET['age']) && $_GET['age'] === '51+'); ?>>
                            <?php _e('51+ ani', 'clinica'); ?>
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="clinica-filters-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Filtrează', 'clinica'); ?>
                </button>
                
                <?php if (!empty($search) || !empty($cnp_filter) || !empty($_GET['family']) || !empty($age_filter)): ?>
                <a href="<?php echo admin_url('admin.php?page=clinica-patients'); ?>" class="button">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Resetează', 'clinica'); ?>
                </a>
                <?php endif; ?>
                
                <button type="button" class="button" onclick="toggleAdvancedFilters()">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Filtre Avansate', 'clinica'); ?>
                </button>
            </div>
        </form>
        
        <!-- Filtre avansate (ascunse implicit) -->
        <div id="advanced-filters" class="clinica-advanced-filters" style="display: none;">
            <div class="clinica-filters-row">
                <div class="clinica-filter-group">
                    <label for="date-from"><?php _e('Data creării de la', 'clinica'); ?></label>
                    <input type="date" id="date-from" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>">
                </div>
                
                <div class="clinica-filter-group">
                    <label for="date-to"><?php _e('Data creării până la', 'clinica'); ?></label>
                    <input type="date" id="date-to" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>">
                </div>
                
                <div class="clinica-filter-group">
                    <label for="gender-filter"><?php _e('Gen', 'clinica'); ?></label>
                    <select id="gender-filter" name="gender">
                        <option value=""><?php _e('Toate', 'clinica'); ?></option>
                        <option value="male" <?php selected(isset($_GET['gender']) && $_GET['gender'] === 'male'); ?>>
                            <?php _e('Masculin', 'clinica'); ?>
                        </option>
                        <option value="female" <?php selected(isset($_GET['gender']) && $_GET['gender'] === 'female'); ?>>
                            <?php _e('Feminin', 'clinica'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="clinica-filter-group">
                    <label for="sort-by"><?php _e('Sortează după', 'clinica'); ?></label>
                    <select id="sort-by" name="sort">
                        <option value="created_at" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'created_at'); ?>>
                            <?php _e('Data creării', 'clinica'); ?>
                        </option>
                        <option value="name" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'name'); ?>>
                            <?php _e('Nume', 'clinica'); ?>
                        </option>
                        <option value="age" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'age'); ?>>
                            <?php _e('Vârsta', 'clinica'); ?>
                        </option>
                        <option value="family" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'family'); ?>>
                            <?php _e('Familie', 'clinica'); ?>
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Rezultate și paginare -->
    <div class="clinica-results-info">
        <div class="clinica-results-left">
            <span class="displaying-num">
                <?php printf(_n('%s pacient găsit', '%s pacienți găsiți', $total, 'clinica'), number_format_i18n($total)); ?>
            </span>
            <?php if (!empty($search) || !empty($cnp_filter) || !empty($_GET['family']) || !empty($age_filter)): ?>
            <span class="clinica-active-filters">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Filtre active', 'clinica'); ?>
                <?php if (!empty($age_filter)): ?>
                <span class="clinica-filter-badge">
                    <?php 
                    $age_labels = array(
                        '0-18' => '0-18 ani',
                        '19-30' => '19-30 ani',
                        '31-50' => '31-50 ani',
                        '51-65' => '51-65 ani',
                        '51+' => '51+ ani',
                        '65+' => '65+ ani'
                    );
                    echo esc_html($age_labels[$age_filter] ?? $age_filter);
                    ?>
                </span>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
        
        <div class="clinica-results-right">
            <div class="clinica-view-options">
                <button type="button" class="button" onclick="setViewMode('table')" id="view-table">
                    <span class="dashicons dashicons-list-table"></span>
                </button>
                <button type="button" class="button" onclick="setViewMode('cards')" id="view-cards">
                    <span class="dashicons dashicons-grid-view"></span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tabel pacienți îmbunătățit -->
    <div id="patients-table-view" class="clinica-patients-view">
        <table class="wp-list-table widefat fixed striped clinica-patients-table">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="select-all-patients">
                    </th>
                    <th class="column-name sortable <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name') ? 'sorted' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('sort' => 'name', 'order' => (isset($_GET['sort']) && $_GET['sort'] === 'name' && isset($_GET['order']) && $_GET['order'] === 'asc') ? 'desc' : 'asc')); ?>">
                            <?php _e('Pacient', 'clinica'); ?>
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                        </a>
                    </th>
                    <th class="column-cnp sortable <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'cnp') ? 'sorted' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('sort' => 'cnp', 'order' => (isset($_GET['sort']) && $_GET['sort'] === 'cnp' && isset($_GET['order']) && $_GET['order'] === 'asc') ? 'desc' : 'asc')); ?>">
                            <?php _e('CNP', 'clinica'); ?>
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                        </a>
                    </th>
                    <th class="column-email sortable <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'email') ? 'sorted' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('sort' => 'email', 'order' => (isset($_GET['sort']) && $_GET['sort'] === 'email' && isset($_GET['order']) && $_GET['order'] === 'asc') ? 'desc' : 'asc')); ?>">
                            <?php _e('Email', 'clinica'); ?>
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                        </a>
                    </th>
                    <th class="column-family sortable <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'family') ? 'sorted' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('sort' => 'family', 'order' => (isset($_GET['sort']) && $_GET['sort'] === 'family' && isset($_GET['order']) && $_GET['order'] === 'asc') ? 'desc' : 'asc')); ?>">
                            <?php _e('Familie', 'clinica'); ?>
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                        </a>
                    </th>
                    <th class="column-gender sortable <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'gender') ? 'sorted' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('sort' => 'gender', 'order' => (isset($_GET['sort']) && $_GET['sort'] === 'gender' && isset($_GET['order']) && $_GET['order'] === 'asc') ? 'desc' : 'asc')); ?>">
                            <?php _e('Sex', 'clinica'); ?>
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                        </a>
                    </th>
                    <th class="column-birth-date sortable <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'birth_date') ? 'sorted' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('sort' => 'birth_date', 'order' => (isset($_GET['sort']) && $_GET['sort'] === 'birth_date' && isset($_GET['order']) && $_GET['order'] === 'asc') ? 'desc' : 'asc')); ?>">
                            <?php _e('Data nașterii', 'clinica'); ?>
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                        </a>
                    </th>
                    <th class="column-age sortable <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'age') ? 'sorted' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('sort' => 'age', 'order' => (isset($_GET['sort']) && $_GET['sort'] === 'age' && isset($_GET['order']) && $_GET['order'] === 'asc') ? 'desc' : 'asc')); ?>">
                            <?php _e('Vârsta', 'clinica'); ?>
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                        </a>
                    </th>
                    <th class="column-status sortable <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'status') ? 'sorted' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('sort' => 'status', 'order' => (isset($_GET['sort']) && $_GET['sort'] === 'status' && isset($_GET['order']) && $_GET['order'] === 'asc') ? 'desc' : 'asc')); ?>">
                            <?php _e('Status', 'clinica'); ?>
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                        </a>
                    </th>
                    <th class="column-actions"><?php _e('Acțiuni', 'clinica'); ?></th>
                </tr>
            </thead>
            
            <tbody>
                <?php if (empty($patients)): ?>
                <tr>
                    <td colspan="10" class="clinica-empty-state">
                        <div class="clinica-empty-content">
                            <span class="dashicons dashicons-groups"></span>
                            <h3><?php _e('Nu s-au găsit pacienți', 'clinica'); ?></h3>
                            <p><?php _e('Nu există pacienți care să corespundă criteriilor de căutare.', 'clinica'); ?></p>
                            <?php if (Clinica_Patient_Permissions::can_create_patient()): ?>
                            <a href="<?php echo admin_url('admin.php?page=clinica-create-patient'); ?>" class="button button-primary">
                                <?php _e('Adaugă primul pacient', 'clinica'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($patients as $patient): ?>
                    <tr class="clinica-patient-row" data-patient-id="<?php echo $patient->user_id; ?>">
                        <td class="check-column">
                            <input type="checkbox" name="selected_patients[]" value="<?php echo $patient->user_id; ?>">
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
                        
                        <td class="column-family">
                            <?php if ($patient->family_id && $patient->family_name): ?>
                            <div class="clinica-family-info">
                                <span class="clinica-family-badge">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php echo esc_html($patient->family_name); ?>
                                </span>
                            </div>
                            <?php else: ?>
                            <span class="clinica-no-family"><?php _e('Fără familie', 'clinica'); ?></span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="column-gender">
                            <?php 
                            // Extrage sexul din CNP
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
                        
                        <td class="column-birth-date">
                            <?php 
                            // Extrage data nașterii din CNP
                            $birth_date = null;
                            if (strlen($patient->cnp) === 13 && ctype_digit($patient->cnp)) {
                                $year = substr($patient->cnp, 1, 2);
                                $month = substr($patient->cnp, 3, 2);
                                $day = substr($patient->cnp, 5, 2);
                                $sex = substr($patient->cnp, 0, 1);
                                
                                // Determină secolul
                                if ($sex == 1 || $sex == 2) {
                                    $full_year = '19' . $year;
                                } elseif ($sex == 3 || $sex == 4) {
                                    $full_year = '18' . $year;
                                } elseif ($sex == 5 || $sex == 6) {
                                    $full_year = '20' . $year;
                                } else {
                                    $full_year = '19' . $year;
                                }
                                
                                $birth_date = $full_year . '-' . $month . '-' . $day;
                            }
                            
                            if ($birth_date && strtotime($birth_date)) {
                                echo esc_html(date('d.m.Y', strtotime($birth_date)));
                            } else {
                                echo '<span class="clinica-no-date">-</span>';
                            }
                            ?>
                        </td>
                        
                        <td class="column-age">
                            <?php 
                            if ($birth_date && strtotime($birth_date)) {
                                $birth_timestamp = strtotime($birth_date);
                                $today = time();
                                $age_years = date('Y', $today) - date('Y', $birth_timestamp);
                                if (date('md', $today) < date('md', $birth_timestamp)) {
                                    $age_years--;
                                }
                                
                                if ($age_years > 0) {
                                    if ($age_years == 1) {
                                        echo esc_html('1 an');
                                    } else {
                                        echo esc_html($age_years . ' ani');
                                    }
                                } else {
                                    // Calculează vârsta în luni pentru pacienții sub 1 an
                                    $birth_date_obj = new DateTime($birth_date);
                                    $today_obj = new DateTime();
                                    $age_diff = $today_obj->diff($birth_date_obj);
                                    $months = $age_diff->y * 12 + $age_diff->m;
                                    echo esc_html($months . ' luni');
                                }
                            } else {
                                echo '<span class="clinica-no-age">-</span>';
                            }
                            ?>
                        </td>
                        
                        <td class="column-status">
                            <div class="clinica-patient-status">
                                <?php 
                                $patient_status = get_user_meta($patient->user_id, 'clinica_patient_status', true);
                                $is_active = empty($patient_status) || $patient_status === 'active';
                                $is_blocked = $patient_status === 'blocked';
                                ?>
                                
                                <div class="clinica-status-toggle">
                                    <label class="clinica-toggle-switch">
                                        <input type="checkbox" 
                                               class="clinica-status-checkbox" 
                                               data-patient-id="<?php echo $patient->user_id; ?>"
                                               <?php echo $is_active ? 'checked' : ''; ?>
                                               <?php echo $is_blocked ? 'disabled' : ''; ?>>
                                        <span class="clinica-toggle-slider"></span>
                                    </label>
                                    
                                    <span class="clinica-status-label">
                                        <?php if ($is_blocked): ?>
                                            <span class="clinica-status-badge clinica-status-blocked">
                                                <span class="dashicons dashicons-lock"></span>
                                                <?php _e('Blocat', 'clinica'); ?>
                                            </span>
                                        <?php elseif ($is_active): ?>
                                            <span class="clinica-status-badge clinica-status-active">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                <?php _e('Activ', 'clinica'); ?>
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
                        
                        <td class="column-actions">
                            <div class="clinica-actions">
                                <div class="clinica-action-buttons">
                                    <?php if (Clinica_Patient_Permissions::can_view_patient($patient->user_id)): ?>
                                    <button type="button" class="clinica-action-btn" onclick="viewPatient(<?php echo $patient->user_id; ?>)" title="<?php _e('Vezi detalii', 'clinica'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (Clinica_Patient_Permissions::can_edit_patient_profile($patient->user_id)): ?>
                                    <button type="button" class="clinica-action-btn" onclick="editPatient(<?php echo $patient->user_id; ?>)" title="<?php _e('Editează', 'clinica'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
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
                                            <?php if ($patient->family_id): ?>
                                            <a href="#" onclick="viewFamilyDetails(<?php echo $patient->family_id; ?>); return false;">
                                                <span class="dashicons dashicons-groups"></span>
                                                <?php _e('Vezi familia', 'clinica'); ?>
                                            </a>
                                            <?php endif; ?>
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
    
    <!-- Vizualizare carduri (ascunsă implicit) -->
    <div id="patients-cards-view" class="clinica-patients-view" style="display: none;">
        <div class="clinica-patients-grid">
            <?php if (empty($patients)): ?>
            <div class="clinica-empty-state">
                <div class="clinica-empty-content">
                    <span class="dashicons dashicons-groups"></span>
                    <h3><?php _e('Nu s-au găsit pacienți', 'clinica'); ?></h3>
                    <p><?php _e('Nu există pacienți care să corespundă criteriilor de căutare.', 'clinica'); ?></p>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($patients as $patient): ?>
                <div class="clinica-patient-card" data-patient-id="<?php echo $patient->user_id; ?>">
                    <div class="clinica-card-header">
                        <div class="clinica-card-avatar">
                            <?php 
                            $avatar = get_avatar($patient->user_id, 60);
                            echo $avatar ? $avatar : '<div class="clinica-avatar-placeholder">' . substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1) . '</div>';
                            ?>
                        </div>
                        <div class="clinica-card-actions">
                            <input type="checkbox" name="selected_patients[]" value="<?php echo $patient->user_id; ?>">
                            <div class="clinica-action-dropdown">
                                <button type="button" class="clinica-action-btn" onclick="toggleCardActionMenu(<?php echo $patient->user_id; ?>)">
                                    <span class="dashicons dashicons-menu"></span>
                                </button>
                                <div class="clinica-action-menu" id="card-action-menu-<?php echo $patient->user_id; ?>">
                                    <a href="#" onclick="viewPatient(<?php echo $patient->user_id; ?>); return false;">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php _e('Vezi', 'clinica'); ?>
                                    </a>
                                    <a href="#" onclick="editPatient(<?php echo $patient->user_id; ?>); return false;">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php _e('Editează', 'clinica'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="clinica-card-body">
                        <h3 class="clinica-card-name">
                            <?php 
                            $full_name = trim($patient->first_name . ' ' . $patient->last_name);
                            echo esc_html(!empty($full_name) ? $full_name : $patient->display_name); 
                            ?>
                        </h3>
                        
                        <div class="clinica-card-details">
                            <div class="clinica-card-item">
                                <span class="dashicons dashicons-id"></span>
                                <code><?php echo esc_html($patient->cnp); ?></code>
                            </div>
                            
                            <?php if ($patient->user_email): ?>
                            <div class="clinica-card-item">
                                <span class="dashicons dashicons-email-alt"></span>
                                <a href="mailto:<?php echo esc_attr($patient->user_email); ?>"><?php echo esc_html($patient->user_email); ?></a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($patient->phone_primary): ?>
                            <div class="clinica-card-item">
                                <span class="dashicons dashicons-phone"></span>
                                <a href="tel:<?php echo esc_attr($patient->phone_primary); ?>"><?php echo esc_html($patient->phone_primary); ?></a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($patient->phone_secondary): ?>
                            <div class="clinica-card-item">
                                <span class="dashicons dashicons-phone"></span>
                                <span class="clinica-secondary-phone"><?php echo esc_html($patient->phone_secondary); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($patient->family_id && $patient->family_name): ?>
                        <div class="clinica-card-family">
                            <span class="clinica-family-badge">
                                <span class="dashicons dashicons-groups"></span>
                                <?php echo esc_html($patient->family_name); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="clinica-card-footer">
                        <span class="clinica-card-date">
                            <?php echo esc_html(date('d.m.Y', strtotime($patient->created_at))); ?>
                        </span>
                        <span class="clinica-card-status"><?php _e('Activ', 'clinica'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
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

<!-- Modal pentru editarea pacientului -->
<div id="edit-patient-modal" class="clinica-modal" style="display: none;">
    <div class="clinica-modal-content">
        <div class="clinica-modal-header">
            <h3 id="edit-patient-title"><?php _e('Editează Pacient', 'clinica'); ?></h3>
            <span class="clinica-modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="clinica-modal-body">
            <form id="edit-patient-form">
                <input type="hidden" id="edit-patient-id" name="patient_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-first-name"><?php _e('Prenume *', 'clinica'); ?></label>
                        <input type="text" id="edit-first-name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-last-name"><?php _e('Nume *', 'clinica'); ?></label>
                        <input type="text" id="edit-last-name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-email"><?php _e('Email', 'clinica'); ?></label>
                        <input type="email" id="edit-email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="edit-phone-primary"><?php _e('Telefon Principal', 'clinica'); ?></label>
                        <input type="tel" id="edit-phone-primary" name="phone_primary">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-phone-secondary"><?php _e('Telefon Secundar', 'clinica'); ?></label>
                        <input type="tel" id="edit-phone-secondary" name="phone_secondary">
                    </div>
                    <div class="form-group">
                        <label for="edit-birth-date"><?php _e('Data nașterii', 'clinica'); ?></label>
                        <input type="date" id="edit-birth-date" name="birth_date">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-gender"><?php _e('Gen', 'clinica'); ?></label>
                        <select id="edit-gender" name="gender">
                            <option value=""><?php _e('Selectează', 'clinica'); ?></option>
                            <option value="male"><?php _e('Masculin', 'clinica'); ?></option>
                            <option value="female"><?php _e('Feminin', 'clinica'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-password-method"><?php _e('Metoda parolă', 'clinica'); ?></label>
                        <select id="edit-password-method" name="password_method">
                            <option value="cnp"><?php _e('Primele 6 cifre CNP', 'clinica'); ?></option>
                            <option value="birth_date"><?php _e('Data nașterii (DDMMYY)', 'clinica'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-address"><?php _e('Adresă', 'clinica'); ?></label>
                        <textarea id="edit-address" name="address" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-emergency-contact"><?php _e('Contact de urgență', 'clinica'); ?></label>
                        <input type="tel" id="edit-emergency-contact" name="emergency_contact">
                    </div>
                </div>
                
                <!-- Secțiunea pentru familie -->
                <div class="form-section">
                    <h4><?php _e('Informații Familie', 'clinica'); ?></h4>
                    
                    <div class="form-group">
                        <label for="edit-family-option"><?php _e('Opțiune familie', 'clinica'); ?></label>
                        <select id="edit-family-option" name="family_option">
                            <option value="none"><?php _e('Nu face parte dintr-o familie', 'clinica'); ?></option>
                            <option value="new"><?php _e('Creează o familie nouă', 'clinica'); ?></option>
                            <option value="existing"><?php _e('Adaugă la o familie existentă', 'clinica'); ?></option>
                            <option value="current"><?php _e('Păstrează familia actuală', 'clinica'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Opțiunea pentru familie nouă -->
                    <div id="edit-new-family-section" class="family-section" style="display: none;">
                        <div class="form-group">
                            <label for="edit-family-name"><?php _e('Numele familiei *', 'clinica'); ?></label>
                            <input type="text" id="edit-family-name" name="family_name" placeholder="<?php _e('Ex: Familia Popescu', 'clinica'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-family-role"><?php _e('Rolul în familie *', 'clinica'); ?></label>
                            <select id="edit-family-role" name="family_role">
                                <option value=""><?php _e('Selectează rolul', 'clinica'); ?></option>
                                <option value="head"><?php _e('Reprezentant familie', 'clinica'); ?></option>
                                <option value="spouse"><?php _e('Soț/Soție', 'clinica'); ?></option>
                                <option value="child"><?php _e('Copil', 'clinica'); ?></option>
                                <option value="parent"><?php _e('Părinte', 'clinica'); ?></option>
                                <option value="sibling"><?php _e('Frate/Soră', 'clinica'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Opțiunea pentru familie existentă -->
                    <div id="edit-existing-family-section" class="family-section" style="display: none;">
                        <div class="form-group">
                            <label for="edit-family-search"><?php _e('Caută familie', 'clinica'); ?></label>
                            <div class="family-search-container">
                                <input type="text" id="edit-family-search" placeholder="<?php _e('Introduceți numele familiei sau al unui membru...', 'clinica'); ?>">
                                <button type="button" id="edit-search-family-btn" class="button"><?php _e('Caută', 'clinica'); ?></button>
                            </div>
                        </div>
                        
                        <div id="edit-family-search-results" class="family-search-results" style="display: none;">
                            <h5><?php _e('Familii găsite:', 'clinica'); ?></h5>
                            <div id="edit-family-results-list"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-existing-family-role"><?php _e('Rolul în familie *', 'clinica'); ?></label>
                            <select id="edit-existing-family-role" name="existing_family_role">
                                <option value=""><?php _e('Selectează rolul', 'clinica'); ?></option>
                                <option value="spouse"><?php _e('Soț/Soție', 'clinica'); ?></option>
                                <option value="child"><?php _e('Copil', 'clinica'); ?></option>
                                <option value="parent"><?php _e('Părinte', 'clinica'); ?></option>
                                <option value="sibling"><?php _e('Frate/Soră', 'clinica'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Afișare familie actuală -->
                    <div id="edit-current-family-info" class="current-family-info" style="display: none;">
                        <h5><?php _e('Familia actuală:', 'clinica'); ?></h5>
                        <div id="edit-current-family-details"></div>
                        <button type="button" id="edit-change-family-btn" class="button button-secondary"><?php _e('Schimbă familia', 'clinica'); ?></button>
                    </div>
                    
                    <!-- Afișare familie selectată -->
                    <div id="edit-selected-family-info" class="selected-family-info" style="display: none;">
                        <h5><?php _e('Familia selectată:', 'clinica'); ?></h5>
                        <div id="edit-selected-family-details"></div>
                        <button type="button" id="edit-change-selected-family-btn" class="button button-secondary"><?php _e('Schimbă familia', 'clinica'); ?></button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Salvează', 'clinica'); ?></button>
                    <button type="button" class="button" onclick="closeEditModal()"><?php _e('Anulează', 'clinica'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Stiluri moderne pentru pagina de pacienți */
.clinica-patients-header {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #e1e5e9;
}

.clinica-header-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
}

.clinica-header-left {
    flex: 1;
}

.clinica-header-left h1 {
    color: #333;
    margin: 0 0 15px 0;
    font-size: 24px;
    font-weight: 600;
}

.clinica-stats {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px 20px;
    background: white;
    color: #333;
    border-radius: 8px;
    min-width: 120px;
    border: 2px solid #0073aa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
    cursor: help;
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 5px;
    color: #0073aa;
}

.stat-label {
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.clinica-header-right {
    margin-left: 20px;
}

.clinica-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.clinica-actions .button {
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.clinica-actions .button-primary {
    background: #0073aa;
    border-color: #0073aa;
    color: white;
}

.clinica-actions .button-primary:hover {
    background: #005a87;
    border-color: #005a87;
    transform: translateY(-1px);
}

.clinica-actions .button:not(.button-primary) {
    background: white;
    border: 1px solid #ddd;
    color: #333;
}

.clinica-actions .button:not(.button-primary):hover {
    background: #f8f9fa;
    border-color: #0073aa;
    color: #0073aa;
    transform: translateY(-1px);
}

/* Filtre avansate */
.clinica-filters-container {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.clinica-filters-form {
    margin: 0;
}

.clinica-filters-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.clinica-filter-group {
    display: flex;
    flex-direction: column;
}

.clinica-filter-group label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #2c3e50;
}

.clinica-filter-group input,
.clinica-filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

/* Container pentru căutare cu autosuggest */
.clinica-search-container {
    position: relative;
}

.clinica-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.clinica-suggestions.show {
    display: block;
}

.clinica-suggestion-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.2s ease;
}

.clinica-suggestion-item:last-child {
    border-bottom: none;
}

.clinica-suggestion-item:hover,
.clinica-suggestion-item.selected {
    background-color: #f8f9fa;
}

.clinica-suggestion-item .suggestion-icon {
    color: #6c757d;
    font-size: 14px;
}

.clinica-suggestion-item .suggestion-text {
    flex: 1;
}

.clinica-suggestion-item .suggestion-name {
    font-weight: 500;
    color: #2c3e50;
}

.clinica-suggestion-item .suggestion-details {
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
}

.clinica-suggestion-item .suggestion-badge {
    background: #e9ecef;
    color: #495057;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 500;
}

.clinica-suggestion-item .suggestion-count {
    background: #3498db;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 500;
}

/* Stiluri pentru loading în suggestions */
.clinica-suggestions .loading {
    padding: 15px;
    text-align: center;
    color: #6c757d;
    font-style: italic;
}

.clinica-suggestions .no-results {
    padding: 15px;
    text-align: center;
    color: #6c757d;
    font-style: italic;
}

/* Highlight pentru textul căutat */
.clinica-suggestion-item .highlight {
    background-color: #fff3cd;
    padding: 1px 2px;
    border-radius: 2px;
}

.clinica-filters-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.clinica-advanced-filters {
    border-top: 1px solid #e1e5e9;
    padding-top: 15px;
    margin-top: 15px;
}

/* Rezultate și informații */
.clinica-results-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px 0;
}

.clinica-results-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.clinica-active-filters {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 12px;
}

.clinica-view-options {
    display: flex;
    gap: 5px;
}

/* Tabel îmbunătățit */
.clinica-patients-table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.clinica-patients-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
    padding: 15px 10px;
    border-bottom: 2px solid #e1e5e9;
}

.clinica-patients-table td {
    padding: 15px 10px;
    vertical-align: middle;
}

.clinica-patient-row:hover {
    background: #f8f9fa;
}

/* Informații pacient */
.clinica-patient-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.clinica-patient-avatar {
    flex-shrink: 0;
}

.clinica-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.clinica-patient-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.clinica-patient-name {
    color: #2c3e50;
    font-size: 14px;
}

.clinica-patient-id {
    font-size: 12px;
    color: #6c757d;
}

.clinica-cnp {
    background: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
}

/* Informații contact */
.clinica-contact-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.clinica-contact-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
}

.clinica-contact-item a {
    color: #3498db;
    text-decoration: none;
}

.clinica-contact-item a:hover {
    text-decoration: underline;
}

.clinica-secondary-phone {
    color: #6c757d;
}

/* Informații familie */
.clinica-family-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.clinica-family-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #d5f4e6;
    color: #27ae60;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.clinica-family-role {
    font-size: 11px;
    color: #6c757d;
}

.clinica-no-family {
    color: #6c757d;
    font-style: italic;
    font-size: 12px;
}

/* Informații demografice */
.clinica-demographics {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.clinica-demographic-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #2c3e50;
}

/* Status pacient */
.clinica-patient-status {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.clinica-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.clinica-status-active {
    background: #d5f4e6;
    color: #27ae60;
}

.clinica-created-date {
    font-size: 11px;
    color: #6c757d;
}

/* Acțiuni */
.clinica-actions {
    display: flex;
    justify-content: center;
}

.clinica-action-buttons {
    display: flex;
    gap: 5px;
}

.clinica-action-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.clinica-action-btn:hover {
    background: #f8f9fa;
    border-color: #3498db;
    color: #3498db;
}

.clinica-action-dropdown {
    position: relative;
}

.clinica-action-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    min-width: 150px;
    z-index: 1000;
    display: none;
}

.clinica-action-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    color: #2c3e50;
    text-decoration: none;
    font-size: 13px;
}

.clinica-action-menu a:hover {
    background: #f8f9fa;
}

/* Vizualizare carduri */
.clinica-patients-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.clinica-patient-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.clinica-patient-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.clinica-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #e1e5e9;
}

.clinica-card-avatar {
    flex-shrink: 0;
}

.clinica-card-avatar .clinica-avatar-placeholder {
    width: 60px;
    height: 60px;
    font-size: 20px;
}

.clinica-card-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.clinica-card-body {
    padding: 15px;
}

.clinica-card-name {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 16px;
    font-weight: 600;
}

.clinica-card-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
}

.clinica-card-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.clinica-card-item a {
    color: #3498db;
    text-decoration: none;
}

.clinica-card-family {
    margin-top: 10px;
}

.clinica-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: #f8f9fa;
    border-top: 1px solid #e1e5e9;
    font-size: 12px;
    color: #6c757d;
}

/* Stare goală */
.clinica-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.clinica-empty-content {
    max-width: 400px;
    margin: 0 auto;
}

.clinica-empty-content .dashicons {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 20px;
}

.clinica-empty-content h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.clinica-empty-content p {
    margin: 0 0 20px 0;
    color: #6c757d;
}

/* Responsive */
@media (max-width: 768px) {
    .clinica-patients-header {
        flex-direction: column;
        gap: 20px;
    }
    
    .clinica-stats {
        justify-content: center;
    }
    
    .clinica-filters-row {
        grid-template-columns: 1fr;
    }
    
    .clinica-patients-grid {
        grid-template-columns: 1fr;
    }
    
    .clinica-patients-table {
        font-size: 12px;
    }
    
    .clinica-patient-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}

/* Acțiuni în masă */
.clinica-bulk-actions {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.clinica-bulk-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.clinica-bulk-buttons {
    display: flex;
    gap: 10px;
}

.clinica-bulk-buttons .button {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
}

/* Butoane de vizualizare active */
.clinica-view-options .button.active {
    background: #3498db;
    border-color: #3498db;
    color: white;
}

/* Îmbunătățiri pentru accesibilitate */
.clinica-action-btn:focus,
.clinica-filter-group input:focus,
.clinica-filter-group select:focus {
    outline: 2px solid #3498db;
    outline-offset: 2px;
}

/* Animații pentru hover */
.clinica-patient-card,
.clinica-action-btn,
.clinica-filter-group input,
.clinica-filter-group select {
    transition: all 0.2s ease;
}

/* Stiluri pentru loading states */
.clinica-loading {
    opacity: 0.6;
    pointer-events: none;
}

.clinica-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.row-actions span:last-child {
    margin-right: 0;
}

/* Modal Styles */
.clinica-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.clinica-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.clinica-modal-header {
    padding: 15px 20px;
    background-color: #f1f1f1;
    border-bottom: 1px solid #ddd;
    border-radius: 5px 5px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.clinica-modal-header h3 {
    margin: 0;
    color: #23282d;
}

.clinica-modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.clinica-modal-close:hover {
    color: #000;
}

.clinica-modal-body {
    padding: 20px;
}

.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    flex: 1;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-group textarea {
    resize: vertical;
}

.form-actions {
    margin-top: 20px;
    text-align: right;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.form-actions button {
    margin-left: 10px;
}

/* Stiluri pentru gestionarea familiilor în formularul de editare */
.form-section {
    margin-top: 20px;
    padding: 15px;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    background: #f8f9fa;
}

.form-section h4 {
    margin-top: 0;
    color: #2c3e50;
    font-size: 16px;
    font-weight: 600;
    border-bottom: 2px solid #3498db;
    padding-bottom: 8px;
    margin-bottom: 15px;
}

.family-section {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    background: white;
}

.family-search-container {
    display: flex;
    gap: 10px;
    align-items: center;
}

.family-search-container input {
    flex: 1;
}

.family-search-results {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    background: white;
}

.family-results {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.family-result-item {
    padding: 15px;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    background: #f8f9fa;
}

.family-result-item h6 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 14px;
}

.family-result-item p {
    margin: 5px 0;
    color: #6c757d;
    font-size: 12px;
}

.select-family-btn {
    margin-top: 10px;
    background: #3498db;
    border-color: #3498db;
    color: white;
    font-size: 12px;
    padding: 5px 10px;
}

.select-family-btn:hover {
    background: #2980b9;
    border-color: #2980b9;
}

.current-family-info,
.selected-family-info {
    margin-top: 15px;
    padding: 15px;
    border: 2px solid #27ae60;
    border-radius: 6px;
    background: #d5f4e6;
}

.current-family-info h5,
.selected-family-info h5 {
    margin: 0 0 10px 0;
    color: #27ae60;
    font-size: 14px;
}

.current-family-info p,
.selected-family-info p {
    margin: 5px 0;
    color: #2c3e50;
    font-size: 12px;
}

.change-family-btn,
.change-selected-family-btn {
    margin-top: 10px;
    background: #f39c12;
    border-color: #f39c12;
    color: white;
    font-size: 12px;
    padding: 5px 10px;
}

.change-family-btn:hover,
.change-selected-family-btn:hover {
    background: #e67e22;
    border-color: #e67e22;
}

/* Responsive design */
@media (max-width: 1200px) {
    .clinica-header-main {
        flex-direction: column;
        gap: 15px;
    }
    
    .clinica-header-right {
        margin-left: 0;
    }
    
    .clinica-stats {
        gap: 15px;
    }
    
    .stat-item {
        min-width: 100px;
        padding: 12px 16px;
    }
}

@media (max-width: 768px) {
    .clinica-patients-header {
        padding: 15px;
    }
    
    .clinica-header-left h1 {
        font-size: 20px;
        margin-bottom: 10px;
    }
    
    .clinica-stats {
        gap: 10px;
    }
    
    .stat-item {
        min-width: 80px;
        padding: 10px 12px;
    }
    
    .stat-number {
        font-size: 22px;
    }
    
    .stat-label {
        font-size: 12px;
    }
    
    .clinica-actions {
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }
    
    .clinica-actions .button {
        width: 100%;
        justify-content: center;
    }
}

/* Stiluri pentru coloanele tabelului */
.column-name {
    width: 20%;
}

.column-cnp {
    width: 12%;
}

.column-email {
    width: 18%;
}

.column-family {
    width: 15%;
}

.column-birth-date {
    width: 12%;
}

.column-age {
    width: 8%;
}

.column-status {
    width: 8%;
}

.column-actions {
    width: 7%;
}

/* Stiluri pentru email */
.column-email a {
    color: #0073aa;
    text-decoration: none;
}

.column-email a:hover {
    text-decoration: underline;
}

.clinica-no-email {
    color: #999;
    font-style: italic;
}

/* Stiluri pentru data nașterii */
.column-birth-date {
    text-align: center;
}

.clinica-no-date {
    color: #999;
    font-style: italic;
}

/* Stiluri pentru vârsta */
.column-age {
    text-align: center;
    font-weight: 600;
    color: #0073aa;
}

.clinica-no-age {
    color: #999;
    font-style: italic;
}

/* Stiluri pentru status (coloană îngustă) */
.column-status {
    text-align: center;
}

.clinica-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.clinica-status-active {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Stiluri pentru familie */
.clinica-family-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: #e3f2fd;
    color: #0d47a1;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.clinica-no-family {
    color: #999;
    font-style: italic;
    font-size: 11px;
}

/* Stiluri pentru badge-ul de filtru activ */
.clinica-filter-badge {
    display: inline-block;
    background: #0073aa;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
}

.clinica-active-filters {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 12px;
}
</style>

<script>
// Funcții pentru pagina de pacienți îmbunătățită

// Variabile globale pentru autosuggest
let searchTimeout = null;
let currentSuggestionIndex = -1;
let suggestionsData = [];

// Funcție pentru highlight text
function highlightText(text, searchTerm) {
    if (!searchTerm) return text;
    const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<span class="highlight">$1</span>');
}

// Funcție pentru căutare pacienți cu autosuggest
function searchPatientsSuggestions(searchTerm, inputId) {
    if (searchTerm.length < 2) {
        hideSuggestions(inputId);
        return;
    }
    
    // Verifică dacă elementele există înainte de a continua
    const inputElement = document.getElementById(inputId);
    
    // Mapare ID-uri pentru suggestions
    let suggestionsId;
    if (inputId === 'cnp-filter') {
        suggestionsId = 'cnp-suggestions';
    } else if (inputId === 'search-input') {
        suggestionsId = 'search-suggestions';
    } else if (inputId === 'family-filter') {
        suggestionsId = 'family-suggestions';
    } else {
        suggestionsId = inputId + '-suggestions';
    }
    
    const suggestionsElement = document.getElementById(suggestionsId);
    
    if (!inputElement || !suggestionsElement) {
        console.error('Elementele necesare nu există pentru:', inputId);
        console.log('Input element:', inputElement);
        console.log('Suggestions element:', suggestionsElement, 'ID căutat:', suggestionsId);
        return;
    }
    
    showLoadingSuggestions(inputId);
    
    console.log('Căutare pacienți:', searchTerm, inputId);
    
    // Verifică dacă variabilele sunt disponibile
    const ajaxUrl = (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.ajaxurl) ? 
                   clinica_autosuggest.ajaxurl : 
                   (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
    
    const searchNonce = (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.search_nonce) ? 
                       clinica_autosuggest.search_nonce : 
                       '<?php echo wp_create_nonce('clinica_search_nonce'); ?>';
    
    console.log('AJAX URL:', ajaxUrl);
    console.log('Search nonce:', searchNonce);
    
    jQuery.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'clinica_search_patients_suggestions',
            search_term: searchTerm,
            search_type: inputId,
            nonce: searchNonce
        },
        success: function(response) {
            console.log('Răspuns AJAX:', response);
            if (response.success) {
                displaySuggestions(response.data.suggestions, inputId);
                suggestionsData.searchTerm = response.data.searchTerm;
            } else {
                showNoResultsSuggestions(inputId);
            }
        },
        error: function(xhr, status, error) {
            console.error('Eroare AJAX:', xhr, status, error);
            showNoResultsSuggestions(inputId);
        }
    });
}

// Funcție pentru căutare familii cu autosuggest
function searchFamiliesSuggestions(searchTerm) {
    if (searchTerm.length < 2) {
        hideSuggestions('family-filter');
        return;
    }
    
    // Verifică dacă elementele există înainte de a continua
    const inputElement = document.getElementById('family-filter');
    const suggestionsElement = document.getElementById('family-suggestions');
    
    if (!inputElement || !suggestionsElement) {
        console.error('Elementele necesare nu există pentru family-filter');
        console.log('Input element:', inputElement);
        console.log('Suggestions element:', suggestionsElement);
        return;
    }
    
    showLoadingSuggestions('family-filter');
    
    console.log('Căutare familii:', searchTerm);
    
    // Verifică dacă variabilele sunt disponibile
    const ajaxUrl = (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.ajaxurl) ? 
                   clinica_autosuggest.ajaxurl : 
                   (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
    
    const familyNonce = (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.family_nonce) ? 
                       clinica_autosuggest.family_nonce : 
                       '<?php echo wp_create_nonce('clinica_family_nonce'); ?>';
    
    console.log('AJAX URL familii:', ajaxUrl);
    console.log('Family nonce:', familyNonce);
    
    jQuery.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'clinica_search_families_suggestions',
            search_term: searchTerm,
            nonce: familyNonce
        },
        success: function(response) {
            console.log('Răspuns AJAX familii:', response);
            if (response.success) {
                displayFamilySuggestions(response.data.suggestions);
                suggestionsData.searchTerm = response.data.searchTerm;
            } else {
                showNoResultsSuggestions('family-filter');
            }
        },
        error: function(xhr, status, error) {
            console.error('Eroare AJAX familii:', xhr, status, error);
            showNoResultsSuggestions('family-filter');
        }
    });
}

// Afișează loading în suggestions
function showLoadingSuggestions(inputId) {
    // Mapare ID-uri pentru suggestions
    let suggestionsId;
    if (inputId === 'cnp-filter') {
        suggestionsId = 'cnp-suggestions';
    } else if (inputId === 'search-input') {
        suggestionsId = 'search-suggestions';
    } else if (inputId === 'family-filter') {
        suggestionsId = 'family-suggestions';
    } else {
        suggestionsId = inputId + '-suggestions';
    }
    
    const suggestions = document.getElementById(suggestionsId);
    if (!suggestions) {
        console.error('Elementul suggestions nu există pentru:', inputId, 'ID căutat:', suggestionsId);
        return;
    }
    suggestions.innerHTML = '<div class="loading">Căutare...</div>';
    suggestions.classList.add('show');
}

// Afișează "nu s-au găsit rezultate"
function showNoResultsSuggestions(inputId) {
    // Mapare ID-uri pentru suggestions
    let suggestionsId;
    if (inputId === 'cnp-filter') {
        suggestionsId = 'cnp-suggestions';
    } else if (inputId === 'search-input') {
        suggestionsId = 'search-suggestions';
    } else if (inputId === 'family-filter') {
        suggestionsId = 'family-suggestions';
    } else {
        suggestionsId = inputId + '-suggestions';
    }
    
    const suggestions = document.getElementById(suggestionsId);
    if (!suggestions) {
        console.error('Elementul suggestions nu există pentru:', inputId, 'ID căutat:', suggestionsId);
        return;
    }
    suggestions.innerHTML = '<div class="no-results">Nu s-au găsit rezultate</div>';
    suggestions.classList.add('show');
}

// Ascunde suggestions
function hideSuggestions(inputId) {
    // Mapare ID-uri pentru suggestions
    let suggestionsId;
    if (inputId === 'cnp-filter') {
        suggestionsId = 'cnp-suggestions';
    } else if (inputId === 'search-input') {
        suggestionsId = 'search-suggestions';
    } else if (inputId === 'family-filter') {
        suggestionsId = 'family-suggestions';
    } else {
        suggestionsId = inputId + '-suggestions';
    }
    
    const suggestions = document.getElementById(suggestionsId);
    if (!suggestions) {
        console.error('Elementul suggestions nu există pentru:', inputId, 'ID căutat:', suggestionsId);
        return;
    }
    suggestions.classList.remove('show');
    currentSuggestionIndex = -1;
    suggestionsData = [];
}

// Afișează suggestions pentru pacienți
function displaySuggestions(data, inputId) {
    // Mapare ID-uri pentru suggestions
    let suggestionsId;
    if (inputId === 'cnp-filter') {
        suggestionsId = 'cnp-suggestions';
    } else if (inputId === 'search-input') {
        suggestionsId = 'search-suggestions';
    } else if (inputId === 'family-filter') {
        suggestionsId = 'family-suggestions';
    } else {
        suggestionsId = inputId + '-suggestions';
    }
    
    const suggestions = document.getElementById(suggestionsId);
    if (!suggestions) {
        console.error('Elementul suggestions nu există pentru:', inputId, 'ID căutat:', suggestionsId);
        return;
    }
    
    suggestionsData = data;
    
    if (!data || data.length === 0) {
        showNoResultsSuggestions(inputId);
        return;
    }
    
    let html = '';
    data.forEach((item, index) => {
        html += '<div class="clinica-suggestion-item" data-index="' + index + '" onclick="selectSuggestion(' + index + ', \'' + inputId + '\')">';
        
        if (inputId === 'search-input') {
            // Suggestion pentru căutare generală
            html += '<span class="suggestion-icon dashicons dashicons-admin-users"></span>';
            html += '<div class="suggestion-text">';
            html += '<div class="suggestion-name">' + highlightText(item.name, suggestionsData.searchTerm) + '</div>';
            html += '<div class="suggestion-details">';
            if (item.email) html += '📧 ' + highlightText(item.email, suggestionsData.searchTerm) + ' ';
            if (item.phone) html += '📞 ' + highlightText(item.phone, suggestionsData.searchTerm) + ' ';
            if (item.cnp) html += '🆔 ' + highlightText(item.cnp, suggestionsData.searchTerm);
            html += '</div>';
            html += '</div>';
            if (item.family_name) {
                html += '<span class="suggestion-badge">' + item.family_name + '</span>';
            }
        } else if (inputId === 'cnp-filter') {
            // Suggestion pentru CNP
            html += '<span class="suggestion-icon dashicons dashicons-id"></span>';
            html += '<div class="suggestion-text">';
            html += '<div class="suggestion-name">' + highlightText(item.cnp, suggestionsData.searchTerm) + '</div>';
            html += '<div class="suggestion-details">' + item.name + '</div>';
            html += '</div>';
        }
        
        html += '</div>';
    });
    
    suggestions.innerHTML = html;
    suggestions.classList.add('show');
}

// Afișează suggestions pentru familii
function displayFamilySuggestions(data) {
    const suggestions = document.getElementById('family-suggestions');
    if (!suggestions) {
        console.error('Elementul family-suggestions nu există');
        return;
    }
    
    suggestionsData = data;
    
    if (!data || data.length === 0) {
        showNoResultsSuggestions('family-filter');
        return;
    }
    
    let html = '';
    data.forEach((family, index) => {
        html += '<div class="clinica-suggestion-item" data-index="' + index + '" onclick="selectFamilySuggestion(' + index + ')">';
        html += '<span class="suggestion-icon dashicons dashicons-groups"></span>';
        html += '<div class="suggestion-text">';
        html += '<div class="suggestion-name">' + highlightText(family.family_name, suggestionsData.searchTerm) + '</div>';
        html += '<div class="suggestion-details">' + family.members + ' membri</div>';
        html += '</div>';
        html += '<span class="suggestion-count">' + family.member_count + '</span>';
        html += '</div>';
    });
    
    suggestions.innerHTML = html;
    suggestions.classList.add('show');
}

// Selectează o suggestion
function selectSuggestion(index, inputId) {
    if (index >= 0 && index < suggestionsData.length) {
        const item = suggestionsData[index];
        const input = document.getElementById(inputId);
        
        if (inputId === 'search-input') {
            input.value = item.name;
        } else if (inputId === 'cnp-filter') {
            input.value = item.cnp;
        }
        
        hideSuggestions(inputId);
        
        // Opțional: trimite formularul automat după selectare
        // document.querySelector('.clinica-filters-form').submit();
    }
}

// Selectează o suggestion de familie
function selectFamilySuggestion(index) {
    if (index >= 0 && index < suggestionsData.length) {
        const family = suggestionsData[index];
        const input = document.getElementById('family-filter');
        const hiddenInput = document.getElementById('family-filter-value');
        
        input.value = family.family_name;
        hiddenInput.value = family.family_id;
        
        hideSuggestions('family-filter');
    }
}

// Navigare cu taste în suggestions
function handleSuggestionNavigation(event, inputId) {
    // Mapare ID-uri pentru suggestions
    let suggestionsId;
    if (inputId === 'cnp-filter') {
        suggestionsId = 'cnp-suggestions';
    } else if (inputId === 'search-input') {
        suggestionsId = 'search-suggestions';
    } else if (inputId === 'family-filter') {
        suggestionsId = 'family-suggestions';
    } else {
        suggestionsId = inputId + '-suggestions';
    }
    
    const suggestions = document.getElementById(suggestionsId);
    if (!suggestions) {
        console.error('Elementul suggestions nu există pentru navigare:', inputId, 'ID căutat:', suggestionsId);
        return;
    }
    
    const items = suggestions.querySelectorAll('.clinica-suggestion-item');
    
    if (items.length === 0) return;
    
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        currentSuggestionIndex = Math.min(currentSuggestionIndex + 1, items.length - 1);
        updateSuggestionSelection(items);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        currentSuggestionIndex = Math.max(currentSuggestionIndex - 1, -1);
        updateSuggestionSelection(items);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        if (currentSuggestionIndex >= 0) {
            if (inputId === 'family-filter') {
                selectFamilySuggestion(currentSuggestionIndex);
            } else {
                selectSuggestion(currentSuggestionIndex, inputId);
            }
        }
    } else if (event.key === 'Escape') {
        hideSuggestions(inputId);
    }
}

// Actualizează selecția în suggestions
function updateSuggestionSelection(items) {
    items.forEach((item, index) => {
        if (index === currentSuggestionIndex) {
            item.classList.add('selected');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('selected');
        }
    });
}

// Toggle filtre avansate
function toggleAdvancedFilters() {
    const advancedFilters = document.getElementById('advanced-filters');
    const isVisible = advancedFilters.style.display !== 'none';
    advancedFilters.style.display = isVisible ? 'none' : 'block';
}

// Schimbare mod de vizualizare
function setViewMode(mode) {
    const tableView = document.getElementById('patients-table-view');
    const cardsView = document.getElementById('patients-cards-view');
    const tableBtn = document.getElementById('view-table');
    const cardsBtn = document.getElementById('view-cards');
    
    if (mode === 'table') {
        tableView.style.display = 'block';
        cardsView.style.display = 'none';
        tableBtn.classList.add('active');
        cardsBtn.classList.remove('active');
        localStorage.setItem('clinica_view_mode', 'table');
    } else {
        tableView.style.display = 'none';
        cardsView.style.display = 'block';
        tableBtn.classList.remove('active');
        cardsBtn.classList.add('active');
        localStorage.setItem('clinica_view_mode', 'cards');
    }
}

// Toggle meniu acțiuni
function toggleActionMenu(patientId) {
    const menu = document.getElementById('action-menu-' + patientId);
    const allMenus = document.querySelectorAll('.clinica-action-menu');
    
    // Închide toate meniurile
    allMenus.forEach(m => {
        if (m !== menu) {
            m.style.display = 'none';
        }
    });
    
    // Toggle meniul curent
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

// Toggle meniu acțiuni pentru carduri
function toggleCardActionMenu(patientId) {
    const menu = document.getElementById('card-action-menu-' + patientId);
    const allMenus = document.querySelectorAll('.clinica-action-menu');
    
    allMenus.forEach(m => {
        if (m !== menu) {
            m.style.display = 'none';
        }
    });
    
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

// Selectare toți pacienții
function selectAllPatients() {
    const selectAll = document.getElementById('select-all-patients');
    const checkboxes = document.querySelectorAll('input[name="selected_patients[]"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

// Actualizare acțiuni în masă
function updateBulkActions() {
    const selectedCount = document.querySelectorAll('input[name="selected_patients[]"]:checked').length;
    const bulkActions = document.getElementById('bulk-actions');
    
    if (selectedCount > 0) {
        if (!bulkActions) {
            createBulkActions();
        }
        document.getElementById('selected-count').textContent = selectedCount;
    } else {
        if (bulkActions) {
            bulkActions.remove();
        }
    }
}

// Creează acțiuni în masă
function createBulkActions() {
    const existingBulk = document.getElementById('bulk-actions');
    if (existingBulk) return;
    
    const bulkActions = document.createElement('div');
    bulkActions.id = 'bulk-actions';
    bulkActions.className = 'clinica-bulk-actions';
    bulkActions.innerHTML = `
        <div class="clinica-bulk-content">
            <span><span id="selected-count">0</span> pacienți selectați</span>
            <div class="clinica-bulk-buttons">
                <button type="button" class="button" onclick="exportSelectedPatients()">
                    <span class="dashicons dashicons-download"></span>
                    Export
                </button>
                <button type="button" class="button" onclick="addToFamily()">
                    <span class="dashicons dashicons-groups"></span>
                    Adaugă la familie
                </button>
                <button type="button" class="button button-link-delete" onclick="deleteSelectedPatients()">
                    <span class="dashicons dashicons-trash"></span>
                    Șterge
                </button>
            </div>
        </div>
    `;
    
    document.querySelector('.clinica-results-info').after(bulkActions);
}

// Funcții pentru acțiuni
function viewPatient(patientId) {
    // Implementează vizualizarea detaliată a pacientului
    window.open(adminurl + 'admin.php?page=clinica-patients&action=view&id=' + patientId, '_blank');
}

function viewPatientHistory(patientId) {
    // Implementează vizualizarea istoricului pacientului
    alert('Funcționalitatea de istoric va fi implementată în curând.');
}

function exportPatientData(patientId) {
    // Implementează exportul datelor pacientului
    window.open(adminurl + 'admin-ajax.php?action=clinica_export_patient&patient_id=' + patientId, '_blank');
}

function viewFamilyDetails(familyId) {
    // Implementează vizualizarea detaliilor familiei
    window.open(adminurl + 'admin.php?page=clinica-families&action=view&id=' + familyId, '_blank');
}

function exportPatients() {
    // Implementează exportul tuturor pacienților
    window.open(adminurl + 'admin-ajax.php?action=clinica_export_patients', '_blank');
}

function showBulkActions() {
    // Implementează afișarea acțiunilor în masă
    alert('Funcționalitatea de acțiuni în masă va fi implementată în curând.');
}

function exportSelectedPatients() {
    const selected = Array.from(document.querySelectorAll('input[name="selected_patients[]"]:checked'))
        .map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Selectați cel puțin un pacient pentru export.');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = adminurl + 'admin-ajax.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'clinica_export_selected_patients';
    
    const patientsInput = document.createElement('input');
    patientsInput.type = 'hidden';
    patientsInput.name = 'patient_ids';
    patientsInput.value = JSON.stringify(selected);
    
    form.appendChild(actionInput);
    form.appendChild(patientsInput);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function addToFamily() {
    const selected = Array.from(document.querySelectorAll('input[name="selected_patients[]"]:checked'))
        .map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Selectați cel puțin un pacient pentru a adăuga la familie.');
        return;
    }
    
    // Implementează adăugarea la familie
    alert('Funcționalitatea de adăugare la familie va fi implementată în curând.');
}

function deleteSelectedPatients() {
    const selected = Array.from(document.querySelectorAll('input[name="selected_patients[]"]:checked'))
        .map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Selectați cel puțin un pacient pentru ștergere.');
        return;
    }
    
    if (confirm('Sigur doriți să ștergeți ' + selected.length + ' pacienți? Această acțiune nu poate fi anulată.')) {
        // Implementează ștergerea pacienților
        alert('Funcționalitatea de ștergere va fi implementată în curând.');
    }
}

// Funcții existente
function editPatient(patientId) {
    // Afișează modalul
    document.getElementById('edit-patient-modal').style.display = 'block';
    
    // Încarcă datele pacientului
    loadPatientData(patientId);
}

function closeEditModal() {
    document.getElementById('edit-patient-modal').style.display = 'none';
}

function loadPatientData(patientId) {
    // Aici vei face un AJAX call pentru a încărca datele pacientului
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'clinica_get_patient_data',
            patient_id: patientId,
            nonce: '<?php echo wp_create_nonce('clinica_nonce'); ?>'
        },
        success: function(response) {
            if (response.success) {
                var patient = response.data;
                
                // Actualizează titlul modalului cu numele și username-ul pacientului
                var fullName = (patient.first_name || '') + ' ' + (patient.last_name || '');
                var username = patient.username || '';
                document.getElementById('edit-patient-title').innerHTML = 
                    'Editează Pacient: ' + fullName.trim() + ' - ' + username;
                
                // Populează formularul
                document.getElementById('edit-patient-id').value = patient.user_id;
                document.getElementById('edit-first-name').value = patient.first_name || '';
                document.getElementById('edit-last-name').value = patient.last_name || '';
                document.getElementById('edit-email').value = patient.email || '';
                document.getElementById('edit-phone-primary').value = patient.phone_primary || '';
                document.getElementById('edit-phone-secondary').value = patient.phone_secondary || '';
                document.getElementById('edit-birth-date').value = patient.birth_date || '';
                document.getElementById('edit-gender').value = patient.gender || '';
                document.getElementById('edit-password-method').value = patient.password_method || 'cnp';
                document.getElementById('edit-address').value = patient.address || '';
                document.getElementById('edit-emergency-contact').value = patient.emergency_contact || '';
                
                // Populează informațiile de familie
                loadFamilyData(patient);
            } else {
                alert('Eroare la încărcarea datelor pacientului: ' + response.data);
            }
        },
        error: function() {
            alert('Eroare la încărcarea datelor pacientului.');
        }
    });
}

function loadFamilyData(patient) {
    // Verifică dacă pacientul face parte dintr-o familie
    if (patient.family_id && patient.family_name) {
        // Afișează informațiile despre familia actuală
        document.getElementById('edit-current-family-details').innerHTML = 
            '<p><strong>Familia:</strong> ' + patient.family_name + '</p>' +
            '<p><strong>Rolul:</strong> ' + getFamilyRoleLabel(patient.family_role) + '</p>' +
            '<p><strong>ID Familie:</strong> ' + patient.family_id + '</p>';
        
        // Setează opțiunea la "Păstrează familia actuală"
        document.getElementById('edit-family-option').value = 'current';
        document.getElementById('edit-current-family-info').style.display = 'block';
        
        // Salvează informațiile despre familie pentru a le păstra la salvare
        document.getElementById('edit-patient-id').setAttribute('data-family-id', patient.family_id);
        document.getElementById('edit-patient-id').setAttribute('data-family-role', patient.family_role);
        document.getElementById('edit-patient-id').setAttribute('data-family-name', patient.family_name);
    } else {
        // Pacientul nu face parte dintr-o familie
        document.getElementById('edit-family-option').value = 'none';
        document.getElementById('edit-current-family-info').style.display = 'none';
    }
}

function getFamilyRoleLabel(role) {
    var labels = {
        'head': 'Reprezentant familie',
        'spouse': 'Soț/Soție',
        'child': 'Copil',
        'parent': 'Părinte',
        'sibling': 'Frate/Soră'
    };
    return labels[role] || role;
}

// Gestionează trimiterea formularului
jQuery(document).ready(function($) {
    // Verifică dacă variabilele sunt disponibile
    console.log('=== DEBUG AUTOSUGGEST ===');
    console.log('Verificare variabile disponibile:');
    console.log('clinica_autosuggest:', typeof clinica_autosuggest !== 'undefined' ? clinica_autosuggest : 'UNDEFINED');
    console.log('clinica_ajax:', typeof clinica_ajax !== 'undefined' ? clinica_ajax : 'UNDEFINED');
    console.log('ajaxurl:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'UNDEFINED');
    console.log('jQuery version:', $.fn.jquery);
    console.log('Document ready triggered');
    console.log('Current page URL:', window.location.href);
    console.log('Scripts loaded:', document.scripts.length);
    
    if (typeof clinica_autosuggest === 'undefined') {
        console.error('clinica_autosuggest nu este definit!');
        // Încearcă să folosească ajaxurl dacă este disponibil
        if (typeof ajaxurl === 'undefined') {
            console.error('Nici ajaxurl nu este disponibil!');
            console.error('Scripturile nu sunt încărcate corect!');
            return;
        }
    }
    
    // Inițializare pagină
    initPatientsPage();
    
    // Event listeners pentru checkbox-uri
    $(document).on('change', 'input[name="selected_patients[]"]', function() {
        updateBulkActions();
    });
    
    $(document).on('change', '#select-all-patients', function() {
        selectAllPatients();
    });
    
    // Închide meniurile când se face click în afara lor
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.clinica-action-dropdown').length) {
            $('.clinica-action-menu').hide();
        }
    });
    
    // Restaurează modul de vizualizare salvat
    const savedViewMode = localStorage.getItem('clinica_view_mode');
    if (savedViewMode) {
        setViewMode(savedViewMode);
    }
    
    // Funcție de inițializare
    function initPatientsPage() {
        // Adaugă loading states pentru butoane
        $('.clinica-action-btn').on('click', function() {
            const $btn = $(this);
            if (!$btn.hasClass('clinica-loading')) {
                $btn.addClass('clinica-loading');
                setTimeout(() => {
                    $btn.removeClass('clinica-loading');
                }, 1000);
            }
        });
        
        // Inițializează autosuggest pentru căutare
        initAutosuggest();
    }
    
    // Inițializează autosuggest
    function initAutosuggest() {
        console.log('Inițializare autosuggest...');
        
        // Verifică dacă elementele există
        const searchInput = document.getElementById('search-input');
        const cnpFilter = document.getElementById('cnp-filter');
        const familyFilter = document.getElementById('family-filter');
        
        const searchSuggestions = document.getElementById('search-suggestions');
        const cnpSuggestions = document.getElementById('cnp-suggestions');
        const familySuggestions = document.getElementById('family-suggestions');
        
        if (!searchInput) console.error('Elementul search-input nu există');
        if (!cnpFilter) console.error('Elementul cnp-filter nu există');
        if (!familyFilter) console.error('Elementul family-filter nu există');
        
        if (!searchSuggestions) console.error('Elementul search-suggestions nu există');
        if (!cnpSuggestions) console.error('Elementul cnp-suggestions nu există');
        if (!familySuggestions) console.error('Elementul family-suggestions nu există');
        
        // Verifică dacă toate elementele necesare sunt prezente
        const allElementsExist = searchInput && cnpFilter && familyFilter && 
                                searchSuggestions && cnpSuggestions && familySuggestions;
        
        if (!allElementsExist) {
            console.error('Nu toate elementele necesare pentru autosuggest sunt prezente!');
            return;
        }
        
        console.log('Toate elementele pentru autosuggest sunt prezente!');
        console.log('Elemente găsite:', {
            searchInput: !!searchInput,
            cnpFilter: !!cnpFilter,
            familyFilter: !!familyFilter,
            searchSuggestions: !!searchSuggestions,
            cnpSuggestions: !!cnpSuggestions,
            familySuggestions: !!familySuggestions
        });
        
        // Funcție pentru a verifica dacă elementele există
        function checkElementsExist() {
            const elements = {
                'search-input': document.getElementById('search-input'),
                'cnp-filter': document.getElementById('cnp-filter'),
                'family-filter': document.getElementById('family-filter'),
                'search-suggestions': document.getElementById('search-suggestions'),
                'cnp-suggestions': document.getElementById('cnp-suggestions'),
                'family-suggestions': document.getElementById('family-suggestions')
            };
            
            const missingElements = Object.entries(elements)
                .filter(([name, element]) => !element)
                .map(([name]) => name);
            
            if (missingElements.length > 0) {
                console.warn('Elemente lipsă:', missingElements);
            }
            
            return elements;
        }
        
        // Căutare generală
        if (searchInput) {
            $('#search-input').on('input', function() {
                const searchTerm = $(this).val();
                console.log('Input search-input:', searchTerm);
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    console.log('Executare căutare pentru search-input cu termenul:', searchTerm);
                    searchPatientsSuggestions(searchTerm, 'search-input');
                }, 300);
            });
        }
        
        // Căutare CNP
        if (cnpFilter) {
            $('#cnp-filter').on('input', function() {
                const searchTerm = $(this).val();
                console.log('Input cnp-filter:', searchTerm);
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    console.log('Executare căutare pentru cnp-filter cu termenul:', searchTerm);
                    searchPatientsSuggestions(searchTerm, 'cnp-filter');
                }, 300);
            });
        }
        
        // Căutare familie
        if (familyFilter) {
            $('#family-filter').on('input', function() {
                const searchTerm = $(this).val();
                console.log('Input family-filter:', searchTerm);
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchFamiliesSuggestions(searchTerm);
                }, 300);
            });
        }
        
        // Navigare cu taste pentru căutare generală
        if (searchInput) {
            $('#search-input').on('keydown', function(e) {
                handleSuggestionNavigation(e, 'search-input');
            });
        }
        
        // Navigare cu taste pentru CNP
        if (cnpFilter) {
            $('#cnp-filter').on('keydown', function(e) {
                handleSuggestionNavigation(e, 'cnp-filter');
            });
        }
        
        // Navigare cu taste pentru familie
        if (familyFilter) {
            $('#family-filter').on('keydown', function(e) {
                handleSuggestionNavigation(e, 'family-filter');
            });
        }
        
        // Închide suggestions când se face click în afara lor
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.clinica-search-container').length) {
                hideSuggestions('search-input');
                hideSuggestions('cnp-filter');
                hideSuggestions('family-filter');
            }
        });
        
        // Focus pe input când se face click pe container
        $('.clinica-search-container').on('click', function() {
            $(this).find('input').focus();
        });
        
        // Protejează elementele de sugestii de la fiind șterse
        const protectSuggestions = () => {
            const suggestions = document.querySelectorAll('.clinica-suggestions');
            suggestions.forEach(suggestion => {
                if (!suggestion.id) {
                    console.warn('Suggestion element fără ID găsit:', suggestion);
                }
            });
        };
        
        // Verifică periodic dacă elementele sunt prezente
        setInterval(protectSuggestions, 1000);
    }
    
    // Gestionare opțiuni familie în formularul de editare
    $('#edit-family-option').on('change', function() {
        var option = $(this).val();
        
        // Ascunde toate secțiunile
        $('#edit-new-family-section').hide();
        $('#edit-existing-family-section').hide();
        $('#edit-current-family-info').hide();
        $('#edit-selected-family-info').hide();
        
        // Arată secțiunea corespunzătoare
        if (option === 'new') {
            $('#edit-new-family-section').show();
        } else if (option === 'existing') {
            $('#edit-existing-family-section').show();
        } else if (option === 'current') {
            $('#edit-current-family-info').show();
        }
    });
    
    // Căutare familii în formularul de editare
    $('#edit-search-family-btn').on('click', function() {
        var searchTerm = $('#edit-family-search').val();
        if (searchTerm.length < 2) {
            alert('Introduceți cel puțin 2 caractere pentru căutare');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_search_families',
                search_term: searchTerm,
                nonce: '<?php echo wp_create_nonce('clinica_family_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayEditFamilyResults(response.data);
                } else {
                    $('#edit-family-results-list').html('<p class="error">Eroare la căutare: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#edit-family-results-list').html('<p class="error">Eroare la căutarea familiilor</p>');
            }
        });
    });
    
    // Funcție pentru afișarea rezultatelor căutării în editare
    function displayEditFamilyResults(families) {
        if (families.length === 0) {
            $('#edit-family-results-list').html('<p>Nu s-au găsit familii care să corespundă căutării.</p>');
            return;
        }
        
        var html = '<div class="family-results">';
        families.forEach(function(family) {
            html += '<div class="family-result-item" data-family-id="' + family.family_id + '">';
            html += '<h6>' + family.family_name + '</h6>';
            html += '<p><strong>Membri:</strong> ' + family.member_count + '</p>';
            html += '<p><strong>Detalii:</strong> ' + family.members + '</p>';
            html += '<button type="button" class="button select-family-btn" data-family-id="' + family.family_id + '">Selectează</button>';
            html += '</div>';
        });
        html += '</div>';
        
        $('#edit-family-results-list').html(html);
        $('#edit-family-search-results').show();
    }
    
    // Selectare familie în editare
    $(document).on('click', '.select-family-btn', function() {
        var familyId = $(this).data('family-id');
        var familyName = $(this).closest('.family-result-item').find('h6').text();
        
        // Salvează informațiile despre familie
        $('<input>').attr({
            type: 'hidden',
            name: 'selected_family_id',
            value: familyId
        }).appendTo('#edit-patient-form');
        
        $('<input>').attr({
            type: 'hidden',
            name: 'selected_family_name',
            value: familyName
        }).appendTo('#edit-patient-form');
        
        // Afișează informațiile despre familia selectată
        $('#edit-selected-family-details').html(
            '<p><strong>Familia:</strong> ' + familyName + '</p>' +
            '<p><strong>ID:</strong> ' + familyId + '</p>'
        );
        
        $('#edit-existing-family-section').hide();
        $('#edit-selected-family-info').show();
    });
    
    // Schimbă familia în editare
    $('#edit-change-family-btn, #edit-change-selected-family-btn').on('click', function() {
        $('input[name="selected_family_id"]').remove();
        $('input[name="selected_family_name"]').remove();
        $('#edit-selected-family-info').hide();
        $('#edit-existing-family-section').show();
        $('#edit-family-search').val('');
        $('#edit-family-results-list').html('');
        $('#edit-family-search-results').hide();
    });
    
    $('#edit-patient-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'clinica_update_patient');
        formData.append('nonce', '<?php echo wp_create_nonce('clinica_nonce'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Pacientul a fost actualizat cu succes!');
                    closeEditModal();
                    location.reload(); // Reîncarcă pagina pentru a vedea modificările
                } else {
                    alert('Eroare la actualizarea pacientului: ' + response.data);
                }
            },
            error: function() {
                alert('Eroare la actualizarea pacientului.');
            }
        });
    });
    
    // Închide modalul când se face click în afara lui
    $(window).click(function(event) {
        var modal = document.getElementById('edit-patient-modal');
        if (event.target == modal) {
            closeEditModal();
        }
    });
    
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
                        location.reload(); // Reîncarcă pagina pentru a vedea modificările
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
});
</script> 