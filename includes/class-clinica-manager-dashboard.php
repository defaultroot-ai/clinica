<?php
/**
 * Manager Dashboard Class
 * 
 * Provides comprehensive management interface for clinic managers
 * with full system access and control capabilities.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Manager_Dashboard {
    
    public function __construct() {
        add_action('wp_ajax_clinica_manager_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_clinica_manager_get_users', array($this, 'ajax_get_users'));
        add_action('wp_ajax_clinica_manager_get_appointments', array($this, 'ajax_get_appointments'));
        add_action('wp_ajax_clinica_manager_get_user_data', array($this, 'ajax_get_user_data'));
        add_action('wp_ajax_clinica_manager_get_reports', array($this, 'ajax_get_reports'));
        add_action('wp_ajax_clinica_manager_update_user', array($this, 'ajax_update_user'));
        add_action('wp_ajax_clinica_manager_delete_user', array($this, 'ajax_delete_user'));
        add_action('wp_ajax_clinica_manager_get_system_stats', array($this, 'ajax_get_system_stats'));
        add_action('wp_ajax_clinica_manager_update_settings', array($this, 'ajax_update_settings'));
        add_action('wp_ajax_clinica_manager_export_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_clinica_manager_backup_system', array($this, 'ajax_backup_system'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_shortcode('clinica_manager_dashboard', array($this, 'render_dashboard'));
    }
    
    /**
     * Proper title case function that handles hyphens correctly
     */
    private function proper_title_case($name) {
        if (empty($name)) return '';
        
        // Split by hyphens, apply title case to each part, then join back
        $parts = explode('-', $name);
        $title_parts = array();
        
        foreach ($parts as $part) {
            $title_parts[] = ucwords(strtolower(trim($part)));
        }
        
        return implode('-', $title_parts);
    }
    
    /**
     * Get sort clause for SQL query
     */
    private function get_sort_clause($sort_by, $sort_order, $letter_filter = '') {
        $valid_orders = array('ASC', 'DESC');
        $sort_order = in_array(strtoupper($sort_order), $valid_orders) ? strtoupper($sort_order) : 'ASC';
        
        // Dacă se filtrează după literă, sortează după nume complet pentru a grupa utilizatorii cu aceeași literă
        if (!empty($letter_filter) && $letter_filter !== 'all') {
            switch ($sort_by) {
                case 'name':
                    return "um2.meta_value {$sort_order}, um1.meta_value {$sort_order}";
                case 'email':
                    return "u.user_email {$sort_order}";
                case 'phone':
                    return "um3.meta_value {$sort_order}";
                case 'role':
                    return "um4.meta_value {$sort_order}";
                case 'registered':
                    return "u.user_registered {$sort_order}";
                default:
                    return "um2.meta_value {$sort_order}, um1.meta_value {$sort_order}";
            }
        }
        
        // Sortare normală când nu se filtrează după literă
        switch ($sort_by) {
            case 'name':
                return "um1.meta_value {$sort_order}, um2.meta_value {$sort_order}";
            case 'email':
                return "u.user_email {$sort_order}";
            case 'phone':
                return "um3.meta_value {$sort_order}";
            case 'role':
                return "um4.meta_value {$sort_order}";
            case 'registered':
                return "u.user_registered {$sort_order}";
            default:
                return "um1.meta_value ASC, um2.meta_value ASC";
        }
    }
    
    /**
     * Încarcă asset-urile necesare pentru dashboard
     */
    public function enqueue_assets() {
        // Verifică dacă suntem pe pagina manager dashboard
        global $post;
        if ($post && has_shortcode($post->post_content, 'clinica_manager_dashboard')) {
            // Verifică dacă asset-urile nu au fost deja încărcate
            if (!wp_script_is('clinica-manager-dashboard', 'enqueued')) {
                // Încarcă Dashicons pentru iconițe
                wp_enqueue_style('dashicons');
                
                wp_enqueue_style('clinica-manager-dashboard', plugin_dir_url(__FILE__) . '../assets/css/manager-dashboard.css', array('dashicons'), '1.4.0.' . time());
                wp_enqueue_style('clinica-manager-dashboard-new-features', plugin_dir_url(__FILE__) . '../assets/css/manager-dashboard-new-features.css', array('clinica-manager-dashboard'), '1.0.1.' . time());
                wp_enqueue_script('clinica-manager-dashboard', plugin_dir_url(__FILE__) . '../assets/js/manager-dashboard.js', array('jquery'), '1.3.0.' . time(), true);
                
                // Încarcă scripturile pentru editarea pacienților
                wp_enqueue_script('clinica-admin', plugin_dir_url(__FILE__) . '../assets/js/admin.js', array('jquery'), '1.0.0', true);
                
                // Localize script pentru AJAX
                wp_localize_script('clinica-manager-dashboard', 'clinicaManagerAjax', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('clinica_manager_dashboard'),
                    'strings' => array(
                        'loading' => __('Se încarcă...', 'clinica'),
                        'error' => __('A apărut o eroare. Vă rugăm să încercați din nou.', 'clinica'),
                        'success' => __('Operațiunea a fost finalizată cu succes.', 'clinica'),
                        'confirm_delete' => __('Sunteți sigur că doriți să ștergeți acest element?', 'clinica'),
                        'no_data' => __('Nu există date disponibile.', 'clinica')
                    )
                ));
            }
        }
    }
    
    /**
     * Metodă statică pentru generarea HTML-ului dashboard-ului
     */
    public static function get_dashboard_html($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return '<div class="clinica-error">Utilizator invalid.</div>';
        }

        $user_roles = $user->roles;
        if (!in_array('clinica_manager', $user_roles) && !in_array('clinica_administrator', $user_roles) && !in_array('administrator', $user_roles)) {
            return '<div class="clinica-error">Accesul este restricționat doar pentru manageri și administratori.</div>';
        }

        // Creează o instanță temporară pentru a folosi metoda render_dashboard
        $dashboard = new self();
        return $dashboard->render_dashboard(array());
    }

    /**
     * Render the manager dashboard
     */
    public function render_dashboard($atts = array()) {
        if (!is_user_logged_in()) {
            return '<div class="clinica-error">Trebuie să fiți autentificat pentru a accesa dashboard-ul managerului.</div>';
        }
        
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        if (!in_array('clinica_manager', $user_roles) && !in_array('clinica_administrator', $user_roles) && !in_array('administrator', $user_roles)) {
            return '<div class="clinica-error">Nu aveți permisiuni pentru a accesa dashboard-ul managerului.</div>';
        }
        
        // Folosește același nonce ca în JavaScript
        $nonce = wp_create_nonce('clinica_manager_dashboard');
        
        ob_start();
        ?>
        <div id="clinica-manager-dashboard" class="clinica-manager-dashboard" data-nonce="<?php echo esc_attr($nonce); ?>">
            <!-- Header Dashboard -->
            <div class="dashboard-header">
                <div class="manager-info-header">
                    <div class="manager-details">
                        <h2><?php 
                            $full_name = trim($current_user->first_name . ' ' . $current_user->last_name);
                            echo esc_html(!empty($full_name) ? $full_name : $current_user->display_name); 
                        ?></h2>
                        <p class="manager-role">Manager Clinica</p>
                        <p class="manager-email"><?php echo esc_html($current_user->user_email); ?></p>
                    </div>
                </div>
                <div class="dashboard-actions">
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="button button-secondary">Deconectare</a>
                </div>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="dashboard-nav">
                <button class="nav-tab active" data-tab="overview">
                    <i class="dashicons dashicons-chart-line"></i> Prezentare Generală
                </button>
                <button class="nav-tab" data-tab="users">
                    <i class="dashicons dashicons-admin-users"></i> Utilizatori
                </button>
                <button class="nav-tab" data-tab="appointments">
                    <i class="dashicons dashicons-calendar-alt"></i> Programări
                </button>
                <button class="nav-tab" data-tab="reports">
                    <i class="dashicons dashicons-chart-bar"></i> Rapoarte
                </button>
                <button class="nav-tab" data-tab="settings">
                    <i class="dashicons dashicons-admin-generic"></i> Setări
                </button>
                <button class="nav-tab" data-tab="system">
                    <i class="dashicons dashicons-admin-tools"></i> Sistem
                </button>
            </div>
            
            <!-- Content Areas -->
            <div class="dashboard-content">
                <!-- Overview Tab -->
                <div id="overview" class="tab-content active">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="dashicons dashicons-groups"></i></div>
                            <div class="stat-info">
                                <h3>Total Utilizatori</h3>
                                <div class="stat-number" id="total-users">-</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="dashicons dashicons-admin-users"></i></div>
                            <div class="stat-info">
                                <h3>Medici</h3>
                                <div class="stat-number" id="total-doctors">-</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="dashicons dashicons-admin-users"></i></div>
                            <div class="stat-info">
                                <h3>Pacienți</h3>
                                <div class="stat-number" id="total-patients">-</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="dashicons dashicons-calendar-alt"></i></div>
                            <div class="stat-info">
                                <h3>Programări Astăzi</h3>
                                <div class="stat-number" id="today-appointments">-</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="charts-section">
                        <div class="chart-container">
                            <h3>Programări pe Lună</h3>
                            <canvas id="appointments-chart"></canvas>
                        </div>
                        <div class="chart-container">
                            <h3>Utilizatori pe Rol</h3>
                            <canvas id="users-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="recent-activity">
                        <h3>Activitate Recentă</h3>
                        <div id="recent-activity-list" class="activity-list">
                            <!-- Activity items will be loaded here -->
                        </div>
                    </div>
                </div>
                
                <!-- Users Tab -->
                <div id="users" class="tab-content">
                    <div class="tab-header">
                        <h2>Gestionare Utilizatori</h2>
                    </div>
                    
                    <div class="filters">
                        <div class="search-container">
                            <input type="text" id="search-users" placeholder="Caută pacienți...">
                            <button class="btn btn-secondary" id="filter-users">Caută</button>
                        </div>
                        <div class="alphabet-filter">
                            <span class="alphabet-label">Filtrează după literă:</span>
                            <div class="alphabet-buttons">
                                <button class="alphabet-btn active" data-letter="all">Toate</button>
                                <button class="alphabet-btn" data-letter="A">A</button>
                                <button class="alphabet-btn" data-letter="B">B</button>
                                <button class="alphabet-btn" data-letter="C">C</button>
                                <button class="alphabet-btn" data-letter="D">D</button>
                                <button class="alphabet-btn" data-letter="E">E</button>
                                <button class="alphabet-btn" data-letter="F">F</button>
                                <button class="alphabet-btn" data-letter="G">G</button>
                                <button class="alphabet-btn" data-letter="H">H</button>
                                <button class="alphabet-btn" data-letter="I">I</button>
                                <button class="alphabet-btn" data-letter="J">J</button>
                                <button class="alphabet-btn" data-letter="K">K</button>
                                <button class="alphabet-btn" data-letter="L">L</button>
                                <button class="alphabet-btn" data-letter="M">M</button>
                                <button class="alphabet-btn" data-letter="N">N</button>
                                <button class="alphabet-btn" data-letter="O">O</button>
                                <button class="alphabet-btn" data-letter="P">P</button>
                                <button class="alphabet-btn" data-letter="Q">Q</button>
                                <button class="alphabet-btn" data-letter="R">R</button>
                                <button class="alphabet-btn" data-letter="S">S</button>
                                <button class="alphabet-btn" data-letter="T">T</button>
                                <button class="alphabet-btn" data-letter="U">U</button>
                                <button class="alphabet-btn" data-letter="V">V</button>
                                <button class="alphabet-btn" data-letter="W">W</button>
                                <button class="alphabet-btn" data-letter="X">X</button>
                                <button class="alphabet-btn" data-letter="Y">Y</button>
                                <button class="alphabet-btn" data-letter="Z">Z</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="users-table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th class="sortable" data-sort="name">
                                        Nume <span class="sort-indicator">↕</span>
                                    </th>
                                    <th>Email</th>
                                    <th>Telefon</th>
                                    <th>Rol</th>
                                    <th>Status</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <!-- Users will be loaded here -->
                            </tbody>
                        </table>
                        
                        <!-- Paginare -->
                        <div class="pagination" id="users-pagination">
                            <button id="prev-page" disabled>
                                <i class="dashicons dashicons-arrow-left-alt2"></i>
                            </button>
                            <div class="pagination-info">
                                <span id="pagination-info">Se încarcă...</span>
                            </div>
                            <button id="next-page" disabled>
                                <i class="dashicons dashicons-arrow-right-alt2"></i>
                            </button>
                            <div class="pagination-go">
                                <input type="number" id="go-to-page" placeholder="Pagina" min="1" style="width: 60px; margin: 0 5px;">
                                <button id="go-button">Mergi</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Appointments Tab -->
                <div id="appointments" class="tab-content">
                    <div class="tab-header">
                        <h2>Gestionare Programări</h2>
                        <div class="tab-actions">
                            <button class="btn btn-primary" id="add-appointment-btn">
                                <i class="dashicons dashicons-plus"></i> Programare Nouă
                            </button>
                        </div>
                    </div>
                    
                    <div class="filters">
                        <select id="doctor-filter">
                            <option value="">Toți Medicii</option>
                        </select>
                        <select id="status-filter">
                            <option value="">Toate Statusurile</option>
                            <option value="scheduled">Programat</option>
                            <option value="completed">Completat</option>
                            <option value="cancelled">Anulat</option>
                        </select>
                        <input type="date" id="date-filter">
                        <button class="btn btn-secondary" id="filter-appointments">Filtrează</button>
                    </div>
                    
                    <div class="appointments-table-container">
                        <table class="appointments-table">
                            <thead>
                                <tr>
                                    <th>Pacient</th>
                                    <th>Medic</th>
                                    <th>Data</th>
                                    <th>Ora</th>
                                    <th>Status</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody id="appointments-table-body">
                                <!-- Appointments will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Reports Tab -->
                <div id="reports" class="tab-content">
                    <div class="tab-header">
                        <h2>Rapoarte și Analize</h2>
                        <div class="tab-actions">
                            <button class="btn btn-primary" id="generate-report-btn">
                                <i class="fas fa-file-alt"></i> Generează Raport
                            </button>
                            <button class="btn btn-secondary" id="export-report-btn">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    
                    <div class="reports-grid">
                        <div class="report-card">
                            <h3>Raport Pacienți</h3>
                            <div class="report-content" id="patients-report">
                                <!-- Report content -->
                            </div>
                        </div>
                        <div class="report-card">
                            <h3>Raport Programări</h3>
                            <div class="report-content" id="appointments-report">
                                <!-- Report content -->
                            </div>
                        </div>
                        <div class="report-card">
                            <h3>Raport Financiar</h3>
                            <div class="report-content" id="financial-report">
                                <!-- Report content -->
                            </div>
                        </div>
                        <div class="report-card">
                            <h3>Raport Performanță</h3>
                            <div class="report-content" id="performance-report">
                                <!-- Report content -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings" class="tab-content">
                    <div class="tab-header">
                        <h2>Setări Sistem</h2>
                        <div class="tab-actions">
                            <button class="btn btn-primary" id="save-settings-btn">
                                <i class="fas fa-save"></i> Salvează
                            </button>
                        </div>
                    </div>
                    
                    <div class="settings-grid">
                        <div class="settings-section">
                            <h3>Setări Generale</h3>
                            <div class="setting-item">
                                <label>Nume Clinică</label>
                                <input type="text" id="clinic-name" value="">
                            </div>
                            <div class="setting-item">
                                <label>Email Contact</label>
                                <input type="email" id="contact-email" value="">
                            </div>
                            <div class="setting-item">
                                <label>Telefon Contact</label>
                                <input type="tel" id="contact-phone" value="">
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3>Setări Programări</h3>
                            <div class="setting-item">
                                <label>Durată Programare (minute)</label>
                                <input type="number" id="appointment-duration" value="30">
                            </div>
                            <div class="setting-item">
                                <label>Program Lucru</label>
                                <input type="time" id="work-start" value="08:00">
                                <input type="time" id="work-end" value="17:00">
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3>Setări Notificări</h3>
                            <div class="setting-item">
                                <label>
                                    <input type="checkbox" id="email-notifications">
                                    Notificări Email
                                </label>
                            </div>
                            <div class="setting-item">
                                <label>
                                    <input type="checkbox" id="sms-notifications">
                                    Notificări SMS
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Tab -->
                <div id="system" class="tab-content">
                    <div class="tab-header">
                        <h2>Administrare Sistem</h2>
                        <div class="tab-actions">
                            <button class="btn btn-primary" id="backup-system-btn">
                                <i class="fas fa-download"></i> Backup
                            </button>
                            <button class="btn btn-warning" id="clear-cache-btn">
                                <i class="fas fa-broom"></i> Curăță Cache
                            </button>
                        </div>
                    </div>
                    
                    <div class="system-grid">
                        <div class="system-card">
                            <h3>Status Sistem</h3>
                            <div class="system-status" id="system-status">
                                <!-- System status -->
                            </div>
                        </div>
                        
                        <div class="system-card">
                            <h3>Bază de Date</h3>
                            <div class="db-info" id="db-info">
                                <!-- Database info -->
                            </div>
                        </div>
                        
                        <div class="system-card">
                            <h3>Log-uri</h3>
                            <div class="logs-container" id="system-logs">
                                <!-- System logs -->
                            </div>
                        </div>
                        
                        <div class="system-card">
                            <h3>Backup-uri</h3>
                            <div class="backups-list" id="backups-list">
                                <!-- Backup list -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modals -->
        <div id="user-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="user-modal-title">Adaugă Utilizator</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="user-form">
                        <div class="form-group">
                            <label>Nume</label>
                            <input type="text" id="user-first-name" required>
                        </div>
                        <div class="form-group">
                            <label>Prenume</label>
                            <input type="text" id="user-last-name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="user-email" required>
                        </div>
                        <div class="form-group">
                            <label>Telefon</label>
                            <input type="tel" id="user-phone">
                        </div>
                        <div class="form-group">
                            <label>Rol</label>
                            <select id="user-role" required>
                                <option value="">Selectează Rol</option>
                                <option value="clinica_doctor">Medic</option>
                                <option value="clinica_assistant">Asistent</option>
                                <option value="clinica_receptionist">Recepționer</option>
                                <option value="clinica_patient">Pacient</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Parolă</label>
                            <input type="password" id="user-password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="cancel-user">Anulează</button>
                    <button class="btn btn-primary" id="save-user">Salvează</button>
                </div>
            </div>
        </div>
        
        <div id="appointment-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Programare Nouă</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="appointment-form">
                        <div class="form-group">
                            <label>Pacient</label>
                            <select id="appointment-patient" required>
                                <option value="">Selectează Pacient</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Medic</label>
                            <select id="appointment-doctor" required>
                                <option value="">Selectează Medic</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Data</label>
                            <input type="date" id="appointment-date" required>
                        </div>
                        <div class="form-group">
                            <label>Ora</label>
                            <input type="time" id="appointment-time" required>
                        </div>
                        <div class="form-group">
                            <label>Notă</label>
                            <textarea id="appointment-note"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="cancel-appointment">Anulează</button>
                    <button class="btn btn-primary" id="save-appointment">Salvează</button>
                </div>
            </div>
        </div>
        
        <!-- Modal pentru editarea pacienților -->
        <div id="edit-patient-modal" class="clinica-modal" style="display: none;">
            <div class="clinica-modal-content">
                <div class="clinica-modal-header">
                    <h3 id="edit-patient-title">Editează Pacient</h3>
                    <span class="clinica-modal-close" onclick="closeEditModal()">&times;</span>
                </div>
                <div class="clinica-modal-body">
                    <form id="edit-patient-form">
                        <input type="hidden" id="edit-patient-id" name="patient_id">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit-first-name">Nume *</label>
                                <input type="text" id="edit-first-name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-last-name">Prenume *</label>
                                <input type="text" id="edit-last-name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit-email">Email *</label>
                                <input type="email" id="edit-email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-phone-primary">Telefon Principal</label>
                                <input type="tel" id="edit-phone-primary" name="phone_primary">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit-phone-secondary">Telefon Secundar</label>
                                <input type="tel" id="edit-phone-secondary" name="phone_secondary">
                            </div>
                            <div class="form-group">
                                <label for="edit-birth-date">Data Nașterii</label>
                                <input type="date" id="edit-birth-date" name="birth_date">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit-gender">Gen</label>
                                <select id="edit-gender" name="gender">
                                    <option value="">Selectează genul</option>
                                    <option value="male">Masculin</option>
                                    <option value="female">Feminin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit-password-method">Metoda Parolă</label>
                                <select id="edit-password-method" name="password_method">
                                    <option value="cnp">CNP</option>
                                    <option value="birth_date">Data nașterii</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-address">Adresă</label>
                            <textarea id="edit-address" name="address" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-emergency-contact">Contact de Urgență</label>
                            <input type="tel" id="edit-emergency-contact" name="emergency_contact">
                        </div>
                    </form>
                </div>
                <div class="clinica-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Anulează</button>
                    <button type="button" class="btn btn-primary" onclick="savePatientData()">Salvează</button>
                </div>
            </div>
        </div>
        
        <script>
            // Initialize manager dashboard
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof ClinicaManagerDashboard !== 'undefined') {
                    ClinicaManagerDashboard.init();
                }
            });
            
            // Funcții pentru editarea pacienților
            window.editPatient = function(patientId) {
                console.log('editPatient called with ID:', patientId);
                // Afișează modalul
                document.getElementById('edit-patient-modal').style.display = 'block';
                
                // Încarcă datele pacientului
                loadPatientData(patientId);
            }
            
            window.closeEditModal = function() {
                document.getElementById('edit-patient-modal').style.display = 'none';
            }
            
            window.loadPatientData = function(patientId) {
                // AJAX call pentru a încărca datele pacientului
                fetch(clinicaManagerAjax.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'clinica_get_patient_data',
                        patient_id: patientId,
                        nonce: '<?php echo wp_create_nonce('clinica_nonce'); ?>'
                    })
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed JSON:', data);
                        
                        if (data.success) {
                            const patient = data.data;
                            
                            // Actualizează titlul modalului cu numele și username-ul pacientului
                            const fullName = (patient.first_name || '') + ' ' + (patient.last_name || '');
                            const username = patient.username || '';
                            document.getElementById('edit-patient-title').innerHTML = 
                                `Editează Pacient: ${fullName.trim()} - ${username}`;
                            
                            // Populează câmpurile formularului
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
                        } else {
                            alert('Eroare la încărcarea datelor pacientului: ' + (data.data || 'Eroare necunoscută'));
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        alert('Eroare la parsarea răspunsului: ' + text);
                    }
                })
                .catch(error => {
                    console.error('Error loading patient data:', error);
                    alert('Eroare la încărcarea datelor pacientului: ' + error.message);
                });
            }
            
            window.savePatientData = function() {
                const form = document.getElementById('edit-patient-form');
                const formData = new FormData(form);
                
                fetch(clinicaManagerAjax.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'clinica_update_patient',
                        patient_id: formData.get('patient_id'),
                        first_name: formData.get('first_name'),
                        last_name: formData.get('last_name'),
                        email: formData.get('email'),
                        phone_primary: formData.get('phone_primary'),
                        phone_secondary: formData.get('phone_secondary'),
                        birth_date: formData.get('birth_date'),
                        gender: formData.get('gender'),
                        password_method: formData.get('password_method'),
                        address: formData.get('address'),
                        emergency_contact: formData.get('emergency_contact'),
                        nonce: '<?php echo wp_create_nonce('clinica_nonce'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Pacientul a fost actualizat cu succes!');
                        closeEditModal();
                        // Reîncarcă lista de utilizatori
                        if (typeof ClinicaManagerDashboard !== 'undefined') {
                            ClinicaManagerDashboard.loadUsersData();
                        }
                    } else {
                        alert('Eroare la actualizarea pacientului: ' + (data.data || 'Eroare necunoscută'));
                    }
                })
                .catch(error => {
                    console.error('Error saving patient data:', error);
                    alert('Eroare la salvarea datelor pacientului');
                });
            }
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX: Get dashboard overview data
     */
    public function ajax_get_dashboard_data() {
        // Verifică nonce-ul direct
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $data = array(
            'stats' => $this->get_system_stats(),
            'recent_activity' => $this->get_recent_activity(),
            'charts' => $this->get_chart_data()
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Get users list with pagination
     */
    public function ajax_get_users() {
        // Verifică dacă există nonce-ul
        if (!isset($_POST['nonce'])) {
            wp_send_json_error('No nonce provided');
        }
        
        // Verifică nonce-ul direct
        $received_nonce = $_POST['nonce'];
        
        // Verifică nonce-ul cu diferite metode
        $verification_result = wp_verify_nonce($received_nonce, 'clinica_manager_dashboard');
        
        // Verifică dacă nonce-ul este valid pentru utilizatorul curent
        if (!$verification_result) {
            // Încearcă să forțeze regenerarea nonce-ului
            wp_cache_delete('nonce_' . $received_nonce, 'nonce');
            $verification_result = wp_verify_nonce($received_nonce, 'clinica_manager_dashboard');
        }
        
        // Verifică dacă nonce-ul este valid pentru utilizatorul curent
        if (!$verification_result) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $role_filter = sanitize_text_field($_POST['role'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        
        $sort_by = sanitize_text_field($_POST['sort_by'] ?? 'name');
        $sort_order = sanitize_text_field($_POST['sort_order'] ?? 'ASC');
        $letter_filter = sanitize_text_field($_POST['letter_filter'] ?? '');
        
        $result = $this->get_users_list($role_filter, $search, $page, $per_page, $sort_by, $sort_order, $letter_filter);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Get appointments list
     */
    public function ajax_get_appointments() {
        // Verifică nonce-ul direct
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $doctor_filter = sanitize_text_field($_POST['doctor'] ?? '');
        $status_filter = sanitize_text_field($_POST['status'] ?? '');
        $date_filter = sanitize_text_field($_POST['date'] ?? '');
        
        $appointments = $this->get_appointments_list($doctor_filter, $status_filter, $date_filter);
        
        wp_send_json_success($appointments);
    }
    
    public function ajax_get_user_data() {
        // Verifică nonce-ul direct
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) {
            wp_send_json_error('ID utilizator invalid');
        }
        
        $user_data = $this->get_user_data($user_id);
        if ($user_data) {
            wp_send_json_success($user_data);
        } else {
            wp_send_json_error('Utilizatorul nu a fost găsit');
        }
    }
    
    
    /**
     * AJAX: Get reports data
     */
    public function ajax_get_reports() {
        // Verifică nonce-ul direct
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $reports = $this->generate_reports();
        
        wp_send_json_success($reports);
    }
    
    /**
     * AJAX: Update user
     */
    public function ajax_update_user() {
        // Verifică nonce-ul direct
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $user_data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'role' => sanitize_text_field($_POST['role']),
            'password' => $_POST['password']
        );
        
        $result = $this->update_user($user_data);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Delete user
     */
    public function ajax_delete_user() {
        // Verifică nonce-ul direct
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id']);
        
        $result = $this->delete_user($user_id);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Get system statistics
     */
    public function ajax_get_system_stats() {
        // Verifică nonce-ul direct
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $stats = $this->get_system_stats();
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Update settings
     */
    public function ajax_update_settings() {
        // Verifică nonce-ul direct
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $settings = array(
            'clinic_name' => sanitize_text_field($_POST['clinic_name']),
            'contact_email' => sanitize_email($_POST['contact_email']),
            'contact_phone' => sanitize_text_field($_POST['contact_phone']),
            'appointment_duration' => intval($_POST['appointment_duration']),
            'work_start' => sanitize_text_field($_POST['work_start']),
            'work_end' => sanitize_text_field($_POST['work_end']),
            'email_notifications' => isset($_POST['email_notifications']),
            'sms_notifications' => isset($_POST['sms_notifications'])
        );
        
        $result = $this->update_settings($settings);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Export data
     */
    public function ajax_export_data() {
        // Verifică nonce-ul direct
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $type = sanitize_text_field($_POST['type']);
        
        $result = $this->export_data($type);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Backup system
     */
    public function ajax_backup_system() {
        // Verifică nonce-ul direct
        if (!wp_verify_nonce($_POST['nonce'], 'clinica_manager_dashboard')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options') && !current_user_can('clinica_manager') && !current_user_can('clinica_administrator')) {
            wp_send_json_error('Unauthorized');
        }
        
        $result = $this->backup_system();
        
        wp_send_json_success($result);
    }
    
    /**
     * Get system statistics
     */
    private function get_system_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total users by role
        $users_query = "SELECT um.meta_value as role, COUNT(*) as count 
                       FROM {$wpdb->users} u 
                       JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
                       WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
                       GROUP BY um.meta_value";
        
        $users_result = $wpdb->get_results($users_query);
        
        foreach ($users_result as $row) {
            $role_data = maybe_unserialize($row->role);
            if (is_array($role_data)) {
                foreach ($role_data as $role => $has_role) {
                    if ($has_role) {
                        $stats[$role] = $row->count;
                        break;
                    }
                }
            }
        }
        
        // Today's appointments
        $today = current_time('Y-m-d');
        $appointments_query = "SELECT COUNT(*) as count FROM {$wpdb->prefix}clinica_appointments WHERE DATE(appointment_date) = %s";
        $stats['today_appointments'] = $wpdb->get_var($wpdb->prepare($appointments_query, $today));
        
        return $stats;
    }
    
    /**
     * Get recent activity
     */
    private function get_recent_activity() {
        global $wpdb;
        
        $activities = array();
        
        // Recent appointments
        $appointments_query = "SELECT a.*, 
                              um1.meta_value as patient_first_name, 
                              um2.meta_value as patient_last_name, 
                              u.display_name as doctor_name
                              FROM {$wpdb->prefix}clinica_appointments a
                              LEFT JOIN {$wpdb->users} u ON a.doctor_id = u.ID
                              LEFT JOIN {$wpdb->usermeta} um1 ON a.patient_id = um1.user_id AND um1.meta_key = 'first_name'
                              LEFT JOIN {$wpdb->usermeta} um2 ON a.patient_id = um2.user_id AND um2.meta_key = 'last_name'
                              ORDER BY a.created_at DESC LIMIT 5";
        
        $appointments = $wpdb->get_results($appointments_query);
        
        foreach ($appointments as $appointment) {
            $patient_name = trim($appointment->patient_first_name . ' ' . $appointment->patient_last_name);
            if (empty($patient_name)) {
                $patient_name = 'Pacient necunoscut';
            }
            
            $status_text = '';
            switch ($appointment->status) {
                case 'scheduled':
                    $status_text = 'programată';
                    break;
                case 'confirmed':
                    $status_text = 'confirmată';
                    break;
                case 'completed':
                    $status_text = 'finalizată';
                    break;
                case 'cancelled':
                    $status_text = 'anulată';
                    break;
                case 'no_show':
                    $status_text = 'neprezentat';
                    break;
            }
            
            $activities[] = array(
                'type' => 'appointment',
                'message' => sprintf('Programare %s pentru %s cu Dr. %s', $status_text, $patient_name, $appointment->doctor_name),
                'date' => $appointment->created_at
            );
        }
        
        // Recent user registrations (doar ultimii 3, excluzând utilizatorii de test)
        $users_query = "SELECT u.ID, u.user_login, u.user_registered, um1.meta_value as first_name, um2.meta_value as last_name 
                       FROM {$wpdb->users} u 
                       LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                       LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                       WHERE u.user_login NOT LIKE 'test_%' 
                       AND u.user_login NOT LIKE 'debug_%'
                       AND u.user_email NOT LIKE '%@example.com'
                       ORDER BY u.user_registered DESC LIMIT 3";
        
        $users = $wpdb->get_results($users_query);
        
        foreach ($users as $user) {
            $full_name = trim($user->first_name . ' ' . $user->last_name);
            if (empty($full_name)) {
                $full_name = $user->user_login;
            }
            
            $activities[] = array(
                'type' => 'user_registration',
                'message' => sprintf('Utilizator nou înregistrat: %s', $full_name),
                'date' => $user->user_registered
            );
        }
        
        // Recent medical records (dacă există)
        $medical_query = "SELECT mr.*, 
                         um1.meta_value as patient_first_name, 
                         um2.meta_value as patient_last_name, 
                         u.display_name as doctor_name
                         FROM {$wpdb->prefix}clinica_medical_records mr
                         LEFT JOIN {$wpdb->users} u ON mr.doctor_id = u.ID
                         LEFT JOIN {$wpdb->usermeta} um1 ON mr.patient_id = um1.user_id AND um1.meta_key = 'first_name'
                         LEFT JOIN {$wpdb->usermeta} um2 ON mr.patient_id = um2.user_id AND um2.meta_key = 'last_name'
                         ORDER BY mr.created_at DESC LIMIT 3";
        
        $medical_records = $wpdb->get_results($medical_query);
        
        foreach ($medical_records as $record) {
            $patient_name = trim($record->patient_first_name . ' ' . $record->patient_last_name);
            if (empty($patient_name)) {
                $patient_name = 'Pacient necunoscut';
            }
            
            $activities[] = array(
                'type' => 'medical_record',
                'message' => sprintf('Dosar medical actualizat pentru %s de Dr. %s', $patient_name, $record->doctor_name),
                'date' => $record->created_at
            );
        }
        
        // Sortează activitățile după dată (cele mai recente primul)
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Returnează doar primele 10 activități
        return array_slice($activities, 0, 10);
    }
    
    /**
     * Get chart data
     */
    private function get_chart_data() {
        global $wpdb;
        
        $charts = array();
        
        // Appointments per month (last 6 months)
        $appointments_query = "SELECT DATE_FORMAT(appointment_date, '%Y-%m') as month, COUNT(*) as count 
                              FROM {$wpdb->prefix}clinica_appointments 
                              WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                              GROUP BY month ORDER BY month";
        
        $charts['appointments'] = $wpdb->get_results($appointments_query);
        
        // Users by role
        $users_query = "SELECT um.meta_value as role, COUNT(*) as count 
                       FROM {$wpdb->users} u 
                       JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
                       WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
                       GROUP BY um.meta_value";
        
        $charts['users'] = $wpdb->get_results($users_query);
        
        return $charts;
    }
    
    /**
     * Get users list with pagination, sorting and filtering
     */
    public function get_users_list($role_filter = '', $search = '', $page = 1, $per_page = 20, $sort_by = 'name', $sort_order = 'ASC', $letter_filter = '') {
        global $wpdb;
        
        $where_conditions = array();
        $where_values = array();
        
        // Filtrează doar utilizatorii cu rolul de pacient
        $where_conditions[] = "um4.meta_value LIKE %s";
        $where_values[] = '%clinica_patient%';
        
        if (!empty($role_filter)) {
            $where_conditions[] = "um4.meta_value LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($role_filter) . '%';
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(u.user_login LIKE %s OR u.user_email LIKE %s OR um1.meta_value LIKE %s OR um2.meta_value LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Filtrare după literă de început
        if (!empty($letter_filter) && $letter_filter !== 'all') {
            // Filtrează după primul caracter al prenumelui sau numelui
            $where_conditions[] = "(um1.meta_value LIKE %s OR um2.meta_value LIKE %s)";
            $letter_term = $wpdb->esc_like($letter_filter) . '%';
            $where_values[] = $letter_term;
            $where_values[] = $letter_term;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Calculează offset-ul pentru paginare
        $offset = ($page - 1) * $per_page;
        
        // Query pentru numărul total de utilizatori
        $count_query = "SELECT COUNT(DISTINCT u.ID) as total
                       FROM {$wpdb->users} u 
                       LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                       LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                       LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'phone'
                       LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = '{$wpdb->prefix}capabilities'
                       {$where_clause}";
        
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        
        $total_users = $wpdb->get_var($count_query);
        
        // Query pentru utilizatorii din pagina curentă
        $query = "SELECT u.ID, u.user_login, u.user_email, u.user_registered, 
                         um1.meta_value as first_name, um2.meta_value as last_name, um3.meta_value as phone, um4.meta_value as role
                  FROM {$wpdb->users} u 
                  LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                  LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                  LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'phone'
                  LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = '{$wpdb->prefix}capabilities'
                  {$where_clause}
                  ORDER BY " . $this->get_sort_clause($sort_by, $sort_order, $letter_filter) . "
                  LIMIT %d OFFSET %d";
        
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $users = $wpdb->get_results($query);
        
        // Decodează rolurile și corectează numele pentru fiecare utilizator
        foreach ($users as $user) {
            // Decodează rolurile și prioritizează rolul de pacient
            if (!empty($user->role)) {
                $capabilities = maybe_unserialize($user->role);
                if (is_array($capabilities)) {
                    $roles = array_keys($capabilities);
                    // Prioritizează rolul de pacient dacă există
                    if (in_array('clinica_patient', $roles)) {
                        $user->role = 'clinica_patient';
                    } elseif (in_array('clinica_doctor', $roles)) {
                        $user->role = 'clinica_doctor';
                    } elseif (in_array('clinica_assistant', $roles)) {
                        $user->role = 'clinica_assistant';
                    } elseif (in_array('clinica_receptionist', $roles)) {
                        $user->role = 'clinica_receptionist';
                    } elseif (in_array('clinica_manager', $roles)) {
                        $user->role = 'clinica_manager';
                    } elseif (in_array('clinica_administrator', $roles)) {
                        $user->role = 'clinica_administrator';
                    } else {
                        $user->role = !empty($roles) ? $roles[0] : 'N/A';
                    }
                } else {
                    $user->role = 'N/A';
                }
            } else {
                $user->role = 'N/A';
            }
            
            // Corectează numele și prenumele în title case
            if (!empty($user->first_name)) {
                $user->first_name = $this->proper_title_case($user->first_name);
            }
            if (!empty($user->last_name)) {
                $user->last_name = $this->proper_title_case($user->last_name);
            }
        }
        
        return array(
            'users' => $users,
            'total' => $total_users,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_users / $per_page)
        );
    }
    
    private function get_user_data($user_id) {
        global $wpdb;
        
        $query = "SELECT u.ID, u.user_login, u.user_email, u.user_registered, 
                         um1.meta_value as first_name, um2.meta_value as last_name, um3.meta_value as phone, um4.meta_value as role
                  FROM {$wpdb->users} u 
                  LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                  LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                  LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'phone'
                  LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = '{$wpdb->prefix}capabilities'
                  WHERE u.ID = %d";
        
        $user = $wpdb->get_row($wpdb->prepare($query, $user_id));
        
        if (!$user) {
            return false;
        }
        
        // Decodează rolurile și prioritizează rolul de pacient
        if (!empty($user->role)) {
            $capabilities = maybe_unserialize($user->role);
            if (is_array($capabilities)) {
                $roles = array_keys($capabilities);
                // Prioritizează rolul de pacient dacă există
                if (in_array('clinica_patient', $roles)) {
                    $user->role = 'clinica_patient';
                } elseif (in_array('clinica_doctor', $roles)) {
                    $user->role = 'clinica_doctor';
                } elseif (in_array('clinica_assistant', $roles)) {
                    $user->role = 'clinica_assistant';
                } elseif (in_array('clinica_receptionist', $roles)) {
                    $user->role = 'clinica_receptionist';
                } elseif (in_array('clinica_manager', $roles)) {
                    $user->role = 'clinica_manager';
                } elseif (in_array('clinica_administrator', $roles)) {
                    $user->role = 'clinica_administrator';
                } else {
                    $user->role = !empty($roles) ? $roles[0] : 'N/A';
                }
            } else {
                $user->role = 'N/A';
            }
        } else {
            $user->role = 'N/A';
        }
        
        // Corectează numele și prenumele în title case
        if (!empty($user->first_name)) {
            $user->first_name = $this->proper_title_case($user->first_name);
        }
        if (!empty($user->last_name)) {
            $user->last_name = $this->proper_title_case($user->last_name);
        }
        
        return $user;
    }
    
    
    /**
     * Get appointments list
     */
    private function get_appointments_list($doctor_filter = '', $status_filter = '', $date_filter = '') {
        global $wpdb;
        
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($doctor_filter)) {
            $where_conditions[] = "a.doctor_id = %d";
            $where_values[] = intval($doctor_filter);
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "a.status = %s";
            $where_values[] = $status_filter;
        }
        
        if (!empty($date_filter)) {
            $where_conditions[] = "DATE(a.appointment_date) = %s";
            $where_values[] = $date_filter;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "SELECT a.*, 
                         p.meta_value as patient_first_name, p2.meta_value as patient_last_name,
                         d.meta_value as doctor_first_name, d2.meta_value as doctor_last_name
                  FROM {$wpdb->prefix}clinica_appointments a
                  LEFT JOIN {$wpdb->usermeta} p ON a.patient_id = p.user_id AND p.meta_key = 'first_name'
                  LEFT JOIN {$wpdb->usermeta} p2 ON a.patient_id = p2.user_id AND p2.meta_key = 'last_name'
                  LEFT JOIN {$wpdb->usermeta} d ON a.doctor_id = d.user_id AND d.meta_key = 'first_name'
                  LEFT JOIN {$wpdb->usermeta} d2 ON a.doctor_id = d2.user_id AND d2.meta_key = 'last_name'
                  {$where_clause}
                  ORDER BY a.appointment_date DESC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Generate reports
     */
    private function generate_reports() {
        global $wpdb;
        
        $reports = array();
        
        // Patients report
        $patients_query = "SELECT COUNT(*) as total,
                                 COUNT(CASE WHEN DATE(user_registered) = CURDATE() THEN 1 END) as today,
                                 COUNT(CASE WHEN DATE(user_registered) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as this_week,
                                 COUNT(CASE WHEN DATE(user_registered) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as this_month
                          FROM {$wpdb->users} u
                          JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                          WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND um.meta_value LIKE '%clinica_patient%'";
        
        $reports['patients'] = $wpdb->get_row($patients_query);
        
        // Appointments report
        $appointments_query = "SELECT COUNT(*) as total,
                                     COUNT(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 END) as today,
                                     COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                                     COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
                              FROM {$wpdb->prefix}clinica_appointments";
        
        $reports['appointments'] = $wpdb->get_row($appointments_query);
        
        return $reports;
    }
    
    /**
     * Update user
     */
    private function update_user($user_data) {
        $user_id = wp_create_user($user_data['email'], $user_data['password'], $user_data['email']);
        
        if (is_wp_error($user_id)) {
            return array('success' => false, 'message' => $user_id->get_error_message());
        }
        
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name']
        ));
        
        update_user_meta($user_id, 'phone', $user_data['phone']);
        
        $user = new WP_User($user_id);
        $user->set_role($user_data['role']);
        
        return array('success' => true, 'message' => 'Utilizator creat cu succes');
    }
    
    /**
     * Delete user
     */
    private function delete_user($user_id) {
        if (wp_delete_user($user_id)) {
            return array('success' => true, 'message' => 'Utilizator șters cu succes');
        }
        
        return array('success' => false, 'message' => 'Eroare la ștergerea utilizatorului');
    }
    
    /**
     * Update settings
     */
    private function update_settings($settings) {
        foreach ($settings as $key => $value) {
            update_option('clinica_' . $key, $value);
        }
        
        return array('success' => true, 'message' => 'Setări actualizate cu succes');
    }
    
    /**
     * Export data
     */
    private function export_data($type) {
        global $wpdb;
        
        switch ($type) {
            case 'users':
                $data = $wpdb->get_results("SELECT * FROM {$wpdb->users}");
                break;
            case 'appointments':
                $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}clinica_appointments");
                break;
            default:
                return array('success' => false, 'message' => 'Tip de export invalid');
        }
        
        $filename = 'clinica_' . $type . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        return array('success' => true, 'filename' => $filename, 'data' => $data);
    }
    
    /**
     * Backup system
     */
    private function backup_system() {
        global $wpdb;
        
        $backup_data = array(
            'users' => $wpdb->get_results("SELECT * FROM {$wpdb->users}"),
            'usermeta' => $wpdb->get_results("SELECT * FROM {$wpdb->usermeta}"),
            'appointments' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}clinica_appointments"),
            'timestamp' => current_time('mysql')
        );
        
        $backup_file = 'clinica_backup_' . date('Y-m-d_H-i-s') . '.json';
        
        return array('success' => true, 'filename' => $backup_file, 'data' => $backup_data);
    }
} 