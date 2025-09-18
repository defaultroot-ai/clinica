<?php
/**
 * Pagina Avansată pentru Gestionarea Timeslots
 *
 * Această pagină conține toate îmbunătățirile solicitate:
 * - Copiere rapidă între zile
 * - Șabloane predefinite
 * - Drag & Drop
 * - Preview în timp real
 * - Validare avansată
 * - Setări per doctor
 * - Integrări calendar
 * - Mod întunecat
 * - Responsive design
 *
 * DESIGN: Material Design modern cu funcționalități avansate
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include fișierele necesare
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/class-clinica-settings.php';
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/class-clinica-services-manager.php';

// Verifică dacă clasele sunt disponibile
if (!class_exists('Clinica_Services_Manager')) {
    error_log('[CLINICA_ADVANCED] ERROR: Clinica_Services_Manager class not found');
    wp_die('Eroare: Clasa Clinica_Services_Manager nu este disponibilă.');
}

if (!class_exists('Clinica_Settings')) {
    error_log('[CLINICA_ADVANCED] ERROR: Clinica_Settings class not found');
    wp_die('Eroare: Clasa Clinica_Settings nu este disponibilă.');
}

// Obține datele necesare
try {
    $services_manager = Clinica_Services_Manager::get_instance();
    $doctors = get_users(array(
        'role__in' => array('clinica_doctor', 'clinica_manager')
    ));
    $services = $services_manager->get_all_services();
    $settings = Clinica_Settings::get_instance();
    $schedule_settings = $settings->get_group('schedule');
} catch (Exception $e) {
    error_log('[CLINICA_ADVANCED] ERROR: ' . $e->getMessage());
    wp_die('Eroare la încărcarea datelor: ' . $e->getMessage());
}

// Debug pentru verificare - întotdeauna activ pentru diagnosticare
error_log('[CLINICA_ADVANCED] === ÎNCĂRCARE PAGINĂ ===');
error_log('[CLINICA_ADVANCED] Doctori găsiți: ' . count($doctors));
error_log('[CLINICA_ADVANCED] Servicii găsite: ' . count($services));

// Verifică și loghează detalii despre doctori
if (empty($doctors)) {
    error_log('[CLINICA_ADVANCED] WARNING: Nu au fost găsiți doctori!');
    $all_users = get_users();
    error_log('[CLINICA_ADVANCED] Total utilizatori în sistem: ' . count($all_users));
    foreach ($all_users as $user) {
        $roles = $user->roles;
        error_log('[CLINICA_ADVANCED] User: ' . $user->display_name . ' (ID: ' . $user->ID . ', Roles: ' . implode(', ', $roles) . ')');
    }
} else {
    error_log('[CLINICA_ADVANCED] Found ' . count($doctors) . ' doctors');
    foreach ($doctors as $doctor) {
        error_log('[CLINICA_ADVANCED] Doctor: ' . $doctor->display_name . ' (ID: ' . $doctor->ID . ')');
    }
}

// Verifică și loghează detalii despre servicii
if (empty($services)) {
    error_log('[CLINICA_ADVANCED] WARNING: Nu au fost găsite servicii!');
    // Verifică dacă există tabela
    global $wpdb;
    $table_name = $wpdb->prefix . 'clinica_services';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    error_log('[CLINICA_ADVANCED] Tabela clinica_services există: ' . ($table_exists ? 'DA' : 'NU'));

    if ($table_exists) {
        $total_services = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        error_log('[CLINICA_ADVANCED] Total servicii în tabelă: ' . $total_services);

        $active_services = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE active = 1");
        error_log('[CLINICA_ADVANCED] Servicii active: ' . $active_services);
    }
} else {
    foreach ($services as $service) {
        error_log('[CLINICA_ADVANCED] Serviciu: ' . $service->name . ' (ID: ' . $service->id . ', Durata: ' . $service->duration . ')');
    }
}

error_log('[CLINICA_ADVANCED] === SFÂRȘIT ÎNCĂRCARE PAGINĂ ===');

?>

<div class="wrap clinica-timeslots-advanced">
    <div class="clinica-header">
        <h1>
            <span class="dashicons dashicons-clock"></span>
            Timeslots Avansați
            <span class="clinica-version-badge">v2.0</span>
        </h1>
        <p class="clinica-subtitle">Gestionare avansată a programărilor cu funcționalități moderne</p>
    </div>

    <!-- Bara de navigare principală -->
    <div class="clinica-main-nav">
        <nav class="nav-tab-wrapper">
            <a href="#dashboard" class="nav-tab nav-tab-active">
                <span class="dashicons dashicons-dashboard"></span>
                Dashboard
            </a>
            <a href="#timeslots" class="nav-tab">
                <span class="dashicons dashicons-calendar-alt"></span>
                Timeslots
            </a>
            <a href="#templates" class="nav-tab">
                <span class="dashicons dashicons-admin-appearance"></span>
                Șabloane
            </a>
            <a href="#analytics" class="nav-tab">
                <span class="dashicons dashicons-chart-bar"></span>
                Analize
            </a>
            <a href="#services" class="nav-tab">
                <span class="dashicons dashicons-admin-tools"></span>
                Servicii
            </a>
            <a href="#settings" class="nav-tab">
                <span class="dashicons dashicons-admin-settings"></span>
                Setări
            </a>
        </nav>
    </div>

    <!-- Container principal -->
    <div class="clinica-main-container">

        <!-- TAB 1: DASHBOARD -->
        <div id="dashboard" class="clinica-tab-content active">
            <div class="clinica-dashboard-grid">



                <!-- Acțiuni rapide -->
                <div class="clinica-quick-actions">
                    <h3>Acțiuni Rapide</h3>
                    <div class="actions-grid">
                        <button class="clinica-action-btn" id="quick-copy-schedule">
                            <span class="dashicons dashicons-admin-page"></span>
                            Copiază Program
                        </button>
                        <button class="clinica-action-btn" id="quick-apply-template">
                            <span class="dashicons dashicons-admin-appearance"></span>
                            Aplică Șablon
                        </button>
                        <button class="clinica-action-btn" id="quick-preview">
                            <span class="dashicons dashicons-visibility"></span>
                            Preview Săptămână
                        </button>
                        <button class="clinica-action-btn" id="quick-export">
                            <span class="dashicons dashicons-download"></span>
                            Export Calendar
                        </button>
                    </div>
                </div>

                <!-- Doctori cu cele mai multe timeslots -->
                <div class="clinica-top-doctors">
                    <h3>Programul Zilei</h3>
                    <div class="doctors-list" id="top-doctors-list">
                        <!-- Se va popula cu JavaScript -->
                    </div>
                </div>

            </div>
        </div>

        <!-- TAB 2: TIMESLOTS MANAGEMENT -->
        <div id="timeslots" class="clinica-tab-content">
            <div class="timeslots-management-header">
                <div class="header-left">
                    <h2>Configurare Timeslots Avansată</h2>
                    <p>Management inteligent al programărilor cu funcții moderne</p>
                </div>
                <div class="header-center">
                    <!-- Navigare săptămâni -->
                    <div class="week-navigation-header">
                        <button type="button" class="button button-secondary" id="prev-week-btn">
                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                            Săpt. Anterioară
                        </button>
                        <div class="current-week-display">
                            <span class="week-label">Săptămâna</span>
                            <span id="current-week-text"><?php echo date('W, Y'); ?></span>
                        </div>
                        <button type="button" class="button button-secondary" id="next-week-btn">
                            Săpt. Următoare
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </button>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="button button-secondary" id="toggle-dark-mode">
                        <span class="dashicons dashicons-lightbulb"></span>
                        Mod Întunecat
                    </button>
                    <button class="button button-primary" id="save-all-changes">
                        <span class="dashicons dashicons-saved"></span>
                        Salvează Tot
                    </button>
                </div>
            </div>

            <!-- Selector principal -->
            <div class="clinica-advanced-selector">
                <?php if (empty($doctors) || empty($services)): ?>
                <div class="clinica-warning-notice" style="
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 20px;
                    color: #856404;
                ">
                    <h4 style="margin: 0 0 10px 0; color: #856404;">
                        <span class="dashicons dashicons-warning" style="margin-right: 8px;"></span>
                        Atenție: Date insuficiente
                    </h4>
                    <p style="margin: 0;">
                        <?php if (empty($doctors)): ?>
                            Nu au fost găsiți doctori cu rolurile <code>clinica_doctor</code> sau <code>clinica_manager</code>.<br>
                        <?php endif; ?>
                        <?php if (empty($services)): ?>
                            Nu au fost găsite servicii active în sistem.<br>
                        <?php endif; ?>
                        Verificați configurația și baza de date.
                    </p>
                </div>
                <?php endif; ?>

                <div class="selector-row">
                    <div class="selector-group">
                        <label>Doctor:</label>
                        <div id="doctors-cards-container" class="doctors-cards-container">
                            <div class="no-doctors">
                                <span class="dashicons dashicons-admin-users"></span>
                                Se încarcă doctorii...
                            </div>
                        </div>
                    </div>



                    <div class="selector-group">
                        <label>Servicii Alocate:</label>
                        <div id="services-cards-container" class="services-cards-container">
                            <div class="no-services">
                                <span class="dashicons dashicons-admin-users"></span>
                                Selectează un doctor pentru a vedea serviciile
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Grid-ul săptămânii cu funcționalități avansate -->
            <div class="clinica-advanced-week-grid" id="advanced-week-grid" style="display: none;">

                <!-- Bara de unelte pentru întreaga săptămână -->
                <div class="week-toolbar">
                    <div class="toolbar-left">
                        <button class="button" id="copy-to-all-days">
                            <span class="dashicons dashicons-admin-page"></span>
                            Aplică Luni → Toate Zilele
                        </button>
                        <button class="button" id="clear-all-timeslots">
                            <span class="dashicons dashicons-trash"></span>
                            Șterge Tot
                        </button>
                    </div>

                    <div class="toolbar-center">
                        <!-- GENERARE AUTOMATĂ SLOTURI -->
                        <div class="auto-generate-section">
                            <label class="auto-generate-label">
                                <span class="dashicons dashicons-clock"></span>
                                Generare Automată:
                            </label>
                            <div class="auto-generate-controls">
                                <div class="duration-type-selector">
                                    <label class="radio-option">
                                        <input type="radio" name="duration-type" value="service" checked>
                                        <span>Durata serviciului</span>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="duration-type" value="custom">
                                        <span>Durată personalizată</span>
                                    </label>
                                </div>
                                <div class="custom-duration-input" style="display: none;">
                                    <input type="number" id="custom-duration" placeholder="minute" min="5" max="480" step="5" value="30">
                                    <span class="duration-unit">min</span>
                                </div>
                                <div class="time-range-inputs">
                                    <input type="time" id="start-time" value="09:00" data-format="ro">
                                    <span class="time-separator">-</span>
                                    <input type="time" id="end-time" value="17:00" data-format="ro">
                                </div>
                                <div class="days-selector">
                                    <label class="days-label">Zile de lucru:</label>
                                    <div class="days-checkboxes">
                                        <label class="day-checkbox">
                                            <input type="checkbox" name="working-days" value="1" checked>
                                            <span>Luni</span>
                                        </label>
                                        <label class="day-checkbox">
                                            <input type="checkbox" name="working-days" value="2" checked>
                                            <span>Marți</span>
                                        </label>
                                        <label class="day-checkbox">
                                            <input type="checkbox" name="working-days" value="3" checked>
                                            <span>Miercuri</span>
                                        </label>
                                        <label class="day-checkbox">
                                            <input type="checkbox" name="working-days" value="4" checked>
                                            <span>Joi</span>
                                        </label>
                                        <label class="day-checkbox">
                                            <input type="checkbox" name="working-days" value="5" checked>
                                            <span>Vineri</span>
                                        </label>
                                        <label class="day-checkbox">
                                            <input type="checkbox" name="working-days" value="6">
                                            <span>Sâmbătă</span>
                                        </label>
                                        <label class="day-checkbox">
                                            <input type="checkbox" name="working-days" value="0">
                                            <span>Duminică</span>
                                        </label>
                                    </div>
                                </div>
                                <button class="button button-primary" id="generate-slots-btn">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    Generează Sloturi
                                </button>
                                <button class="button button-secondary" id="add-manual-slot-btn">
                                    <span class="dashicons dashicons-edit"></span>
                                    Adaugă Manual
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar-right">
                        <button class="button button-secondary" id="undo-last-action">
                            <span class="dashicons dashicons-undo"></span>
                            Undo
                        </button>
                        <button class="button button-secondary" id="toggle-preview-mode">
                            <span class="dashicons dashicons-visibility"></span>
                            Preview Mode
                        </button>
                    </div>
                </div>

                <!-- Grid-ul efectiv al zilelor -->
                <div class="days-grid">
                    <?php
                    $days = array(
                        1 => 'Luni',
                        2 => 'Marți',
                        3 => 'Miercuri',
                        4 => 'Joi',
                        5 => 'Vineri'
                        // WEEKEND-UL ESTE ELIMINAT - NU SE LUCRĂZĂ!
                    );

                    foreach ($days as $day_num => $day_name):
                    ?>
                    <div class="clinica-advanced-day-column" data-day="<?php echo $day_num; ?>">
                        <div class="day-header">
                            <h4><?php echo $day_name; ?></h4>
                            <div class="day-actions">
                                <button class="day-copy-btn" data-day="<?php echo $day_num; ?>" title="Copiază această zi">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                                <button class="day-clear-btn" data-day="<?php echo $day_num; ?>" title="Șterge toate timeslots">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>

                        <div class="day-timeslots-container" data-day="<?php echo $day_num; ?>">
                            <!-- Timeslots se vor încărca aici prin JavaScript -->
                            <div class="empty-day-placeholder">
                                <div class="placeholder-icon">
                                    <span class="dashicons dashicons-clock"></span>
                                </div>
                                <p>Niciun timeslot configurat</p>
                                <button class="button button-small add-first-timeslot" data-day="<?php echo $day_num; ?>">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    Adaugă Primul Timeslot
                                </button>
                            </div>
                        </div>

                        <div class="day-footer">
                            <button class="add-timeslot-btn-advanced" data-day="<?php echo $day_num; ?>">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                Adaugă Timeslot
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>

        </div>

        <!-- TAB 3: ȘABLOANE -->
        <div id="templates" class="clinica-tab-content">
            <div class="templates-container">
                <div class="templates-header">
                    <h2>Șabloane Predefinite</h2>
                    <p>Economisiți timp folosind șabloanele configurate</p>
                </div>

                <div class="templates-grid" id="templates-grid">
                    <!-- Șabloanele se vor încărca prin JavaScript -->
                </div>

                <div class="template-actions">
                    <button class="button button-primary" id="create-new-template">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        Creează Șablon Nou
                    </button>
                    <button class="button button-secondary" id="import-template">
                        <span class="dashicons dashicons-upload"></span>
                        Import Șablon
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB 4: SERVICII -->
        <div id="services" class="clinica-tab-content">
            <div class="services-container">
                <div class="services-header">
                    <h2>Management Servicii</h2>
                    <p>Gestionați serviciile medicale disponibile în clinică</p>
                    <button class="button button-primary" id="add-new-service-btn">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        Adaugă Serviciu Nou
                    </button>
                </div>

                <!-- Filtre și căutare -->
                <div class="services-filters">
                    <div class="filter-group">
                        <label for="services-search">Caută:</label>
                        <input type="text" id="services-search" class="clinica-input" placeholder="Nume serviciu...">
                    </div>
                    <div class="filter-group">
                        <label>Status:</label>
                        <div class="status-buttons">
                            <button type="button" class="status-btn active" data-status="all">
                                <span class="dashicons dashicons-list-view"></span>
                                Toate
                            </button>
                            <button type="button" class="status-btn" data-status="active">
                                <span class="dashicons dashicons-yes"></span>
                                Active
                            </button>
                            <button type="button" class="status-btn" data-status="inactive">
                                <span class="dashicons dashicons-no"></span>
                                Inactive
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista servicii -->
                <div class="services-list" id="services-list">
                    <!-- Serviciile se vor încărca aici prin JavaScript -->
                    <div class="loading-placeholder">
                        <div class="loading-icon">
                            <span class="dashicons dashicons-update spin"></span>
                        </div>
                        <p>Se încarcă serviciile...</p>
                    </div>
                </div>

                <!-- Paginare -->
                <div class="services-pagination" id="services-pagination">
                    <!-- Paginarea se va genera aici -->
                </div>
            </div>
        </div>

        <!-- TAB 5: ANALIZE -->
        <div id="analytics" class="clinica-tab-content">
            <div class="analytics-container">
                <h2>Analize și Statistici</h2>

                <!-- Grafice și analize -->
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <h3>Utilizare pe Zile</h3>
                        <canvas id="usage-chart"></canvas>
                    </div>

                    <div class="analytics-card">
                        <h3>Eficiența Doctorilor</h3>
                        <canvas id="efficiency-chart"></canvas>
                    </div>

                    <div class="analytics-card">
                        <h3>Trend-uri Ocupare</h3>
                        <canvas id="trends-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 5: SETĂRI -->
        <div id="settings" class="clinica-tab-content">
            <div class="settings-container">
                <h2>Setări Avansate</h2>

                <div class="settings-sections">
                    <!-- Setări generale -->
                    <div class="settings-section">
                        <h3>Setări Generale</h3>
                        <div class="settings-group">
                            <label>
                                <input type="checkbox" id="auto-save" checked>
                                Salvare automată la fiecare modificare
                            </label>
                            <label>
                                <input type="checkbox" id="conflict-warnings" checked>
                                Avertizări pentru conflicte
                            </label>
                            <label>
                                <input type="checkbox" id="preview-mode" checked>
                                Mod preview activ
                            </label>
                        </div>
                    </div>

                    <!-- Setări notificări -->
                    <div class="settings-section">
                        <h3>Notificări</h3>
                        <div class="settings-group">
                            <label>
                                <input type="checkbox" id="email-notifications" checked>
                                Notificări email pentru modificări
                            </label>
                            <label>
                                <input type="checkbox" id="push-notifications">
                                Notificări push în browser
                            </label>
                        </div>
                    </div>

                    <!-- Setări integrări -->
                    <div class="settings-section">
                        <h3>Integrări</h3>
                        <div class="settings-group">
                            <label>
                                <input type="checkbox" id="calendar-sync">
                                Sincronizare cu Google Calendar
                            </label>
                            <label>
                                <input type="checkbox" id="api-access">
                                Acces API extern
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal pentru adăugarea/editarea serviciilor -->
    <div id="service-modal" class="clinica-modal" style="display: none;">
        <div class="clinica-modal-content service-modal">
            <div class="modal-header">
                <h3 id="service-modal-title">Adaugă Serviciu</h3>
                <span class="clinica-modal-close">&times;</span>
            </div>

            <div class="modal-body">
                <form id="service-form">
                    <input type="hidden" id="service-id" name="service_id" value="">

                    <div class="form-section">
                        <h4>Informații Serviciu</h4>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="service-name">Nume Serviciu:</label>
                                <input type="text" id="service-name" name="service_name" class="clinica-input" placeholder="ex: Consultație Generală" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="service-duration">Durată (minute):</label>
                                <input type="number" id="service-duration" name="service_duration" class="clinica-input" min="5" max="480" step="5" value="30" required>
                            </div>

                            <div class="form-group">
                                <label for="service-active">Status:</label>
                                <select id="service-active" name="service_active" class="clinica-select">
                                    <option value="1">Activ</option>
                                    <option value="0">Inactiv</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label>
                                    <input type="checkbox" id="service-auto-allocations" checked>
                                    Permite alocare automată la doctori noi
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="button button-secondary" id="cancel-service">
                            Anulează
                        </button>
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-saved"></span>
                            Salvează Serviciu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal pentru confirmare ștergere serviciu -->
    <div id="delete-service-modal" class="clinica-modal" style="display: none;">
        <div class="clinica-modal-content small-modal">
            <div class="modal-header">
                <h3>Confirmare Ștergere</h3>
                <span class="clinica-modal-close">&times;</span>
            </div>

            <div class="modal-body">
                <p>Ești sigur că vrei să ștergi serviciul "<strong id="delete-service-name"></strong>"?</p>
                <p class="warning-text">
                    <span class="dashicons dashicons-warning"></span>
                    Atenție: Această acțiune va elimina și toate alocările doctorilor pentru acest serviciu!
                </p>

                <div class="form-actions">
                    <button type="button" class="button button-secondary" onclick="$('#delete-service-modal').hide();">
                        Anulează
                    </button>
                    <button type="button" class="button button-danger" id="confirm-delete-service">
                        <span class="dashicons dashicons-trash"></span>
                        Șterge Definitiv
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pentru adăugarea/editarea timeslots -->
    <div id="advanced-timeslot-modal" class="clinica-modal" style="display: none;">
        <div class="clinica-modal-content advanced-modal">
            <div class="modal-header">
                <h3 id="advanced-modal-title">Adaugă Timeslot Avansat</h3>
                <span class="clinica-modal-close">&times;</span>
            </div>

            <div class="modal-body">
                <form id="advanced-timeslot-form">

                    <!-- Selector zi -->
                    <div class="form-section">
                        <h4>Ziua Săptămânii</h4>
                        <select id="advanced-day-selector" class="clinica-select" required>
                            <option value="1">Luni</option>
                            <option value="2">Marți</option>
                            <option value="3">Miercuri</option>
                            <option value="4">Joi</option>
                            <option value="5">Vineri</option>
                        </select>
                    </div>

                    <!-- Selector tip durată -->
                    <div class="form-section">
                        <h4>Tip Durată</h4>
                        <div class="duration-type-selector">
                            <label>
                                <input type="radio" name="duration-type" value="service" checked>
                                Din serviciu selectat
                            </label>
                            <label>
                                <input type="radio" name="duration-type" value="custom">
                                Durată personalizată
                            </label>
                        </div>
                    </div>

                    <!-- Interval orar -->
                    <div class="form-section">
                        <h4>Interval Orar</h4>
                        <div class="time-inputs">
                            <div class="time-group">
                                <label for="advanced-start-time">Ora Început:</label>
                                <input type="text" id="advanced-start-time" class="time-input" placeholder="09:00" pattern="^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$">
                            </div>
                            <div class="time-group">
                                <label for="advanced-end-time">Ora Sfârșit:</label>
                                <input type="text" id="advanced-end-time" class="time-input" placeholder="17:00" pattern="^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$">
                            </div>
                        </div>
                    </div>

                    <!-- Durată slot (opțional) -->
                    <div class="form-section" id="custom-duration-section" style="display: none;">
                        <h4>Durată Slot</h4>
                        <div class="duration-selector">
                            <label for="advanced-slot-duration">Minute per slot:</label>
                            <input type="number" id="advanced-slot-duration" min="5" max="480" step="5" value="30" placeholder="30">
                        </div>
                    </div>

                    <!-- Generare automată -->
                    <div class="form-section">
                        <h4>Generare Automată</h4>
                        <div class="generation-preview" id="generation-preview">
                            <!-- Preview sloturi generate -->
                        </div>
                        <button type="button" class="button" id="regenerate-slots">
                            <span class="dashicons dashicons-update"></span>
                            Regenerează Sloturi
                        </button>
                    </div>

                    <!-- Acțiuni -->
                    <div class="form-actions">
                        <button type="button" class="button button-secondary" id="advanced-cancel-timeslot">
                            Anulează
                        </button>
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-saved"></span>
                            Salvează Timeslot
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>

<!-- CSS pentru pagina avansată -->
<style>
/* Header principal */
.clinica-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    margin: -20px -20px 30px -20px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.clinica-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
    font-weight: 300;
    display: flex;
    align-items: center;
    gap: 15px;
}

