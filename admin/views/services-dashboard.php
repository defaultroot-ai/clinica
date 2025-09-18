<?php
/**
 * Dashboard unificat pentru servicii, alocări doctori și timeslots
 * 
 * DESIGN: Material Design cu card-uri moderne și tab-uri animate
 */

if (!defined('ABSPATH')) {
    exit;
}

// Datele sunt transmise din metoda admin_services_dashboard()
// $services și $doctors sunt disponibile aici
        ?>
        <div class="wrap">
            <h1>Dashboard Servicii & Programare</h1>
            

            
            <!-- Dashboard Stats -->
            <div class="clinica-stats-grid">
                <div class="clinica-stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-clipboard"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count($services); ?></div>
                        <div class="stat-label">Total Servicii</div>
                    </div>
                </div>
                
                <div class="clinica-stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count($doctors); ?></div>
                        <div class="stat-label">Doctori Activi</div>
                    </div>
                </div>
                
                <div class="clinica-stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="total-timeslots">0</div>
                        <div class="stat-label">Timeslots Configurate</div>
                    </div>
                </div>
                
                <div class="clinica-stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="active-allocations">0</div>
                        <div class="stat-label">Alocări Active</div>
                    </div>
                </div>
            </div>
            
            <!-- Tab-uri principale -->
            <div class="clinica-tabs-container">
                <nav class="clinica-tabs-nav">
                    <button class="clinica-tab-btn active" data-tab="services">
                        <span class="dashicons dashicons-clipboard"></span>
                        Servicii
                    </button>
                    <button class="clinica-tab-btn" data-tab="allocations">
                        <span class="dashicons dashicons-admin-users"></span>
                        Alocări Doctori
                    </button>
                    <button class="clinica-tab-btn" data-tab="timeslots">
                        <span class="dashicons dashicons-clock"></span>
                        Timeslots
                    </button>
                    <button class="clinica-tab-btn" data-tab="schedule">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        Program General
                    </button>
                </nav>
                
                <!-- Conținutul tab-urilor -->
                <div class="clinica-tabs-content">
                    <!-- TAB 1: Servicii -->
                    <div class="clinica-tab-content active" id="tab-services">
                        <div class="clinica-tab-header">
                            <h2>Gestionare Servicii</h2>
                            <button class="button button-primary" id="add-service-btn">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                Adaugă Serviciu
                            </button>
                        </div>
                        
                        <div class="clinica-services-grid">
                            <?php foreach ($services as $service): ?>
                            <div class="clinica-service-card" data-service-id="<?php echo $service->id; ?>">
                                <div class="service-header">
                                    <h3><?php echo esc_html($service->name); ?></h3>
                                    <div class="service-actions">
                                        <button class="button button-small edit-service-btn" title="Editează">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button class="button button-small delete-service-btn" title="Șterge">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="service-details">
                                    <div class="service-duration">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo $service->duration; ?> min
                                    </div>
                                    <div class="service-doctors">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <?php echo $service->active_doctors; ?> doctori activi
                                    </div>
                                </div>
                                <div class="service-status">
                                    <span class="status-badge <?php echo $service->active ? 'active' : 'inactive'; ?>">
                                        <?php echo $service->active ? 'Activ' : 'Inactiv'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- TAB 2: Alocări Doctori -->
                    <div class="clinica-tab-content" id="tab-allocations">
                        <div class="clinica-tab-header">
                            <h2>Alocare Doctori la Servicii</h2>
                             <div class="clinica-allocations-controls">
                                 <div class="clinica-search-filters">
                                     <input type="text" id="search-services" placeholder="Caută servicii..." class="clinica-search-input">
                                     <input type="text" id="search-doctors" placeholder="Caută doctori..." class="clinica-search-input">
                                 </div>
                                                                   <div class="clinica-bulk-actions">
                                      <button type="button" class="button button-secondary" id="bulk-allocate-btn" disabled>
                                          <span class="dashicons dashicons-plus-alt2"></span>
                                          Alocă selectații
                                      </button>
                                      <button type="button" class="button button-secondary" id="bulk-deallocate-btn" disabled>
                                          <span class="dashicons dashicons-minus"></span>
                                          Dezalocă selectații
                                      </button>
                                      <button type="button" class="button button-small" id="clear-selections-btn" style="display: none;">
                                          <span class="dashicons dashicons-dismiss"></span>
                                          Curăță selecțiile
                                      </button>
                                  </div>
                            </div>
                        </div>
                        
                        <div class="clinica-allocations-container">
                            <div class="clinica-allocations-grid" id="allocations-grid">
                                <!-- Alocările se vor încărca aici dinamic -->
                            </div>
                              
                              <div class="clinica-allocations-help">
                                  <p><strong>💡 Ajutor:</strong></p>
                                  <ul>
                                      <li><strong>Click stânga</strong> pe toggle pentru a activa/dezactiva o alocare</li>
                                      <li><strong>Click dreapta</strong> pe toggle pentru a selecta/deselecta pentru operații bulk</li>
                                      <li>Folosiți câmpurile de căutare pentru a filtra serviciile și doctorii</li>
                                      <li>Operațiile bulk permit alocarea/dezalocarea mai multor doctori simultan</li>
                                  </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TAB 3: Timeslots -->
                    <div class="clinica-tab-content" id="tab-timeslots">
                        <div class="clinica-tab-header">
                            <h2>Gestionare Timeslots</h2>
                        </div>
                        
                        <!-- Container pentru selecția personalului medical -->
                        <div class="clinica-personnel-selection-container">
                            <h3>Selectează Personalul Medical</h3>
                            <p class="personnel-selection-description">Alege doctorul pentru care dorești să configurezi timeslots-urile:</p>
                            
                            <!-- Layout cu două coloane: carduri + rezumat -->
                            <div class="personnel-layout-container">
                                <!-- Carduri pentru personalul medical -->
                                <div class="personnel-cards-container">
                                    <?php foreach ($doctors as $doctor): ?>
                                    <div class="personnel-card" data-person-id="<?php echo $doctor->ID; ?>">
                                        <div class="personnel-card-content">
                                            <div class="personnel-info">
                                                <span class="dashicons dashicons-admin-users"></span>
                                                <span class="personnel-name"><?php echo esc_html($doctor->display_name); ?></span>
                                            </div>
                                            <button type="button" class="personnel-select-btn" data-person-id="<?php echo $doctor->ID; ?>">
                                                <span class="dashicons dashicons-plus"></span>
                                                Selectează
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Panoul de rezumat (apare doar când e selectat un medic) -->
                                <div class="doctor-summary-panel" id="doctor-summary-panel" style="display: none;">
                                    <h4 id="selected-doctor-name">Doctor selectat</h4>
                                    <div class="summary-grid" id="summary-grid">
                                        <!-- Conținutul se va încărca aici -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Container pentru configurarea timeslots-urilor -->
                        <div class="clinica-timeslots-configuration-container">
                            <h3>Configurare Timeslots</h3>
                            <div class="clinica-timeslot-selectors">
                                <select id="timeslot-doctor-selector">
                                    <option value="">Selectează doctorul</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor->ID; ?>">
                                        <?php echo esc_html($doctor->display_name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <select id="timeslot-service-selector" disabled>
                                    <option value="">Selectează serviciul</option>
                                </select>
                             
                             <button type="button" class="button" id="test-services-btn" title="Testează încărcarea serviciilor">
                                 <span class="dashicons dashicons-admin-tools"></span>
                                 Test
                             </button>
                             
                             <button type="button" class="button" id="debug-services-btn" title="Debug servicii disponibile">
                                 <span class="dashicons dashicons-admin-tools"></span>
                                 Debug
                             </button>
                            </div>
                        </div>
                        
                            <div class="clinica-timeslots-container" id="timeslots-container" style="display: none;">
                                <div class="clinica-week-grid">
                                    <?php
                                    $days = array(
                                        1 => 'Luni', 2 => 'Marți', 3 => 'Miercuri', 
                                         4 => 'Joi', 5 => 'Vineri'
                                    );
                                    
                                    foreach ($days as $day_num => $day_name):
                                    ?>
                                    <div class="clinica-day-column" data-day="<?php echo $day_num; ?>">
                                        <h4><?php echo $day_name; ?></h4>
                                        
                                        <div class="clinica-day-timeslots">
                                            <button type="button" class="button add-timeslot-btn" data-day="<?php echo $day_num; ?>">
                                                <span class="dashicons dashicons-plus-alt2"></span>
                                                Adaugă
                                            </button>
                                            
                                            <div class="clinica-timeslots-list" data-day="<?php echo $day_num; ?>">
                                                <!-- Timeslot-urile se vor încărca aici -->
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TAB 4: Program General -->
                    <div class="clinica-tab-content" id="tab-schedule">
                        <div class="clinica-tab-header">
                            <h2>Program General Clinică</h2>
                            <div class="clinica-schedule-filters">
                                <input type="date" id="schedule-date" value="<?php echo date('Y-m-d'); ?>">
                                <button class="button" id="refresh-schedule-btn">
                                    <span class="dashicons dashicons-update"></span>
                                    Actualizează
                                </button>
                            </div>
                        </div>
                        
                        <div class="clinica-schedule-overview" id="schedule-overview">
                            <!-- Programul general se va încărca aici -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal pentru adăugare/editare serviciu -->
            <div id="service-modal" class="clinica-modal" style="display: none;">
                <div class="clinica-modal-content">
                    <span class="clinica-modal-close">&times;</span>
                    <h3 id="service-modal-title">Adaugă Serviciu</h3>
                    
                    <form id="service-form">
                        <input type="hidden" id="service-id" name="service_id" value="">
                        
                        <div class="form-group">
                            <label for="service-name">Nume serviciu:</label>
                            <input type="text" id="service-name" name="service_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="service-duration">Durată (minute):</label>
                            <input type="number" id="service-duration" name="service_duration" min="15" max="240" step="15" required>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="service-active" name="service_active" checked>
                                Serviciu activ
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary">Salvează</button>
                            <button type="button" class="button button-secondary" id="cancel-service">Anulează</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Modal pentru timeslots -->
            <div id="timeslot-modal" class="clinica-modal" style="display: none;">
                <div class="clinica-modal-content">
                    <span class="clinica-modal-close">&times;</span>
                    <h3 id="timeslot-modal-title">Adaugă Timeslot</h3>
                    
                    <form id="timeslot-form">
                        <input type="hidden" id="timeslot-id" name="timeslot_id" value="">
                        <input type="hidden" id="timeslot-day" name="day_of_week" value="">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start-time">Ora început:</label>
                                  <input type="text" id="start-time" name="start_time" value="09:00" placeholder="09:00" pattern="^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$" required>
                                  <small class="form-help">Format 24h (ex: 09:00, 17:00) - fără AM/PM</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="end-time">Ora sfârșit:</label>
                                  <input type="text" id="end-time" name="end_time" value="17:00" placeholder="17:00" pattern="^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$" required>
                                  <small class="form-help">Format 24h (ex: 09:00, 17:00) - fără AM/PM</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="slot-duration">Durata slot-ului (minute):</label>
                             <div class="duration-selector">
                                 <select id="duration-type" name="duration_type">
                                     <option value="service">Durata serviciului (<?php echo '<span id="service-duration-display">30</span>'; ?> min)</option>
                                     <option value="custom">Durată custom</option>
                                 </select>
                                 <input type="number" id="slot-duration" name="slot_duration" min="1" max="480" value="30" required>
                             </div>
                             <small class="form-help">Alegeți între durata serviciului sau o durată custom</small>
                         </div>
                          
                          <!-- Sloturile generate automat -->
                          <div class="form-group">
                              <label>Sloturi generate automat:</label>
                              <div id="generated-slots" class="generated-slots-container">
                                  <!-- Sloturile se vor genera aici -->
                              </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary">Salvează</button>
                            <button type="button" class="button button-secondary" id="cancel-timeslot">Anulează</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- CSS pentru design modern -->
        <style>
        .clinica-dashboard-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0073aa;
            margin-bottom: 30px;
        }
        
        .clinica-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .clinica-stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .clinica-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0073aa, #46b450);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
            line-height: 1;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 4px;
        }
        
        .clinica-tabs-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .clinica-tabs-nav {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .clinica-tab-btn {
            background: none;
            border: none;
            padding: 16px 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-weight: 500;
            transition: all 0.2s;
            border-bottom: 3px solid transparent;
        }
        
        .clinica-tab-btn:hover {
            background: #e9ecef;
            color: #0073aa;
        }
        
        .clinica-tab-btn.active {
            color: #0073aa;
            border-bottom-color: #0073aa;
            background: white;
        }
        
        .clinica-tab-content {
            display: none;
             padding: 20px;
        }
        
        .clinica-tab-content.active {
            display: block;
        }
        
        .clinica-tab-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .clinica-services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .clinica-service-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.2s;
        }
        
        .clinica-service-card:hover {
            border-color: #0073aa;
            box-shadow: 0 4px 12px rgba(0,115,170,0.15);
        }
        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .service-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
        }
        
        .service-actions {
            display: flex;
            gap: 8px;
        }
        
        .service-details {
            display: flex;
            gap: 20px;
            margin-bottom: 16px;
        }
        
        .service-duration, .service-doctors {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #666;
            font-size: 14px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .clinica-filters {
            display: flex;
            gap: 12px;
        }
        
        .clinica-filters select {
            min-width: 200px;
        }
        
        /* CONTAINERE PENTRU TIMESLOTS */
        .clinica-personnel-selection-container {
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .clinica-personnel-selection-container h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
        
        .personnel-selection-description {
            margin: 0 0 15px 0;
            color: #666;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .clinica-timeslots-configuration-container {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
        }
        
        .clinica-timeslots-configuration-container h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
        
        /* LAYOUT CONTAINER PENTRU CARDURI + REZUMAT */
        .personnel-layout-container {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            min-height: 200px;
        }
        
        /* PERSONNEL CARDS - LAYOUT NOU ORIZONTAL */
        .personnel-cards-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            padding: 20px 0;
            margin-bottom: 20px;
            align-items: flex-start;
            justify-content: flex-start;
            flex: 1;
        }
        
        /* PANOUL DE REZUMAT - DESIGN MINIMALIST */
        .doctor-summary-panel {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 350px;
            min-width: 350px;
            max-width: 400px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .doctor-summary-panel h4 {
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px 15px;
            align-items: center;
        }
        
        .service-item {
            font-size: 14px;
            color: #333;
            padding: 4px 0;
        }
        
        .days-item {
            font-size: 13px;
            color: #666;
            background: #f5f5f5;
            padding: 4px 8px;
            border-radius: 3px;
            text-align: center;
            min-width: 60px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .personnel-layout-container {
                flex-direction: column;
            }
            
            .doctor-summary-panel {
                width: 100%;
                max-width: none;
            }
        }
        
        .personnel-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            transition: all 0.2s ease;
            cursor: pointer;
            width: 220px;
            min-width: 200px;
            max-width: 250px;
            flex: 1 1 220px;
            box-sizing: border-box;
            text-align: center;
        }
        
        .personnel-card:hover {
            border-color: #0073aa;
            background: #f8f9fa;
        }
        
        .personnel-card.selected {
            border-color: #0073aa;
            background: #e3f2fd;
        }
        
        .personnel-card.selected .personnel-select-btn {
            background-color: #0073aa;
            color: white;
        }
        
        .personnel-card.selected .personnel-select-btn .dashicons {
            color: white;
        }
        
        .personnel-card-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .personnel-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .personnel-info .dashicons {
            color: #0073aa;
            font-size: 24px;
        }
        
        .personnel-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
            text-align: center;
        }
        
        .personnel-select-btn {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            justify-content: center;
        }
        
        .personnel-select-btn:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        
        .personnel-select-btn .dashicons {
            font-size: 14px;
            color: #6c757d;
        }
        

        
        .clinica-week-grid {
            display: grid;
             grid-template-columns: repeat(5, 1fr);
             gap: 20px;
        }
        
        /* Responsive pentru carduri pe ecrane mici */
        @media (max-width: 768px) {
            .personnel-cards-container {
                justify-content: center;
                gap: 15px;
            }
            
            .personnel-card {
                width: 100%;
                min-width: 280px;
                flex: 1 1 280px;
            }
            
            .personnel-summary-panel {
                width: 100%;
                min-width: 100%;
                max-width: 100%;
            }
            
            .summary-content {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
             width: 100%;
         }
         
         .clinica-timeslots-container {
             width: 100%;
             margin-top: 20px;
        }
        
        .clinica-day-column {
            border: 1px solid #e9ecef;
             border-radius: 12px;
             padding: 20px;
             background: white;
             box-shadow: 0 2px 8px rgba(0,0,0,0.08);
             transition: all 0.2s ease;
         }
         
         .clinica-day-column:hover {
             box-shadow: 0 4px 16px rgba(0,0,0,0.12);
             transform: translateY(-2px);
        }
        
        .clinica-day-column h4 {
             margin: 0 0 20px 0;
            text-align: center;
             color: #0073aa;
             font-size: 16px;
             font-weight: 600;
             text-transform: uppercase;
             letter-spacing: 0.5px;
             padding-bottom: 12px;
             border-bottom: 2px solid #e9ecef;
        }
        
        .add-timeslot-btn {
            width: 100%;
             margin-bottom: 20px;
             padding: 12px 16px;
             background: #0073aa;
             border: none;
             border-radius: 8px;
             color: white;
             font-weight: 500;
             transition: all 0.2s ease;
         }
         
         .add-timeslot-btn:hover {
             background: #005a8b;
             transform: translateY(-1px);
             box-shadow: 0 4px 12px rgba(0,115,170,0.3);
        }
        
        .clinica-timeslots-list {
             min-height: 120px;
             padding: 8px 0;
         }
         
         .clinica-timeslot-item {
             background: #f8f9fa;
             border: 1px solid #e9ecef;
             border-radius: 8px;
             padding: 8px 12px;
             margin-bottom: 6px;
             transition: all 0.2s ease;
             display: flex;
             align-items: center;
             gap: 16px;
         }
         
         .clinica-timeslot-item:hover {
             background: white;
             border-color: #0073aa;
             box-shadow: 0 2px 8px rgba(0,115,170,0.15);
         }
         
         .timeslot-time {
             font-weight: 600;
             color: #0073aa;
             min-width: 120px;
         }
         
         .timeslot-duration {
             color: #666;
             font-size: 13px;
             min-width: 80px;
         }
         
         .timeslot-actions {
             display: flex;
             gap: 6px;
             margin-left: auto;
         }
         
         .timeslot-actions button {
             padding: 6px 10px;
             border: none;
             border-radius: 6px;
             cursor: pointer;
             font-size: 12px;
             transition: all 0.2s ease;
             min-width: 32px;
             height: 32px;
             display: flex;
             align-items: center;
             justify-content: center;
         }
         
         .edit-timeslot-btn {
             background: #28a745;
             color: white;
         }
         
         .edit-timeslot-btn:hover {
             background: #218838;
         }
         
         .delete-timeslot-btn {
             background: #dc3545;
             color: white;
         }
         
         .delete-timeslot-btn:hover {
             background: #c82333;
        }
        
        .clinica-modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
        }
        
        .clinica-modal-content {
            background-color: white;
            margin: 0;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            position: relative;
        }
        
        .clinica-modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }
        
        .clinica-modal-close:hover {
            color: #333;
        }
        
        /* Titlu modal cu padding pentru butonul de închidere */
        .clinica-modal-content h3 {
            margin: 0 0 25px 0;
            padding-right: 40px;
            color: #0073aa;
            font-size: 20px;
            font-weight: 600;
        }
        
        /* Scrollbar personalizat pentru modal */
        .clinica-modal-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .clinica-modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .clinica-modal-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .clinica-modal-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        /* Asigură-te că toate câmpurile din modal sunt vizibile */
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        /* Mesaje de ajutor pentru câmpuri */
        .form-help {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
            font-style: italic;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        /* Responsive pentru grid-ul de formular */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
        
        .form-actions {
            text-align: right;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            position: sticky;
            bottom: 0;
            background: white;
            padding-bottom: 10px;
        }
        
        .form-actions button {
            margin-left: 12px;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            font-size: 14px;
        }
        
        .form-actions button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .form-actions button:first-child {
            margin-left: 0;
        }
        
        /* Stilizare specifică pentru butoanele de acțiune */
        .form-actions .button-primary {
            background: #0073aa;
            color: white;
        }
        
        .form-actions .button-primary:hover {
            background: #005a87;
        }
        
        .form-actions .button-secondary {
            background: #6c757d;
            color: white;
        }
        
        .form-actions .button-secondary:hover {
            background: #5a6268;
        }
        
        .clinica-timeslot-selectors {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .clinica-timeslot-selectors select {
            min-width: 200px;
            flex: 1;
        }
        
        .clinica-timeslot-selectors .button {
            background: #0073aa;
            border: 1px solid #0073aa;
            color: white;
        }
        
        .clinica-timeslot-selectors .button:hover {
            background: #005a87;
            border-color: #005a87;
        }
        
        /* Responsive pentru selectorii de timeslot */
        @media (max-width: 768px) {
            .clinica-timeslot-selectors {
                flex-direction: column;
                gap: 8px;
            }
            
            .clinica-timeslot-selectors select {
                min-width: auto;
                width: 100%;
            }
        }
         
                   /* CSS pentru alocări îmbunătățite */
          .clinica-allocations-controls {
              display: flex;
              justify-content: space-between;
              align-items: center;
              gap: 20px;
              flex-wrap: wrap;
          }
          
          .clinica-search-filters {
              display: flex;
              gap: 12px;
              flex: 1;
          }
          
          .clinica-search-input {
              min-width: 200px;
              padding: 8px 12px;
              border: 1px solid #ddd;
              border-radius: 6px;
              font-size: 14px;
          }
          
          .clinica-bulk-actions {
              display: flex;
              gap: 8px;
          }
          
          .clinica-bulk-actions button:disabled {
              opacity: 0.5;
              cursor: not-allowed;
          }
          
          .clinica-allocations-grid {
              display: grid;
              grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
              gap: 20px;
              margin-top: 20px;
          }
          
          .clinica-service-allocation-card {
              background: white;
              border: 1px solid #e9ecef;
              border-radius: 12px;
              padding: 20px;
              transition: all 0.2s;
              position: relative;
          }
          
          .clinica-service-allocation-card:hover {
              border-color: #0073aa;
              box-shadow: 0 4px 12px rgba(0,115,170,0.15);
          }
          
          .clinica-service-allocation-card.expanded {
              border-color: #0073aa;
              background: #f8f9fa;
          }
          
          .service-allocation-header {
              display: flex;
              justify-content: space-between;
              align-items: flex-start;
              margin-bottom: 16px;
              cursor: pointer;
          }
          
          .service-allocation-header:hover .service-name {
              color: #0073aa;
          }
          
          .service-allocation-info {
              flex: 1;
          }
          
          .service-name {
              font-size: 18px;
              font-weight: 600;
              color: #333;
              margin: 0 0 8px 0;
              transition: color 0.2s;
          }
          
          .service-duration-badge {
              display: inline-flex;
              align-items: center;
              gap: 6px;
              background: #e9ecef;
              color: #495057;
              padding: 4px 8px;
              border-radius: 12px;
              font-size: 12px;
              font-weight: 500;
          }
          
          .service-duration-badge .dashicons {
              font-size: 12px;
              width: 12px;
              height: 12px;
          }
          
          .expand-status-button {
              display: flex;
              align-items: center;
              gap: 8px;
              padding: 8px 16px;
              border: 2px solid;
              border-radius: 20px;
              cursor: pointer;
              transition: all 0.3s;
              font-size: 14px;
              font-weight: 600;
              background: white;
              min-width: 120px;
              justify-content: center;
              user-select: none;
          }
          
          .expand-status-button:hover {
              transform: translateY(-2px);
              box-shadow: 0 4px 8px rgba(0,0,0,0.15);
          }
          
          .expand-status-button:active {
              transform: translateY(0);
              box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          }
          
          /* Stările pentru buton */
          .expand-status-button.active {
              border-color: #28a745;
              color: #28a745;
          }
          
          .expand-status-button.active:hover {
              background: #28a745;
              color: white;
          }
          
          .expand-status-button.partial {
              border-color: #ffc107;
              color: #ffc107;
          }
          
          .expand-status-button.partial:hover {
              background: #ffc107;
              color: #212529;
          }
          
          .expand-status-button.inactive {
              border-color: #dc3545;
              color: #dc3545;
          }
          
          .expand-status-button.inactive:hover {
              background: #dc3545;
              color: white;
          }
          
          /* Iconițele */
          .status-icon {
              font-size: 16px;
              display: inline-block;
              width: 16px;
              height: 16px;
              line-height: 16px;
              text-align: center;
              margin-right: 4px;
          }
          
          .status-icon.dashicons {
              font-size: 16px;
              width: 16px;
              height: 16px;
          }
          
          .expand-icon {
              font-size: 14px;
              transition: transform 0.3s;
              display: inline-block;
              width: 14px;
              height: 14px;
              line-height: 14px;
              text-align: center;
              margin-left: 4px;
          }
          
          .expand-icon.dashicons {
              font-size: 14px;
              width: 14px;
              height: 14px;
          }
          
          /* Starea expandată */
          .clinica-service-allocation-card.expanded .expand-icon {
              transform: rotate(180deg);
          }
          

          
          .doctors-allocation-list {
              display: none;
              margin-top: 16px;
              padding-top: 16px;
              border-top: 1px solid #e9ecef;
          }
          
          .clinica-service-allocation-card.expanded .doctors-allocation-list {
              display: block;
          }
          
          .doctor-allocation-item {
              display: flex;
              justify-content: space-between;
              align-items: center;
              padding: 12px;
              background: white;
              border: 1px solid #e9ecef;
              border-radius: 8px;
              margin-bottom: 8px;
              transition: all 0.2s;
          }
          
          .doctor-allocation-item:hover {
              border-color: #0073aa;
              background: #f8f9fa;
          }
          
          .doctor-info {
              display: flex;
              align-items: center;
              gap: 12px;
          }
          
          .doctor-avatar {
              width: 32px;
              height: 32px;
              border-radius: 50%;
              background: linear-gradient(135deg, #0073aa, #46b450);
              display: flex;
              align-items: center;
              justify-content: center;
              color: white;
              font-weight: 600;
              font-size: 14px;
          }
          
          .doctor-details h4 {
              margin: 0 0 4px 0;
              font-size: 14px;
              color: #333;
          }
          
          .doctor-email {
              font-size: 12px;
              color: #666;
              margin: 0;
          }
          
          .allocation-toggle {
              position: relative;
              width: 80px;
              height: 32px;
              background: #e9ecef;
              border: 2px solid #dee2e6;
              border-radius: 16px;
              cursor: pointer;
              transition: all 0.3s;
              display: flex;
              align-items: center;
              justify-content: center;
              font-size: 12px;
              font-weight: 600;
              color: #6c757d;
              user-select: none;
          }
          
          .allocation-toggle:hover {
              background: #dee2e6;
              border-color: #adb5bd;
              transform: translateY(-1px);
              box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          }
          
          .allocation-toggle.active {
              background: #28a745;
              border-color: #28a745;
              color: white;
          }
          
          .allocation-toggle.active:hover {
              background: #218838;
              border-color: #218838;
              transform: translateY(-1px);
              box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          }
          
          .allocation-toggle:active {
              transform: translateY(0);
              box-shadow: 0 1px 2px rgba(0,0,0,0.1);
          }
          
          .allocation-toggle::before {
              content: 'OFF';
              transition: all 0.3s;
          }
          
          .allocation-toggle.active::before {
              content: 'ON';
          }
           
           /* Stilizare pentru selecția multiplă */
           .allocation-toggle.selected-for-bulk {
               box-shadow: 0 0 0 3px #0073aa;
               border: 2px solid #0073aa;
               background: #e3f2fd;
           }
           
           .allocation-toggle.selected-for-bulk::after {
               content: '✓';
               position: absolute;
               top: -8px;
               right: -8px;
               width: 18px;
               height: 18px;
               background: #0073aa;
               color: white;
               border-radius: 50%;
               display: flex;
               align-items: center;
               justify-content: center;
               font-size: 11px;
               font-weight: bold;
               z-index: 1;
               border: 2px solid white;
           }
           
           /* Stilizare pentru progres bar */
           .bulk-progress h3 {
               margin: 0 0 15px 0;
               color: #333;
               text-align: center;
           }
           
           .bulk-progress .progress-bar {
               margin-bottom: 10px;
           }
           
           .bulk-progress .progress-text {
               margin: 0;
               text-align: center;
               color: #666;
               font-size: 14px;
           }
           
           /* Stilizare pentru mesajul de ajutor */
           .clinica-allocations-help {
               margin-top: 30px;
               padding: 20px;
               background: #f8f9fa;
               border: 1px solid #e9ecef;
               border-radius: 8px;
               border-left: 4px solid #0073aa;
           }
           
           .clinica-allocations-help p {
               margin: 0 0 15px 0;
               color: #333;
               font-weight: 500;
           }
           
           .clinica-allocations-help ul {
               margin: 0;
               padding-left: 20px;
               color: #666;
           }
           
           .clinica-allocations-help li {
               margin-bottom: 8px;
               line-height: 1.4;
           }
          
          .allocation-stats {
              display: flex;
              gap: 16px;
              margin-top: 12px;
              padding-top: 12px;
              border-top: 1px solid #e9ecef;
              font-size: 12px;
              color: #666;
          }
          
          .stat-item {
              display: flex;
              align-items: center;
              gap: 8px;
              margin-bottom: 8px;
          }
          
          .stat-item .dashicons {
              font-size: 16px;
              color: #0073aa;
              width: 16px;
              height: 16px;
          }
          
          .stat-number {
              font-weight: 600;
              color: #333;
          }
         
         /* CSS pentru programul general */
         .clinica-schedule-overview-content h3 {
             margin-bottom: 20px;
             color: #333;
         }
         
         .clinica-schedule-grid {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
             gap: 16px;
             margin-bottom: 30px;
         }
         
         .clinica-schedule-day {
             background: white;
             border: 1px solid #e9ecef;
             border-radius: 8px;
             padding: 16px;
             text-align: center;
         }
         
         .clinica-schedule-day.active {
             border-color: #28a745;
             background: #f8fff9;
         }
         
         .clinica-schedule-day.inactive {
             border-color: #dc3545;
             background: #fff8f8;
         }
         
         .clinica-schedule-day h4 {
             margin: 0 0 8px 0;
             color: #333;
             font-size: 16px;
         }
         
         .day-status {
             font-weight: 600;
             margin-bottom: 12px;
         }
         
         .clinica-schedule-day.active .day-status {
             color: #28a745;
         }
         
         .clinica-schedule-day.inactive .day-status {
             color: #dc3545;
         }
         
         .day-hours {
             font-size: 14px;
             color: #666;
         }
         
         .day-hours > div {
             margin-bottom: 4px;
         }
         
         .clinica-selected-date {
             background: white;
             border: 1px solid #e9ecef;
             border-radius: 8px;
             padding: 20px;
             margin-top: 20px;
         }
         
         .clinica-selected-date.closed {
             border-color: #dc3545;
             background: #fff8f8;
         }
         
         .clinica-selected-date h4 {
             margin: 0 0 16px 0;
             color: #333;
         }
         
         .clinica-selected-date p {
             margin: 8px 0;
             color: #666;
         }
         
                              .clinica-selected-date.closed p {
               color: #dc3545;
           }
           
           /* Stilizare pentru programul general îmbunătățit */
           .current-date-info {
               background: #e3f2fd;
               border: 1px solid #2196f3;
               border-radius: 8px;
               padding: 15px;
               margin-bottom: 20px;
           }
           
           .current-date-info p {
               margin: 8px 0;
               color: #1976d2;
           }
           
           .clinica-schedule-day.current-day {
               border-color: #2196f3;
               background: #e3f2fd;
               position: relative;
           }
           
           .current-day-indicator {
               position: absolute;
               top: -8px;
               right: -8px;
               background: #2196f3;
               color: white;
               padding: 4px 8px;
               border-radius: 12px;
               font-size: 10px;
               font-weight: 600;
               text-transform: uppercase;
           }
           
           .hours-main {
               font-weight: 600;
               color: #333;
               margin-bottom: 4px;
           }
           
           .hours-break {
               font-size: 12px;
               color: #666;
               font-style: italic;
           }
           
           .day-hours.closed {
               color: #999;
               font-style: italic;
           }
           

           
           .schedule-loading, .schedule-error {
               text-align: center;
               padding: 40px 20px;
               color: #666;
           }
           
           .schedule-error {
               color: #dc3545;
               background: #f8d7da;
               border: 1px solid #f5c6cb;
               border-radius: 8px;
           }
          
          /* CSS pentru sloturile generate automat */
          .generated-slots-container {
              margin-top: 12px;
              padding: 12px;
              background: #f8f9fa;
              border-radius: 6px;
              border: 1px solid #e9ecef;
          }
          
          .slots-info {
              margin-bottom: 10px;
              padding-bottom: 6px;
              border-bottom: 1px solid #dee2e6;
          }
          
          .slots-info p {
              margin: 4px 0;
              color: #495057;
              font-size: 13px;
              font-weight: 500;
          }
          
          /* Stilizare pentru informațiile despre sloturi */
          .slots-info .slot-count {
              color: #0073aa;
              font-weight: 600;
          }
          
          .slots-info .slot-duration {
              color: #28a745;
              font-weight: 600;
          }
          
          .slots-info .slot-interval {
              color: #6c757d;
              font-weight: 600;
          }
          
          .slots-grid {
              display: grid;
              grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
              gap: 8px;
              max-height: 180px;
              overflow-y: auto;
              margin-bottom: 15px;
          }
          
          /* Scrollbar personalizat pentru grid-ul de sloturi */
          .slots-grid::-webkit-scrollbar {
              width: 4px;
          }
          
          .slots-grid::-webkit-scrollbar-track {
              background: #f1f1f1;
              border-radius: 2px;
          }
          
          .slots-grid::-webkit-scrollbar-thumb {
              background: #c1c1c1;
              border-radius: 2px;
          }
          
          .slots-grid::-webkit-scrollbar-thumb:hover {
              background: #a8a8a8;
          }
          
          /* Asigură-te că sloturile generate nu depășesc modalul */
          .generated-slots-container {
              max-height: 50vh;
              overflow-y: auto;
              margin-bottom: 20px;
          }
          
          /* Scrollbar personalizat pentru container-ul de sloturi */
          .generated-slots-container::-webkit-scrollbar {
              width: 4px;
          }
          
          .generated-slots-container::-webkit-scrollbar-track {
              background: #f1f1f1;
              border-radius: 2px;
          }
          
          .generated-slots-container::-webkit-scrollbar-thumb {
              background: #c1c1c1;
              border-radius: 2px;
          }
          
          .generated-slots-container::-webkit-scrollbar-thumb:hover {
              background: #a8a8a8;
          }
          
          /* CSS pentru grupurile de perioade */
          .slots-periods {
              margin: 16px 0;
          }
          
          .period-group {
              background: white;
              border: 1px solid #e9ecef;
              border-radius: 12px;
              margin-bottom: 12px;
              overflow: hidden;
              box-shadow: 0 2px 8px rgba(0,0,0,0.08);
          }
          
          .period-header {
              background: linear-gradient(135deg, #0073aa, #46b450);
              color: white;
              padding: 12px 16px;
              display: flex;
              justify-content: space-between;
              align-items: center;
          }
          
          .period-header h4 {
              margin: 0;
              font-size: 14px;
              font-weight: 600;
          }
          
          .period-count {
              background: rgba(255,255,255,0.2);
              padding: 3px 10px;
              border-radius: 20px;
              font-size: 12px;
              font-weight: 500;
          }
          
          .period-slots {
              padding: 20px;
              display: grid;
              grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
              gap: 8px;
              max-height: 250px;
              overflow-y: auto;
          }
          
          /* Scrollbar personalizat pentru sloturile de perioadă */
          .period-slots::-webkit-scrollbar {
              width: 4px;
          }
          
          .period-slots::-webkit-scrollbar-track {
              background: #f1f1f1;
              border-radius: 2px;
          }
          
          .period-slots::-webkit-scrollbar-thumb {
              background: #c1c1c1;
              border-radius: 2px;
          }
          
          .period-slots::-webkit-scrollbar-thumb:hover {
              background: #a8a8a8;
          }
          
          .period-actions {
              padding: 12px 16px;
              background: #f8f9fa;
              border-top: 1px solid #e9ecef;
              text-align: center;
          }
          
          .period-toggle {
              background: #6c757d;
              border-color: #6c757d;
              color: white;
              padding: 6px 12px;
              border-radius: 6px;
              font-size: 12px;
              font-weight: 500;
              cursor: pointer;
              transition: all 0.2s ease;
              display: flex;
              align-items: center;
              gap: 6px;
          }
          
          .period-toggle:hover {
              background: #5a6268;
              border-color: #5a6268;
              transform: translateY(-1px);
              box-shadow: 0 2px 8px rgba(0,0,0,0.15);
          }
          
          .time-slot {
              background: white;
              border: 1px solid #dee2e6;
              border-radius: 4px;
              padding: 6px 8px;
              text-align: center;
              font-size: 13px;
              color: #495057;
              transition: all 0.2s ease;
              display: flex;
              align-items: center;
              justify-content: space-between;
              gap: 8px;
          }
          
          .time-slot:hover {
              background: #f8f9fa;
              border-color: #0073aa;
              transform: translateY(-1px);
              box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          }
          
          .time-slot .slot-time {
              font-weight: 600;
              color: #0073aa;
              flex: 1;
          }
          
          .time-slot .slot-duration {
              color: #666;
              font-size: 12px;
              background: #f8f9fa;
              padding: 2px 6px;
              border-radius: 3px;
              white-space: nowrap;
          }
              font-size: 12px;
              color: #495057;
              transition: all 0.2s;
          }
          
                     .time-slot:hover {
               border-color: #0073aa;
               background: #f8f9fa;
           }
           
           .slot-time {
               font-weight: 500;
           }
           
           /* Stilizare pentru sloturile generate */
           .slots-actions {
               margin-top: 20px;
               text-align: center;
               padding-top: 15px;
               border-top: 1px solid #dee2e6;
           }
           
           .slots-actions button {
               padding: 12px 24px;
               font-size: 16px;
               font-weight: 500;
           }
           
           .slot-duration {
               display: block;
               font-size: 10px;
               color: #6c757d;
               margin-top: 4px;
           }
          
          .no-slots {
              color: #6c757d;
              font-style: italic;
              text-align: center;
              margin: 20px 0;
          }
          
          .error-message {
              color: #dc3545;
              font-weight: 500;
              text-align: center;
              margin: 20px 0;
              padding: 10px;
              background: #f8d7da;
              border-radius: 4px;
              border: 1px solid #f5c6cb;
          }
          
          /* CSS pentru formatul orei 24H */
          .form-help {
              display: block;
              margin-top: 4px;
              font-size: 12px;
              color: #6c757d;
              font-style: italic;
          }
          
          /* Stilizează câmpurile de oră pentru formatul 24H */
          #start-time, #end-time {
              font-family: 'Courier New', monospace;
              text-align: center;
              font-size: 16px;
              font-weight: 500;
              letter-spacing: 1px;
              background: #f8f9fa;
              border: 2px solid #e9ecef;
              transition: all 0.2s;
          }
          
          #start-time:focus, #end-time:focus {
              background: white;
              border-color: #0073aa;
              box-shadow: 0 0 0 3px rgba(0,115,170,0.1);
              outline: none;
          }
          
                     #start-time::placeholder, #end-time::placeholder {
               color: #adb5bd;
               font-weight: 400;
           }
           
           /* CSS pentru selectorul de durată */
           .duration-selector {
               display: flex;
               gap: 10px;
               align-items: center;
           }
           
           .duration-selector select {
               flex: 0 0 auto;
               min-width: 200px;
           }
           
           .duration-selector input {
               flex: 1;
           }
           
           #slot-duration:disabled {
               background-color: #f5f5f5;
               color: #999;
               cursor: not-allowed;
        }
        </style>
        
        <!-- JavaScript pentru funcționalitate -->
        <script>
        jQuery(document).ready(function($) {
            console.log('[CLINICA_DEBUG] Services Dashboard initialized');
            
            // Verifică dacă ajaxurl este disponibil
            if (typeof ajaxurl === 'undefined') {
                console.error('[CLINICA_DEBUG] ajaxurl is not defined! This will cause AJAX calls to fail.');
                // Încearcă să găsească ajaxurl din alte locuri
                if (typeof window.ajaxurl !== 'undefined') {
                    window.ajaxurl = window.ajaxurl;
                    console.log('[CLINICA_DEBUG] Found ajaxurl in window object');
                } else {
                    // Fallback pentru ajaxurl
                    window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                    console.log('[CLINICA_DEBUG] Set fallback ajaxurl:', window.ajaxurl);
                }
            }
            
            // Variabile globale
            var selectedDoctor = null;
            var selectedService = null;
            var servicesManager = null;
            
            // Inițializare
            init();
            
            function init() {
                // Inițializează Services Manager
                if (typeof Clinica_Services_Manager !== 'undefined') {
                    servicesManager = Clinica_Services_Manager.get_instance();
                }
                 
                 // Restorează tab-ul activ din localStorage
                 restoreActiveTab();
                 

                
                // Încarcă statisticile
                loadStats();
                
                // Încarcă alocările
                loadAllocations();
                
                // Setup event listeners
                setupEventListeners();
                
                // Încarcă timeslots-urile dacă sunt selectați doctorul și serviciul
                if (selectedDoctor && selectedService) {
                    loadTimeslots(selectedDoctor, selectedService);
                }
                
                // Încarcă timeslots-urile dacă sunt selectați din selectoare
                var initialDoctor = $('#timeslot-doctor-selector').val();
                var initialService = $('#timeslot-service-selector').val();
                if (initialDoctor && initialService) {
                    selectedDoctor = initialDoctor;
                    selectedService = initialService;
                    loadTimeslots(selectedDoctor, selectedService);
                }
                
                // Încarcă timeslots-urile dacă sunt selectați din selectoare
                var initialDoctor = $('#timeslot-doctor-selector').val();
                var initialService = $('#timeslot-service-selector').val();
                if (initialDoctor && initialService) {
                    selectedDoctor = initialDoctor;
                    selectedService = initialService;
                    loadTimeslots(selectedDoctor, selectedService);
                }
            }
            
            function setupEventListeners() {
                // Tab navigation
                $('.clinica-tab-btn').on('click', function() {
                    var tabId = $(this).data('tab');
                    switchTab(tabId);
                });
                
                // Service management
                $('#add-service-btn').on('click', function() {
                    openServiceModal('add');
                });
                
                $('.edit-service-btn').on('click', function() {
                    var serviceId = $(this).closest('.clinica-service-card').data('service-id');
                    openServiceModal('edit', serviceId);
                });
                
                $('.delete-service-btn').on('click', function() {
                    var serviceId = $(this).closest('.clinica-service-card').data('service-id');
                    deleteService(serviceId);
                });
                
                // Service form
                $('#service-form').on('submit', function(e) {
                    e.preventDefault();
                    saveService();
                });
                
                // Personnel cards selection - NOU
                $(document).on('click', '.personnel-select-btn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var personId = $(this).data('person-id');
                    var $card = $(this).closest('.personnel-card');
                    
                    // Dacă cardul este deja selectat, deselectează-l
                    if ($card.hasClass('selected')) {
                        $card.removeClass('selected');
                        $(this).html('<span class="dashicons dashicons-plus"></span>Selectează');
                        $('#timeslot-doctor-selector').val('');
                        selectedDoctor = null;
                        $('#timeslot-service-selector').prop('disabled', true).val('');
                        $('#timeslots-container').hide();
                        $('#total-timeslots').text('0');
                        // Ascunde panoul de rezumat
                        $('#doctor-summary-panel').hide();
                    } else {
                        // Deselectează toate cardurile și selectează pe cel curent
                        $('.personnel-card').removeClass('selected');
                        $card.addClass('selected');
                        $(this).html('<span class="dashicons dashicons-yes"></span>Selectat');
                        
                        // Setează valoarea în selector și declanșează change event
                        $('#timeslot-doctor-selector').val(personId).trigger('change');
                        
                        // Afișează panoul de rezumat
                        showDoctorSummary(personId);
                    }
                });
                
                // Timeslot selectors
                $('#timeslot-doctor-selector').on('change', function() {
                    selectedDoctor = $(this).val();
                    $('#timeslot-service-selector').prop('disabled', !selectedDoctor);
                    
                    // Sincronizează starea cardurilor cu selectorul
                    $('.personnel-card').removeClass('selected');
                    if (selectedDoctor) {
                        $('.personnel-card[data-person-id="' + selectedDoctor + '"]').addClass('selected');
                        $('.personnel-card[data-person-id="' + selectedDoctor + '"] .personnel-select-btn').html('<span class="dashicons dashicons-yes"></span>Selectat');
                        loadServicesForDoctor(selectedDoctor);
                        // Resetează serviciul și timeslots-urile când se schimbă doctorul
                        $('#timeslot-service-selector').val('').prop('disabled', false);
                        $('#timeslots-container').hide();
                        $('#total-timeslots').text('0');
                    }
                });
                
                $('#timeslot-service-selector').on('change', function() {
                    selectedService = $(this).val();
                    if (selectedService) {
                        $('#timeslots-container').show();
                        loadTimeslots(selectedDoctor, selectedService);
                    } else {
                        $('#timeslots-container').hide();
                        // Resetează contorul când nu este selectat niciun serviciu
                        $('#total-timeslots').text('0');
                    }
                });
                
                // Timeslot management
                $(document).on('click', '.add-timeslot-btn', function() {
                    var day = $(this).data('day');
                    openTimeslotModal('add', day);
                });
                
                                 // Timeslot form submission
                 $('#timeslot-form').on('submit', function(e) {
                     e.preventDefault();
                     saveTimeslot();
                 });
                 
                 // Regenerare automată a sloturilor când se schimbă ora sau durata
                 $('#start-time, #end-time, #slot-duration').off('change').on('change', function() {
                     // Debounce pentru a evita apeluri multiple
                     clearTimeout(window.slotGenerationTimeout);
                     window.slotGenerationTimeout = setTimeout(function() {
                         generateTimeSlots();
                     }, 300);
                 });
                 
                 // Gestionare selector de durată
                 $('#duration-type').off('change').on('change', function() {
                     var durationType = $(this).val();
                     var $slotDuration = $('#slot-duration');
                     
                     if (durationType === 'service') {
                         // Setează durata din serviciul selectat
                         var selectedService = $('#timeslot-service-selector option:selected');
                         if (selectedService.length && selectedService.data('duration')) {
                             $slotDuration.val(selectedService.data('duration'));
                             $slotDuration.prop('disabled', true);
                         }
                     } else {
                         // Permite durată custom
                         $slotDuration.prop('disabled', false);
                         $slotDuration.focus();
                     }
                     
                     // Regenerează sloturile cu debounce
                     clearTimeout(window.slotGenerationTimeout);
                     window.slotGenerationTimeout = setTimeout(function() {
                         generateTimeSlots();
                     }, 300);
                 });
                 
                 // Validare simplă pentru câmpurile de oră
                  $('#start-time, #end-time').off('input').on('input', function() {
                      var value = $(this).val();
                      var $input = $(this);
                      var cursorPos = $input[0].selectionStart;
                      
                      // Permite doar cifre și :
                      if (!/^[0-9:]*$/.test(value)) {
                          // Restorează valoarea anterioară dacă s-a introdus caracter invalid
                          $input.val(value.replace(/[^0-9:]/g, ''));
                          return;
                      }
                      
                      // Nu mai generăm sloturile la fiecare input - doar la change
                  });
                  
                  // Păstrează poziția cursorului după modificări
                  $('#start-time, #end-time').on('keyup', function() {
                      var $input = $(this);
                      var value = $input.val();
                      
                      // Dacă utilizatorul introduce cifre, ajută cu formatarea
                      if (value.length === 2 && !value.includes(':')) {
                          // Adaugă : după ore
                          $input.val(value + ':');
                          // Poziționează cursorul după :
                          $input[0].setSelectionRange(3, 3);
                      }
                  });
                  
                  // Validare la focus out - doar verifică formatul final
                  $('#start-time, #end-time').on('blur', function() {
                      var value = $(this).val();
                      if (value) {
                          // Verifică dacă ora este în format valid
                          if (!/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(value)) {
                              // Încearcă să corecteze formatul
                              var corrected = correctTimeFormat(value);
                              if (corrected) {
                                  $(this).val(corrected);
                              } else {
                                  alert('Introduceți ora în format 24h (ex: 09:00, 17:00)');
                                  $(this).focus();
                              }
                          }
                      }
                  });
                  
                  // Funcție helper pentru corectarea formatului orei
                  function correctTimeFormat(timeStr) {
                      // Elimină spațiile și caracterele invalide
                      timeStr = timeStr.replace(/[^0-9:]/g, '');
                      
                      if (!timeStr.includes(':')) {
                          // Dacă nu are :, adaugă după primele 2 cifre
                          if (timeStr.length >= 2) {
                              timeStr = timeStr.substring(0, 2) + ':' + timeStr.substring(2);
                          }
                      }
                      
                      var parts = timeStr.split(':');
                      if (parts.length !== 2) return null;
                      
                      var hours = parseInt(parts[0]) || 0;
                      var minutes = parseInt(parts[1]) || 0;
                      
                      // Validează orele (0-23) și minutele (0-59)
                      if (hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59) {
                          return (hours < 10 ? '0' : '') + hours + ':' + (minutes < 10 ? '0' : '') + minutes;
                      }
                      
                      return null;
                  }
                
                // Test button for services
                $('#test-services-btn').on('click', function() {
                    console.log('[CLINICA_DEBUG] Test button clicked');
                    var doctorId = $('#timeslot-doctor-selector').val();
                    if (doctorId) {
                        console.log('[CLINICA_DEBUG] Testing with doctor ID:', doctorId);
                        loadServicesForDoctor(doctorId);
                    } else {
                        console.log('[CLINICA_DEBUG] No doctor selected for test');
                        alert('Selectați un doctor pentru test');
                    }
                });
                
                // Debug button for services
                $('#debug-services-btn').on('click', function() {
                    console.log('[CLINICA_DEBUG] Debug button clicked');
                    debugAvailableServices();
                });
                
                // Modal close
                 $('.clinica-modal-close').on('click', function() {
                    $('.clinica-modal').hide();
                });
                
                 // Cancel buttons
                 $('#cancel-timeslot').on('click', function() {
                     $('#timeslot-modal').hide();
                 });
                 
                 $('#cancel-service').on('click', function() {
                     $('#service-modal').hide();
                 });
                
                                 // Căutare și filtrare pentru alocări
                 $('#search-services, #search-doctors').on('input', function() {
                     filterAllocations();
                 });
                 
                                   // Acțiuni bulk pentru alocări
                  $('#bulk-allocate-btn').on('click', function() {
                      bulkAllocateDoctors();
                  });
                  
                  $('#bulk-deallocate-btn').on('click', function() {
                      bulkDeallocateDoctors();
                  });
                  
                  // Click dreapta pe toggle-uri pentru selecție multiplă
                  $(document).on('contextmenu', '.allocation-toggle', function(e) {
                      e.preventDefault();
                      var $toggle = $(this);
                      $toggle.toggleClass('selected-for-bulk');
                      updateBulkButtonsState();
                  });
                  
                  // Click pe header-ul cardului pentru expandare
                  $(document).on('click', '.service-allocation-header', function(e) {
                      if (!$(e.target).hasClass('expand-status-button') && !$(e.target).closest('.expand-status-button').length) {
                          toggleServiceCard(this);
                      }
                  });
                  
                  // Click pe butonul de expandare cu status
                  $(document).on('click', '.expand-status-button', function(e) {
                      e.stopPropagation();
                      toggleServiceCard($(this).closest('.service-allocation-header'));
                  });
                  
                  // Click stânga pentru toggle normal
                  $(document).on('click', '.allocation-toggle', function(e) {
                      console.log('[CLINICA_DEBUG] Toggle clicked:', this);
                      if (!$(this).hasClass('selected-for-bulk')) {
                          var doctorId = $(this).data('doctor-id');
                          var serviceId = $(this).data('service-id');
                          console.log('[CLINICA_DEBUG] Toggle data:', {doctorId, serviceId});
                          toggleDoctorAllocation(this, doctorId, serviceId);
                      }
                  });
                  
                  // Buton pentru curățarea selecțiilor
                  $('#clear-selections-btn').on('click', function() {
                      $('.allocation-toggle.selected-for-bulk').removeClass('selected-for-bulk');
                      updateBulkButtonsState();
                  });
            }
            
            function switchTab(tabId) {
                // Update active tab button
                $('.clinica-tab-btn').removeClass('active');
                $('[data-tab="' + tabId + '"]').addClass('active');
                
                // Update active tab content
                $('.clinica-tab-content').removeClass('active');
                $('#tab-' + tabId).addClass('active');
                 
                 // Salvează tab-ul activ în localStorage
                 localStorage.setItem('clinica_active_tab', tabId);
                
                // Load tab-specific content
                switch(tabId) {
                    case 'allocations':
                        loadAllocations();
                        break;
                    case 'timeslots':
                        // Timeslots are loaded when doctor/service are selected
                        if (selectedDoctor && selectedService) {
                            loadTimeslots(selectedDoctor, selectedService);
                        }
                        break;
                    case 'schedule':
                        loadScheduleOverview();
                        break;
                }
            }
            
            function loadStats() {
                // Load timeslots count
                if (servicesManager) {
                    // This would be an AJAX call to get total timeslots
                    $('#total-timeslots').text('0');
                }
                
                // Load active allocations count
                var activeAllocations = 0;
                $('.clinica-service-card').each(function() {
                    var activeDoctors = parseInt($(this).find('.service-doctors').text().match(/\d+/)[0]);
                    activeAllocations += activeDoctors;
                });
                $('#active-allocations').text(activeAllocations);
            }
            
            function loadAllocations() {
                console.log('[CLINICA_DEBUG] Loading allocations...');
                console.log('[CLINICA_DEBUG] ajaxurl:', ajaxurl);
                
                // Încarcă alocările existente din baza de date
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clinica_get_service_doctors',
                        nonce: '<?php echo wp_create_nonce('clinica_services_nonce'); ?>',
                        service_id: 0 // 0 = toate serviciile
                    },
                    success: function(response) {
                        console.log('[CLINICA_DEBUG] Allocations AJAX response:', response);
                        if (response.success) {
                            renderAllocations(response.data);
                        } else {
                            console.error('[CLINICA_DEBUG] Error loading allocations:', response.data);
                            // Fallback cu array gol
                            renderAllocations([]);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[CLINICA_DEBUG] Failed to load allocations:', status, error);
                        console.error('[CLINICA_DEBUG] XHR response:', xhr.responseText);
                        // Fallback cu array gol
                        renderAllocations([]);
                    }
                });
            }
            
                         function renderAllocations(doctors) {
                 var html = '';
                 
                 // Grupează alocările pe servicii
                 var allocationsByService = {};
                 
                 // Inițializează toate serviciile
                 <?php foreach ($services as $service): ?>
                 allocationsByService[<?php echo $service->id; ?>] = {
                     service: {
                         id: <?php echo $service->id; ?>,
                         name: '<?php echo esc_js($service->name); ?>',
                         duration: <?php echo $service->duration; ?>,
                         active: <?php echo $service->active ? 'true' : 'false'; ?>
                     },
                     doctors: [],
                     totalDoctors: <?php echo count($doctors); ?>,
                     allocatedDoctors: 0
                 };
                 <?php endforeach; ?>
                 
                                   // Populează cu datele reale de alocări
                  if (doctors && doctors.length > 0) {
                      console.log('[CLINICA_DEBUG] Processing', doctors.length, 'doctor allocations');
                      doctors.forEach(function(doctor) {
                          console.log('[CLINICA_DEBUG] Processing doctor:', doctor);
                          if (doctor.service_id && allocationsByService[doctor.service_id]) {
                              allocationsByService[doctor.service_id].doctors.push(doctor);
                              if (doctor.active == 1) {
                                  allocationsByService[doctor.service_id].allocatedDoctors++;
                                  console.log('[CLINICA_DEBUG] Doctor', doctor.doctor_id, 'is active for service', doctor.service_id);
                              }
                          } else {
                              console.log('[CLINICA_DEBUG] Doctor', doctor.doctor_id, 'not processed - service_id:', doctor.service_id, 'exists:', !!allocationsByService[doctor.service_id]);
                          }
                      });
                  } else {
                      console.log('[CLINICA_DEBUG] No doctor allocations found');
                  }
                  
                  // Debug: afișează structura finală
                  console.log('[CLINICA_DEBUG] Final allocationsByService structure:', allocationsByService);
                  
                  console.log('[CLINICA_DEBUG] Allocations data:', doctors);
                  console.log('[CLINICA_DEBUG] Processed allocations:', allocationsByService);
                 
                 // Renderizează card-urile pentru fiecare serviciu
                 Object.keys(allocationsByService).forEach(function(serviceId) {
                     var serviceData = allocationsByService[serviceId];
                     var service = serviceData.service;
                     var allocatedCount = serviceData.allocatedDoctors;
                     var totalCount = serviceData.totalDoctors;
                     
                                           // Determină statusul indicatorului
                      var statusClass = 'inactive';
                      if (allocatedCount > 0 && allocatedCount < totalCount) {
                          statusClass = 'partial';
                      } else if (allocatedCount === totalCount && totalCount > 0) {
                          statusClass = 'active';
                      } else if (allocatedCount === 0) {
                          statusClass = 'inactive';
                      }
                      
                      console.log('[CLINICA_DEBUG] Service', service.id, 'status:', statusClass, 'allocated:', allocatedCount, 'total:', totalCount);
                     
                     html += '<div class="clinica-service-allocation-card" data-service-id="' + service.id + '">';
                     html += '<div class="service-allocation-header" style="cursor: pointer; user-select: none;">';
                     html += '<div class="service-allocation-info">';
                     html += '<h3 class="service-name">' + service.name + '</h3>';
                     html += '<div class="service-duration-badge">';
                     html += '<span class="dashicons dashicons-clock"></span>';
                     html += service.duration + ' min';
                     html += '</div>';
                     html += '</div>';
                     // Buton de expandare cu status integrat
                     var statusText = '';
                     var statusIcon = '';
                     if (statusClass === 'active') {
                         statusText = 'Alocați: ' + allocatedCount + '/' + totalCount;
                         statusIcon = 'dashicons dashicons-yes-alt';
                     } else if (statusClass === 'partial') {
                         statusText = 'Alocați: ' + allocatedCount + '/' + totalCount;
                         statusIcon = 'dashicons dashicons-warning';
                     } else {
                         statusText = 'Alocați: ' + allocatedCount + '/' + totalCount;
                         statusIcon = 'dashicons dashicons-no-alt';
                     }
                     
                     html += '<button class="expand-status-button ' + statusClass + '">';
                     html += '<span class="status-icon ' + statusIcon + '"></span>';
                     html += '<span class="status-text">' + statusText + '</span>';
                     html += '<span class="expand-icon dashicons dashicons-arrow-down-alt2"></span>';
                     html += '</button>';
                     html += '</div>';
                     
                     // Statistici rapide
                     html += '<div class="allocation-stats">';
                     html += '<div class="stat-item">';
                     html += '<span class="dashicons dashicons-admin-users"></span>';
                     html += '<span>Alocați: <span class="stat-number">' + allocatedCount + '</span></span>';
                     html += '</div>';
                     html += '<div class="stat-item">';
                     html += '<span class="dashicons dashicons-groups"></span>';
                     html += '<span>Total: <span class="stat-number">' + totalCount + '</span></span>';
                     html += '</div>';
                     html += '<div class="stat-item">';
                     html += '<span class="dashicons dashicons-clock"></span>';
                     html += '<span>Status: <span class="stat-number">' + (service.active ? 'Activ' : 'Inactiv') + '</span></span>';
                     html += '</div>';
                     html += '</div>';
                     
                     // Lista doctorilor (expandabilă)
                     html += '<div class="doctors-allocation-list">';
                     
                     <?php foreach ($doctors as $doctor): ?>
                     var doctorId = <?php echo $doctor->ID; ?>;
                     var doctorName = '<?php echo esc_js($doctor->display_name); ?>';
                     var doctorEmail = '<?php echo esc_js($doctor->user_email); ?>';
                     var doctorInitials = doctorName.split(' ').map(n => n[0]).join('').toUpperCase();
                     
                                           // Verifică dacă doctorul este alocat la acest serviciu
                      var isAllocated = false;
                      var allocationActive = false;
                      
                      if (serviceData.doctors && serviceData.doctors.length > 0) {
                          var existingAllocation = serviceData.doctors.find(d => d.doctor_id == doctorId);
                          if (existingAllocation) {
                              isAllocated = true;
                              allocationActive = existingAllocation.active == 1;
                              console.log('[CLINICA_DEBUG] Doctor', doctorId, 'allocated to service', service.id, 'with active:', allocationActive);
                          }
                      }
                     
                     html += '<div class="doctor-allocation-item" data-doctor-id="' + doctorId + '" data-service-id="' + service.id + '">';
                     html += '<div class="doctor-info">';
                     html += '<div class="doctor-avatar">' + doctorInitials + '</div>';
                     html += '<div class="doctor-details">';
                     html += '<h4>' + doctorName + '</h4>';
                     html += '<p class="doctor-email">' + doctorEmail + '</p>';
                     html += '</div>';
                     html += '</div>';
                     html += '<div class="allocation-toggle ' + (allocationActive ? 'active' : '') + '" onclick="toggleDoctorAllocation(this, ' + doctorId + ', ' + service.id + ')" data-doctor-id="' + doctorId + '" data-service-id="' + service.id + '" title="' + (allocationActive ? 'Dezalocarează doctorul' : 'Alocarează doctorul') + '"></div>';
                     html += '</div>';
                     <?php endforeach; ?>
                     
                     html += '</div>'; // .doctors-allocation-list
                     html += '</div>'; // .clinica-service-allocation-card
                 });
                 
                 $('#allocations-grid').html(html);
             }
            
            // Funcția veche toggleAllocation a fost înlocuită cu toggleDoctorAllocation
            // Păstrată pentru compatibilitate dacă este folosită în altă parte
            
            function loadServicesForDoctor(doctorId) {
                console.log('[CLINICA_DEBUG] Loading services for doctor:', doctorId);
                console.log('[CLINICA_DEBUG] ajaxurl available:', typeof ajaxurl !== 'undefined');
                console.log('[CLINICA_DEBUG] ajaxurl value:', ajaxurl);
                
                if (!servicesManager) {
                    console.log('[CLINICA_DEBUG] Services manager not available, using direct AJAX');
                }
                
                var requestData = {
                        action: 'clinica_get_services_for_doctor',
                        doctor_id: doctorId,
                        nonce: '<?php echo wp_create_nonce('clinica_services_nonce'); ?>'
                };
                
                console.log('[CLINICA_DEBUG] Request data:', requestData);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: requestData,
                    success: function(response) {
                        console.log('[CLINICA_DEBUG] AJAX response:', response);
                        if (response.success) {
                            populateServiceSelector(response.data);
                        } else {
                            console.error('[CLINICA_DEBUG] AJAX error:', response.data);
                            $('#timeslot-service-selector').html('<option value="" disabled>Eroare la încărcarea serviciilor: ' + response.data + '</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[CLINICA_DEBUG] AJAX request failed:', status, error);
                        console.error('[CLINICA_DEBUG] XHR response:', xhr.responseText);
                        $('#timeslot-service-selector').html('<option value="" disabled>Eroare la comunicarea cu serverul</option>');
                    }
                });
            }
            
            function populateServiceSelector(services) {
                var $selector = $('#timeslot-service-selector');
                $selector.find('option:not(:first)').remove();
                
                console.log('[CLINICA_DEBUG] Populating service selector with:', services);
                
                if (services && services.length > 0) {
                services.forEach(function(service) {
                        // Afișează toate serviciile, nu doar cele alocate
                        var allocationStatus = service.allocation_active ? ' (Alocat)' : ' (Nealocat)';
                        $selector.append('<option value="' + service.id + '" data-duration="' + service.duration + '">' + 
                                       service.name + ' (' + service.duration + ' min)' + allocationStatus + '</option>');
                    });
                    
                    // Activează selectorul de servicii
                    $selector.prop('disabled', false);
                    console.log('[CLINICA_DEBUG] Service selector populated with ' + services.length + ' services');
                } else {
                    console.log('[CLINICA_DEBUG] No services found for doctor');
                    $selector.append('<option value="" disabled>Nu există servicii disponibile</option>');
                }
            }
            
            function loadTimeslots(doctorId, serviceId) {
                console.log('[CLINICA_DEBUG] loadTimeslots called with doctorId:', doctorId, 'serviceId:', serviceId);
                
                if (!doctorId || !serviceId) {
                    console.log('[CLINICA_DEBUG] Missing doctorId or serviceId');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clinica_get_doctor_timeslots',
                        doctor_id: doctorId,
                        service_id: serviceId,
                        nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>'
                    },
                    success: function(response) {
                        console.log('[CLINICA_DEBUG] Timeslots AJAX response:', response);
                        if (response.success) {
                            renderTimeslots(response.data);
                        } else {
                            console.error('[CLINICA_DEBUG] Timeslots AJAX error:', response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[CLINICA_DEBUG] Timeslots AJAX failed:', status, error);
                        console.error('[CLINICA_DEBUG] XHR response:', xhr.responseText);
                    }
                });
            }
            
            function renderTimeslots(timeslots) {
                // Clear all timeslot lists
                $('.clinica-timeslots-list').empty();
                
                // Group timeslots by day
                var timeslotsByDay = {};
                timeslots.forEach(function(timeslot) {
                    if (!timeslotsByDay[timeslot.day_of_week]) {
                        timeslotsByDay[timeslot.day_of_week] = [];
                    }
                    timeslotsByDay[timeslot.day_of_week].push(timeslot);
                });
                
                // Render timeslots for each day
                Object.keys(timeslotsByDay).forEach(function(day) {
                    var dayList = $('.clinica-timeslots-list[data-day="' + day + '"]');
                    var dayTimeslots = timeslotsByDay[day];
                    
                    dayTimeslots.forEach(function(timeslot) {
                        var timeslotHtml = createTimeslotHtml(timeslot);
                        dayList.append(timeslotHtml);
                    });
                });
                
                // Actualizează contorul total de timeslots
                updateTimeslotsCount();
            }
            
            function createTimeslotHtml(timeslot) {
                // Elimină secundele din ore (09:00:00 -> 09:00)
                var startTime = timeslot.start_time.replace(':00', '');
                var endTime = timeslot.end_time.replace(':00', '');
                
                return '<div class="clinica-timeslot-item" data-id="' + timeslot.id + '">' +
                       '<div class="timeslot-time">' + startTime + ' - ' + endTime + '</div>' +
                       '<div class="timeslot-duration">Slot: ' + timeslot.slot_duration + ' min</div>' +
                       '<div class="timeslot-actions">' +
                       '<button type="button" class="edit-timeslot-btn" data-id="' + timeslot.id + '" title="Editează">✏️</button>' +
                       '<button type="button" class="delete-timeslot-btn" data-id="' + timeslot.id + '" title="Șterge">🗑️</button>' +
                       '</div>' +
                       '</div>';
            }
            
            function updateTimeslotsCount() {
                var totalCount = 0;
                $('.clinica-timeslot-item').each(function() {
                    totalCount++;
                });
                $('#total-timeslots').text(totalCount);
            }
            
            // Funcție pentru afișarea rezumatului medicului
            function showDoctorSummary(doctorId) {
                // Afișează panoul
                $('#doctor-summary-panel').show();
                
                // Actualizează numele medicului
                var doctorName = $('.personnel-card[data-person-id="' + doctorId + '"] .personnel-name').text();
                $('#selected-doctor-name').text(doctorName);
                
                // Încarcă datele
                loadDoctorSummaryData(doctorId);
            }
            
            // Funcție pentru încărcarea datelor rezumatului
            function loadDoctorSummaryData(doctorId) {
                $('#summary-grid').html('<div class="loading">Se încarcă...</div>');
                
                // Încarcă serviciile alocate
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'clinica_get_services_for_doctor',
                        doctor_id: doctorId,
                        nonce: '<?php echo wp_create_nonce('clinica_services_nonce'); ?>'
                    },
                    success: function(servicesResponse) {
                        if (servicesResponse.success && servicesResponse.data && servicesResponse.data.length > 0) {
                            // Pentru fiecare serviciu, încarcă zilele cu timeslots-uri
                            loadServicesWithDays(doctorId, servicesResponse.data);
                        } else {
                            $('#summary-grid').html('<div class="no-data">Nu sunt alocate servicii</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[CLINICA_DEBUG] Services error:', xhr, status, error);
                        $('#summary-grid').html('<div class="error">Eroare la încărcarea serviciilor</div>');
                    }
                });
            }
            
            // Funcție pentru încărcarea serviciilor cu zilele lor
            function loadServicesWithDays(doctorId, services) {
                var servicesWithDays = [];
                var completedRequests = 0;
                
                if (services.length === 0) {
                    $('#summary-grid').html('<div class="no-data">Nu sunt alocate servicii</div>');
                    return;
                }
                
                services.forEach(function(service) {
                    // Pentru fiecare serviciu, încarcă timeslots-urile
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clinica_get_doctor_timeslots',
                            doctor_id: doctorId,
                            service_id: service.id,
                            nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>'
                        },
                        success: function(response) {
                            var days = [];
                            if (response.success && response.data && response.data.length > 0) {
                                // Grupează timeslots-urile pe zile
                                var daysWithTimeslots = {};
                                response.data.forEach(function(timeslot) {
                                    if (!daysWithTimeslots[timeslot.day_of_week]) {
                                        daysWithTimeslots[timeslot.day_of_week] = true;
                                    }
                                });
                                
                                // Convertește numerele de zile în abrevieri
                                var dayNames = ['L', 'Ma', 'Mi', 'J', 'V', 'S', 'D'];
                                Object.keys(daysWithTimeslots).forEach(function(dayNum) {
                                    days.push(dayNames[dayNum - 1]);
                                });
                            }
                            
                            servicesWithDays.push({
                                service: service,
                                days: days
                            });
                            
                            completedRequests++;
                            if (completedRequests === services.length) {
                                renderDoctorSummary(servicesWithDays);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('[CLINICA_DEBUG] Timeslots error for service', service.id, xhr, status, error);
                            
                            servicesWithDays.push({
                                service: service,
                                days: []
                            });
                            
                            completedRequests++;
                            if (completedRequests === services.length) {
                                renderDoctorSummary(servicesWithDays);
                            }
                        }
                    });
                });
            }
            
            // Funcție pentru renderizarea rezumatului
            function renderDoctorSummary(servicesWithDays) {
                var html = '';
                
                if (servicesWithDays.length === 0) {
                    html = '<div class="no-data">Nu sunt alocate servicii</div>';
                } else {
                    servicesWithDays.forEach(function(item) {
                        var serviceName = item.service.name || item.service.service_name || 'Serviciu necunoscut';
                        var daysText = item.days.length > 0 ? item.days.join(', ') : '-';
                        
                        html += '<div class="service-item">' + serviceName + '</div>';
                        html += '<div class="days-item">' + daysText + '</div>';
                    });
                }
                
                $('#summary-grid').html(html);
            }
            

            
            function openServiceModal(mode, serviceId) {
                if (mode === 'add') {
                    $('#service-modal-title').text('Adaugă Serviciu');
                    $('#service-form')[0].reset();
                    $('#service-id').val('');
                } else {
                    $('#service-modal-title').text('Editează Serviciu');
                    // Load service data for editing
                    loadServiceData(serviceId);
                }
                
                $('#service-modal').show();
            }
            
            function openTimeslotModal(mode, day, timeslotId) {
                if (mode === 'add') {
                    $('#timeslot-modal-title').text('Adaugă Timeslot');
                    $('#timeslot-form')[0].reset();
                    $('#timeslot-id').val('');
                    $('#timeslot-day').val(day);
                    
                    // Obține orele din programul clinicii pentru ziua respectivă
                    var schedule = <?php 
                        $settings = Clinica_Settings::get_instance();
                        $schedule_settings = $settings->get_group('schedule');
                        $working_hours = isset($schedule_settings['working_hours']['value']) ? $schedule_settings['working_hours']['value'] : array();
                        
                        $converted_schedule = array();
                        $work_days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
                        
                        foreach ($work_days as $day) {
                            if (isset($working_hours[$day])) {
                                $hours = $working_hours[$day];
                                $converted_schedule[$day] = array(
                                    'active' => !empty($hours['active']) && $hours['active'],
                                    'start_time' => !empty($hours['start']) ? $hours['start'] : '',
                                    'end_time' => !empty($hours['end']) ? $hours['end'] : ''
                                );
                            }
                        }
                        echo json_encode($converted_schedule);
                    ?>;
                    
                    // Mapează ziua numerică la cheia din program
                    var dayMap = { 1: 'monday', 2: 'tuesday', 3: 'wednesday', 4: 'thursday', 5: 'friday' };
                    var dayKey = dayMap[day];
                    var daySchedule = schedule[dayKey];
                    
                    if (daySchedule && daySchedule.active && daySchedule.start_time && daySchedule.end_time) {
                        // Setează orele din programul clinicii
                        $('#start-time').val(daySchedule.start_time);
                        $('#end-time').val(daySchedule.end_time);
                        console.log('[CLINICA_DEBUG] Set program hours for day', day, ':', daySchedule.start_time, '-', daySchedule.end_time);
                    } else {
                        // Fallback la valorile implicite dacă nu există program
                        $('#start-time').val('09:00');
                        $('#end-time').val('17:00');
                        console.log('[CLINICA_DEBUG] No program found for day', day, ', using default hours');
                    }
                    
                    // Forțează formatul 24H
                    $('#start-time, #end-time').attr('data-format', '24h');
                     
                                           // Populează durata slot-ului din serviciul selectat
                      var selectedService = $('#timeslot-service-selector option:selected');
                      if (selectedService.length && selectedService.data('duration')) {
                          var serviceDuration = selectedService.data('duration');
                          $('#slot-duration').val(serviceDuration);
                          $('#service-duration-display').text(serviceDuration);
                          
                          // Inițializează selectorul de durată
                          $('#duration-type').val('service');
                          $('#slot-duration').prop('disabled', true);
                      } else {
                          // Dacă nu este selectat niciun serviciu, dezactivează modalul
                          alert('Selectați mai întâi un serviciu pentru a configura timeslot-urile');
                          $('#timeslot-modal').hide();
                          return;
                      }
                     
                     // Generează sloturile automat
                     generateTimeSlots();
                     
                     console.log('[CLINICA_DEBUG] Opening timeslot modal for day:', day);
                     console.log('[CLINICA_DEBUG] Form values:', {
                         day: $('#timeslot-day').val(),
                         start_time: $('#start-time').val(),
                         end_time: $('#end-time').val(),
                         slot_duration: $('#slot-duration').val()
                     });
                } else {
                    $('#timeslot-modal-title').text('Editează Timeslot');
                    // Load timeslot data for editing
                    loadTimeslotData(timeslotId);
                }
                
                $('#timeslot-modal').show();
            }
            
            function loadServiceData(serviceId) {
                // Găsește serviciul în lista afișată
                var serviceCard = $('.clinica-service-card[data-service-id="' + serviceId + '"]');
                if (serviceCard.length) {
                    var serviceName = serviceCard.find('h3').text();
                    var serviceDuration = serviceCard.find('.service-duration').text().match(/\d+/)[0];
                    var serviceActive = serviceCard.find('.status-badge').hasClass('active');
                    
                    $('#service-name').val(serviceName);
                    $('#service-duration').val(serviceDuration);
                    $('#service-active').prop('checked', serviceActive);
                    $('#service-id').val(serviceId);
                }
            }
            
            function loadTimeslotData(timeslotId) {
                // Găsește timeslot-ul în lista afișată
                var timeslotItem = $('.clinica-timeslot-item[data-id="' + timeslotId + '"]');
                if (timeslotItem.length) {
                    var timeText = timeslotItem.find('.timeslot-time').text();
                    var times = timeText.split(' - ');
                    var duration = timeslotItem.find('.timeslot-duration').text().match(/\d+/)[0];
                    
                    $('#start-time').val(times[0]);
                    $('#end-time').val(times[1]);
                    $('#slot-duration').val(duration);
                    $('#timeslot-id').val(timeslotId);
                }
            }
            
            function saveService() {
                var serviceId = $('#service-id').val();
                var serviceName = $('#service-name').val();
                var serviceDuration = $('#service-duration').val();
                var serviceActive = $('#service-active').is(':checked') ? 1 : 0;
                
                if (!serviceName || !serviceDuration) {
                    alert('Toate câmpurile sunt obligatorii');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clinica_services_save',
                        nonce: '<?php echo wp_create_nonce('clinica_settings_nonce'); ?>',
                        id: serviceId,
                        name: serviceName,
                        duration: serviceDuration,
                        active: serviceActive
                    },
                    success: function(response) {
                        if (response.success) {
                $('#service-modal').hide();
                            location.reload(); // Reîncarcă pagina pentru a afișa modificările
                        } else {
                            alert('Eroare: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Eroare la comunicarea cu serverul');
                    }
                });
            }
            
            function deleteService(serviceId) {
                if (confirm('Sigur doriți să ștergeți acest serviciu? Această acțiune nu poate fi anulată.')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clinica_services_delete',
                            nonce: '<?php echo wp_create_nonce('clinica_settings_nonce'); ?>',
                            id: serviceId
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload(); // Reîncarcă pagina pentru a afișa modificările
                            } else {
                                alert('Eroare: ' + response.data);
                            }
                        },
                        error: function() {
                            alert('Eroare la comunicarea cu serverul');
                        }
                    });
                }
            }
            
                         function saveTimeslot() {
                 var timeslotId = $('#timeslot-id').val();
                 var doctorId = $('#timeslot-doctor-selector').val();
                 var serviceId = $('#timeslot-service-selector').val();
                 var dayOfWeek = $('#timeslot-day').val();
                 var startTime = $('#start-time').val();
                 var endTime = $('#end-time').val();
                 var slotDuration = parseInt($('#slot-duration').val()) || 30;
                 
                 console.log('[CLINICA_DEBUG] Saving timeslot with data:', {
                     timeslotId, doctorId, serviceId, dayOfWeek, startTime, endTime, slotDuration
                 });
                 
                 // Validare completă
                 if (!doctorId) {
                     alert('Selectați un doctor');
                     return;
                 }
                 
                 if (!serviceId) {
                     alert('Selectați un serviciu');
                     return;
                 }
                 
                 if (!dayOfWeek) {
                     alert('Ziua săptămânii este obligatorie');
                     return;
                 }
                 
                 if (!startTime) {
                     alert('Ora de început este obligatorie');
                     return;
                 }
                 
                 if (!endTime) {
                     alert('Ora de sfârșit este obligatorie');
                     return;
                 }
                 
                 if (!slotDuration) {
                     alert('Durata slot-ului este obligatorie');
                     return;
                 }
                 
                 // Validare logică
                 if (startTime >= endTime) {
                     alert('Ora de început trebuie să fie mai mică decât ora de sfârșit');
                     return;
                 }
                 
                 if (slotDuration < 1 || slotDuration > 480) {
                     alert('Durata slot-ului trebuie să fie între 1 și 480 minute');
                     return;
                 }
                 
                 // Generează toate sloturile pentru intervalul selectat
                 var slots = generateAllSlots(startTime, endTime, slotDuration);
                 
                 if (slots.length === 0) {
                     alert('Nu s-au putut genera sloturi pentru intervalul selectat');
                     return;
                 }
                 
                 // Salvează fiecare slot individual
                 var savedSlots = 0;
                 var totalSlots = slots.length;
                 
                 slots.forEach(function(slot, index) {
                     $.ajax({
                         url: ajaxurl,
                         type: 'POST',
                         data: {
                             action: 'clinica_save_timeslot',
                             nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                             timeslot_id: '', // Slot nou
                             doctor_id: doctorId,
                             service_id: serviceId,
                             day_of_week: dayOfWeek,
                             start_time: slot.start,
                             end_time: slot.end,
                             slot_duration: slotDuration
                         },
                         success: function(response) {
                             savedSlots++;
                             console.log('[CLINICA_DEBUG] Slot ' + (index + 1) + ' saved:', response);
                             
                             if (savedSlots === totalSlots) {
                                 // Toate sloturile au fost salvate
                                 $('#timeslot-modal').hide();
                                 alert('Au fost create ' + totalSlots + ' sloturi cu succes!');
                                 
                                 // Reîncarcă timeslot-urile pentru doctorul și serviciul selectat
                                 if (selectedDoctor && selectedService) {
                                     loadTimeslots(selectedDoctor, selectedService);
                                 }
                                 
                                 // Actualizează rezumatul medicului
                                 if (selectedDoctor) {
                                     loadDoctorSummaryData(selectedDoctor);
                                 }
                             }
                         },
                         error: function(xhr, status, error) {
                             console.error('[CLINICA_DEBUG] Slot ' + (index + 1) + ' save error:', status, error);
                             alert('Eroare la salvarea slotului ' + (index + 1) + ': ' + error);
                         }
                     });
                 });
            }
            
            function loadScheduleOverview() {
                var date = $('#schedule-date').val();
                
                // Obține programul direct din setările PHP (doar zilele de lucru)
                var schedule = <?php 
                    $settings = Clinica_Settings::get_instance();
                    $schedule_settings = $settings->get_group('schedule');
                    $working_hours = isset($schedule_settings['working_hours']['value']) ? $schedule_settings['working_hours']['value'] : array();
                    
                    // Convertește la formatul așteptat de JavaScript (exclude sâmbătă și duminică)
                    $converted_schedule = array();
                    $work_days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
                    
                    foreach ($work_days as $day) {
                        if (isset($working_hours[$day])) {
                            $hours = $working_hours[$day];
                            $converted_schedule[$day] = array(
                                'active' => !empty($hours['active']) && $hours['active'],
                                'start_time' => !empty($hours['start']) ? $hours['start'] : '',
                                'end_time' => !empty($hours['end']) ? $hours['end'] : '',
                                'break_start' => !empty($hours['break_start']) ? $hours['break_start'] : '',
                                'break_end' => !empty($hours['break_end']) ? $hours['break_end'] : ''
                            );
                        } else {
                            $converted_schedule[$day] = array(
                                'active' => false,
                                'start_time' => '',
                                'end_time' => '',
                                'break_start' => '',
                                'break_end' => ''
                            );
                        }
                    }
                    echo json_encode($converted_schedule);
                ?>;
                
                renderScheduleOverview(schedule, date);
            }
            
            function renderScheduleOverview(schedule, selectedDate) {
                var html = '<div class="clinica-schedule-overview-content">';
                html += '<h3>Program General Clinică</h3>';
                
                // Afișează data curentă
                var currentDate = new Date();
                html += '<div class="current-date-info">';
                html += '<p><strong>Data curentă:</strong> ' + currentDate.toLocaleDateString('ro-RO', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'}) + '</p>';
                html += '<p><strong>Ora curentă:</strong> ' + currentDate.toLocaleTimeString('ro-RO', {hour: '2-digit', minute: '2-digit'}) + '</p>';
                html += '</div>';
                
                var days = {
                    'monday': 'Luni',
                    'tuesday': 'Marți', 
                    'wednesday': 'Miercuri',
                    'thursday': 'Joi',
                    'friday': 'Vineri'
                };
                
                html += '<div class="clinica-schedule-grid">';
                Object.keys(days).forEach(function(day) {
                    var dayData = schedule[day] || { active: false };
                    var dayName = days[day];
                    var isActive = dayData.active;
                    var statusClass = isActive ? 'active' : 'inactive';
                    var statusText = isActive ? 'Deschis' : 'Închis';
                    
                    // Verifică dacă este ziua curentă
                    var currentDayIndex = currentDate.getDay(); // 0 = Sunday, 1 = Monday, etc.
                    var dayIndexMap = { 'monday': 1, 'tuesday': 2, 'wednesday': 3, 'thursday': 4, 'friday': 5 };
                    var isCurrentDay = dayIndexMap[day] === currentDayIndex;
                    var currentDayClass = isCurrentDay ? ' current-day' : '';
                    
                    html += '<div class="clinica-schedule-day ' + statusClass + currentDayClass + '">';
                    html += '<h4>' + dayName + '</h4>';
                    
                    if (isCurrentDay) {
                        html += '<div class="current-day-indicator">Astăzi</div>';
                    }
                    
                    html += '<div class="day-status">' + statusText + '</div>';
                    
                    if (isActive && dayData.start_time && dayData.end_time) {
                        html += '<div class="day-hours">';
                        html += '<div class="hours-main">' + dayData.start_time + ' - ' + dayData.end_time + '</div>';
                        if (dayData.break_start && dayData.break_end) {
                            html += '<div class="hours-break">Pauză: ' + dayData.break_start + ' - ' + dayData.break_end + '</div>';
                        }
                        html += '</div>';
                    } else {
                        html += '<div class="day-hours closed">Program nedefinit</div>';
                    }
                    html += '</div>';
                });
                html += '</div>';
                
                // Afișează informații pentru data selectată
                if (selectedDate) {
                    // Mapează numele zilei din română la cheia în engleză
                    var selectedDayRomanian = new Date(selectedDate).toLocaleDateString('ro-RO', {weekday: 'long'}).toLowerCase();
                    var romanianToEnglishMap = {
                        'luni': 'monday',
                        'marți': 'tuesday', 
                        'miercuri': 'wednesday',
                        'joi': 'thursday',
                        'vineri': 'friday'
                    };
                    var selectedDay = romanianToEnglishMap[selectedDayRomanian];
                    
                    // Verifică dacă ziua există în programul de lucru (nu weekend)
                    if (!selectedDay) {
                        html += '<div class="clinica-selected-date closed">';
                        html += '<h4>Program pentru ' + new Date(selectedDate).toLocaleDateString('ro-RO', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'}) + '</h4>';
                        html += '<p><strong>Clinică închisă</strong> - nu se lucrează în weekend</p>';
                        html += '</div>';
                        return;
                    }
                    
                    var selectedDayData = schedule[selectedDay];
                    
                    if (selectedDayData && selectedDayData.active) {
                        html += '<div class="clinica-selected-date">';
                        html += '<h4>Program pentru ' + new Date(selectedDate).toLocaleDateString('ro-RO', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'}) + '</h4>';
                        html += '<p><strong>Clinică deschisă:</strong> ' + selectedDayData.start_time + ' - ' + selectedDayData.end_time + '</p>';
                        if (selectedDayData.break_start && selectedDayData.break_end) {
                            html += '<p><strong>Pauză:</strong> ' + selectedDayData.break_start + ' - ' + selectedDayData.break_end + '</p>';
                        }
                        
                        // Calculează durata programului
                        if (selectedDayData.start_time && selectedDayData.end_time) {
                            var startMinutes = timeToMinutes(selectedDayData.start_time);
                            var endMinutes = timeToMinutes(selectedDayData.end_time);
                            var totalMinutes = endMinutes - startMinutes;
                            var hours = Math.floor(totalMinutes / 60);
                            var minutes = totalMinutes % 60;
                            
                            html += '<p><strong>Durata programului:</strong> ' + hours + 'h ' + minutes + 'm</p>';
                        }
                        
                        html += '</div>';
                    } else {
                        html += '<div class="clinica-selected-date closed">';
                        html += '<h4>Program pentru ' + new Date(selectedDate).toLocaleDateString('ro-RO', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'}) + '</h4>';
                        html += '<p><strong>Clinică închisă</strong> în această zi</p>';
                        html += '</div>';
                    }
                }
                

                
                html += '</div>';
                $('#schedule-overview').html(html);
                
                 
             }
             

             
             function debugAvailableServices() {
                 console.log('[CLINICA_DEBUG] Debugging available services...');
                 
                 // Afișează serviciile disponibile în PHP
                 console.log('[CLINICA_DEBUG] PHP Services:', <?php echo json_encode($services); ?>);
                 
                 // Afișează doctorii disponibili în PHP
                 console.log('[CLINICA_DEBUG] PHP Doctors:', <?php echo json_encode($doctors); ?>);
                 
                 // Testează AJAX direct
                 $.ajax({
                     url: ajaxurl,
                     type: 'POST',
                     data: {
                         action: 'clinica_get_services_for_doctor',
                         doctor_id: 1, // Test cu ID 1
                         nonce: '<?php echo wp_create_nonce('clinica_services_nonce'); ?>'
                     },
                     success: function(response) {
                         console.log('[CLINICA_DEBUG] Direct AJAX test response:', response);
                     },
                     error: function(xhr, status, error) {
                         console.error('[CLINICA_DEBUG] Direct AJAX test failed:', status, error);
                         console.error('[CLINICA_DEBUG] XHR response:', xhr.responseText);
                     }
                                   });
              }
              
                             // Funcția pentru generarea automată a sloturilor
               function generateTimeSlots() {
                   var startTime = $('#start-time').val();
                   var endTime = $('#end-time').val();
                   var slotDuration = parseInt($('#slot-duration').val()) || 30;
                   
                   if (!startTime || !endTime) {
                       $('#generated-slots').html('<p class="no-slots">Selectați ora de început și sfârșit</p>');
                       return;
                   }
                   
                   // Convertește orele în minute pentru calcul
                   var startMinutes = timeToMinutes(startTime);
                   var endMinutes = timeToMinutes(endTime);
                   
                   if (startMinutes >= endMinutes) {
                       $('#generated-slots').html('<p class="error-message">Ora de început trebuie să fie mai mică decât ora de sfârșit</p>');
                       return;
                   }
                   
                   // Calculează numărul de sloturi
                   var totalMinutes = endMinutes - startMinutes;
                   var numberOfSlots = Math.floor(totalMinutes / slotDuration);
                   
                   if (numberOfSlots <= 0) {
                       $('#generated-slots').html('<p class="error-message">Intervalul este prea mic pentru durata slot-ului selectată</p>');
                       return;
                   }
                   
                   // Generează sloturile cu informații detaliate
                   var html = '<div class="slots-info">';
                   html += '<p><strong>Total sloturi generate: ' + numberOfSlots + '</strong></p>';
                   html += '<p>Durata fiecărui slot: ' + slotDuration + ' minute</p>';
                   html += '<p>Interval total: ' + startTime + ' - ' + endTime + ' (' + totalMinutes + ' minute)</p>';
                   html += '</div>';
                   
                   // Grupează sloturile pe perioade ale zilei
                   var groupedSlots = groupSlotsByPeriod(startMinutes, endMinutes, slotDuration);
                   
                   html += '<div class="slots-periods">';
                   Object.keys(groupedSlots).forEach(function(period) {
                       var periodSlots = groupedSlots[period];
                       if (periodSlots.length > 0) {
                           html += '<div class="period-group" data-period="' + period + '">';
                           html += '<div class="period-header">';
                           html += '<h4>' + getPeriodDisplayName(period) + '</h4>';
                           html += '<span class="period-count">' + periodSlots.length + ' sloturi</span>';
                           html += '</div>';
                           
                                                           html += '<div class="period-slots" style="display: none;">';
                                periodSlots.forEach(function(slot, index) {
                                    html += '<div class="time-slot" data-slot-index="' + slot.index + '">';
                                    html += '<span class="slot-time">' + slot.start + ' - ' + slot.end + '</span>';
                                    html += '<span class="slot-duration">' + slotDuration + ' min</span>';
                                    html += '</div>';
                                });
                                html += '</div>';
                                
                                html += '<div class="period-actions">';
                                html += '<button type="button" class="button button-secondary period-toggle" data-period="' + period + '">';
                                html += '<span class="dashicons dashicons-arrow-down-alt2"></span>';
                                html += ' Arată';
                                html += '</button>';
                                html += '</div>';
                           
                           html += '</div>';
                       }
                   });
                   html += '</div>';
                   
                   // Adaugă buton pentru salvarea tuturor sloturilor
                   html += '<div class="slots-actions">';
                   html += '<button type="button" class="button button-primary" id="save-all-slots">';
                   html += '<span class="dashicons dashicons-saved"></span>';
                   html += ' Salvează toate sloturile (' + numberOfSlots + ')';
                   html += '</button>';
                   html += '</div>';
                   
                   $('#generated-slots').html(html);
                   
                   // Event listener pentru salvarea tuturor sloturilor
                   $('#save-all-slots').on('click', function() {
                       saveAllGeneratedSlots(startTime, endTime, slotDuration);
                   });
                   
                   // Event listener pentru toggle-ul perioadelor - folosește event delegation
                   $(document).off('click', '.period-toggle').on('click', '.period-toggle', function() {
                       var period = $(this).data('period');
                       var periodGroup = $('.period-group[data-period="' + period + '"]');
                       var periodSlots = periodGroup.find('.period-slots');
                       var icon = $(this).find('.dashicons');
                       
                       if (periodSlots.is(':visible')) {
                           periodSlots.slideUp(300);
                           icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                           $(this).html('<span class="dashicons dashicons-arrow-down-alt2"></span> Arată');
                       } else {
                           periodSlots.slideDown(300);
                           icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                           $(this).html('<span class="dashicons dashicons-arrow-up-alt2"></span> Ascunde');
                       }
                   });
               }
               
               // Funcția pentru grupare pe perioade ale zilei
               function groupSlotsByPeriod(startMinutes, endMinutes, slotDuration) {
                   var grouped = {
                       'morning': [],
                       'lunch': [],
                       'afternoon': [],
                       'evening': []
                   };
                   
                   var currentSlot = startMinutes;
                   var slotIndex = 0;
                   
                   while (currentSlot + slotDuration <= endMinutes) {
                       var slotStart = currentSlot;
                       var slotEnd = currentSlot + slotDuration;
                       
                       var slot = {
                           index: slotIndex,
                           start: minutesToTime(slotStart),
                           end: minutesToTime(slotEnd),
                           startMinutes: slotStart,
                           endMinutes: slotEnd
                       };
                       
                       // Determină perioada pentru acest slot
                       var period = getSlotPeriod(slotStart);
                       if (grouped[period]) {
                           grouped[period].push(slot);
                       }
                       
                       currentSlot = slotEnd;
                       slotIndex++;
                   }
                   
                   return grouped;
               }
               
               // Funcția pentru determinarea perioadei unui slot
               function getSlotPeriod(minutes) {
                   var hour = Math.floor(minutes / 60);
                   
                   if (hour >= 5 && hour < 12) return 'morning';
                   if (hour >= 12 && hour < 14) return 'lunch';
                   if (hour >= 14 && hour < 18) return 'afternoon';
                   if (hour >= 18 && hour < 22) return 'evening';
                   
                   // Fallback pentru orele extreme
                   if (hour < 5) return 'morning';
                   return 'evening';
               }
               
               // Funcția pentru afișarea numelui perioadei
               function getPeriodDisplayName(period) {
                   var names = {
                       'morning': '🌅 DIMINEAȚA',
                       'lunch': '🍽️ PRÂNZUL',
                       'afternoon': '☀️ DUPĂ-AMIAZA',
                       'evening': '🌆 SEARA'
                   };
                   return names[period] || period;
               }
               
               // Funcția pentru salvarea tuturor sloturilor generate
               function saveAllGeneratedSlots(startTime, endTime, slotDuration) {
                   var doctorId = $('#timeslot-doctor-selector').val();
                   var serviceId = $('#timeslot-service-selector').val();
                   var dayOfWeek = $('#timeslot-day').val();
                   
                   if (!doctorId || !serviceId || !dayOfWeek) {
                       alert('Selectați doctorul, serviciul și ziua pentru a salva sloturile!');
                       return;
                   }
                   
                   // Generează toate sloturile
                   var slots = generateAllSlots(startTime, endTime, slotDuration);
                   
                   if (slots.length === 0) {
                       alert('Nu s-au putut genera sloturi pentru intervalul selectat');
                       return;
                   }
                   
                   // Progres bar pentru salvarea sloturilor
                   var progressHtml = '<div class="bulk-progress" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 1001;">';
                   progressHtml += '<h3>Salvare sloturi în curs...</h3>';
                   progressHtml += '<div class="progress-bar" style="width: 300px; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden;">';
                   progressHtml += '<div class="progress-fill" style="width: 0%; height: 100%; background: #0073aa; transition: width 0.3s;"></div>';
                   progressHtml += '</div>';
                   progressHtml += '<p class="progress-text">0 / ' + slots.length + ' sloturi salvate</p>';
                   progressHtml += '</div>';
                   
                   $('body').append(progressHtml);
                   
                   // Salvează fiecare slot individual
                   var savedSlots = 0;
                   var failedSlots = 0;
                   
                   slots.forEach(function(slot, index) {
                       $.ajax({
                           url: ajaxurl,
                           type: 'POST',
                           data: {
                               action: 'clinica_save_timeslot',
                               nonce: '<?php echo wp_create_nonce('clinica_timeslots_nonce'); ?>',
                               timeslot_id: '', // Slot nou
                               doctor_id: doctorId,
                               service_id: serviceId,
                               day_of_week: dayOfWeek,
                               start_time: slot.start,
                               end_time: slot.end,
                               slot_duration: slotDuration
                           },
                           success: function(response) {
                               savedSlots++;
                               updateBulkProgress(savedSlots, slots.length);
                               
                               if (savedSlots === slots.length) {
                                   // Toate sloturile au fost salvate
                                   $('.bulk-progress').remove();
                                   $('#timeslot-modal').hide();
                                   
                                   if (failedSlots === 0) {
                                       alert('Au fost create ' + slots.length + ' sloturi cu succes!');
                                   } else {
                                       alert('Au fost create ' + savedSlots + ' sloturi cu succes, ' + failedSlots + ' au eșuat.');
                                   }
                                   
                                   // Reîncarcă timeslot-urile pentru doctorul și serviciul selectat
                                   if (selectedDoctor && selectedService) {
                                       loadTimeslots(selectedDoctor, selectedService);
                                   }
                                   
                                   // Actualizează rezumatul medicului
                                   if (selectedDoctor) {
                                       loadDoctorSummaryData(selectedDoctor);
                                   }
                               }
                           },
                           error: function() {
                               savedSlots++;
                               failedSlots++;
                               updateBulkProgress(savedSlots, slots.length);
                               
                               if (savedSlots === slots.length) {
                                   // Toate sloturile au fost procesate
                                   $('.bulk-progress').remove();
                                   
                                   if (failedSlots === 0) {
                                       alert('Au fost create ' + slots.length + ' sloturi cu succes!');
                                       $('#timeslot-modal').hide();
                                   } else {
                                       alert('Au fost create ' + (slots.length - failedSlots) + ' sloturi cu succes, ' + failedSlots + ' au eșuat.');
                                   }
                                   
                                   // Reîncarcă timeslot-urile pentru doctorul și serviciul selectat
                                   if (selectedDoctor && selectedService) {
                                       loadTimeslots(selectedDoctor, selectedService);
                                   }
                               }
                           }
                       });
                   });
               }
              
              // Funcții helper pentru conversia orei
              function timeToMinutes(timeString) {
                  var parts = timeString.split(':');
                  return parseInt(parts[0]) * 60 + parseInt(parts[1]);
              }
              
              function minutesToTime(minutes) {
                  var hours = Math.floor(minutes / 60);
                  var mins = minutes % 60;
                  return (hours < 10 ? '0' : '') + hours + ':' + (mins < 10 ? '0' : '') + mins;
              }
              
              // Funcția pentru generarea tuturor sloturilor pentru salvare
              function generateAllSlots(startTime, endTime, slotDuration) {
                  var startMinutes = timeToMinutes(startTime);
                  var endMinutes = timeToMinutes(endTime);
                  
                  if (startMinutes >= endMinutes) {
                      return [];
                  }
                  
                  var slots = [];
                  var currentTime = startMinutes;
                  
                  while (currentTime + slotDuration <= endMinutes) {
                      var slotStart = minutesToTime(currentTime);
                      var slotEnd = minutesToTime(currentTime + slotDuration);
                      
                      slots.push({
                          start: slotStart,
                          end: slotEnd
                      });
                      
                      currentTime += slotDuration;
                  }
                  
                                     return slots;
               }
               
               // Funcții pentru noua interfață de alocări
               function toggleServiceCard(headerElement) {
                   console.log('[CLINICA_DEBUG] toggleServiceCard called with:', headerElement);
                   
                   var card = $(headerElement).closest('.clinica-service-allocation-card');
                   var toggle = card.find('.expand-toggle');
                   var doctorsList = card.find('.doctors-allocation-list');
                   
                   console.log('[CLINICA_DEBUG] Found card:', card.length, 'toggle:', toggle.length, 'doctorsList:', doctorsList.length);
                   
                   card.toggleClass('expanded');
                   toggle.toggleClass('expanded');
                   
                   if (card.hasClass('expanded')) {
                       toggle.find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                       console.log('[CLINICA_DEBUG] Card expanded');
                   } else {
                       toggle.find('.dashicons').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                       console.log('[CLINICA_DEBUG] Card collapsed');
                   }
                   
                   // Debug: afișează starea cardului
                   console.log('[CLINICA_DEBUG] Card expanded state:', card.hasClass('expanded'));
               }
               
            // Funcția globală pentru toggle allocation
            window.toggleDoctorAllocation = function(toggleElement, doctorId, serviceId) {
                console.log('[CLINICA_DEBUG] toggleDoctorAllocation called with:', {doctorId, serviceId, toggleElement});
                
                var $toggle = $(toggleElement);
                var isActive = $toggle.hasClass('active');
                var newActive = !isActive;
                
                console.log('[CLINICA_DEBUG] Toggle state:', {isActive, newActive});
                
                // Actualizează vizual toggle-ul
                $toggle.toggleClass('active', newActive);
                
                // Salvează alocarea
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clinica_save_doctor_service_allocation',
                        nonce: '<?php echo wp_create_nonce('clinica_services_nonce'); ?>',
                        doctor_id: doctorId,
                        service_id: serviceId,
                        active: newActive ? 1 : 0
                    },
                    success: function(response) {
                        console.log('[CLINICA_DEBUG] Save allocation response:', response);
                        if (response.success) {
                            // Actualizează statisticile
                            updateAllocationStats(serviceId);
                            // Reîncarcă alocările pentru a reflecta modificările
                            loadAllocations();
                        } else {
                            // Restorează starea anterioară în caz de eroare
                            $toggle.toggleClass('active', isActive);
                            alert('Eroare: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[CLINICA_DEBUG] Save allocation error:', status, error);
                        console.error('[CLINICA_DEBUG] XHR response:', xhr.responseText);
                        // Restorează starea anterioară în caz de eroare
                        $toggle.toggleClass('active', isActive);
                        alert('Eroare la comunicarea cu serverul: ' + error);
                    }
                });
            }
                
                function updateBulkButtonsState() {
                    var selectedCount = $('.allocation-toggle.selected-for-bulk').length;
                    var $bulkAllocateBtn = $('#bulk-allocate-btn');
                    var $bulkDeallocateBtn = $('#bulk-deallocate-btn');
                    var $clearSelectionsBtn = $('#clear-selections-btn');
                    
                    if (selectedCount > 0) {
                        $bulkAllocateBtn.prop('disabled', false).text('+ Alocă ' + selectedCount + ' selecții');
                        $bulkDeallocateBtn.prop('disabled', false).text('- Dezalocă ' + selectedCount + ' selecții');
                        $clearSelectionsBtn.show();
                    } else {
                        $bulkAllocateBtn.prop('disabled', true).text('+ Alocă selectații');
                        $bulkDeallocateBtn.prop('disabled', true).text('- Dezalocă selectații');
                        $clearSelectionsBtn.hide();
                    }
                }
               
                               function updateAllocationStats(serviceId) {
                    var card = $('.clinica-service-allocation-card[data-service-id="' + serviceId + '"]');
                    var activeToggles = card.find('.allocation-toggle.active').length;
                    var totalToggles = card.find('.allocation-toggle').length;
                    
                    // Actualizează numărul de doctori alocați (primul stat-number este pentru "Alocați")
                    card.find('.stat-item:first-child .stat-number').text(activeToggles);
                    
                    // Actualizează indicatorul de status
                    var statusIndicator = card.find('.service-status-indicator');
                    statusIndicator.removeClass('active partial inactive');
                    
                    if (activeToggles === 0) {
                        statusIndicator.addClass('inactive');
                    } else if (activeToggles === totalToggles && totalToggles > 0) {
                        statusIndicator.addClass('active');
                    } else if (activeToggles > 0) {
                        statusIndicator.addClass('partial');
                    }
                    
                    console.log('[CLINICA_DEBUG] Updated stats for service', serviceId, 'active:', activeToggles, 'total:', totalToggles);
                }
               
               function filterAllocations() {
                   var serviceSearch = $('#search-services').val().toLowerCase();
                   var doctorSearch = $('#search-doctors').val().toLowerCase();
                   
                   $('.clinica-service-allocation-card').each(function() {
                       var $card = $(this);
                       var serviceName = $card.find('.service-name').text().toLowerCase();
                       var serviceVisible = serviceName.includes(serviceSearch);
                       
                       if (serviceVisible) {
                           $card.show();
                           
                           // Filtrează doctorii în funcție de căutare
                           if (doctorSearch) {
                               $card.find('.doctor-allocation-item').each(function() {
                                   var $doctorItem = $(this);
                                   var doctorName = $doctorItem.find('h4').text().toLowerCase();
                                   var doctorEmail = $doctorItem.find('.doctor-email').text().toLowerCase();
                                   
                                   if (doctorName.includes(doctorSearch) || doctorEmail.includes(doctorSearch)) {
                                       $doctorItem.show();
                                   } else {
                                       $doctorItem.hide();
                                   }
                               });
                           } else {
                               $card.find('.doctor-allocation-item').show();
                           }
                       } else {
                           $card.hide();
                       }
                   });
               }
               
                               function bulkAllocateDoctors() {
                    var selectedServices = [];
                    var selectedDoctors = [];
                    
                    // Colectează serviciile și doctorii selectați
                    $('.clinica-service-allocation-card').each(function() {
                        var $card = $(this);
                        var serviceId = $card.data('service-id');
                        var hasSelectedDoctors = false;
                        
                        $card.find('.doctor-allocation-item').each(function() {
                            var $doctorItem = $(this);
                            var doctorId = $doctorItem.data('doctor-id');
                            var $toggle = $doctorItem.find('.allocation-toggle');
                            
                            if ($toggle.hasClass('selected-for-bulk')) {
                                hasSelectedDoctors = true;
                                if (!selectedDoctors.includes(doctorId)) {
                                    selectedDoctors.push(doctorId);
                                }
                            }
                        });
                        
                        if (hasSelectedDoctors) {
                            selectedServices.push(serviceId);
                        }
                    });
                    
                    if (selectedDoctors.length === 0) {
                        alert('Selectați doctorii pentru alocare!');
                        return;
                    }
                    
                    if (selectedServices.length === 0) {
                        alert('Selectați serviciile pentru alocare!');
                        return;
                    }
                    
                    if (confirm('Sigur doriți să alocați ' + selectedDoctors.length + ' doctori la ' + selectedServices.length + ' servicii?')) {
                        bulkAllocateProcess(selectedDoctors, selectedServices, true);
                    }
                }
                
                function bulkDeallocateDoctors() {
                    var selectedServices = [];
                    var selectedDoctors = [];
                    
                    // Colectează serviciile și doctorii selectați
                    $('.clinica-service-allocation-card').each(function() {
                        var $card = $(this);
                        var serviceId = $card.data('service-id');
                        var hasSelectedDoctors = false;
                        
                        $card.find('.doctor-allocation-item').each(function() {
                            var $doctorItem = $(this);
                            var doctorId = $doctorItem.data('doctor-id');
                            var $toggle = $doctorItem.find('.allocation-toggle');
                            
                            if ($toggle.hasClass('selected-for-bulk')) {
                                hasSelectedDoctors = true;
                                if (!selectedDoctors.includes(doctorId)) {
                                    selectedDoctors.push(doctorId);
                                }
                            }
                        });
                        
                        if (hasSelectedDoctors) {
                            selectedServices.push(serviceId);
                        }
                    });
                    
                    if (selectedDoctors.length === 0) {
                        alert('Selectați doctorii pentru dezalocare!');
                        return;
                    }
                    
                    if (selectedServices.length === 0) {
                        alert('Selectați serviciile pentru dezalocare!');
                        return;
                    }
                    
                    if (confirm('Sigur doriți să dezalocați ' + selectedDoctors.length + ' doctori de la ' + selectedServices.length + ' servicii?')) {
                        bulkAllocateProcess(selectedDoctors, selectedServices, false);
                    }
                }
                
                function bulkAllocateProcess(doctorIds, serviceIds, allocate) {
                    var totalOperations = doctorIds.length * serviceIds.length;
                    var completedOperations = 0;
                    var failedOperations = 0;
                    
                    // Progres bar
                    var progressHtml = '<div class="bulk-progress" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 1001;">';
                    progressHtml += '<h3>' + (allocate ? 'Alocare în curs...' : 'Dezalocare în curs...') + '</h3>';
                    progressHtml += '<div class="progress-bar" style="width: 300px; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden;">';
                    progressHtml += '<div class="progress-fill" style="width: 0%; height: 100%; background: #0073aa; transition: width 0.3s;"></div>';
                    progressHtml += '</div>';
                    progressHtml += '<p class="progress-text">0 / ' + totalOperations + ' operații completate</p>';
                    progressHtml += '</div>';
                    
                    $('body').append(progressHtml);
                    
                    // Procesează fiecare combinație doctor-serviciu
                    doctorIds.forEach(function(doctorId) {
                        serviceIds.forEach(function(serviceId) {
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'clinica_save_doctor_service_allocation',
                                    nonce: '<?php echo wp_create_nonce('clinica_services_nonce'); ?>',
                                    doctor_id: doctorId,
                                    service_id: serviceId,
                                    active: allocate ? 1 : 0
                                },
                                success: function(response) {
                                    completedOperations++;
                                    updateBulkProgress(completedOperations, totalOperations);
                                    
                                    if (response.success) {
                                        // Actualizează toggle-ul vizual
                                        var $toggle = $('.clinica-service-allocation-card[data-service-id="' + serviceId + '"] .doctor-allocation-item[data-doctor-id="' + doctorId + '"] .allocation-toggle');
                                        $toggle.toggleClass('active', allocate);
                                        $toggle.removeClass('selected-for-bulk');
                                        
                                        // Actualizează statisticile
                                        updateAllocationStats(serviceId);
                                    } else {
                                        failedOperations++;
                                        console.error('[CLINICA_DEBUG] Bulk operation failed:', response.data);
                                    }
                                    
                                    if (completedOperations === totalOperations) {
                                        completeBulkOperation(totalOperations, failedOperations);
                                    }
                                },
                                error: function() {
                                    completedOperations++;
                                    failedOperations++;
                                    updateBulkProgress(completedOperations, totalOperations);
                                    
                                    if (completedOperations === totalOperations) {
                                        completeBulkOperation(totalOperations, failedOperations);
                                    }
                                }
                            });
                        });
                    });
                }
                
                function updateBulkProgress(completed, total) {
                    var percentage = Math.round((completed / total) * 100);
                    $('.progress-fill').css('width', percentage + '%');
                    $('.progress-text').text(completed + ' / ' + total + ' operații completate');
                }
                
                function completeBulkOperation(total, failed) {
                    $('.bulk-progress').remove();
                    
                    if (failed === 0) {
                        alert('Toate operațiile au fost completate cu succes!');
                    } else {
                        alert('Operațiile au fost completate cu ' + failed + ' erori din ' + total + ' încercări.');
                    }
                    
                    // Reîncarcă alocările pentru a reflecta toate modificările
                    loadAllocations();
                }
               
               // Funcția pentru restaurarea tab-ului activ
               function restoreActiveTab() {
                   var activeTab = localStorage.getItem('clinica_active_tab');
                   
                   if (activeTab && activeTab !== 'services') {
                       // Verifică dacă tab-ul există
                       var $tabButton = $('[data-tab="' + activeTab + '"]');
                       var $tabContent = $('#tab-' + activeTab);
                       
                       if ($tabButton.length && $tabContent.length) {
                           // Restorează tab-ul activ
                           switchTab(activeTab);
                           console.log('[CLINICA_DEBUG] Restored active tab:', activeTab);
                       } else {
                           // Dacă tab-ul nu există, folosește default
                           localStorage.removeItem('clinica_active_tab');
                           console.log('[CLINICA_DEBUG] Invalid tab restored, using default');
                       }
                   } else {
                       console.log('[CLINICA_DEBUG] No saved tab or default tab, using services');
                   }
               }
               
               
           });
           </script>