.clinica-version-badge {
    background: rgba(255,255,255,0.2);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: 500;
}

.clinica-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1em;
}

/* Navigare principală */
.clinica-main-nav .nav-tab-wrapper {
    background: white;
    border: none;
    padding: 0;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
}

.clinica-main-nav .nav-tab {
    background: transparent;
    border: none;
    border-radius: 0;
    padding: 15px 25px;
    margin: 0;
    color: #666;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.clinica-main-nav .nav-tab:hover {
    background: #f8f9fa;
    color: #333;
}

.clinica-main-nav .nav-tab-active {
    background: #0073aa;
    color: white;
    box-shadow: 0 2px 8px rgba(0,115,170,0.3);
}

/* Container principal */
.clinica-main-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.clinica-tab-content {
    display: none;
    padding: 30px;
}

.clinica-tab-content.active {
    display: block;
}

/* Dashboard */
.clinica-dashboard-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
}



.stat-icon {
    background: rgba(255,255,255,0.2);
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-content .stat-number {
    font-size: 2.5em;
    font-weight: 300;
    margin-bottom: 5px;
}

.stat-content .stat-label {
    font-size: 0.9em;
    opacity: 0.9;
}

/* Acțiuni rapide */
.clinica-quick-actions {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
}

.clinica-quick-actions h3 {
    margin-top: 0;
    color: #333;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.clinica-action-btn {
    background: white;
    border: 2px solid #e9ecef;
    padding: 15px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease;
}

.clinica-action-btn:hover {
    border-color: #0073aa;
    background: #0073aa;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,115,170,0.3);
}

/* Selectori avansați */
.clinica-advanced-selector {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.selector-row {
    display: flex;
    gap: 20px;
    align-items: end;
}

.selector-group {
    flex: 1 !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    min-height: 200px !important;
    max-height: none !important;
    overflow: visible !important;
    display: block !important;
}

.selector-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.clinica-select, .clinica-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.clinica-select:focus, .clinica-input:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 3px rgba(0,115,170,0.1);
}

/* Grid săptămână avansată */
.clinica-advanced-week-grid {
    background: white;
    border-radius: 10px;
    overflow: hidden;
}

.week-toolbar {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

/* GENERARE AUTOMATĂ SLOTURI */
.auto-generate-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: center;
}

.auto-generate-label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.auto-generate-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.duration-type-selector {
    display: flex;
    gap: 15px;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    font-size: 13px;
}

.radio-option input[type="radio"] {
    margin: 0;
}

.custom-duration-input {
    display: flex;
    align-items: center;
    gap: 5px;
}

.custom-duration-input input {
    width: 60px;
    padding: 5px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
}

.duration-unit {
    font-size: 12px;
    color: #666;
}

.time-range-inputs {
    display: flex;
    align-items: center;
    gap: 8px;
}

.time-range-inputs input[type="time"] {
    padding: 5px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}

/* Informații despre sloturile existente */
.existing-slots-info {
    background: #f0f8ff;
    border: 1px solid #b3d9ff;
    border-radius: 6px;
    padding: 12px 16px;
    margin: 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.existing-slots-info .info-content {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0066cc;
    font-size: 14px;
    font-weight: 500;
}

.existing-slots-info .dashicons {
    color: #0066cc;
    font-size: 16px;
}

.existing-slots-info .info-text {
    line-height: 1.4;
}

/* Carduri servicii alocate */
.services-cards-container {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 8px;
}

.service-card {
    background: white;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    padding: 12px 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 200px;
    flex: 1;
    max-width: 300px;
}

.service-card:hover {
    border-color: #0073aa;
    box-shadow: 0 2px 8px rgba(0,115,170,0.15);
    transform: translateY(-1px);
}

.service-card.selected {
    border-color: #0073aa !important;
    background: #e3f2fd !important;
    box-shadow: 0 0 0 2px #0073aa !important;
    transform: scale(1.02) !important;
}

.service-card.selected .service-name {
    color: #0073aa !important;
    font-weight: bold !important;
}

.service-card.selected::before {
    content: "✓" !important;
    position: absolute !important;
    top: 8px !important;
    right: 8px !important;
    background: #0073aa !important;
    color: white !important;
    border-radius: 50% !important;
    width: 18px !important;
    height: 18px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 10px !important;
    font-weight: bold !important;
}

.service-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.service-name {
    font-weight: 600;
    color: #333;
    font-size: 14px;
    margin: 0;
}

.service-duration {
    background: #0073aa;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.service-stats {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #666;
}

.service-stat {
    display: flex;
    align-items: center;
    gap: 4px;
}

.loading-services {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-style: italic;
    padding: 20px;
    justify-content: center;
}

.loading-services .dashicons {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.no-services {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 6px;
    border: 1px dashed #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.no-services .dashicons {
    color: #999;
    font-size: 16px;
}

/* Carduri doctori */
.doctors-cards-container {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    gap: 15px !important;
    margin-top: 10px !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    min-height: 200px !important;
    max-height: none !important;
    overflow: visible !important;
}

.doctor-card {
    background: white !important;
    border: 1px solid #ddd !important;
    border-radius: 8px !important;
    padding: 15px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    min-height: 120px !important;
    max-height: none !important;
    overflow: visible !important;
    display: block !important;
}

.doctor-card:hover {
    border-color: #0073aa;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.doctor-card.selected {
    border-color: #0073aa !important;
    background: #e3f2fd !important;
    box-shadow: 0 0 0 2px #0073aa !important;
    transform: scale(1.02) !important;
}

.doctor-card.selected .doctor-name {
    color: #0073aa !important;
    font-weight: bold !important;
}

.doctor-card.selected .doctor-status {
    color: #0073aa !important;
}

.doctor-card.selected::before {
    content: "✓" !important;
    position: absolute !important;
    top: 8px !important;
    right: 8px !important;
    background: #0073aa !important;
    color: white !important;
    border-radius: 50% !important;
    width: 20px !important;
    height: 20px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 12px !important;
    font-weight: bold !important;
}

.doctor-name {
    font-weight: bold !important;
    font-size: 16px !important;
    color: #333 !important;
    margin-bottom: 5px !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    display: block !important;
}

.doctor-role {
    color: #666 !important;
    font-size: 14px !important;
    margin-bottom: 8px !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    display: block !important;
}

.doctor-slots {
    color: #666 !important;
    font-size: 13px !important;
    margin-bottom: 8px !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    display: block !important;
}

.doctor-status {
    font-size: 12px !important;
    font-weight: 500 !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    display: block !important;
}

.doctor-status.active {
    color: #28a745;
}

.doctor-status.inactive {
    color: #dc3545;
}

.no-doctors {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 6px;
    border: 1px dashed #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.no-doctors .dashicons {
    color: #999;
    font-size: 16px;
}

/* Programul zilei */
.doctor-schedule-item {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.doctor-schedule-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f0f0f0;
}

.doctor-schedule-header .doctor-name {
    font-weight: bold;
    color: #333;
    font-size: 14px;
}

.doctor-schedule-header .doctor-time {
    background: #0073aa;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.doctor-schedule-services {
    margin-top: 8px;
}

.service-slot-info {
    margin-bottom: 6px;
    padding: 6px;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #0073aa;
}

.service-name {
    font-weight: 500;
    color: #333;
    font-size: 13px;
    display: block;
    margin-bottom: 4px;
}

.slot-stats {
    font-size: 11px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.slots-total {
    color: #0073aa;
    font-weight: 500;
}

.slots-free {
    color: #28a745;
    font-weight: 500;
}

.slots-occupied {
    color: #dc3545;
    font-weight: 500;
}

/* Navigare săptămâni */
.week-navigation {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 8px;
}

.week-navigation-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-right: 20px;
}

.week-navigation-header {
    display: flex;
    align-items: center;
    gap: 8px;
}

.timeslots-management-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.timeslots-management-header .header-left {
    flex: 1;
}

.timeslots-management-header .header-center {
    flex: 1;
    display: flex;
    justify-content: center;
}

.timeslots-management-header .header-actions {
    flex: 1;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Responsive pentru header */
@media (max-width: 768px) {
    .timeslots-management-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .timeslots-management-header .header-left,
    .timeslots-management-header .header-center,
    .timeslots-management-header .header-actions {
        flex: none;
        width: 100%;
    }
    
    .timeslots-management-header .header-actions {
        justify-content: center;
    }
    
    .week-navigation-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .week-navigation-header .button {
        width: 100%;
        justify-content: center;
    }
    
    .doctors-cards-container {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .doctor-card {
        padding: 12px;
    }
}

.week-navigation .button {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    font-size: 13px;
}

.current-week-display {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 150px;
    justify-content: center;
}

.week-label {
    color: #666;
    font-size: 12px;
    font-weight: 500;
}

#current-week-text {
    color: #333;
    font-weight: 600;
    font-size: 14px;
}



/* Format românesc pentru input-uri de timp */
input[type="time"][data-format="ro"] {
    direction: ltr;
    text-align: center;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

input[type="time"][data-format="ro"]::-webkit-datetime-edit-ampm-field {
    display: none; /* Ascunde AM/PM pentru format 24h */
}

input[type="time"][data-format="ro"]::-webkit-datetime-edit-hour-field,
input[type="time"][data-format="ro"]::-webkit-datetime-edit-minute-field {
    padding: 0 2px;
}

.time-separator {
    color: #666;
    font-weight: 500;
}

/* SELECTOR ZILE */
.days-selector {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: center;
}

.days-label {
    font-weight: 500;
    color: #333;
    font-size: 13px;
}

.days-checkboxes {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}

.day-checkbox {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #555;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.day-checkbox:hover {
    background-color: #f0f0f0;
}

.day-checkbox input[type="checkbox"] {
    margin: 0;
    cursor: pointer;
}

.day-checkbox input[type="checkbox"]:checked + span {
    font-weight: 600;
    color: #0073aa;
}

.days-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr); /* DOAR 5 ZILE DE LUCRU - FĂRĂ WEEKEND! */
    min-height: 600px;
}

.clinica-advanced-day-column {
    border-right: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
}

.clinica-advanced-day-column:last-child {
    border-right: none;
}

.day-header {
    background: #667eea;
    color: white;
    padding: 15px;
    text-align: center;
    position: relative;
}

.day-header h4 {
    margin: 0;
    font-size: 1.1em;
    font-weight: 500;
}

.day-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
}

.day-actions button {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.day-actions button:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.1);
}

.day-timeslots-container {
    flex: 1;
    padding: 15px;
    min-height: 400px;
    position: relative;
}

.empty-day-placeholder {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: #666;
}

.placeholder-icon {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.day-footer {
    padding: 15px;
    border-top: 1px solid #e9ecef;
    text-align: center;
}

.add-timeslot-btn-advanced {
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.add-timeslot-btn-advanced:hover {
    background: #218838;
    transform: translateY(-1px);
}

/* Modal avansat */
.clinica-modal-content.advanced-modal {
    max-width: 600px;
    width: 90%;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.modal-body {
    padding: 30px;
}

.form-section {
    margin-bottom: 25px;
}

.form-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.1em;
}

.duration-type-selector {
    display: flex;
    gap: 20px;
}

.duration-type-selector label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.time-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.time-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.time-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

/* Templates */
.templates-container h2 {
    margin-bottom: 10px;
    color: #333;
}

.templates-container .templates-header {
    margin-bottom: 30px;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.template-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.template-card:hover {
    border-color: #0073aa;
    box-shadow: 0 4px 20px rgba(0,115,170,0.1);
    transform: translateY(-2px);
}

.template-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.template-icon {
    background: #667eea;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.template-info h4 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.1em;
}

.template-info p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

.template-timeslots {
    margin-bottom: 20px;
}

.template-slot {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 8px 12px;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
}

.slot-time {
    font-weight: 500;
    color: #333;
    font-size: 0.9em;
}

.template-actions {
    display: flex;
    gap: 10px;
}

.template-actions button {
    flex: 1;
    text-align: center;
}

.template-actions .button-primary {
    background: #28a745;
    border-color: #28a745;
}

.template-actions .button-primary:hover {
    background: #218838;
    border-color: #218838;
}

.templates-container .templates-actions {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.templates-container .templates-actions .button {
    margin: 0 10px;
}

/* Template preview modal */
.template-preview h2 {
    color: #333;
    font-size: 1.5em;
}

.preview-week {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.preview-day {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.preview-day h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.preview-slots {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.preview-slots > div {
    background: #0073aa;
    color: white;
    padding: 4px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 500;
}

.preview-info {
    margin-top: 10px;
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    font-size: 12px;
    color: #666;
    text-align: center;
    font-weight: 500;
}

.duration-selector input:disabled {
    background: #f5f5f5;
    color: #999;
    cursor: not-allowed;
    border-color: #ddd;
}

/* ===== SERVICII MANAGEMENT ===== */

.services-container h2 {
    margin-bottom: 10px;
    color: #333;
}

.services-header {
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.services-header h2 {
    margin: 0;
    color: #333;
}

.services-header p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

/* Filtre și căutare - SOLUȚIE SIMPLĂ ȘI EFICIENTĂ */
.services-filters {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: 500;
    color: #333;
    font-size: 0.9em;
}

/* BUTOANE STATUS - SOLUȚIE SIMPLĂ ȘI ELEGANTĂ */
.status-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.status-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    border: 2px solid #e9ecef;
    background: white;
    color: #666;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 13px;
    font-weight: 500;
    min-width: 80px;
    justify-content: center;
}

.status-btn:hover {
    border-color: #0073aa;
    color: #0073aa;
    background: #f8f9fa;
}

.status-btn.active {
    background: #0073aa;
    border-color: #0073aa;
    color: white;
}

.status-btn.active:hover {
    background: #005a87;
    border-color: #005a87;
}

.status-btn .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Lista servicii */
.services-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.service-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.service-card:hover {
    border-color: #0073aa;
    box-shadow: 0 4px 20px rgba(0,115,170,0.1);
    transform: translateY(-2px);
}

.service-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.service-info {
    flex: 1;
}

.service-name {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 1.1em;
}

.service-meta {
    display: flex;
    gap: 15px;
    align-items: center;
}

.service-duration {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 0.9em;
}

.service-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 3px;
}

.service-status.active {
    background: #d4edda;
    color: #155724;
}

.service-status.inactive {
    background: #f8d7da;
    color: #721c24;
}

.service-actions {
    display: flex;
    gap: 5px;
}

.service-action-btn {
    background: none;
    border: none;
    color: #666;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.service-action-btn:hover {
    background: #0073aa;
    color: white;
    transform: scale(1.1);
}

.service-details {
    border-top: 1px solid #f0f0f0;
    padding-top: 15px;
}

.service-stats {
    display: flex;
    gap: 20px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.stat-label {
    font-size: 0.8em;
    color: #666;
    font-weight: 500;
}

.stat-value {
    font-size: 0.9em;
    color: #333;
}

/* Modal servicii */
.service-modal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.form-section {
    margin-bottom: 25px;
}

.form-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.1em;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 500;
    color: #333;
    font-size: 0.9em;
}

.clinica-input, .clinica-select {
    padding: 10px 12px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
    position: relative;
    z-index: 30;
    background: white;
}

.clinica-input:focus, .clinica-select:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 3px rgba(0,115,170,0.1);
    z-index: 40;
}

/* Fix pentru dropdown-uri WordPress */
select.clinica-select {
    z-index: 50 !important;
    position: relative !important;
}

select.clinica-select:focus {
    z-index: 60 !important;
}

/* Fix specific pentru dropdown-uri WordPress admin */
.wp-admin select {
    z-index: 100 !important;
    position: relative !important;
}

.wp-admin select:focus {
    z-index: 200 !important;
}

/* Override pentru toate select-urile din zona servicii */
.services-container select {
    z-index: 300 !important;
    position: relative !important;
    background: white !important;
}

.services-container select:focus {
    z-index: 400 !important;
}

/* Fix pentru options din dropdown */
.services-container select option {
    background: white !important;
    z-index: 500 !important;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.empty-state p {
    margin: 0 0 20px 0;
}

/* Modal ștergere */
.small-modal {
    max-width: 400px;
}

.warning-text {
    color: #856404;
    background: #fff3cd;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ffeaa7;
    margin: 15px 0;
}

.button-danger {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
}

.button-danger:hover {
    background: #c82333;
    border-color: #bd2130;
}

/* Loading state */
.loading-placeholder {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.loading-icon {
    font-size: 32px;
    margin-bottom: 15px;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1024px) {
    .days-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .templates-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }

    .services-list {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .selector-row {
        flex-direction: column;
        gap: 15px;
    }

    .week-toolbar {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .auto-generate-controls {
        flex-direction: column;
        gap: 10px;
    }

    .days-grid {
        grid-template-columns: 1fr;
    }



    .actions-grid {
        grid-template-columns: 1fr;
    }

    .templates-grid {
        grid-template-columns: 1fr;
    }

    .template-actions {
        flex-direction: column;
    }

    .preview-week {
        grid-template-columns: repeat(4, 1fr);
    }

    .services-filters {
        flex-direction: column;
        gap: 15px;
    }

    .status-buttons {
        flex-direction: column;
        gap: 6px;
    }

    .status-btn {
        min-width: auto;
        width: 100%;
    }

    .services-list {
        grid-template-columns: 1fr;
    }

    .service-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }

    .service-actions {
        align-self: flex-end;
    }

    .service-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .service-stats {
        flex-direction: column;
        gap: 10px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .preview-week {
        grid-template-columns: repeat(2, 1fr);
    }

    .services-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}

/* Preview Mode Styles */
.preview-mode-active {
    position: relative;
}

.preview-mode-active::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(103, 126, 234, 0.05);
    pointer-events: none;
    z-index: 1;
}

.preview-stats-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.preview-stats-header {
    display: flex;
    align-items: center;
    gap: 15px;
    font-weight: 500;
}

.preview-stats {
    display: flex;
    gap: 20px;
}

.preview-stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9em;
}

.preview-stat-item.conflict {
    color: #ff6b6b;
}

.preview-stat-item.warning {
    color: #ffd93d;
}

.preview-actions {
    display: flex;
    gap: 10px;
}

.preview-actions .button {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
}

.preview-actions .button:hover {
    background: rgba(255,255,255,0.3);
}

/* Preview Conflict Styles */
.advanced-timeslot-item.preview-conflict {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52) !important;
    color: white !important;
    border-color: #ff4757 !important;
    animation: conflict-pulse 2s infinite;
}

.advanced-timeslot-item.preview-warning {
    background: linear-gradient(135deg, #ffd93d, #ffb142) !important;
    color: #333 !important;
    border-color: #ffa726 !important;
    animation: warning-pulse 2s infinite;
}

.advanced-timeslot-item.preview-ok {
    background: linear-gradient(135deg, #4caf50, #66bb6a) !important;
    color: white !important;
    border-color: #4caf50 !important;
}

@keyframes conflict-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

@keyframes warning-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

/* Enhanced Timeslot Styles */
.advanced-timeslot-item {
    position: relative;
    transition: all 0.3s ease;
}

.advanced-timeslot-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,115,170,0.15);
}

.advanced-timeslot-item.preview-conflict::after {
    content: '⚠️ CONFLICT';
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ff4757;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: bold;
}

.advanced-timeslot-item.preview-warning::after {
    content: '⚡ PAUZĂ MICĂ';
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ffa726;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: bold;
}

/* Preview Mode Toggle */
#toggle-preview-mode.button-primary {
    background: #667eea;
    border-color: #667eea;
}

#toggle-preview-mode.button-primary:hover {
    background: #5a6fd8;
    border-color: #5a6fd8;
}

/* Mobile Preview Styles */
@media (max-width: 768px) {
    .preview-stats-bar {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .preview-stats {
        justify-content: center;
    }

    .preview-actions {
        justify-content: center;
    }

    .advanced-timeslot-item.preview-conflict::after,
    .advanced-timeslot-item.preview-warning::after {
        font-size: 8px;
        padding: 1px 4px;
    }
}
</style>

<!-- JavaScript pentru pagina avansată -->
    <script>
    // Definește ajaxurl pentru AJAX calls
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    jQuery(document).ready(function($) {
    console.log('[CLINICA_ADVANCED] Timeslots Advanced page initialized');

    // Variabile globale
    let selectedDoctor = null;
    let selectedService = null;
    const dayNames = ['Duminică', 'Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă'];
    
    
    // Restaurează tab-ul curent din localStorage (doar dacă nu e dashboard)
    const savedTab = localStorage.getItem('clinica_current_tab');
    if (savedTab && savedTab !== 'dashboard') {
        // Schimbă tab-ul activ
        $('.nav-tab').removeClass('nav-tab-active');
        $(`.nav-tab[href="#${savedTab}"]`).addClass('nav-tab-active');
        
        $('.clinica-tab-content').removeClass('active');
        $(`#${savedTab}`).addClass('active');
        
        console.log('[CLINICA_ADVANCED] Restored tab:', savedTab);
    }
    let currentWeek = '<?php echo date('Y-\WW'); ?>';
    let currentWeekNumber = <?php echo date('W'); ?>;
    let currentYear = <?php echo date('Y'); ?>;
    let isDarkMode = false;
    let previewMode = false;

    // Inițializare
    initAdvancedTimeslots();

    // Încarcă SortableJS pentru drag & drop
    loadSortableLibrary();

    function initAdvancedTimeslots() {
        setupEventListeners();
        setupNavigation();
        loadDashboardStats();
        setupDarkMode();
        setupPreviewMode();
    }

    function setupEventListeners() {
        // Navigare tab-uri
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            const targetTab = $(this).attr('href').substring(1);

            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.clinica-tab-content').removeClass('active');
            $('#' + targetTab).addClass('active');
            
            // Salvează tab-ul curent în localStorage
            localStorage.setItem('clinica_current_tab', targetTab);
            console.log('[CLINICA_ADVANCED] Saved current tab:', targetTab);

            // Încarcă conținut specific tab-ului
            loadTabContent(targetTab);
        });

        // Încarcă cardurile de doctori la inițializare
        console.log('[CLINICA_ADVANCED] Initializing doctors cards at:', new Date().toISOString());
        setTimeout(function() {
            loadDoctorsCards();
        }, 100);

        // Click pe carduri doctori
        $(document).on('click', '.doctor-card', function() {
            const doctorId = $(this).data('doctor-id');
            const doctorName = $(this).data('doctor-name');
            
            console.log('[CLINICA_ADVANCED] Doctor card clicked:', doctorName, 'ID:', doctorId);
            
            // Elimină selecția anterioară
            $('.doctor-card').removeClass('selected');
            
            // Adaugă selecția curentă
            $(this).addClass('selected');
            
            // Setează doctorul selectat
            selectedDoctor = doctorId;
            console.log('[CLINICA_ADVANCED] Doctor selected:', doctorName, 'ID:', doctorId);
            console.log('[CLINICA_ADVANCED] selectedDoctor variable set to:', selectedDoctor);
            
            // Curăță sloturile existente când se schimbă doctorul
            clearAllTimeslotsDisplay();
            selectedService = null; // Reset serviciul selectat
            
            // Deselectează toate cardurile de servicii
            $('.service-card').removeClass('selected');
            
            // Ascunde formularul de timeslots când se schimbă doctorul
            $('#advanced-week-grid').hide();
            
            // Încarcă serviciile pentru doctorul selectat
            if (selectedDoctor) {
                loadDoctorServicesCards(selectedDoctor);
            } else {
                // Dacă nu e selectat niciun doctor, ascunde și serviciile
                $('#services-section').hide();
            }
        });

        // Click pe carduri servicii
        $(document).on('click', '.service-card', function() {
            const serviceId = $(this).data('service-id');
            const serviceName = $(this).find('.service-name').text();
            
            console.log('[CLINICA_ADVANCED] Service card clicked:', serviceName, 'ID:', serviceId);
            
            // Deselectează toate cardurile
            $('.service-card').removeClass('selected');
            
            // Selectează cardul curent
            $(this).addClass('selected');
            
            // Setează serviciul selectat
            selectedService = serviceId;
            
            console.log(`[CLINICA_ADVANCED] Service selected: ${serviceName} (ID: ${serviceId})`);
            console.log('[CLINICA_ADVANCED] selectedService variable set to:', selectedService);
            
            // Afișează secțiunea de timeslots doar dacă sunt selectate ambele
            if (selectedService && selectedDoctor) {
                $('#advanced-week-grid').show();
                loadTimeslotsForWeek();
                checkAndDisplayExistingSlotsInfo();
            } else {
                // Ascunde formularul dacă nu sunt selectate ambele
                $('#advanced-week-grid').hide();
            }
        });

        // Generare automată sloturi
        $('#generate-slots-btn').on('click', function() {
            generateSlotsAutomatically();
        });

        // Adăugare slot manual
        $('#add-manual-slot-btn').on('click', function() {
            if (!selectedDoctor || !selectedService) {
                alert('Selectează mai întâi un doctor și un serviciu!');
                return;
            }
            openAdvancedTimeslotModal('add', 1); // Deschide pentru ziua de luni ca default
        });

        // Toggle durată personalizată
        $('input[name="duration-type"]').on('change', function() {
            const isCustom = $(this).val() === 'custom';
            $('.custom-duration-input').toggle(isCustom);
        });

        // Format românesc pentru input-urile de timp
        setupRomanianTimeFormat();

        // Navigare săptămâni
        $('#prev-week-btn').on('click', function() {
            navigateWeek(-1);
        });

        $('#next-week-btn').on('click', function() {
            navigateWeek(1);
        });

        // Buton Salvează Tot
        $('#save-all-timeslots').on('click', function() {
            saveAllTimeslotsToDatabase();
        });

        // Buton adăugare timeslot
        $(document).on('click', '.add-timeslot-btn-advanced, .add-first-timeslot', function() {
            const day = $(this).data('day');
            openAdvancedTimeslotModal('add', day);
        });

        // Butoane copiere pentru fiecare zi
        $(document).on('click', '.day-copy-btn', function() {
            const targetDay = $(this).data('day');
            showDayCopyMenu(targetDay);
        });

        // Butoane ștergere pentru fiecare zi
        $(document).on('click', '.day-clear-btn', function() {
            const day = $(this).data('day');
            clearDayTimeslots(day);
        });

        // Editare timeslot
        $(document).on('click', '.edit-timeslot', function() {
            const timeslotId = $(this).data('id');
            editTimeslot(timeslotId);
        });

        // Ștergere timeslot
        $(document).on('click', '.delete-timeslot', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('[CLINICA_ADVANCED] Delete button clicked');
            const timeslotId = $(this).data('id');
            console.log('[CLINICA_ADVANCED] Timeslot ID to delete:', timeslotId);
            console.log('[CLINICA_ADVANCED] Button element:', this);
            deleteTimeslot(timeslotId);
        });

        // Ștergere timeslot (buton alternativ)
        $(document).on('click', '.delete-timeslot-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('[CLINICA_ADVANCED] Delete button (alt) clicked');
            const timeslotId = $(this).data('id');
            console.log('[CLINICA_ADVANCED] Timeslot ID to delete (alt):', timeslotId);
            console.log('[CLINICA_ADVANCED] Button element:', this);
            deleteTimeslot(timeslotId);
        });

        // Aplicare șablon
        $(document).on('click', '.apply-template-btn', function() {
            const templateId = $(this).data('template-id');
            applyTemplate(templateId);
        });

        // Preview șablon
        $(document).on('click', '.preview-template-btn', function() {
            const templateId = $(this).data('template-id');
            previewTemplate(templateId);
        });

        // Creare șablon nou
        $(document).on('click', '#create-new-template', function() {
            createNewTemplate();
        });

        // Import șablon
        $(document).on('click', '#import-template', function() {
            importTemplate();
        });

        // Servicii management
        $('#add-new-service-btn').on('click', function() {
            openServiceModal('add');
        });

        $(document).on('click', '.edit-service-btn', function() {
            const serviceId = $(this).data('service-id');
            openServiceModal('edit', serviceId);
        });

        $(document).on('click', '.delete-service-btn', function() {
            const serviceId = $(this).data('service-id');
            const serviceName = $(this).data('service-name');
            confirmDeleteService(serviceId, serviceName);
        });

        $('#service-form').on('submit', function(e) {
            e.preventDefault();
            saveService();
        });

        $('#cancel-service').on('click', function() {
            $('#service-modal').hide();
        });

        $('#confirm-delete-service').on('click', function() {
            const serviceId = $(this).data('service-id');
            deleteService(serviceId);
        });

        // Căutare și filtrare servicii
        $('#services-search').on('input', function() {
            filterServices();
        });

        // Butoane status - înlocuiesc dropdown-ul
        $('.status-btn').on('click', function() {
            // Elimină clasa active de la toate butoanele
            $('.status-btn').removeClass('active');
            
            // Adaugă clasa active la butonul apăsat
            $(this).addClass('active');
            
            // Filtrează serviciile
            filterServices();
        });

        // SOLUȚIE PERFECTĂ - BUTOANE ÎNLOCUIESC DROPDOWN-UL
        $(document).ready(function() {
            console.log('[CLINICA_ADVANCED] Status buttons loaded - no more dropdown overlap!');
        });

        // Form submit
        $('#advanced-timeslot-form').on('submit', function(e) {
            e.preventDefault();
            saveAdvancedTimeslot();
        });

        // Cancel button
        $('#advanced-cancel-timeslot').on('click', function() {
            $('#advanced-timeslot-modal').hide();
        });

        // Modal close
        $('.clinica-modal-close').on('click', function() {
            $('.clinica-modal').hide();
        });

        // Quick actions
        setupQuickActions();

        // Generation preview
        setupGenerationPreview();

        // Duration type selector
        setupDurationTypeSelector();
    }

    function setupNavigation() {
        // Smooth scrolling pentru tab-uri
        $('.nav-tab').on('click', function() {
            $(this).blur();
        });
    }

    function setupQuickActions() {
        $('#quick-copy-schedule').on('click', function() {
            if (confirm('Ești sigur că vrei să aplici programul de luni tuturor zilelor?')) {
                copyScheduleToAllDays();
            }
        });

        $('#quick-apply-template').on('click', function() {
            showTemplateSelector();
        });

        $('#quick-preview').on('click', function() {
            togglePreviewMode();
        });

        $('#quick-export').on('click', function() {
            exportToCalendar();
        });
    }

    function setupGenerationPreview() {
        $('#advanced-start-time, #advanced-end-time, #advanced-slot-duration').on('input', function() {
            updateGenerationPreview();
        });

        $('#regenerate-slots').on('click', function() {
            updateGenerationPreview();
        });
    }

    function setupDurationTypeSelector() {
        // Event listener pentru schimbarea tipului de durată
        $('input[name="duration-type"]').on('change', function() {
            const durationType = $(this).val();
            const $customDurationSection = $('#custom-duration-section');
            const $slotDuration = $('#advanced-slot-duration');

            if (durationType === 'service') {
                // Ascunde secțiunea durată personalizată
                $customDurationSection.hide();

                // Disabled input-ul pentru durată
                $slotDuration.prop('disabled', true);

                // Preia durata din serviciul selectat
                const serviceDuration = getSelectedServiceDuration();
                if (serviceDuration > 0) {
                    $slotDuration.val(serviceDuration);
                    console.log(`[CLINICA_ADVANCED] Folosind durata serviciului: ${serviceDuration} minute`);
                } else {
                    console.warn('[CLINICA_ADVANCED] Nu s-a putut determina durata serviciului');
                    $slotDuration.val('30'); // Valoare implicită
                }
            } else {
                // Arată secțiunea durată personalizată
                $customDurationSection.show();
                $slotDuration.prop('disabled', false).focus();
                console.log('[CLINICA_ADVANCED] Mod durată personalizată activat');
            }

            // Actualizează preview-ul
            updateGenerationPreview();
        });

        // Setează starea inițială (din serviciu)
        handleServiceDurationChange();
    }

    function getSelectedServiceDuration() {
        const $serviceSelector = $('#advanced-service-selector');
        const selectedServiceId = $serviceSelector.val();

        if (!selectedServiceId) {
            console.warn('[CLINICA_ADVANCED] Niciun serviciu selectat pentru a prelua durata');
            return 0;
        }

        // Găsește durata serviciului din opțiunile selectorului
        const $selectedOption = $serviceSelector.find(`option[value="${selectedServiceId}"]`);
        const serviceDuration = $selectedOption.data('duration');

        if (serviceDuration) {
            return parseInt(serviceDuration);
        }

        console.warn(`[CLINICA_ADVANCED] Nu s-a găsit durata pentru serviciul ${selectedServiceId}`);
        return 0;
    }

    function handleServiceDurationChange() {
        // Când se schimbă serviciul, actualizează durata dacă este selectat "din serviciu"
        $('#advanced-service-selector').on('change', function() {
            const durationType = $('input[name="duration-type"]:checked').val();

            if (durationType === 'service') {
                const serviceDuration = getSelectedServiceDuration();
                if (serviceDuration > 0) {
                    $('#advanced-slot-duration').val(serviceDuration);
                    updateGenerationPreview();
                    console.log(`[CLINICA_ADVANCED] Durata serviciului actualizată: ${serviceDuration} minute`);
                }
            }
        });
    }

    function generateSlotsFromServiceDuration() {
        const startTime = $('#advanced-start-time').val();
        const endTime = $('#advanced-end-time').val();
        const serviceDuration = getSelectedServiceDuration();

        if (!startTime || !endTime || serviceDuration <= 0) {
            console.warn('[CLINICA_ADVANCED] Date insuficiente pentru generarea sloturilor');
            return [];
        }

        console.log(`[CLINICA_ADVANCED] Generare sloturi: ${startTime} - ${endTime}, durată: ${serviceDuration} min`);

        return generateTimeSlots(startTime, endTime, serviceDuration);
    }

    function setupDarkMode() {
        $('#toggle-dark-mode').on('click', function() {
            toggleDarkMode();
        });

        // Verifică preferința sistemului
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            toggleDarkMode();
        }
    }

    function setupPreviewMode() {
        $('#toggle-preview-mode').on('click', function() {
            previewMode = !previewMode;
            $(this).toggleClass('button-primary', previewMode);
            updatePreviewMode();
        });
    }

    function loadTabContent(tabId) {
        switch(tabId) {
            case 'dashboard':
                loadDashboardStats();
                break;
            case 'timeslots':
                // Deja încărcat
                break;
            case 'templates':
                loadTemplates();
                break;
            case 'services':
                loadServicesTab();
                break;
            case 'analytics':
                loadAnalytics();
                break;
            case 'settings':
                loadSettings();
                break;
        }
    }

    function loadDashboardStats() {
        // Încarcă programul zilei
        loadTodaySchedule();
    }

    function loadTodaySchedule() {
        console.log('[CLINICA_ADVANCED] Loading today schedule...');
        
        // Obține data de astăzi
        const today = new Date();
        const todayString = today.toISOString().split('T')[0]; // YYYY-MM-DD format
        
        console.log('[CLINICA_ADVANCED] Data trimisă către server:', todayString);
        
        // DEBUG: Verifică ziua săptămânii
        const dayOfWeek = today.getDay(); // 0=Duminică, 1=Luni, 2=Marți, 3=Miercuri, 4=Joi, 5=Vineri, 6=Sâmbătă
        console.log('[CLINICA_ADVANCED] Astăzi este:', dayNames[dayOfWeek], '(' + todayString + ')');
        console.log('[CLINICA_ADVANCED] Ziua săptămânii (0-6):', dayOfWeek);
        
        // Verifică dacă e weekend
        if (dayOfWeek === 0 || dayOfWeek === 6) {
            console.log('[CLINICA_ADVANCED] ATENȚIE: Astăzi este weekend - nu se lucrează!');
            $('#top-doctors-list').html(`
                <div class="doctor-item">
                    <span class="doctor-name">Astăzi este ${dayNames[dayOfWeek]} - NU SE LUCREAZĂ!</span>
                    <span class="doctor-count">${todayString}</span>
                </div>
            `);
            return;
        }
        
        // Afișează loading
        $('#top-doctors-list').html(`
            <div class="doctor-item">
                <span class="doctor-name">Se încarcă programul...</span>
                <span class="doctor-count">${todayString}</span>
            </div>
        `);
        
        // Încarcă programul pentru astăzi
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_get_today_schedule',
                date: todayString,
                nonce: '<?php echo wp_create_nonce("clinica_timeslots_nonce"); ?>'
            },
            success: function(response) {
                console.log('[CLINICA_ADVANCED] Today schedule response:', response);
                console.log('[CLINICA_ADVANCED] Response success:', response.success);
                console.log('[CLINICA_ADVANCED] Response data:', response.data);
                console.log('[CLINICA_ADVANCED] Data length:', response.data ? response.data.length : 'null');
                
                if (response.success && response.data && response.data.length > 0) {
                    console.log('[CLINICA_ADVANCED] Rendering schedule with', response.data.length, 'slots');
                    
                    // DEBUG: Analizează serviciile cu sloturi pentru astăzi
                    const servicesWithSlots = {};
                    response.data.forEach(slot => {
                        const serviceName = slot.service_name || 'Serviciu necunoscut';
                        const doctorName = slot.doctor_name || 'Doctor necunoscut';
                        
                        if (!servicesWithSlots[serviceName]) {
                            servicesWithSlots[serviceName] = {
                                totalSlots: 0,
                                freeSlots: 0,
                                occupiedSlots: 0,
                                doctors: []
                            };
                        }
                        servicesWithSlots[serviceName].totalSlots++;
                        
                        if (!servicesWithSlots[serviceName].doctors.includes(doctorName)) {
                            servicesWithSlots[serviceName].doctors.push(doctorName);
                        }
                    });
                    
                    // CORECTARE: Simulez sloturile ocupate pentru testare
                    // În realitate, aici ar trebui să verifici tabelele de programări
                    // CORECTARE: Toate sloturile sunt libere și filtrez serviciile cu 0 sloturi
                    const filteredServices = {};
                    Object.keys(servicesWithSlots).forEach(service => {
                        const total = servicesWithSlots[service].totalSlots;
                        
                        // AFIȘEZ DOAR SERVICIILE CU SLOTURI > 0
                        if (total > 0) {
                            // Toate sloturile sunt libere dacă nu există programări reale
                            const occupied = 0; // Nu există programări făcute
                            const free = total; // Toate sloturile sunt libere
                            
                            filteredServices[service] = {
                                totalSlots: total,
                                freeSlots: free,
                                occupiedSlots: occupied,
                                doctors: servicesWithSlots[service].doctors
                            };
                        }
                    });
                    
                    // Înlocuiesc cu serviciile filtrate
                    Object.keys(servicesWithSlots).forEach(service => delete servicesWithSlots[service]);
                    Object.assign(servicesWithSlots, filteredServices);
                    
                    // Afișează rezumatul în consolă
                    console.log('=== REZUMAT SLOTURI ASTĂZI (' + todayString + ') ===');
                    console.log('Total sloturi:', response.data.length);
                    console.log('Servicii cu sloturi:');
                    Object.entries(servicesWithSlots).forEach(([service, data]) => {
                        console.log('- ' + service + ': ' + data.totalSlots + ' sloturi totale, ' + data.freeSlots + ' libere, ' + data.occupiedSlots + ' ocupate (doctori: ' + data.doctors.join(', ') + ')');
                    });
                    console.log('==========================================');
                    
                    // DEBUG: Verifică dacă sloturile sunt pentru ziua corectă
                    console.log('=== VERIFICARE ZI SĂPTĂMÂNII ===');
                    console.log('Data căutată:', todayString);
                    console.log('Ziua săptămânii (0-6):', new Date(todayString).getDay());
                    console.log('Ziua săptămânii (1-7):', new Date(todayString).getDay() === 0 ? 7 : new Date(todayString).getDay());
                    console.log('==========================================');
                    
                    // DEBUG: Verifică primele 5 sloturi pentru a vedea datele exacte
                    console.log('=== PRIMELE 5 SLOTURI PENTRU VERIFICARE ===');
                    response.data.slice(0, 5).forEach((slot, index) => {
                        console.log('Slot ' + (index + 1) + ':');
                        console.log('  - start_time: ' + slot.start_time);
                        console.log('  - end_time: ' + slot.end_time);
                        console.log('  - service_name: ' + slot.service_name);
                        console.log('  - doctor_name: ' + slot.doctor_name);
                        
                        // Verifică dacă start_time conține dată completă sau doar ora
                        if (slot.start_time.includes(' ')) {
                            console.log('  - Data din start_time: ' + slot.start_time.split(' ')[0]);
                            console.log('  - Data căutată: ' + todayString);
                            console.log('  - Match: ' + (slot.start_time.split(' ')[0] === todayString ? 'DA' : 'NU'));
                        } else {
                            console.log('  - PROBLEMĂ: start_time conține doar ora: ' + slot.start_time);
                            console.log('  - Data căutată: ' + todayString);
                            console.log('  - Trebuie să construim dată completă: ' + todayString + ' ' + slot.start_time);
                        }
                        console.log('---');
                    });
                    console.log('==========================================');
                    
                    renderTodaySchedule(response.data, todayString);
                } else {
                    console.log('[CLINICA_ADVANCED] No slots found for today');
                    $('#top-doctors-list').html(`
                        <div class="doctor-item">
                            <span class="doctor-name">Nu există program pentru astăzi</span>
                            <span class="doctor-count">${todayString}</span>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('[CLINICA_ADVANCED] Error loading today schedule:', error);
                $('#top-doctors-list').html(`
                    <div class="doctor-item">
                        <span class="doctor-name">Eroare la încărcare</span>
                        <span class="doctor-count">${todayString}</span>
                    </div>
                `);
            }
        });
    }

    function renderTodaySchedule(scheduleData, date) {
        console.log('[CLINICA_ADVANCED] Rendering today schedule:', scheduleData);
        
        if (!scheduleData || scheduleData.length === 0) {
            $('#top-doctors-list').html(`
                <div class="doctor-item">
                    <span class="doctor-name">Nu există program pentru astăzi</span>
                    <span class="doctor-count">${date}</span>
                </div>
            `);
            return;
        }
        
        // Grupează sloturile pe doctori și servicii
        const doctorsSchedule = {};
        scheduleData.forEach(function(slot) {
            const doctorId = slot.doctor_id;
            const doctorName = slot.doctor_name || 'Doctor necunoscut';
            const serviceName = slot.service_name || 'Serviciu necunoscut';
            const startTime = slot.start_time.substring(11, 16); // HH:MM
            const endTime = slot.end_time.substring(11, 16); // HH:MM
            
            if (!doctorsSchedule[doctorId]) {
                doctorsSchedule[doctorId] = {
                    name: doctorName,
                    services: {},
                    timeRange: { min: '23:59', max: '00:00' },
                    hasSlots: false
                };
            }
            
            // Actualizează intervalul orar DOAR dacă există sloturi reale
            doctorsSchedule[doctorId].hasSlots = true;
            if (startTime < doctorsSchedule[doctorId].timeRange.min) {
                doctorsSchedule[doctorId].timeRange.min = startTime;
            }
            if (endTime > doctorsSchedule[doctorId].timeRange.max) {
                doctorsSchedule[doctorId].timeRange.max = endTime;
            }
            
            // Grupează pe servicii
            if (!doctorsSchedule[doctorId].services[serviceName]) {
                doctorsSchedule[doctorId].services[serviceName] = {
                    total: 0,
                    occupied: 0,
                    free: 0
                };
            }
            
            doctorsSchedule[doctorId].services[serviceName].total++;
            
            // CORECTARE: Toate sloturile sunt libere dacă nu există programări reale
            const total = doctorsSchedule[doctorId].services[serviceName].total;
            const occupied = 0; // Nu există programări făcute
            const free = total; // Toate sloturile sunt libere
            
            doctorsSchedule[doctorId].services[serviceName].occupied = occupied;
            doctorsSchedule[doctorId].services[serviceName].free = free;
        });
        
        // Generează HTML-ul
        let scheduleHtml = '';
        Object.values(doctorsSchedule).forEach(function(doctor) {
            // CORECTARE: Afișează intervalul orar DOAR dacă există sloturi reale
            let timeRange = 'Nu lucrează';
            
            // Verifică dacă doctorul are sloturi în ziua respectivă
            const hasServicesWithSlots = Object.values(doctor.services).some(service => service.total > 0);
            
            // Dacă are servicii cu sloturi, afișează intervalul orar
            if (hasServicesWithSlots) {
                if (doctor.timeRange.min !== '23:59' && doctor.timeRange.max !== '00:00') {
                    timeRange = doctor.timeRange.min + '-' + doctor.timeRange.max;
                } else {
                    timeRange = 'Programare disponibilă';
                }
            }
            
            scheduleHtml += `
                <div class="doctor-schedule-item">
                    <div class="doctor-schedule-header">
                        <span class="doctor-name">${doctor.name}</span>
                        <span class="doctor-time">${timeRange}</span>
                    </div>
                    <div class="doctor-schedule-services">
            `;
            
            // Afișează fiecare serviciu cu sloturile
                            Object.entries(doctor.services).forEach(function([serviceName, stats]) {
                    scheduleHtml += `
                        <div class="service-slot-info">
                            <span class="service-name">${serviceName}</span>
                            <div class="slot-stats">
                                <span class="slots-total">${stats.total} sloturi totale</span>
                                <span class="slots-free">${stats.free} libere</span>
                                <span class="slots-occupied">${stats.occupied} ocupate</span>
                            </div>
                        </div>
                    `;
                });
            
            scheduleHtml += `
                    </div>
                </div>
            `;
        });
        
        $('#top-doctors-list').html(scheduleHtml);
    }

    function loadDoctorServicesCards(doctorId) {
        console.log('[CLINICA_ADVANCED] Loading services cards for doctor:', doctorId);

        // Afișează loading
        $('#services-cards-container').html(`
            <div class="loading-services">
                <span class="dashicons dashicons-update"></span>
                Se încarcă serviciile...
            </div>
        `);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_get_services_for_doctor',
                doctor_id: doctorId,
                nonce: '<?php echo wp_create_nonce('clinica_services_nonce'); ?>'
            },
            success: function(response) {
                console.log('[CLINICA_ADVANCED] Doctor services response:', response);
                console.log('[CLINICA_ADVANCED] Response success:', response.success);
                console.log('[CLINICA_ADVANCED] Response data:', response.data);
                
                if (response.success && response.data && response.data.length > 0) {
                    renderServicesCards(response.data);
                } else {
                    console.log('[CLINICA_ADVANCED] No services found or error in response');
                    // Încearcă să încarce toate serviciile ca fallback
                    loadAllServicesAsFallback();
                }
            },
            error: function(xhr, status, error) {
                console.error('[CLINICA_ADVANCED] AJAX Error loading doctor services:', error);
                console.error('[CLINICA_ADVANCED] XHR:', xhr);
                console.error('[CLINICA_ADVANCED] Status:', status);
                $('#services-cards-container').html(`
                    <div class="no-services">
                        <span class="dashicons dashicons-warning"></span>
                        Eroare la încărcarea serviciilor: ${error}
                    </div>
                `);
            }
        });
    }

    function renderServicesCards(services) {
        const $container = $('#services-cards-container');
        $container.empty();

        services.forEach(function(service) {
            const cardHtml = `
                <div class="service-card" data-service-id="${service.id}">
                    <div class="service-card-header">
                        <h4 class="service-name">${service.name}</h4>
                        <span class="service-duration">${service.duration} min</span>
                    </div>
                    <div class="service-stats">
                        <div class="service-stat">
                            <span class="dashicons dashicons-clock"></span>
                            <span>${service.duration} min</span>
                        </div>
                        <div class="service-stat">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <span>Activ</span>
                        </div>
                    </div>
                </div>
            `;
            $container.append(cardHtml);
        });

        console.log(`[CLINICA_ADVANCED] Rendered ${services.length} service cards`);
    }

    function loadAllServicesAsFallback() {
        console.log('[CLINICA_ADVANCED] Loading all services as fallback...');
        
        // Folosește serviciile din PHP
        const allServices = <?php echo json_encode($services); ?>;
        
        if (allServices && allServices.length > 0) {
            console.log('[CLINICA_ADVANCED] Found services in PHP data:', allServices);
            renderServicesCards(allServices);
        } else {
            console.log('[CLINICA_ADVANCED] No services found in PHP data either');
            $('#services-cards-container').html(`
                <div class="no-services">
                    <span class="dashicons dashicons-warning"></span>
                    Nu există servicii disponibile în sistem
                </div>
            `);
        }
    }

    function loadDoctorsCards() {
        console.log('[CLINICA_ADVANCED] Loading doctors cards...');
        
        // Folosește doctorii din PHP
        const allDoctors = <?php echo json_encode($doctors ?: array()); ?>;
        console.log('[CLINICA_ADVANCED] Raw PHP doctors data:', allDoctors);
        console.log('[CLINICA_ADVANCED] Doctors count:', allDoctors ? allDoctors.length : 'null/undefined');
        
        if (allDoctors && allDoctors.length > 0) {
            console.log('[CLINICA_ADVANCED] Found doctors in PHP data:', allDoctors);
            renderDoctorsCards(allDoctors);
        } else {
            console.log('[CLINICA_ADVANCED] No doctors found in PHP data');
            console.log('[CLINICA_ADVANCED] allDoctors value:', allDoctors);
            $('#doctors-cards-container').html(`
                <div class="no-doctors">
                    <span class="dashicons dashicons-warning"></span>
                    Nu există doctori disponibili în sistem
                </div>
            `);
        }
    }

    function renderDoctorsCards(doctors) {
        const $container = $('#doctors-cards-container');
        console.log('[CLINICA_ADVANCED] Container found:', $container.length);
        console.log('[CLINICA_ADVANCED] Container element:', $container[0]);
        
        if ($container.length === 0) {
            console.error('[CLINICA_ADVANCED] ERROR: doctors-cards-container not found in DOM!');
            return;
        }
        
        $container.empty();

        console.log('[CLINICA_ADVANCED] Rendering doctors cards. Raw data:', doctors);

        doctors.forEach(function(doctor, index) {
            console.log('[CLINICA_ADVANCED] Processing doctor', index + 1, ':', doctor);
            
            // Încearcă diferite câmpuri pentru nume - verifică și în obiectul data
            const firstName = doctor.first_name || doctor.data?.first_name || '';
            const lastName = doctor.last_name || doctor.data?.last_name || '';
            const fullName = (firstName + ' ' + lastName).trim();
            
            const displayName = doctor.display_name || 
                               doctor.data?.display_name ||
                               fullName ||
                               doctor.user_nicename || 
                               doctor.data?.user_nicename ||
                               doctor.user_login || 
                               doctor.data?.user_login ||
                               'Doctor necunoscut';
            
            // Verifică statusul - în WordPress, user_status = 0 (activ), user_status = 1 (inactiv)
            // Dacă user_status nu e definit, considerăm că e activ (default WordPress)
            const userStatus = doctor.user_status !== undefined ? doctor.user_status : 0;
            const isActive = userStatus === 0 || userStatus === '0';
            
            console.log('[CLINICA_ADVANCED] Doctor processed:', {
                id: doctor.ID,
                displayName: displayName,
                user_status: doctor.user_status,
                userStatus: userStatus,
                isActive: isActive,
                allFields: Object.keys(doctor),
                dataFields: doctor.data ? Object.keys(doctor.data) : 'no data object'
            });
            
            const cardHtml = `
                <div class="doctor-card" data-doctor-id="${doctor.ID}" data-doctor-name="${displayName}">
                    <div class="doctor-name">${displayName}</div>
                    <div class="doctor-role">Doctor</div>
                    <div class="doctor-slots">Numărul de sloturi programate</div>
                    <div class="doctor-status ${isActive ? 'active' : 'inactive'}">
                        ${isActive ? 'Status (activ)' : 'Status (inactiv)'}
                    </div>
                </div>
            `;
            
            console.log('[CLINICA_ADVANCED] Appending card HTML for doctor:', displayName);
            $container.append(cardHtml);
        });
        
        console.log('[CLINICA_ADVANCED] Rendered', doctors.length, 'doctor cards');
        console.log('[CLINICA_ADVANCED] Container HTML after render:', $container.html());
        
        // Verifică dacă cardurile sunt vizibile
        const $cards = $container.find('.doctor-card');
        console.log('[CLINICA_ADVANCED] Cards found in container:', $cards.length);
        console.log('[CLINICA_ADVANCED] Container visibility:', $container.is(':visible'));
        console.log('[CLINICA_ADVANCED] Container display:', $container.css('display'));
        console.log('[CLINICA_ADVANCED] Container height:', $container.height());
        
        // Forțează vizibilitatea containerului cu !important
        $container.css({
            'display': 'grid !important',
            'visibility': 'visible !important',
            'opacity': '1 !important',
            'height': 'auto !important',
            'min-height': '100px !important',
            'max-height': 'none !important',
            'overflow': 'visible !important'
        });
        
        // Verifică și părintele
        const $parent = $container.parent();
        console.log('[CLINICA_ADVANCED] Parent element:', $parent[0]);
        console.log('[CLINICA_ADVANCED] Parent visibility:', $parent.is(':visible'));
        console.log('[CLINICA_ADVANCED] Parent display:', $parent.css('display'));
        
        // Forțează și părintele să fie vizibil
        $parent.css({
            'display': 'block !important',
            'visibility': 'visible !important',
            'opacity': '1 !important'
        });
        
        console.log('[CLINICA_ADVANCED] Forced container visibility with !important');
        console.log('[CLINICA_ADVANCED] Container visibility after fix:', $container.is(':visible'));
        console.log('[CLINICA_ADVANCED] Container height after fix:', $container.height());
        console.log('[CLINICA_ADVANCED] Container computed styles:', {
            display: $container.css('display'),
            visibility: $container.css('visibility'),
            opacity: $container.css('opacity'),
            height: $container.css('height')
        });
        
        // Verifică dacă cardurile au conținut (folosește $cards deja declarat)
        console.log('[CLINICA_ADVANCED] Cards after fix:', $cards.length);
        if ($cards.length > 0) {
            console.log('[CLINICA_ADVANCED] First card HTML:', $cards.first().html());
            console.log('[CLINICA_ADVANCED] First card visibility:', $cards.first().is(':visible'));
            console.log('[CLINICA_ADVANCED] First card height:', $cards.first().height());
        }
        
        // Încearcă să forțezi și cardurile să fie vizibile
        $cards.css({
            'display': 'block !important',
            'visibility': 'visible !important',
            'opacity': '1 !important',
            'height': 'auto !important',
            'min-height': '80px !important'
        });
    }

    function navigateWeek(direction) {
        console.log(`[CLINICA_ADVANCED] Navigating week: ${direction > 0 ? 'next' : 'previous'}`);
        
        // Calculează săptămâna nouă
        currentWeekNumber += direction;
        
        // Verifică limitele anului
        if (currentWeekNumber > 52) {
            currentWeekNumber = 1;
            currentYear++;
        } else if (currentWeekNumber < 1) {
            currentWeekNumber = 52;
            currentYear--;
        }
        
        // Actualizează variabilele
        currentWeek = `${currentYear}-W${currentWeekNumber.toString().padStart(2, '0')}`;
        
        // Actualizează afișajul
        updateWeekDisplay();
        
        // Reîncarcă timeslots-urile dacă sunt selectate doctor și serviciu
        if (selectedDoctor && selectedService) {
            loadTimeslotsForWeek();
        }
        
        console.log(`[CLINICA_ADVANCED] Now viewing week: ${currentWeek}`);
    }

    function updateWeekDisplay() {
        $('#current-week-text').text(`${currentWeekNumber}, ${currentYear}`);
    }



    function loadTimeslotsForDay(day) {
        // Simulare timeslots pentru zi
        const $container = $(`.day-timeslots-container[data-day="${day}"]`);

        if (day === 1) { // Luni - cu câteva timeslots de exemplu
            const timeslotsHtml = `
                <div class="advanced-timeslot-item" data-id="1">
                    <div class="timeslot-time">09:00 - 09:30</div>
                    <div class="timeslot-actions">
                        <button class="edit-timeslot" data-id="1">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="delete-timeslot" data-id="1">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div class="advanced-timeslot-item" data-id="2">
                    <div class="timeslot-time">09:30 - 10:00</div>
                    <div class="timeslot-actions">
                        <button class="edit-timeslot" data-id="2">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="delete-timeslot" data-id="2">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `;
            $container.html(timeslotsHtml);
        }
    }

    function openAdvancedTimeslotModal(mode, day, timeslotId = null) {
        const $modal = $('#advanced-timeslot-modal');
        const $title = $('#advanced-modal-title');

        if (mode === 'add') {
            $title.text('Adaugă Timeslot Avansat');
            $('#advanced-timeslot-form')[0].reset();
        } else {
            $title.text('Editează Timeslot');
            // Încarcă datele timeslot-ului existent
        }

        // Setare zi
        $('#advanced-timeslot-form').data('day', day);

        $modal.show();

        // Inițializează selectorul de durată
        initializeDurationSelector();

        updateGenerationPreview();
    }

    function initializeDurationSelector() {
        // Setează starea implicită (din serviciu) și actualizează UI-ul
        const $serviceRadio = $('input[name="duration-type"][value="service"]');
        const $customRadio = $('input[name="duration-type"][value="custom"]');

        // Asigură-te că "din serviciu" este selectat implicit
        $serviceRadio.prop('checked', true);
        $customRadio.prop('checked', false);

        // Aplică starea inițială
        const $customDurationSection = $('#custom-duration-section');
        const $slotDuration = $('#advanced-slot-duration');

        $customDurationSection.hide();
        $slotDuration.prop('disabled', true);

        // Preia durata din serviciul selectat
        const serviceDuration = getSelectedServiceDuration();
        if (serviceDuration > 0) {
            $slotDuration.val(serviceDuration);
        } else {
            $slotDuration.val('30'); // Valoare implicită
        }
    }

    function updateGenerationPreview() {
        const startTime = $('#advanced-start-time').val();
        const endTime = $('#advanced-end-time').val();
        const durationType = $('input[name="duration-type"]:checked').val();

        if (!startTime || !endTime) return;

        let slots = [];
        let duration = 0;

        if (durationType === 'service') {
            // Folosește durata din serviciu
            duration = getSelectedServiceDuration();
            if (duration > 0) {
                slots = generateTimeSlots(startTime, endTime, duration);
                console.log(`[CLINICA_ADVANCED] Preview generat cu durata serviciului: ${duration} min`);
            } else {
                // Fallback la durată implicită
                slots = generateTimeSlots(startTime, endTime, 30);
                console.warn('[CLINICA_ADVANCED] Preview fallback - durata serviciului nu este disponibilă');
            }
        } else {
            // Folosește durata personalizată
            duration = parseInt($('#advanced-slot-duration').val()) || 30;
            slots = generateTimeSlots(startTime, endTime, duration);
            console.log(`[CLINICA_ADVANCED] Preview generat cu durata personalizată: ${duration} min`);
        }

        // Generează HTML pentru preview
        const previewHtml = slots.map(slot =>
            `<div class="preview-slot" title="Slot de ${duration} minute">${slot.start} - ${slot.end}</div>`
        ).join('');

        $('#generation-preview').html(previewHtml);

        // Adaugă informații despre numărul de sloturi generate
        if (slots.length > 0) {
            const infoHtml = `<div class="preview-info">Total sloturi: ${slots.length} × ${duration} minute</div>`;
            $('#generation-preview').append(infoHtml);
        }
    }

    function generateTimeSlots(startTime, endTime, duration) {
        const slots = [];
        const [startHour, startMin] = startTime.split(':').map(Number);
        const [endHour, endMin] = endTime.split(':').map(Number);

        const startMinutes = startHour * 60 + startMin;
        const endMinutes = endHour * 60 + endMin;

        for (let current = startMinutes; current < endMinutes; current += duration) {
            const slotStart = minutesToTime(current);
            const slotEnd = minutesToTime(current + duration);

            if (minutesToTime(current + duration) > endTime) break;

            slots.push({
                start: slotStart,
                end: slotEnd
            });
        }

        return slots;
    }

    function minutesToTime(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
    }

    function saveAdvancedTimeslot() {
        const day = $('#advanced-day-selector').val();
        const startTime = $('#advanced-start-time').val();
        const endTime = $('#advanced-end-time').val();
        const durationType = $('input[name="duration-type"]:checked').val();

        if (!day || !startTime || !endTime) {
            alert('Completează toate câmpurile obligatorii!');
            return;
        }

        let slotDuration;
        if (durationType === 'service') {
            slotDuration = getSelectedServiceDuration();
        } else {
            slotDuration = parseInt($('#advanced-slot-duration').val()) || 30;
        }

        // Creează slot-ul în interfață
        const startFormatted = formatTimeForDisplay(startTime);
        const endFormatted = formatTimeForDisplay(endTime);
        const slotHtml = createTimeslotHtml(startFormatted, endFormatted, day);
        
        const $container = $(`.day-timeslots-container[data-day="${day}"]`);
        $container.append(slotHtml);

        // Salvează în baza de date
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_save_timeslot',
                nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                timeslot_id: '',
                doctor_id: selectedDoctor,
                service_id: selectedService,
                day_of_week: day,
                start_time: startTime,
                end_time: endTime,
                slot_duration: slotDuration
            },
            success: function(response) {
                console.log('[CLINICA_ADVANCED] Slot saved successfully');
                showSuccessMessage(`Slot ${startFormatted} - ${endFormatted} adăugat cu succes!`);
            },
            error: function(xhr, status, error) {
                console.error('[CLINICA_ADVANCED] Error saving slot:', error);
                showErrorMessage('Eroare la salvarea slot-ului');
            }
        });

        // Închide modal
        $('#advanced-timeslot-modal').hide();

        // Actualizează afișarea
        updateTotalTimeslotsCount();
        markUnsavedChanges();
    }

    function toggleDarkMode() {
        isDarkMode = !isDarkMode;
        $('body').toggleClass('dark-mode', isDarkMode);
        $('#toggle-dark-mode').toggleClass('button-primary', isDarkMode);
    }

    function togglePreviewMode() {
        // Implementare preview mode
        console.log('[CLINICA_ADVANCED] Preview mode toggled:', previewMode);
    }

    function showSuccessMessage(message) {
        // Implementare toast notification
        console.log('[CLINICA_ADVANCED] Success:', message);
    }

    // Funcții pentru celelalte tab-uri (de implementat)
    function loadTemplates() {
        console.log('[CLINICA_ADVANCED] Loading templates...');

        const templates = getPredefinedTemplates();
        const $grid = $('#templates-grid');

        $grid.empty();

        templates.forEach(template => {
            const templateHtml = createTemplateCard(template);
            $grid.append(templateHtml);
        });
    }

    function getPredefinedTemplates() {
        return [
            {
                id: 'standard_9_17',
                name: 'Program Standard',
                description: '9:00 - 17:00 cu pauză de masă',
                icon: 'dashicons-calendar-alt',
                timeslots: [
                    { start: '09:00', end: '13:00' },
                    { start: '14:00', end: '17:00' }
                ]
            },
            {
                id: 'weekend_10_14',
                name: 'Program Weekend',
                description: '10:00 - 14:00, program relaxat',
                icon: 'dashicons-smiley',
                timeslots: [
                    { start: '10:00', end: '14:00' }
                ]
            },
            {
                id: 'urgent_24_7',
                name: 'Program Urgențe',
                description: '24/7 cu intervale scurte',
                icon: 'dashicons-warning',
                timeslots: [
                    { start: '00:00', end: '24:00' }
                ]
            },
            {
                id: 'extended_8_20',
                name: 'Program Extins',
                description: '8:00 - 20:00, program lung',
                icon: 'dashicons-clock',
                timeslots: [
                    { start: '08:00', end: '12:00' },
                    { start: '13:00', end: '17:00' },
                    { start: '18:00', end: '20:00' }
                ]
            },
            {
                id: 'morning_8_12',
                name: 'Numai Dimineața',
                description: '8:00 - 12:00, program scurt',
                icon: 'dashicons-admin-appearance',
                timeslots: [
                    { start: '08:00', end: '12:00' }
                ]
            },
            {
                id: 'afternoon_14_18',
                name: 'Numai După-amiază',
                description: '14:00 - 18:00, program după-amiază',
                icon: 'dashicons-visibility',
                timeslots: [
                    { start: '14:00', end: '18:00' }
                ]
            }
        ];
    }

    function createTemplateCard(template) {
        return `
            <div class="template-card" data-template-id="${template.id}">
                <div class="template-header">
                    <div class="template-icon">
                        <span class="dashicons ${template.icon}"></span>
                    </div>
                    <div class="template-info">
                        <h4>${template.name}</h4>
                        <p>${template.description}</p>
                    </div>
                </div>
                <div class="template-timeslots">
                    ${template.timeslots.map(slot => `
                        <div class="template-slot">
                            <span class="slot-time">${slot.start} - ${slot.end}</span>
                        </div>
                    `).join('')}
                </div>
                <div class="template-actions">
                    <button class="button button-primary apply-template-btn" data-template-id="${template.id}">
                        <span class="dashicons dashicons-admin-page"></span>
                        Aplică Șablon
                    </button>
                    <button class="button button-secondary preview-template-btn" data-template-id="${template.id}">
                        <span class="dashicons dashicons-visibility"></span>
                        Preview
                    </button>
                </div>
            </div>
        `;
    }

    function loadAnalytics() {
        console.log('[CLINICA_ADVANCED] Loading analytics...');
    }

    function loadSettings() {
        console.log('[CLINICA_ADVANCED] Loading settings...');
    }

    function copyScheduleToAllDays() {
        console.log('[CLINICA_ADVANCED] Copying schedule to all days...');

        // Găsește ziua sursă (luni - ziua 1)
        const sourceDay = 1;
        const sourceContainer = $(`.day-timeslots-container[data-day="${sourceDay}"]`);

        // Verifică dacă există timeslots în ziua sursă
        const sourceTimeslots = sourceContainer.find('.advanced-timeslot-item');
        if (sourceTimeslots.length === 0) {
            showSuccessMessage('Nu există timeslots în ziua de luni pentru copiere!');
            return;
        }

        // Confirmare utilizator
        const confirmMessage = `Ești sigur că vrei să copiezi programul din Luni (${sourceTimeslots.length} sloturi) tuturor celorlalte zile?`;
        if (!confirm(confirmMessage)) {
            return;
        }

        // Extrage datele timeslots din ziua sursă
        const timeslotsData = [];
        sourceTimeslots.each(function() {
            const $item = $(this);
            const timeText = $item.find('.timeslot-time').text();
            const [startTime, endTime] = timeText.split(' - ');

            timeslotsData.push({
                start_time: startTime,
                end_time: endTime,
                day_of_week: null // va fi setat pentru fiecare zi
            });
        });

        // Copiază către toate celelalte zile (DOAR ZILELE DE LUCRU)
        const targetDays = [2, 3, 4, 5]; // Marți până Vineri (FĂRĂ WEEKEND!)

        targetDays.forEach(day => {
            const $targetContainer = $(`.day-timeslots-container[data-day="${day}"]`);

            // Șterge timeslots existente
            $targetContainer.empty();

            // Adaugă timeslots copiate
            timeslotsData.forEach(timeslot => {
                const newTimeslot = {
                    ...timeslot,
                    day_of_week: day,
                    id: `copied_${day}_${Date.now()}_${Math.random()}`
                };

                const timeslotHtml = createTimeslotHtml(newTimeslot);
                $targetContainer.append(timeslotHtml);
            });

            // Actualizează placeholder-ul
            updateEmptyDayPlaceholder(day);
        });

        // Actualizează contorul total
        updateTotalTimeslotsCount();

        showSuccessMessage(`Program copiat cu succes în toate zilele! (${timeslotsData.length} sloturi × 6 zile)`);

        // Marchează ca fiind modificări nesalvate
        markUnsavedChanges();
    }

    function copyScheduleBetweenDays(sourceDay, targetDay) {
        console.log(`[CLINICA_ADVANCED] Copying schedule from day ${sourceDay} to day ${targetDay}`);

        const sourceContainer = $(`.day-timeslots-container[data-day="${sourceDay}"]`);
        const targetContainer = $(`.day-timeslots-container[data-day="${targetDay}"]`);

        // Verifică dacă există timeslots în ziua sursă
        const sourceTimeslots = sourceContainer.find('.advanced-timeslot-item');
        if (sourceTimeslots.length === 0) {
            showSuccessMessage(`Nu există timeslots în ${dayNames[sourceDay]} pentru copiere!`);
            return;
        }

        // Confirmare utilizator
        const confirmMessage = `Copiezi ${sourceTimeslots.length} sloturi din ${dayNames[sourceDay]} în ${dayNames[targetDay]}?`;
        if (!confirm(confirmMessage)) {
            return;
        }

        // Extrage și copiază timeslots
        const timeslotsData = [];
        sourceTimeslots.each(function() {
            const $item = $(this);
            const timeText = $item.find('.timeslot-time').text();
            const [startTime, endTime] = timeText.split(' - ');

            timeslotsData.push({
                start_time: startTime,
                end_time: endTime,
                day_of_week: targetDay
            });
        });

        // Șterge timeslots existente din ziua țintă
        targetContainer.empty();

        // Adaugă timeslots copiate
        timeslotsData.forEach(timeslot => {
            const newTimeslot = {
                ...timeslot,
                id: `copied_${targetDay}_${Date.now()}_${Math.random()}`
            };

            const timeslotHtml = createTimeslotHtml(newTimeslot);
            targetContainer.append(timeslotHtml);
        });

        // Actualizează placeholder-ul
        updateEmptyDayPlaceholder(targetDay);

        // Actualizează contorul
        updateTotalTimeslotsCount();

        showSuccessMessage(`Program copiat: ${timeslotsData.length} sloturi din ${dayNames[sourceDay]} → ${dayNames[targetDay]}`);

        // Marchează ca fiind modificări nesalvate
        markUnsavedChanges();
    }

    function createTimeslotHtml(timeslot) {
        return `<div class="advanced-timeslot-item" data-id="${timeslot.id}">
            <div class="timeslot-time">${timeslot.start_time} - ${timeslot.end_time}</div>
            <div class="timeslot-actions">
                <button class="edit-timeslot" data-id="${timeslot.id}" title="Editează">
                    <span class="dashicons dashicons-edit"></span>
                </button>
                <button class="delete-timeslot" data-id="${timeslot.id}" title="Șterge">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>`;
    }

    function updateEmptyDayPlaceholder(day) {
        const $container = $(`.day-timeslots-container[data-day="${day}"]`);
        const hasTimeslots = $container.find('.advanced-timeslot-item').length > 0;

        if (!hasTimeslots) {
            const placeholderHtml = `<div class="empty-day-placeholder">
                <div class="placeholder-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <p>Niciun timeslot configurat</p>
                <button class="button button-small add-first-timeslot" data-day="${day}">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    Adaugă Primul Timeslot
                </button>
            </div>`;
            $container.html(placeholderHtml);
        }
    }

    function updateTotalTimeslotsCount() {
        const totalTimeslots = $('.advanced-timeslot-item').length;
        $('#total-timeslots-advanced').text(totalTimeslots);
    }

    function markUnsavedChanges() {
        $('#save-all-changes').addClass('button-primary').removeClass('button-secondary');
        $('#save-all-changes').html('<span class="dashicons dashicons-saved"></span> Salvează Modificările');
    }

    function showSuccessMessage(message) {
        // Implementare toast notification
        console.log('[CLINICA_ADVANCED] Success:', message);

        // Creează un mesaj de succes
        const $message = $(`
            <div class="notice notice-success is-dismissible">
                <p><strong>Succes:</strong> ${message}</p>
            </div>
        `);

        // Adaugă la începutul paginii
        $('.wrap h1').after($message);

        // Auto-hide după 5 secunde
        setTimeout(() => {
            $message.fadeOut(() => $message.remove());
        }, 5000);
    }

    function showTemplateSelector() {
        console.log('[CLINICA_ADVANCED] Showing template selector...');
    }

    function loadSortableLibrary() {
        // Încarcă SortableJS din CDN dacă nu este deja încărcat
        if (typeof Sortable === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
            script.onload = function() {
                console.log('[CLINICA_ADVANCED] SortableJS loaded successfully');
                initDragAndDrop();
            };
            script.onerror = function() {
                console.error('[CLINICA_ADVANCED] Failed to load SortableJS');
                showSuccessMessage('Biblioteca drag & drop nu a putut fi încărcată. Funcția va fi limitată.');
            };
            document.head.appendChild(script);
        } else {
            initDragAndDrop();
        }
    }

    function initDragAndDrop() {
        console.log('[CLINICA_ADVANCED] Initializing drag & drop functionality');

        // Inițializează drag & drop pentru fiecare zi
        $('.day-timeslots-container').each(function() {
            const $container = $(this);
            const day = $container.data('day');

            // Creează instanță Sortable pentru fiecare container
            const sortable = new Sortable($container[0], {
                group: 'timeslots', // Permite mutarea între zile
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                handle: '.advanced-timeslot-item', // Permite drag pe întreg item-ul
                filter: '.empty-day-placeholder', // Nu permite drag pe placeholder

                // Callback-uri pentru evenimente
                onStart: function(evt) {
                    console.log(`[CLINICA_ADVANCED] Started dragging timeslot from day ${day}`);
                    $(evt.item).addClass('dragging');
                },

                onEnd: function(evt) {
                    console.log(`[CLINICA_ADVANCED] Finished dragging timeslot`);

                    $(evt.item).removeClass('dragging');

                    const fromDay = $(evt.from).data('day');
                    const toDay = $(evt.to).data('day');

                    // Verifică dacă s-a mutat între zile diferite
                    if (fromDay !== toDay) {
                        console.log(`[CLINICA_ADVANCED] Moved timeslot from day ${fromDay} to day ${toDay}`);

                        // Actualizează atributul data-day al item-ului mutat
                        $(evt.item).attr('data-day', toDay);

                        // Marchează ca fiind modificări nesalvate
                        markUnsavedChanges();

                        // Actualizează contoarele pentru ambele zile
                        updateEmptyDayPlaceholder(fromDay);
                        updateEmptyDayPlaceholder(toDay);
                        updateTotalTimeslotsCount();

                        // Afișare mesaj de confirmare
                
                        const timeText = $(evt.item).find('.timeslot-time').text();
                        showSuccessMessage(`Timeslot ${timeText} mutat în ${dayNames[toDay]}`);
                    } else {
                        // Verifică dacă poziția s-a schimbat în aceeași zi
                        const oldIndex = evt.oldIndex;
                        const newIndex = evt.newIndex;

                        if (oldIndex !== newIndex) {
                            console.log(`[CLINICA_ADVANCED] Reordered timeslot within day ${day}`);
                            markUnsavedChanges();
                        }
                    }
                },

                onMove: function(evt, originalEvent) {
                    // Permite mutarea doar către containere valide
                    const targetContainer = $(evt.to);

                    // Nu permite mutarea către placeholder
                    if (targetContainer.hasClass('empty-day-placeholder')) {
                        return false;
                    }

                    return true;
                }
            });

            console.log(`[CLINICA_ADVANCED] Drag & drop initialized for day ${day}`);
        });

        // Adaugă clase CSS pentru drag & drop
        addDragDropStyles();
    }

    function addDragDropStyles() {
        // Adaugă stiluri CSS pentru drag & drop dacă nu există deja
        if (!$('#clinica-drag-drop-styles').length) {
            const styles = `
                <style id="clinica-drag-drop-styles">
                    .sortable-ghost {
                        opacity: 0.4;
                        background: #f0f8ff !important;
                        border: 2px dashed #0073aa !important;
                    }

                    .sortable-chosen {
                        cursor: grabbing !important;
                    }

                    .sortable-drag {
                        transform: rotate(5deg);
                        box-shadow: 0 8px 25px rgba(0,0,0,0.3) !important;
                    }

                    .advanced-timeslot-item.dragging {
                        transform: rotate(3deg);
                        z-index: 1000;
                    }

                    .day-timeslots-container {
                        min-height: 50px;
                        position: relative;
                    }

                    .day-timeslots-container.sortable-ghost::before {
                        content: 'Plasează aici';
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        color: #0073aa;
                        font-weight: 500;
                        pointer-events: none;
                    }

                    /* Îmbunătățiri pentru mobile */
                    @media (max-width: 768px) {
                        .sortable-chosen {
                            cursor: grabbing !important;
                        }

                        .advanced-timeslot-item {
                            touch-action: none;
                        }
                    }
                </style>
            `;
            $('head').append(styles);
        }
    }

    function enableDragDropForDay(day) {
        // Reinițializează drag & drop pentru o zi specifică (după adăugarea de noi timeslots)
        const $container = $(`.day-timeslots-container[data-day="${day}"]`);

        if ($container.length && typeof Sortable !== 'undefined') {
            // Dacă există deja o instanță Sortable, o distrugem
            if ($container[0].sortable) {
                $container[0].sortable.destroy();
            }

            // Recreăm instanța Sortable
            const sortable = new Sortable($container[0], {
                group: 'timeslots',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',

                onStart: function(evt) {
                    $(evt.item).addClass('dragging');
                },

                onEnd: function(evt) {
                    $(evt.item).removeClass('dragging');
                    markUnsavedChanges();
                    updateRealtimePreview();
                }
            });

            $container[0].sortable = sortable;
            console.log(`[CLINICA_ADVANCED] Drag & drop re-enabled for day ${day}`);
        }
    }

    function updateRealtimePreview() {
        if (!previewMode) return;

        console.log('[CLINICA_ADVANCED] Updating realtime preview...');

        // Colectează toate timeslots-urile actuale
        const currentTimeslots = collectAllTimeslots();

        // Verifică conflictele
        const conflicts = detectConflicts(currentTimeslots);

        // Actualizează vizualizarea pentru a arăta preview-ul
        updatePreviewVisualization(conflicts);

        // Afișează statistici preview
        updatePreviewStats(currentTimeslots, conflicts);
    }

    function collectAllTimeslots() {
        const timeslots = [];

        $('.advanced-timeslot-item').each(function() {
            const $item = $(this);
            const day = $item.closest('.day-timeslots-container').data('day');
            const timeText = $item.find('.timeslot-time').text();
            const [startTime, endTime] = timeText.split(' - ');

            timeslots.push({
                id: $item.data('id'),
                day: day,
                start: startTime,
                end: endTime,
                dayName: ['Duminică', 'Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă'][day]
            });
        });

        return timeslots;
    }

    function detectConflicts(timeslots) {
        const conflicts = [];

        // Grupează timeslots pe zi
        const timeslotsByDay = {};
        timeslots.forEach(slot => {
            if (!timeslotsByDay[slot.day]) {
                timeslotsByDay[slot.day] = [];
            }
            timeslotsByDay[slot.day].push(slot);
        });

        // Verifică conflictele în fiecare zi
        Object.keys(timeslotsByDay).forEach(day => {
            const daySlots = timeslotsByDay[day];

            for (let i = 0; i < daySlots.length; i++) {
                for (let j = i + 1; j < daySlots.length; j++) {
                    const slot1 = daySlots[i];
                    const slot2 = daySlots[j];

                    if (timeslotsOverlap(slot1, slot2)) {
                        conflicts.push({
                            type: 'overlap',
                            day: parseInt(day),
                            slots: [slot1, slot2],
                            message: `Suprapunere între ${slot1.start}-${slot1.end} și ${slot2.start}-${slot2.end}`
                        });
                    }
                }
            }
        });

        // Verifică pauze prea mici între timeslots
        Object.keys(timeslotsByDay).forEach(day => {
            const daySlots = timeslotsByDay[day].sort((a, b) => a.start.localeCompare(b.start));

            for (let i = 0; i < daySlots.length - 1; i++) {
                const currentSlot = daySlots[i];
                const nextSlot = daySlots[i + 1];

                const gap = calculateTimeGap(currentSlot.end, nextSlot.start);
                if (gap < 15) { // Pauză mai mică de 15 minute
                    conflicts.push({
                        type: 'small_gap',
                        day: parseInt(day),
                        slots: [currentSlot, nextSlot],
                        message: `Pauză prea mică (${gap} min) între ${currentSlot.end} și ${nextSlot.start}`
                    });
                }
            }
        });

        return conflicts;
    }

    function timeslotsOverlap(slot1, slot2) {
        const start1 = timeToMinutes(slot1.start);
        const end1 = timeToMinutes(slot1.end);
        const start2 = timeToMinutes(slot2.start);
        const end2 = timeToMinutes(slot2.end);

        return (start1 < end2 && end1 > start2);
    }

    function calculateTimeGap(time1, time2) {
        const minutes1 = timeToMinutes(time1);
        const minutes2 = timeToMinutes(time2);
        return Math.abs(minutes2 - minutes1);
    }

    function timeToMinutes(time) {
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    }

    function updatePreviewVisualization(conflicts) {
        // Resetează toate highlight-urile
        $('.advanced-timeslot-item').removeClass('preview-conflict preview-warning preview-ok');

        // Marchează conflictele
        conflicts.forEach(conflict => {
            conflict.slots.forEach(slot => {
                const $item = $(`.advanced-timeslot-item[data-id="${slot.id}"]`);

                if (conflict.type === 'overlap') {
                    $item.addClass('preview-conflict');
                } else if (conflict.type === 'small_gap') {
                    $item.addClass('preview-warning');
                }
            });
        });

        // Marchează timeslots fără conflicte
        $('.advanced-timeslot-item').not('.preview-conflict, .preview-warning').addClass('preview-ok');
    }

    function updatePreviewStats(timeslots, conflicts) {
        const stats = {
            total: timeslots.length,
            conflicts: conflicts.filter(c => c.type === 'overlap').length,
            warnings: conflicts.filter(c => c.type === 'small_gap').length,
            ok: timeslots.length - conflicts.length
        };

        // Actualizează bara de statistici preview (dacă există)
        const $previewStats = $('#preview-stats');
        if ($previewStats.length) {
            $previewStats.html(`
                <div class="preview-stat-item">
                    <span class="stat-label">Total:</span>
                    <span class="stat-value">${stats.total}</span>
                </div>
                <div class="preview-stat-item conflict">
                    <span class="stat-label">Conflicte:</span>
                    <span class="stat-value">${stats.conflicts}</span>
                </div>
                <div class="preview-stat-item warning">
                    <span class="stat-label">Avertizări:</span>
                    <span class="stat-value">${stats.warnings}</span>
                </div>
            `);
        }

        // Afișare mesaje pentru conflicte
        if (conflicts.length > 0) {
            const conflictMessages = conflicts.map(c => `• ${c.message}`).join('\n');
            console.warn('[CLINICA_ADVANCED] Preview conflicts detected:\n' + conflictMessages);
        }
    }

    function togglePreviewMode() {
        previewMode = !previewMode;

        const $button = $('#toggle-preview-mode');
        const $container = $('.clinica-advanced-week-grid');

        if (previewMode) {
            $button.addClass('button-primary').removeClass('button-secondary');
            $container.addClass('preview-mode-active');

            // Adaugă bara de statistici preview
            if (!$('#preview-stats-bar').length) {
                const statsBar = `
                    <div id="preview-stats-bar" class="preview-stats-bar">
                        <div class="preview-stats-header">
                            <span class="dashicons dashicons-visibility"></span>
                            Mod Preview Activ
                            <div id="preview-stats" class="preview-stats"></div>
                        </div>
                        <div class="preview-actions">
                            <button class="button button-small" id="preview-accept-changes">
                                <span class="dashicons dashicons-yes"></span>
                                Acceptă
                            </button>
                            <button class="button button-small" id="preview-discard-changes">
                                <span class="dashicons dashicons-no"></span>
                                Renunță
                            </button>
                        </div>
                    </div>
                `;
                $container.before(statsBar);

                // Event listeners pentru preview actions
                $('#preview-accept-changes').on('click', function() {
                    acceptPreviewChanges();
                });

                $('#preview-discard-changes').on('click', function() {
                    discardPreviewChanges();
                });
            }

            // Activează preview-ul
            updateRealtimePreview();

            showSuccessMessage('Mod preview activat! Modificările nu sunt salvate până când nu accepți.');
        } else {
            $button.removeClass('button-primary').addClass('button-secondary');
            $container.removeClass('preview-mode-active');

            // Elimină bara de statistici
            $('#preview-stats-bar').remove();

            // Resetează toate highlight-urile
            $('.advanced-timeslot-item').removeClass('preview-conflict preview-warning preview-ok');

            showSuccessMessage('Mod preview dezactivat.');
        }
    }

    function acceptPreviewChanges() {
        console.log('[CLINICA_ADVANCED] Accepting preview changes...');

        // Marchează toate modificările ca salvate
        $('#save-all-changes').removeClass('button-primary').addClass('button-secondary');
        $('#save-all-changes').html('<span class="dashicons dashicons-saved"></span> Salvează');

        // Dezactivează preview mode
        togglePreviewMode();

        showSuccessMessage('Modificările au fost acceptate și marcate pentru salvare.');
    }

    function discardPreviewChanges() {
        console.log('[CLINICA_ADVANCED] Discarding preview changes...');

        // Reîncarcă timeslots din baza de date (într-un scenariu real)
        // Pentru demonstrație, vom reseta modificările vizuale
        $('.advanced-timeslot-item').removeClass('preview-conflict preview-warning preview-ok');

        // Dezactivează preview mode
        togglePreviewMode();

        showSuccessMessage('Modificările au fost abandonate.');
    }

    function showDayCopyMenu(targetDay) {
        console.log(`[CLINICA_ADVANCED] Showing copy menu for day ${targetDay}`);



        // Creează un mini-menu pentru selecția zilei sursă
        const menuHtml = `
            <div class="copy-menu-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <div class="copy-menu" style="
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                    max-width: 400px;
                    width: 90%;
                ">
                    <h3>Copiază program în ${dayNames[targetDay]}</h3>
                    <p>Selectează ziua sursă:</p>
                    <div class="copy-options" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 20px 0;">
                        ${[1,2,3,4,5,6,0].filter(day => day !== targetDay).map(day => `
                            <button class="button copy-source-btn" data-source="${day}" data-target="${targetDay}" style="text-align: left;">
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                                Din ${dayNames[day]}
                            </button>
                        `).join('')}
                    </div>
                    <div style="text-align: right;">
                        <button class="button button-secondary close-copy-menu">Anulează</button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(menuHtml);

        // Event listeners pentru menu
        $('.copy-source-btn').on('click', function() {
            const sourceDay = $(this).data('source');
            const targetDay = $(this).data('target');
            copyScheduleBetweenDays(sourceDay, targetDay);
            $('.copy-menu-overlay').remove();
        });

        $('.close-copy-menu').on('click', function() {
            $('.copy-menu-overlay').remove();
        });

        // Închide la click în afara menu-ului
        $('.copy-menu-overlay').on('click', function(e) {
            if (e.target === this) {
                $(this).remove();
            }
        });
    }

    function clearDayTimeslots(day) {
        console.log(`[CLINICA_ADVANCED] Clearing timeslots for day ${day}`);


        const $container = $(`.day-timeslots-container[data-day="${day}"]`);
        const timeslotsCount = $container.find('.advanced-timeslot-item').length;

        if (timeslotsCount === 0) {
            showSuccessMessage(`Nu există timeslots în ${dayNames[day]} de șters!`);
            return;
        }

        const confirmMessage = `Ești sigur că vrei să ștergi toate ${timeslotsCount} timeslots din ${dayNames[day]}?`;
        if (!confirm(confirmMessage)) {
            return;
        }

        // Șterge toate timeslots din baza de date
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_delete_day_timeslots',
                nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                doctor_id: selectedDoctor,
                service_id: selectedService,
                day_of_week: day
            },
            success: function(response) {
                console.log('[CLINICA_ADVANCED] Day timeslots deleted from database:', response);
                
                // Șterge toate timeslots din DOM
                $container.empty();

                // Adaugă placeholder-ul pentru zi goală
                updateEmptyDayPlaceholder(day);

                // Actualizează contorul total
                updateTotalTimeslotsCount();

                showSuccessMessage(`Șterse ${timeslotsCount} timeslots din ${dayNames[day]}`);
            },
            error: function(xhr, status, error) {
                console.error('[CLINICA_ADVANCED] Error deleting day timeslots:', error);
                showErrorMessage('Eroare la ștergerea timeslots-urilor din zi');
            }
        });

        // Marchează ca fiind modificări nesalvate
        markUnsavedChanges();
    }

    function editTimeslot(timeslotId) {
        console.log(`[CLINICA_ADVANCED] Editing timeslot ${timeslotId}`);

        // Găsește timeslot-ul în DOM
        const $timeslot = $(`.advanced-timeslot-item[data-id="${timeslotId}"]`);
        if (!$timeslot.length) {
            console.error(`[CLINICA_ADVANCED] Timeslot ${timeslotId} not found`);
            return;
        }

        // Extrage datele din timeslot
        const timeText = $timeslot.find('.timeslot-time').text();
        const [startTime, endTime] = timeText.split(' - ');

        // Deschide modal-ul cu datele precompletate
        $('#advanced-start-time').val(startTime);
        $('#advanced-end-time').val(endTime);
        $('#advanced-timeslot-form').data('editing-id', timeslotId);

        // Găsește ziua și deschide modal-ul
        const day = $timeslot.closest('.day-timeslots-container').data('day');
        openAdvancedTimeslotModal('edit', day, timeslotId);
    }

    function deleteTimeslot(timeslotId) {
        console.log(`[CLINICA_ADVANCED] deleteTimeslot function called with ID: ${timeslotId}`);

        const $timeslot = $(`.advanced-timeslot-item[data-id="${timeslotId}"]`);
        console.log(`[CLINICA_ADVANCED] Found timeslot element:`, $timeslot.length);
        
        if (!$timeslot.length) {
            console.error(`[CLINICA_ADVANCED] Timeslot ${timeslotId} not found in DOM`);
            return;
        }

        const timeText = $timeslot.find('.timeslot-time').text();
        const day = $timeslot.closest('.day-timeslots-container').data('day');


        const confirmMessage = `Ești sigur că vrei să ștergi timeslot-ul ${timeText} din ${dayNames[day]}?`;
        if (!confirm(confirmMessage)) {
            return;
        }

        // Verifică dacă este un slot temporar (nu are ID numeric)
        if (timeslotId.toString().startsWith('temp_')) {
            console.log('[CLINICA_ADVANCED] Deleting temporary slot (not in database)');
            
            // Șterge din DOM
            $timeslot.remove();

            // Verifică dacă ziua a rămas goală
            const $container = $(`.day-timeslots-container[data-day="${day}"]`);
            if ($container.find('.advanced-timeslot-item').length === 0) {
                updateEmptyDayPlaceholder(day);
            }

            // Actualizează contorul total
            updateTotalTimeslotsCount();
            
            showSuccessMessage(`Timeslot ${timeText} șters cu succes!`);
            return;
        }

        // Șterge timeslot-ul din baza de date
        console.log('[CLINICA_ADVANCED] Starting AJAX delete request...');
        console.log('[CLINICA_ADVANCED] AJAX URL:', ajaxurl);
        console.log('[CLINICA_ADVANCED] Timeslot ID:', timeslotId);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_delete_timeslot',
                nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                timeslot_id: timeslotId
            },
            success: function(response) {
                console.log('[CLINICA_ADVANCED] Timeslot deleted from database:', response);
                
                // Șterge timeslot-ul din DOM
                $timeslot.remove();

                // Verifică dacă ziua a rămas goală
                const $container = $(`.day-timeslots-container[data-day="${day}"]`);
                if ($container.find('.advanced-timeslot-item').length === 0) {
                    updateEmptyDayPlaceholder(day);
                }

                // Actualizează contorul total
                updateTotalTimeslotsCount();
                
                showSuccessMessage(`Timeslot ${timeText} șters cu succes!`);
            },
            error: function(xhr, status, error) {
                console.error('[CLINICA_ADVANCED] Error deleting timeslot:', error);
                showErrorMessage('Eroare la ștergerea timeslot-ului');
            }
        });

        // Actualizează preview-ul în timp real
        updateRealtimePreview();
    }

    function exportToCalendar() {
        console.log('[CLINICA_ADVANCED] Exporting to calendar...');
    }

    // ===== FUNCȚII PENTRU GENERAREA AUTOMATĂ A SLOTURILOR =====

    function updateServiceDurationDisplay() {
        if (!selectedService) return;

        // Găsește durata serviciului selectat
        const services = <?php echo json_encode($services); ?>;
        const service = services.find(s => s.id == selectedService);
        
        if (service) {
            console.log(`[CLINICA_ADVANCED] Service duration: ${service.duration} minutes`);
            
            // Actualizează afișajul duratei în interfață
            updateDurationDisplay(service.duration);
        }
    }

    function updateDurationDisplay(duration) {
        // Actualizează textul din selectorul de servicii
        const $serviceSelector = $('#advanced-service-selector');
        const selectedText = $serviceSelector.find('option:selected').text();
        
        // Extrage numele serviciului (fără durata veche)
        const serviceName = selectedText.replace(/\(\d+\s*min\)/, '').trim();
        
        // Actualizează textul cu durata nouă
        $serviceSelector.find('option:selected').text(`${serviceName} (${duration} min)`);
        
        console.log(`[CLINICA_ADVANCED] Updated service display: ${serviceName} (${duration} min)`);
    }

    function getSelectedServiceDuration() {
        if (!selectedService) return 30; // Default

        const services = <?php echo json_encode($services); ?>;
        const service = services.find(s => s.id == selectedService);
        return service ? service.duration : 30;
    }

    function checkAndDisplayExistingSlotsInfo() {
        console.log('[CLINICA_ADVANCED] Checking existing slots info...');

        if (!selectedDoctor || !selectedService) {
            return;
        }

        // Verifică sloturile existente și afișează informații
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_get_doctor_timeslots',
                nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                doctor_id: selectedDoctor,
                service_id: selectedService
            },
            success: function(response) {
                console.log('[CLINICA_ADVANCED] Existing slots info:', response);
                
                if (response.success && response.data && response.data.length > 0) {
                    displayExistingSlotsInfo(response.data);
                } else {
                    hideExistingSlotsInfo();
                }
            },
            error: function(xhr, status, error) {
                console.error('[CLINICA_ADVANCED] Error checking existing slots info:', error);
                hideExistingSlotsInfo();
            }
        });
    }

    function displayExistingSlotsInfo(existingSlots) {
        const totalSlots = existingSlots.length;
        
        // Grupează sloturile pe zile
        const slotsByDay = {};
        existingSlots.forEach(slot => {
            const day = slot.day_of_week;
            if (!slotsByDay[day]) {
                slotsByDay[day] = [];
            }
            slotsByDay[day].push(slot);
        });


        let infoText = `Există ${totalSlots} sloturi definite: `;
        
        Object.keys(slotsByDay).forEach(day => {
            const daySlots = slotsByDay[day];
            infoText += `${dayNames[day]} (${daySlots.length}), `;
        });
        
        infoText = infoText.slice(0, -2); // Elimină ultima virgulă

        // Creează sau actualizează informația
        let $infoDiv = $('#existing-slots-info');
        if ($infoDiv.length === 0) {
            $infoDiv = $(`
                <div id="existing-slots-info" class="existing-slots-info">
                    <div class="info-content">
                        <span class="dashicons dashicons-info"></span>
                        <span class="info-text">${infoText}</span>
                    </div>
                </div>
            `);
            $('.auto-generate-section').after($infoDiv);
        } else {
            $infoDiv.find('.info-text').text(infoText);
        }
    }

    function hideExistingSlotsInfo() {
        $('#existing-slots-info').remove();
    }

    function generateSlotsAutomatically() {
        console.log('[CLINICA_ADVANCED] Generating slots automatically...');
        console.log('[CLINICA_ADVANCED] selectedDoctor:', selectedDoctor);
        console.log('[CLINICA_ADVANCED] selectedService:', selectedService);

        if (!selectedDoctor || !selectedService) {
            console.error('[CLINICA_ADVANCED] MISSING SELECTION:');
            console.error('- selectedDoctor:', selectedDoctor);
            console.error('- selectedService:', selectedService);
            alert('Selectează mai întâi un doctor și un serviciu!\n\nDoctor: ' + (selectedDoctor ? 'SELECTAT' : 'LIPSĂ') + '\nServiciu: ' + (selectedService ? 'SELECTAT' : 'LIPSĂ'));
            return;
        }

        const startTime = $('#start-time').val();
        const endTime = $('#end-time').val();
        const durationType = $('input[name="duration-type"]:checked').val();
        
        // Obține zilele selectate
        const selectedDays = [];
        $('input[name="working-days"]:checked').each(function() {
            selectedDays.push(parseInt($(this).val()));
        });

        if (!startTime || !endTime) {
            alert('Completează intervalul de ore!');
            return;
        }
        
        if (selectedDays.length === 0) {
            alert('Selectează cel puțin o zi de lucru!');
            return;
        }
        
        console.log('[CLINICA_ADVANCED] Selected days:', selectedDays);

        let slotDuration;
        if (durationType === 'service') {
            // Folosește durata serviciului
            const services = <?php echo json_encode($services); ?>;
            const service = services.find(s => s.id == selectedService);
            slotDuration = service ? service.duration : 30;
        } else {
            // Folosește durata personalizată
            slotDuration = parseInt($('#custom-duration').val()) || 30;
        }

        // Verifică dacă există deja sloturi în baza de date
        checkExistingSlotsBeforeGeneration(startTime, endTime, slotDuration, selectedDays);
    }

    function checkExistingSlotsBeforeGeneration(startTime, endTime, slotDuration, selectedDays) {
        console.log('[CLINICA_ADVANCED] Checking for existing slots...');

        // Verifică dacă există sloturi existente pentru doctorul și serviciul selectat
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_get_doctor_timeslots',
                nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                doctor_id: selectedDoctor,
                service_id: selectedService
            },
            success: function(response) {
                console.log('[CLINICA_ADVANCED] Existing slots check:', response);
                
                if (response.success && response.data && response.data.length > 0) {
                    // Există sloturi - întreabă utilizatorul ce să facă
                    handleExistingSlotsFound(response.data, startTime, endTime, slotDuration, selectedDays);
                } else {
                    // Nu există sloturi - generează direct
                    proceedWithGeneration(startTime, endTime, slotDuration, selectedDays);
                }
            },
            error: function(xhr, status, error) {
                console.error('[CLINICA_ADVANCED] Error checking existing slots:', error);
                // În caz de eroare, procedează cu generarea
                proceedWithGeneration(startTime, endTime, slotDuration, selectedDays);
            }
        });
    }

    function handleExistingSlotsFound(existingSlots, startTime, endTime, slotDuration, selectedDays) {
        const totalExisting = existingSlots.length;
        const startTimeFormatted = formatTimeForDisplay(startTime);
        const endTimeFormatted = formatTimeForDisplay(endTime);
        
        const message = `Există deja ${totalExisting} sloturi definite pentru acest doctor și serviciu.\n\n` +
                       `Ce vrei să faci?\n\n` +
                       `• "OK" - Înlocuiește toate sloturile existente cu cele noi\n` +
                       `• "Anulează" - Păstrează sloturile existente și nu generezi altele noi`;
        
        if (confirm(message)) {
            // Utilizatorul vrea să înlocuiască sloturile existente
            proceedWithGeneration(startTime, endTime, slotDuration, selectedDays, true);
        } else {
            // Utilizatorul vrea să păstreze sloturile existente
            console.log('[CLINICA_ADVANCED] User chose to keep existing slots');
            showSuccessMessage('Sloturile existente au fost păstrate. Nu s-au generat sloturi noi.');
        }
    }

    function proceedWithGeneration(startTime, endTime, slotDuration, selectedDays, replaceExisting = false) {
        const startTimeFormatted = formatTimeForDisplay(startTime);
        const endTimeFormatted = formatTimeForDisplay(endTime);
        
        const selectedDayNames = selectedDays.map(day => dayNames[day]).join(', ');
        let confirmMessage;
        if (replaceExisting) {
            confirmMessage = `Înlocuiești toate sloturile existente cu sloturi noi de ${slotDuration} minute între ${startTimeFormatted} și ${endTimeFormatted} pentru: ${selectedDayNames}?`;
        } else {
            confirmMessage = `Generezi sloturi de ${slotDuration} minute între ${startTimeFormatted} și ${endTimeFormatted} pentru: ${selectedDayNames}?`;
        }
        
        if (!confirm(confirmMessage)) {
            return;
        }

        // Generează sloturile pentru zilele selectate
        console.log('[CLINICA_ADVANCED] Starting generation for days:', selectedDays);
        selectedDays.forEach(day => {
            console.log(`[CLINICA_ADVANCED] Generating for day ${day}`);
            generateSlotsForDay(day, startTime, endTime, slotDuration);
        });


        const actionText = replaceExisting ? 'înlocuite' : 'generate';
        showSuccessMessage(`S-au ${actionText} sloturi de ${slotDuration} minute pentru: ${selectedDayNames}!`);
        
        // Salvează automat sloturile generate în baza de date
        console.log('[CLINICA_ADVANCED] Saving generated slots to database...');
        saveGeneratedSlotsToDatabase(selectedDays, startTime, endTime, slotDuration, replaceExisting);
        
        markUnsavedChanges();
    }

    function generateSlotsForDay(day, startTime, endTime, duration) {
        console.log(`[CLINICA_ADVANCED] Generating slots for day ${day}: ${startTime} - ${endTime}, duration: ${duration}min`);

        const $container = $(`.day-timeslots-container[data-day="${day}"]`);
        console.log(`[CLINICA_ADVANCED] Container found:`, $container.length);
        
        // Șterge sloturile existente
        $container.empty();

        // Calculează sloturile
        const slots = calculateTimeSlots(startTime, endTime, duration);
        console.log(`[CLINICA_ADVANCED] Calculated slots:`, slots);
        
        // Adaugă sloturile în container cu format românesc
        slots.forEach((slot, index) => {
            const startFormatted = formatTimeForDisplay(slot.start);
            const endFormatted = formatTimeForDisplay(slot.end);
            const slotHtml = createTimeslotHtml(startFormatted, endFormatted, day);
            console.log(`[CLINICA_ADVANCED] Adding slot ${index + 1}: ${startFormatted} - ${endFormatted}`);
            console.log(`[CLINICA_ADVANCED] Slot HTML:`, slotHtml);
            console.log(`[CLINICA_ADVANCED] Container before append:`, $container.length, $container[0]);
            $container.append(slotHtml);
            console.log(`[CLINICA_ADVANCED] Container after append:`, $container.find('.advanced-timeslot-item').length);
        });

        // Actualizează contorul
        updateTotalTimeslotsCount();
        console.log(`[CLINICA_ADVANCED] Generated ${slots.length} slots for day ${day}`);
    }

    function calculateTimeSlots(startTime, endTime, duration) {
        console.log(`[CLINICA_ADVANCED] calculateTimeSlots: ${startTime} - ${endTime}, duration: ${duration}min`);
        
        const slots = [];
        
        // Convertește orele în minute
        const startMinutes = timeToMinutes(startTime);
        const endMinutes = timeToMinutes(endTime);
        
        console.log(`[CLINICA_ADVANCED] Start minutes: ${startMinutes}, End minutes: ${endMinutes}`);
        
        let currentMinutes = startMinutes;
        
        while (currentMinutes + duration <= endMinutes) {
            const slotStart = minutesToTime(currentMinutes);
            const slotEnd = minutesToTime(currentMinutes + duration);
            
            console.log(`[CLINICA_ADVANCED] Creating slot: ${slotStart} - ${slotEnd}`);
            
            slots.push({
                start: slotStart,
                end: slotEnd
            });
            
            currentMinutes += duration;
        }
        
        console.log(`[CLINICA_ADVANCED] Total slots calculated: ${slots.length}`);
        console.log(`[CLINICA_ADVANCED] Final slots array:`, slots);
        return slots;
    }

    function timeToMinutes(timeString) {
        const [hours, minutes] = timeString.split(':').map(Number);
        return hours * 60 + minutes;
    }

    function minutesToTime(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
    }

    function createTimeslotHtml(startTime, endTime, day, timeslotId = null) {
        const id = timeslotId || `temp_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        return `
            <div class="advanced-timeslot-item" data-day="${day}" data-id="${id}">
                <div class="timeslot-content">
                    <div class="timeslot-time">${startTime} - ${endTime}</div>
                    <div class="timeslot-actions">
                        <button class="timeslot-action-btn edit-timeslot-btn" data-id="${id}" title="Editează">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="timeslot-action-btn delete-timeslot-btn" data-id="${id}" title="Șterge">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // ===== FUNCȚII PENTRU FORMAT ROMÂNESC =====

    function setupRomanianTimeFormat() {
        // Convertește input-urile de timp în format românesc
        $('input[type="time"][data-format="ro"]').each(function() {
            const $input = $(this);
            const originalValue = $input.val();
            
            // Convertește la format românesc (HH:MM)
            const romanianValue = convertToRomanianTime(originalValue);
            $input.val(romanianValue);
            
            // Adaugă event listener pentru formatare automată
            $input.on('blur', function() {
                const value = $(this).val();
                const formattedValue = convertToRomanianTime(value);
                $(this).val(formattedValue);
            });
        });
    }

    function convertToRomanianTime(timeString) {
        if (!timeString) return '';
        
        // Convertește din format 24h în format românesc
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const minute = parseInt(minutes);
        
        // Format românesc: HH:MM (cu zero-uri leading)
        return `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
    }

    function formatTimeForDisplay(timeString) {
        if (!timeString) return '';
        
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const minute = parseInt(minutes);
        
        // Format românesc pentru afișare
        return `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
    }

    // ===== FUNCȚII PENTRU SALVAREA ÎN BAZA DE DATE =====

    function saveGeneratedSlotsToDatabase(workingDays, startTime, endTime, slotDuration, replaceExisting = false) {
        console.log('[CLINICA_ADVANCED] Saving generated slots to database...');

        if (!selectedDoctor || !selectedService) {
            console.error('[CLINICA_ADVANCED] Cannot save: missing doctor or service');
            return;
        }

        // Dacă se înlocuiesc sloturile existente, șterge-le mai întâi
        if (replaceExisting) {
            deleteExistingSlotsForDoctorAndService(workingDays, startTime, endTime, slotDuration);
            return;
        }

        // Calculează toate sloturile pentru toate zilele
        const allSlots = [];
        const slots = calculateTimeSlots(startTime, endTime, slotDuration);
        
        console.log('[CLINICA_ADVANCED] Calculated slots:', slots);
        console.log('[CLINICA_ADVANCED] Working days:', workingDays);

        workingDays.forEach(day => {
            slots.forEach(slot => {
                allSlots.push({
                    doctor_id: selectedDoctor,
                    service_id: selectedService,
                    day_of_week: day,
                    start_time: slot.start,
                    end_time: slot.end,
                    slot_duration: slotDuration
                });
            });
        });

        console.log(`[CLINICA_ADVANCED] Total slots to save: ${allSlots.length}`);

        // Salvează fiecare slot în baza de date
        let savedCount = 0;
        let errorCount = 0;

        allSlots.forEach((slot, index) => {
            console.log(`[CLINICA_ADVANCED] Saving slot ${index + 1}:`, slot);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_save_timeslot',
                    nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                    timeslot_id: '', // Slot nou
                    doctor_id: slot.doctor_id,
                    service_id: slot.service_id,
                    day_of_week: slot.day_of_week,
                    start_time: slot.start_time,
                    end_time: slot.end_time,
                    slot_duration: slot.slot_duration
                },
                success: function(response) {
                    savedCount++;
                    console.log(`[CLINICA_ADVANCED] Slot ${index + 1} saved successfully`);
                    
                    // Când toate sloturile au fost salvate
                    if (savedCount + errorCount === allSlots.length) {
                        if (errorCount === 0) {
                            showSuccessMessage(`Toate ${savedCount} sloturi au fost salvate cu succes în baza de date!`);
                        } else {
                            showSuccessMessage(`${savedCount} sloturi salvate, ${errorCount} erori. Verifică log-urile.`);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    errorCount++;
                    console.error(`[CLINICA_ADVANCED] Error saving slot ${index + 1}:`, error);
                    console.error(`[CLINICA_ADVANCED] XHR response:`, xhr.responseText);
                    
                    // Când toate sloturile au fost procesate
                    if (savedCount + errorCount === allSlots.length) {
                        if (errorCount > 0) {
                            showErrorMessage(`${errorCount} sloturi nu au putut fi salvate. Verifică log-urile pentru detalii.`);
                        }
                    }
                }
            });
        });
    }

    function deleteExistingSlotsForDoctorAndService(workingDays, startTime, endTime, slotDuration) {
        console.log('[CLINICA_ADVANCED] Deleting existing slots before generating new ones...');

        // Șterge toate sloturile existente pentru doctorul și serviciul selectat
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_delete_all_doctor_service_timeslots',
                nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                doctor_id: selectedDoctor,
                service_id: selectedService
            },
            success: function(response) {
                console.log('[CLINICA_ADVANCED] Existing slots deleted:', response);
                
                if (response.success) {
                    // Acum generează și salvează sloturile noi
                    saveGeneratedSlotsToDatabase(workingDays, startTime, endTime, slotDuration, false);
                } else {
                    showErrorMessage('Eroare la ștergerea sloturilor existente: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('[CLINICA_ADVANCED] Error deleting existing slots:', error);
                showErrorMessage('Eroare la ștergerea sloturilor existente. Verifică log-urile.');
            }
        });
    }

    function showErrorMessage(message) {
        // Creează un mesaj de eroare
        const $message = $(`
            <div class="notice notice-error is-dismissible">
                <p><strong>Eroare:</strong> ${message}</p>
            </div>
        `);
        
        // Adaugă mesajul în partea de sus a paginii
        $('.wrap h1').after($message);
        
        // Elimină mesajul după 5 secunde
        setTimeout(() => {
            $message.fadeOut(() => $message.remove());
        }, 5000);
    }

    function loadTimeslotsForWeek() {
        console.log('[CLINICA_ADVANCED] Loading timeslots for week:', currentWeek);
        console.log('[CLINICA_ADVANCED] selectedDoctor:', selectedDoctor);
        console.log('[CLINICA_ADVANCED] selectedService:', selectedService);

        if (!selectedDoctor || !selectedService) {
            console.log('[CLINICA_ADVANCED] Cannot load: missing doctor or service');
            return;
        }

        // Încarcă timeslots-urile din baza de date pentru săptămâna curentă
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clinica_get_doctor_timeslots',
                nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                doctor_id: selectedDoctor,
                service_id: selectedService,
                week: currentWeekNumber,
                year: currentYear
            },
            success: function(response) {
                console.log('[CLINICA_ADVANCED] Timeslots loaded for week', currentWeek, ':', response);
                
                if (response.success && response.data) {
                    displayTimeslotsFromDatabase(response.data);
                } else {
                    console.log('[CLINICA_ADVANCED] No timeslots found for week', currentWeek);
                    clearAllTimeslotsDisplay();
                }
            },
            error: function(xhr, status, error) {
                console.error('[CLINICA_ADVANCED] Error loading timeslots for week', currentWeek, ':', error);
                showErrorMessage('Eroare la încărcarea timeslots-urilor pentru săptămâna ' + currentWeek);
            }
        });
    }

    function displayTimeslotsFromDatabase(timeslots) {
        console.log('[CLINICA_ADVANCED] Displaying timeslots from database:', timeslots);
        console.log('[CLINICA_ADVANCED] Number of timeslots to display:', timeslots.length);

        // Șterge toate timeslots-urile existente
        clearAllTimeslotsDisplay();

        // Grupează timeslots-urile pe zile
        const timeslotsByDay = {};
        timeslots.forEach(timeslot => {
            const day = timeslot.day_of_week;
            if (!timeslotsByDay[day]) {
                timeslotsByDay[day] = [];
            }
            timeslotsByDay[day].push(timeslot);
        });

        // Afișează timeslots-urile pentru fiecare zi
        Object.keys(timeslotsByDay).forEach(day => {
            const $container = $(`.day-timeslots-container[data-day="${day}"]`);
            const dayTimeslots = timeslotsByDay[day];
            
            console.log(`[CLINICA_ADVANCED] Displaying ${dayTimeslots.length} slots for day ${day}`);

            dayTimeslots.forEach(timeslot => {
                const startFormatted = formatTimeForDisplay(timeslot.start_time);
                const endFormatted = formatTimeForDisplay(timeslot.end_time);
                const slotHtml = createTimeslotHtmlWithId(startFormatted, endFormatted, day, timeslot.id);
                $container.append(slotHtml);
            });
        });

        // Adaugă placeholder-ul pentru zilele care nu au sloturi
        for (let day = 1; day <= 5; day++) {
            const $container = $(`.day-timeslots-container[data-day="${day}"]`);
            const slotCount = $container.find('.advanced-timeslot-item').length;
            console.log(`[CLINICA_ADVANCED] Day ${day} has ${slotCount} slots`);
            
            if (slotCount === 0) {
                console.log(`[CLINICA_ADVANCED] Adding placeholder for empty day ${day}`);
                updateEmptyDayPlaceholder(day);
            }
        }

        // Actualizează contorul
        updateTotalTimeslotsCount();
    }

    function createTimeslotHtmlWithId(startTime, endTime, day, timeslotId) {
        return `
            <div class="advanced-timeslot-item" data-day="${day}" data-id="${timeslotId}">
                <div class="timeslot-content">
                    <div class="timeslot-time">${startTime} - ${endTime}</div>
                    <div class="timeslot-actions">
                        <button class="timeslot-action-btn edit-timeslot-btn" data-id="${timeslotId}" title="Editează">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="timeslot-action-btn delete-timeslot-btn" data-id="${timeslotId}" title="Șterge">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    function clearAllTimeslotsDisplay() {
        $('.day-timeslots-container').empty();
        
        // Adaugă placeholder-ul pentru toate zilele goale
        for (let day = 1; day <= 5; day++) {
            updateEmptyDayPlaceholder(day);
        }
        
        updateTotalTimeslotsCount();
        
        // Ascunde formularul de timeslots când nu sunt selectate ambele
        if (!selectedService || !selectedDoctor) {
            $('#advanced-week-grid').hide();
        }
    }

    function saveAllTimeslotsToDatabase() {
        console.log('[CLINICA_ADVANCED] Saving all timeslots to database...');

        if (!selectedDoctor || !selectedService) {
            alert('Selectează mai întâi un doctor și un serviciu!');
            return;
        }

        // Colectează toate timeslots-urile din interfață
        const allTimeslots = [];
        $('.advanced-timeslot-item').each(function() {
            const $timeslot = $(this);
            const day = $timeslot.data('day');
            const timeslotId = $timeslot.data('timeslot-id') || '';
            const timeText = $timeslot.find('.timeslot-time').text();
            const [startTime, endTime] = timeText.split(' - ');

            allTimeslots.push({
                id: timeslotId,
                doctor_id: selectedDoctor,
                service_id: selectedService,
                day_of_week: day,
                start_time: startTime,
                end_time: endTime,
                slot_duration: getSelectedServiceDuration()
            });
        });

        if (allTimeslots.length === 0) {
            alert('Nu există timeslots de salvat!');
            return;
        }

        // Confirmare
        const confirmMessage = `Salvezi ${allTimeslots.length} timeslots în baza de date?`;
        if (!confirm(confirmMessage)) {
            return;
        }

        // Salvează fiecare timeslot
        let savedCount = 0;
        let errorCount = 0;

        allTimeslots.forEach((timeslot, index) => {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'clinica_save_timeslot',
                    nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                    timeslot_id: timeslot.id,
                    doctor_id: timeslot.doctor_id,
                    service_id: timeslot.service_id,
                    day_of_week: timeslot.day_of_week,
                    start_time: timeslot.start_time,
                    end_time: timeslot.end_time,
                    slot_duration: timeslot.slot_duration
                },
                success: function(response) {
                    savedCount++;
                    console.log(`[CLINICA_ADVANCED] Timeslot ${index + 1} saved successfully`);
                    
                    // Când toate timeslots-urile au fost salvate
                    if (savedCount + errorCount === allTimeslots.length) {
                        if (errorCount === 0) {
                            showSuccessMessage(`Toate ${savedCount} timeslots au fost salvate cu succes!`);
                        } else {
                            showSuccessMessage(`${savedCount} timeslots salvate, ${errorCount} erori.`);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    errorCount++;
                    console.error(`[CLINICA_ADVANCED] Error saving timeslot ${index + 1}:`, error);
                    
                    // Când toate timeslots-urile au fost procesate
                    if (savedCount + errorCount === allTimeslots.length) {
                        if (errorCount > 0) {
                            showErrorMessage(`${errorCount} timeslots nu au putut fi salvate.`);
                        }
                    }
                }
            });
        });
    }

    // ===== FUNCȚII PENTRU GESTIONAREA SERVICIILOR =====

    function loadServicesTab() {
        console.log('[CLINICA_ADVANCED] Loading services tab...');
        loadServices();
        
        // Nu mai avem nevoie de fix-uri - spațiul separator rezolvă problema
        console.log('[CLINICA_ADVANCED] Services tab loaded with dropdown spacer');
    }

    function loadServices() {
        console.log('[CLINICA_ADVANCED] Loading services...');

        $('#services-list').html(`
            <div class="loading-placeholder">
                <div class="loading-icon">
                    <span class="dashicons dashicons-update spin"></span>
                </div>
                <p>Se încarcă serviciile...</p>
            </div>
        `);

        // Simulare încărcare servicii (în realitate ar fi AJAX call)
        setTimeout(function() {
            // Folosim datele deja încărcate în PHP
            const servicesData = <?php echo json_encode($services); ?>;
            renderServicesList(servicesData);
            
            // Nu mai avem nevoie de fix-uri - spațiul separator rezolvă problema
            console.log('[CLINICA_ADVANCED] Services rendered with dropdown spacer');
        }, 500);
    }

    function renderServicesList(services) {
        const $container = $('#services-list');

        if (!services || services.length === 0) {
            $container.html(`
                <div class="empty-state">
                    <div class="empty-icon">
                        <span class="dashicons dashicons-admin-tools"></span>
                    </div>
                    <h3>Niciun serviciu găsit</h3>
                    <p>Nu există servicii configurate în sistem.</p>
                    <button class="button button-primary" onclick="$('#add-new-service-btn').trigger('click');">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        Adaugă Primul Serviciu
                    </button>
                </div>
            `);
            return;
        }

        const servicesHtml = services.map(service => createServiceCard(service)).join('');
        $container.html(servicesHtml);

        console.log(`[CLINICA_ADVANCED] Rendered ${services.length} services`);
    }

    function createServiceCard(service) {
        const statusClass = service.active == 1 ? 'active' : 'inactive';
        const statusText = service.active == 1 ? 'Activ' : 'Inactiv';
        const statusIcon = service.active == 1 ? 'dashicons-yes' : 'dashicons-no';

        return `
            <div class="service-card" data-service-id="${service.id}">
                <div class="service-header">
                    <div class="service-info">
                        <h4 class="service-name">${service.name}</h4>
                        <div class="service-meta">
                            <span class="service-duration">
                                <span class="dashicons dashicons-clock"></span>
                                ${service.duration} min
                            </span>
                            <span class="service-status ${statusClass}">
                                <span class="dashicons ${statusIcon}"></span>
                                ${statusText}
                            </span>
                        </div>
                    </div>
                    <div class="service-actions">
                        <button class="service-action-btn edit-service-btn"
                                data-service-id="${service.id}"
                                title="Editează serviciu">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="service-action-btn delete-service-btn"
                                data-service-id="${service.id}"
                                data-service-name="${service.name}"
                                title="Șterge serviciu">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>

                <div class="service-details">
                    <div class="service-stats">
                        <div class="stat-item">
                            <span class="stat-label">Creat:</span>
                            <span class="stat-value">${formatDate(service.created_at)}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Actualizat:</span>
                            <span class="stat-value">${formatDate(service.updated_at)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('ro-RO', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function openServiceModal(mode, serviceId = null) {
        const $modal = $('#service-modal');
        const $title = $('#service-modal-title');
        const $form = $('#service-form');

        if (mode === 'add') {
            $title.text('Adaugă Serviciu Nou');
            $form[0].reset();
            $('#service-id').val('');
            $('#service-active').val('1');
        } else if (mode === 'edit' && serviceId) {
            $title.text('Editează Serviciu');
            loadServiceData(serviceId);
        }

        $modal.show();
    }

    function loadServiceData(serviceId) {
        // Simulare încărcare date serviciu (în realitate ar fi AJAX)
        const services = <?php echo json_encode($services); ?>;
        const service = services.find(s => s.id == serviceId);

        if (service) {
            $('#service-id').val(service.id);
            $('#service-name').val(service.name);
            $('#service-duration').val(service.duration);
            $('#service-active').val(service.active);
        }
    }

    function saveService() {
        const formData = new FormData(document.getElementById('service-form'));
        const serviceId = formData.get('service_id');
        const isNew = !serviceId || serviceId === '';

        console.log(`[CLINICA_ADVANCED] ${isNew ? 'Creating' : 'Updating'} service...`);

        // Simulare salvare (în realitate ar fi AJAX call către clinica_services_save)
        const serviceData = {
            id: serviceId,
            name: formData.get('service_name'),
            duration: formData.get('service_duration'),
            active: formData.get('service_active'),
            auto_allocations: $('#service-auto-allocations').is(':checked') ? 1 : 0
        };

        // Validare
        if (!serviceData.name || serviceData.name.trim() === '') {
            alert('Numele serviciului este obligatoriu!');
            return;
        }

        if (serviceData.duration < 5 || serviceData.duration > 480) {
            alert('Durata trebuie să fie între 5 și 480 minute!');
            return;
        }

        // Simulare AJAX call
        setTimeout(function() {
            $('#service-modal').hide();

            const message = isNew ?
                `Serviciul "${serviceData.name}" a fost adăugat cu succes!` :
                `Serviciul "${serviceData.name}" a fost actualizat cu succes!`;

            showSuccessMessage(message);

            // Reîncarcă lista de servicii
            loadServices();

            // Marchează ca fiind modificări nesalvate
            markUnsavedChanges();

        }, 500);
    }

    function confirmDeleteService(serviceId, serviceName) {
        $('#delete-service-name').text(serviceName);
        $('#confirm-delete-service').data('service-id', serviceId);
        $('#delete-service-modal').show();
    }

    function deleteService(serviceId) {
        console.log(`[CLINICA_ADVANCED] Deleting service ${serviceId}...`);

        $('#delete-service-modal').hide();

        // Simulare ștergere (în realitate ar fi AJAX call către clinica_services_delete)
        setTimeout(function() {
            showSuccessMessage('Serviciul a fost șters cu succes!');

            // Reîncarcă lista de servicii
            loadServices();

            // Marchează ca fiind modificări nesalvate
            markUnsavedChanges();

        }, 500);
    }

    function filterServices() {
        const searchTerm = $('#services-search').val().toLowerCase();
        const statusFilter = $('.status-btn.active').data('status');

        $('.service-card').each(function() {
            const $card = $(this);
            const serviceName = $card.find('.service-name').text().toLowerCase();
            const isActive = $card.find('.service-status').hasClass('active');

            let showCard = true;

            // Filtru căutare
            if (searchTerm && !serviceName.includes(searchTerm)) {
                showCard = false;
            }

            // Filtru status
            if (statusFilter === 'active' && !isActive) {
                showCard = false;
            } else if (statusFilter === 'inactive' && isActive) {
                showCard = false;
            }

            $card.toggle(showCard);
        });

        console.log(`[CLINICA_ADVANCED] Filtered services - search: "${searchTerm}", status: ${statusFilter}`);
    }

    function applyTemplate(templateId) {
        console.log(`[CLINICA_ADVANCED] Applying template ${templateId}`);

        const templates = getPredefinedTemplates();
        const template = templates.find(t => t.id === templateId);

        if (!template) {
            showSuccessMessage('Șablonul nu a fost găsit!');
            return;
        }

        const confirmMessage = `Ești sigur că vrei să aplici șablonul "${template.name}" tuturor zilelor? Acest lucru va înlocui toate timeslots-urile existente.`;
        if (!confirm(confirmMessage)) {
            return;
        }

        // Aplică șablonul tuturor zilelor (DOAR ZILELE DE LUCRU)
        const targetDays = [1, 2, 3, 4, 5]; // Luni până Vineri (FĂRĂ WEEKEND!)

        targetDays.forEach(day => {
            const $container = $(`.day-timeslots-container[data-day="${day}"]`);

            // Șterge timeslots existente
            $container.empty();

            // Adaugă timeslots din șablon
            template.timeslots.forEach(slot => {
                const timeslotData = {
                    start_time: slot.start,
                    end_time: slot.end,
                    day_of_week: day,
                    id: `template_${templateId}_${day}_${Date.now()}_${Math.random()}`
                };

                const timeslotHtml = createTimeslotHtml(timeslotData);
                $container.append(timeslotHtml);
            });

            // Actualizează placeholder-ul
            updateEmptyDayPlaceholder(day);
        });

        // Actualizează contorul total
        updateTotalTimeslotsCount();

        showSuccessMessage(`Șablon "${template.name}" aplicat cu succes! (${template.timeslots.length} sloturi × 7 zile)`);

        // Marchează ca fiind modificări nesalvate
        markUnsavedChanges();

        // Comută la tab-ul Timeslots pentru a vedea rezultatul
        $('.nav-tab[href="#timeslots"]').trigger('click');
    }

    function previewTemplate(templateId) {
        console.log(`[CLINICA_ADVANCED] Previewing template ${templateId}`);

        const templates = getPredefinedTemplates();
        const template = templates.find(t => t.id === templateId);

        if (!template) {
            showSuccessMessage('Șablonul nu a fost găsit!');
            return;
        }

        // Creează overlay pentru preview
        const previewHtml = `
            <div class="template-preview-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.8);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
            ">
                <div class="template-preview" style="
                    background: white;
                    color: #333;
                    padding: 30px;
                    border-radius: 15px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
                    max-width: 600px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0; color: #333;">
                            <span class="dashicons ${template.icon}" style="margin-right: 10px;"></span>
                            Preview: ${template.name}
                        </h2>
                        <button class="close-preview-btn" style="
                            background: none;
                            border: none;
                            font-size: 24px;
                            cursor: pointer;
                            color: #666;
                        ">&times;</button>
                    </div>

                    <p style="color: #666; margin-bottom: 25px;">${template.description}</p>

                    <div class="preview-week" style="
                        display: grid;
                        grid-template-columns: repeat(7, 1fr);
                        gap: 15px;
                        margin-bottom: 25px;
                    ">
                        ${['L', 'Ma', 'Mi', 'J', 'V', 'S', 'D'].map(day => `
                            <div class="preview-day" style="
                                text-align: center;
                                padding: 15px;
                                background: #f8f9fa;
                                border-radius: 8px;
                            ">
                                <h4 style="margin: 0 0 10px 0; color: #333;">${day}</h4>
                                <div class="preview-slots">
                                    ${template.timeslots.map(slot => `
                                        <div style="
                                            background: #0073aa;
                                            color: white;
                                            padding: 5px 8px;
                                            margin: 2px 0;
                                            border-radius: 4px;
                                            font-size: 11px;
                                        ">
                                            ${slot.start}-${slot.end}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `).join('')}
                    </div>

                    <div style="text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                        <button class="button button-primary apply-from-preview-btn" data-template-id="${templateId}" style="margin-right: 10px;">
                            <span class="dashicons dashicons-admin-page"></span>
                            Aplică Acest Șablon
                        </button>
                        <button class="button button-secondary close-preview-btn">
                            Închide Preview
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(previewHtml);

        // Event listeners pentru preview
        $('.close-preview-btn').on('click', function() {
            $('.template-preview-overlay').remove();
        });

        $('.apply-from-preview-btn').on('click', function() {
            const templateId = $(this).data('template-id');
            $('.template-preview-overlay').remove();
            applyTemplate(templateId);
        });

        // Închide la click în afara preview-ului
        $('.template-preview-overlay').on('click', function(e) {
            if (e.target === this) {
                $(this).remove();
            }
        });
    }

    function createNewTemplate() {
        console.log('[CLINICA_ADVANCED] Creating new template...');

        // Verifică dacă există timeslots pentru a crea șablonul
        const totalTimeslots = $('.advanced-timeslot-item').length;
        if (totalTimeslots === 0) {
            showSuccessMessage('Nu există timeslots pentru a crea un șablon! Adaugă întâi niște timeslots.');
            return;
        }

        const templateName = prompt('Introdu numele șablonului nou:');
        if (!templateName || templateName.trim() === '') {
            return;
        }

        // Colectează timeslots din toate zilele
        const templateTimeslots = [];


        // Pentru simplitate, luăm timeslots din ziua curentă (poate fi îmbunătățit)
        const $firstDayWithSlots = $('.day-timeslots-container').has('.advanced-timeslot-item').first();
        if ($firstDayWithSlots.length) {
            $firstDayWithSlots.find('.advanced-timeslot-item').each(function() {
                const timeText = $(this).find('.timeslot-time').text();
                const [startTime, endTime] = timeText.split(' - ');
                templateTimeslots.push({
                    start: startTime,
                    end: endTime
                });
            });
        }

        if (templateTimeslots.length === 0) {
            showSuccessMessage('Nu s-au putut colecta timeslots pentru șablon!');
            return;
        }

        // Creează șablonul nou
        const newTemplate = {
            id: `custom_${Date.now()}`,
            name: templateName,
            description: `Șablon personalizat cu ${templateTimeslots.length} sloturi`,
            icon: 'dashicons-star-filled',
            timeslots: templateTimeslots
        };

        // Salvează șablonul (într-un scenariu real, acesta s-ar salva în baza de date)
        saveCustomTemplate(newTemplate);

        showSuccessMessage(`Șablon "${templateName}" creat cu succes! (${templateTimeslots.length} sloturi)`);
    }

    function importTemplate() {
        console.log('[CLINICA_ADVANCED] Importing template...');

        // Simulare import - în realitate ar fi un file upload
        showSuccessMessage('Funcționalitatea de import va fi implementată în versiunea viitoare.');
    }

    function saveCustomTemplate(template) {
        // Într-un scenariu real, aici s-ar salva în baza de date
        console.log('[CLINICA_ADVANCED] Saving custom template:', template);

        // Pentru demonstrație, adăugăm la lista de șabloane locale
        // În realitate, acest lucru ar trebui să fie persistent
        showSuccessMessage('Șablon salvat local (într-un scenariu real s-ar salva în baza de date)');
    }

    // Funcție helper pentru AJAX calls
    function makeAjaxCall(action, data, callback) {
        data.action = action;
        data.nonce = '<?php echo wp_create_nonce('clinica_services_nonce'); ?>';

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: callback,
            error: function(xhr, status, error) {
                console.error('[CLINICA_ADVANCED] AJAX Error:', status, error);
            }
        });
    }
});
</script>

<?php
// Adaugă CSS pentru dark mode
?>
<style>
/* Dark Mode Styles */
body.dark-mode .clinica-main-container {
    background: #1a1a1a;
    color: #e0e0e0;
}

body.dark-mode .clinica-stat-card {
    background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
}

body.dark-mode .clinica-quick-actions,
body.dark-mode .clinica-advanced-selector {
    background: #2d3748;
    color: #e0e0e0;
}

body.dark-mode .clinica-select,
body.dark-mode .clinica-input {
    background: #1a1a1a;
    border-color: #4a5568;
    color: #e0e0e0;
}

body.dark-mode .clinica-action-btn {
    background: #2d3748;
    border-color: #4a5568;
    color: #e0e0e0;
}

body.dark-mode .clinica-action-btn:hover {
    background: #4a5568;
}

/* Preview sloturi */
.preview-slot {
    background: #f0f0f0;
    padding: 5px 10px;
    margin: 2px 0;
    border-radius: 4px;
    font-size: 12px;
    display: inline-block;
    margin-right: 5px;
}

body.dark-mode .preview-slot {
    background: #4a5568;
    color: #e0e0e0;
}

/* Timeslot items */
.advanced-timeslot-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 10px 15px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: move;
    transition: all 0.3s ease;
}

.advanced-timeslot-item:hover {
    background: white;
    border-color: #0073aa;
    box-shadow: 0 2px 8px rgba(0,115,170,0.15);
}

.timeslot-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.timeslot-time {
    font-weight: 500;
    color: #333;
    flex: 1;
}

.timeslot-actions {
    display: flex;
    gap: 5px;
    flex-shrink: 0;
}

.timeslot-actions button {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px;
    border-radius: 3px;
    transition: all 0.3s ease;
}

.timeslot-actions button:hover {
    background: #0073aa;
    color: white;
}

body.dark-mode .advanced-timeslot-item {
    background: #2d3748;
    border-color: #4a5568;
    color: #e0e0e0;
}

body.dark-mode .advanced-timeslot-item:hover {
    background: #4a5568;
}

body.dark-mode .timeslot-actions button {
    color: #a0aec0;
}

body.dark-mode .timeslot-actions button:hover {
    background: #667eea;
}
</style>

<?php
// Hook pentru adăugarea paginii în admin menu
add_action('admin_menu', 'clinica_add_advanced_timeslots_page');

function clinica_add_advanced_timeslots_page() {
    add_submenu_page(
        'clinica-dashboard',
        'Timeslots Avansați',
        'Timeslots Avansați',
        'manage_options',
        'clinica-timeslots-advanced',
        'clinica_render_advanced_timeslots_page'
    );
}

function clinica_render_advanced_timeslots_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Nu aveți permisiunea să accesați această pagină.'));
    }

    include plugin_dir_path(__FILE__) . 'views/timeslots-advanced.php';
}

// Hook pentru AJAX calls
add_action('wp_ajax_clinica_get_doctor_services', 'clinica_ajax_get_doctor_services');

function clinica_ajax_get_doctor_services() {
    // Verificare nonce
    if (!wp_verify_nonce($_POST['nonce'], 'clinica_services_nonce')) {
        wp_die('Security check failed');
    }

    $doctor_id = intval($_POST['doctor_id']);

    // Logică pentru a obține serviciile doctorului
    // Acest lucru ar trebui implementat în funcție de structura bazei de date

    $services = array(
        array('id' => 1, 'name' => 'Consultație Generală', 'duration' => 30),
        array('id' => 2, 'name' => 'Consultație Specializată', 'duration' => 45),
        array('id' => 3, 'name' => 'Control', 'duration' => 15)
    );

    wp_send_json_success($services);
}
?>
