<?php
/**
 * Pagină pentru gestionarea familiilor
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!Clinica_Patient_Permissions::can_view_patients()) {
    wp_die('Nu aveți permisiunea de a accesa această pagină');
}

// Inițializează managerul de familii
$family_manager = new Clinica_Family_Manager();

// Procesează acțiunile
if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'clinica_family_action')) {
    switch ($_POST['action']) {
        case 'create_family':
            $family_name = sanitize_text_field($_POST['family_name']);
            $head_patient_id = intval($_POST['head_patient_id']);
            
            if (!empty($family_name)) {
                $result = $family_manager->create_family($family_name, $head_patient_id);
                if ($result['success']) {
                    echo '<div class="notice notice-success"><p>Familia a fost creată cu succes!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
                }
            }
            break;
            
        case 'add_member':
            $patient_id = intval($_POST['patient_id']);
            $family_id = intval($_POST['family_id']);
            $family_role = sanitize_text_field($_POST['family_role']);
            
            $result = $family_manager->add_family_member($patient_id, $family_id, $family_role);
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>Membrul a fost adăugat cu succes!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
            break;
            
        case 'remove_member':
            $patient_id = intval($_POST['patient_id']);
            
            $result = $family_manager->remove_family_member($patient_id);
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>Membrul a fost eliminat cu succes!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
            break;
            
        case 'edit_member_role':
            $patient_id = intval($_POST['patient_id']);
            $new_role = sanitize_text_field($_POST['new_role']);
            
            $result = $family_manager->update_family_member_role($patient_id, $new_role);
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>Rolul membrului a fost actualizat cu succes!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
            break;
    }
}

// Familiile se încarcă prin AJAX - nu mai sunt necesare aici

// Obține pacienții fără familie pentru a-i putea adăuga
global $wpdb;
$table_patients = $wpdb->prefix . 'clinica_patients';
$patients_without_family = $wpdb->get_results(
    "SELECT p.*, u.display_name, u.user_email 
     FROM $table_patients p 
     LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
     WHERE p.family_id IS NULL 
     ORDER BY u.display_name"
);
?>

<div class="wrap">
                <h1 class="wp-heading-inline">Gestionare Familii</h1>
            <a href="#" class="page-title-action" id="create-family-btn">Creează Familie Nouă</a>
            <a href="#" class="page-title-action" id="auto-create-families-btn">Creează Familii Automat</a>
            <a href="#" class="page-title-action" id="view-family-logs-btn">Vezi Log-uri Familii</a>
    <hr class="wp-header-end">

    <!-- Secțiunea pentru crearea automată a familiilor -->
    <div class="auto-create-families-section" style="display: none; background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3>Creare Automată Familii pe baza Email-urilor</h3>
        <p>Acest sistem va grupa automat pacienții în familii pe baza adreselor de email.</p>
        
        <div class="email-patterns">
            <h4>Pattern-uri de Email Detectate:</h4>
            <ul>
                <li><strong>Părinte:</strong> nume@email.com</li>
                <li><strong>Copil/Membru:</strong> nume+altnume@email.com</li>
                <li><em>Algoritmul detectează DOAR pattern-ul + pentru familii</em></li>
            </ul>
        </div>

        <div class="auto-create-options">
            <h4>Opțiuni de Creare:</h4>
            <label>
                <input type="checkbox" id="create-parent-as-head" checked>
                Creează părintele ca șef de familie
            </label>
            <br>
            <label>
                <input type="checkbox" id="assign-roles-automatically" checked>
                Atribuie roluri automat (Părinte, Copil, etc.)
            </label>
            <br>
            <label>
                <input type="checkbox" id="only-unassigned-patients" checked>
                Doar pacienții fără familie
            </label>
        </div>

        <div class="preview-section">
            <h4>Previzualizare Familii Detectate:</h4>
            <div id="families-preview" style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <p>Click pe "Detectează Familii" pentru a vedea previzualizarea...</p>
            </div>
        </div>

        <div class="auto-create-actions">
            <button type="button" class="button button-secondary" id="detect-families-btn">Detectează Familii</button>
            <button type="button" class="button button-primary" id="create-families-auto-btn" style="display: none;">Creează Familiile Detectate</button>
            <button type="button" class="button button-link" id="cancel-auto-create-btn">Anulează</button>
                    </div>
        </div>

        <!-- Secțiunea pentru log-uri -->
        <div class="family-logs-section" style="display: none; background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3>Log-uri Creare Automată Familii</h3>
            <p>Istoricul creării automate a familiilor pe baza adreselor de email.</p>
            
            <div class="logs-controls">
                <button type="button" class="button button-secondary" id="refresh-logs-btn">Reîmprospătează</button>
                <button type="button" class="button button-link" id="export-logs-btn">Exportă Log-uri</button>
                <button type="button" class="button button-link" id="clear-old-logs-btn">Șterge Log-uri Vechi</button>
            </div>
            
            <div class="logs-container">
                <div id="family-logs-list" style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <p>Se încarcă log-urile...</p>
                </div>
            </div>
        </div>

    <!-- Secțiunea pentru afișarea familiilor -->
    <div class="clinica-families-container">
        <div class="clinica-section-header">
            <h3>Familii Existent</h3>
            <div class="clinica-header-actions">
                <button class="button button-secondary" id="refresh-families-btn">
                    <span class="dashicons dashicons-update"></span>
                    Reîmprospătează
                </button>
            </div>
        </div>
        
        <!-- Statistici familii -->
        <div class="clinica-stats-container">
            <div class="clinica-stat-card">
                <div class="stat-icon">🏠</div>
                <div class="stat-number" id="total-families">-</div>
                <div class="stat-label">Total Familii</div>
            </div>
            <div class="clinica-stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number" id="total-members">-</div>
                <div class="stat-label">Total Membri</div>
            </div>
            <div class="clinica-stat-card">
                <div class="stat-icon">👑</div>
                <div class="stat-number" id="families-with-head">-</div>
                <div class="stat-label">Familii cu Reprezentant</div>
            </div>
        </div>

        <!-- Container pentru tabelul de familii -->
        <div class="clinica-table-container">
            <!-- Loading indicator -->
            <div class="clinica-loading" id="families-loading">
                <div class="loading-spinner"></div>
                <p>Se încarcă familiile...</p>
            </div>

                         <!-- Paginare deasupra tabelului -->
             <div class="clinica-pagination" id="families-pagination" style="display: none;">
                 <div class="pagination-left">
                     <div class="pagination-info">
                         <span id="pagination-info">Se afișează familiile 1-5 din 463 total</span>
                     </div>
                                              <div class="per-page-selector">
                             <label for="per-page-select">Familii per pagină:</label>
                                                           <select id="per-page-select">
                                  <option value="5" selected>5</option>
                                  <option value="10">10</option>
                                  <option value="20">20</option>
                                  <option value="50">50</option>
                                  <option value="100">100</option>
                                  <option value="200">200</option>
                                  <option value="0">Toți</option>
                              </select>
                         </div>
                 </div>
                 <div class="pagination-controls">
                     <button class="button button-secondary" id="prev-page-btn" disabled>
                         <span class="dashicons dashicons-arrow-left-alt2"></span>
                         Anterior
                     </button>
                     <div class="page-numbers" id="page-numbers">
                         <!-- Numerele paginilor vor fi generate dinamic -->
                     </div>
                     <button class="button button-secondary" id="next-page-btn">
                         Următor
                         <span class="dashicons dashicons-arrow-right-alt2"></span>
                     </button>
                 </div>
             </div>
             
             <!-- Tabelul pentru familii (se încarcă via AJAX) -->
             <div class="clinica-families-table" id="families-grid" style="display: none;">
                 <table class="wp-list-table widefat fixed striped">
                     <thead>
                         <tr>
                             <th scope="col" class="manage-column column-family-name">Nume Familie</th>
                             <th scope="col" class="manage-column column-members-count">Membri</th>
                             <th scope="col" class="manage-column column-head">Reprezentant familie</th>
                             <th scope="col" class="manage-column column-actions">Acțiuni</th>
                         </tr>
                     </thead>
                     <tbody>
                         <!-- Familiile vor fi încărcate prin AJAX -->
                     </tbody>
                 </table>
             </div>

            <!-- Mesaj pentru când nu sunt familii -->
            <div class="clinica-empty-state" id="no-families-message" style="display: none;">
                <div class="empty-icon">🏠</div>
                <h3>Nu există familii create</h3>
                <p>Nu au fost create familii încă. Folosește butonul "Creează Familie Nouă" sau "Creează Familii Automat" pentru a începe.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal pentru crearea unei familii -->
<div id="create-family-modal" class="clinica-modal" style="display: none;">
    <div class="clinica-modal-content">
        <div class="clinica-modal-header">
            <h2>Creează Familie Nouă</h2>
            <button class="clinica-modal-close">&times;</button>
        </div>
        
        <form method="post" class="clinica-family-form">
            <?php wp_nonce_field('clinica_family_action'); ?>
            <input type="hidden" name="action" value="create_family">
            
            <div class="form-group">
                <label for="family_name">Numele familiei *</label>
                <input type="text" id="family_name" name="family_name" required 
                       placeholder="Ex: Familia Popescu">
            </div>
            
            <div class="form-group">
                <label for="head_patient_id">Cap de familie (opțional)</label>
                <select id="head_patient_id" name="head_patient_id">
                    <option value="">Selectează un pacient</option>
                    <?php foreach ($patients_without_family as $patient): ?>
                        <option value="<?php echo $patient->id; ?>">
                            <?php echo esc_html($patient->display_name); ?> 
                            (<?php echo esc_html($patient->cnp); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button button-primary">Creează familia</button>
                <button type="button" class="button clinica-modal-close">Anulează</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pentru adăugarea unui membru -->
<div id="add-member-modal" class="clinica-modal" style="display: none;">
    <div class="clinica-modal-content">
        <div class="clinica-modal-header">
            <h2>Adaugă Membru în Familie</h2>
            <button class="clinica-modal-close">&times;</button>
        </div>
        
        <form method="post" class="clinica-family-form">
            <?php wp_nonce_field('clinica_family_action'); ?>
            <input type="hidden" name="action" value="add_member">
            <input type="hidden" id="add_member_family_id" name="family_id">
            
            <div class="form-group">
                <label for="add_member_patient_id">Selectează pacientul *</label>
                <select id="add_member_patient_id" name="patient_id" required>
                    <option value="">Selectează un pacient</option>
                    <?php foreach ($patients_without_family as $patient): ?>
                        <option value="<?php echo $patient->id; ?>">
                            <?php echo esc_html($patient->display_name); ?> 
                            (<?php echo esc_html($patient->cnp); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="add_member_role">Rolul în familie *</label>
                <select id="add_member_role" name="family_role" required>
                    <option value="">Selectează rolul</option>
                    <option value="spouse">Soț/Soție</option>
                    <option value="child">Copil</option>
                    <option value="parent">Părinte</option>
                    <option value="sibling">Frate/Soră</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button button-primary">Adaugă membru</button>
                <button type="button" class="button clinica-modal-close">Anulează</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pentru confirmarea eliminării -->
<div id="remove-member-modal" class="clinica-modal" style="display: none;">
    <div class="clinica-modal-content">
        <div class="clinica-modal-header">
            <h2>Confirmă eliminarea</h2>
            <button class="clinica-modal-close">&times;</button>
        </div>
        
        <div class="clinica-modal-body">
            <p>Ești sigur că vrei să elimini acest membru din familie?</p>
            <p><strong>Notă:</strong> Această acțiune nu șterge pacientul, doar îl elimină din familie.</p>
        </div>
        
        <form method="post" class="clinica-family-form">
            <?php wp_nonce_field('clinica_family_action'); ?>
            <input type="hidden" name="action" value="remove_member">
            <input type="hidden" id="remove_member_patient_id" name="patient_id">
            
            <div class="form-actions">
                <button type="submit" class="button button-danger">Elimină din familie</button>
                <button type="button" class="button clinica-modal-close">Anulează</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pentru editarea rolului unui membru -->
<div id="edit-member-role-modal" class="clinica-modal" style="display: none;">
    <div class="clinica-modal-content">
        <div class="clinica-modal-header">
            <h2>Editează Rolul Membrului</h2>
            <button class="clinica-modal-close">&times;</button>
        </div>
        
        <div class="clinica-modal-body">
            <p>Schimbă rolul membrului <strong id="edit-role-member-name"></strong> în familie.</p>
        </div>
        
        <form method="post" class="clinica-family-form" id="edit-role-form">
            <?php wp_nonce_field('clinica_family_action'); ?>
            <input type="hidden" name="action" value="edit_member_role">
            <input type="hidden" id="edit_role_patient_id" name="patient_id">
            <input type="hidden" id="edit_role_current_role" name="current_role">
            
            <div class="form-group">
                <label for="edit_role_new_role">Noul rol în familie *</label>
                <select id="edit_role_new_role" name="new_role" required>
                    <option value="">Selectează noul rol</option>
                    <option value="head">Reprezentant familie</option>
                    <option value="spouse">Soț/Soție</option>
                    <option value="child">Copil</option>
                    <option value="parent">Părinte</option>
                    <option value="sibling">Frate/Soră</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button button-primary">Salvează rolul</button>
                <button type="button" class="button clinica-modal-close">Anulează</button>
            </div>
        </form>
    </div>
</div>

<style>
.clinica-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e1e5e9;
}

.clinica-section-header h3 {
    margin: 0;
    font-size: 24px;
    color: #2c3e50;
    font-weight: 600;
}

.clinica-header-actions {
    display: flex;
    gap: 10px;
}

 .clinica-stats-container {
     display: grid;
     grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
     gap: 25px;
     margin: 30px 0;
 }

 .clinica-stat-card {
     background: white;
     padding: 15px 20px;
     border-radius: 12px;
     box-shadow: 0 4px 15px rgba(0,0,0,0.1);
     text-align: center;
     color: #2c3e50;
     transition: all 0.3s ease;
     border: 2px solid #e1e5e9;
 }

.clinica-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #667eea;
}

 .stat-icon {
     font-size: 1.5em;
     margin-bottom: 8px;
     color: #667eea;
 }
 
 .stat-number {
     font-size: 1.5em;
     font-weight: 700;
     margin-bottom: 4px;
     color: #2c3e50;
 }
 
 .stat-label {
     font-size: 11px;
     font-weight: 500;
     text-transform: uppercase;
     letter-spacing: 0.5px;
     color: #6c757d;
 }

.clinica-families-container {
    margin-top: 40px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 30px;
    border: 1px solid #e1e5e9;
}

.clinica-table-container {
    margin-top: 30px;
    position: relative;
}

.clinica-loading {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 2px dashed #dee2e6;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.clinica-loading p {
    color: #6c757d;
    font-size: 16px;
    font-weight: 500;
    margin: 0;
}

.clinica-families-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid #e1e5e9;
}

.clinica-families-table table {
    margin: 0;
    border: none;
}

.clinica-families-table thead th {
    background: #2c3e50;
    color: white !important;
    font-weight: 700;
    border: none;
    padding: 20px 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 14px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.clinica-families-table tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.clinica-families-table tbody td {
    padding: 20px 15px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

/* Stilizare paginare */
.clinica-pagination {
    background: #f8f9fa;
    padding: 20px;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.pagination-left {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.per-page-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.per-page-selector label {
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
}

.per-page-selector select {
    padding: 6px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: white url('data:image/svg+xml;utf8,<svg fill="%23666" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 8px center;
    background-size: 16px;
    color: #495057;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 32px;
    min-width: 60px;
}

.per-page-selector select:hover {
    border-color: #667eea;
}

.per-page-selector select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.pagination-info {
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-numbers {
    display: flex;
    gap: 5px;
}

.page-numbers .page-number {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    background: white;
    color: #495057;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
}

.page-numbers .page-number:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.page-numbers .page-number.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.page-numbers .page-number.disabled {
    color: #adb5bd;
    cursor: not-allowed;
    background: #f8f9fa;
}

.pagination-controls .button {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.pagination-controls .button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.clinica-family-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.family-header {
    background: #f8f9fa;
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.family-header h3 {
    margin: 0;
    color: #333;
}

.family-member-count {
    background: #0073aa;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}

.family-members {
    padding: 15px;
}

.family-member {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.family-member:last-child {
    border-bottom: none;
}

.member-info {
    display: flex;
    flex-direction: column;
}

.member-name {
    font-weight: 500;
    color: #333;
}

.member-role {
    font-size: 0.8em;
    color: #666;
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    margin-top: 2px;
}

.member-actions {
    display: flex;
    gap: 5px;
}

.family-actions {
    padding: 15px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 10px;
}

.clinica-empty-state {
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    border: 2px dashed #dee2e6;
    margin: 20px 0;
}

.empty-icon {
    font-size: 5em;
    margin-bottom: 25px;
    opacity: 0.7;
}

.clinica-empty-state h3 {
    color: #495057;
    font-size: 24px;
    margin-bottom: 15px;
    font-weight: 600;
}

.clinica-empty-state p {
    color: #6c757d;
    font-size: 16px;
    line-height: 1.6;
    margin: 0;
}

/* Stilizare pentru butoanele din tabel */
.clinica-families-table .button {
    border-radius: 6px;
    font-weight: 500;
    padding: 8px 16px;
    font-size: 13px;
    transition: all 0.2s ease;
    border: none;
    margin-right: 8px;
}

.clinica-families-table .button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.clinica-families-table .add-member-btn {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.clinica-families-table .button-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

.clinica-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.clinica-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.clinica-modal-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.clinica-modal-header h2 {
    margin: 0;
}

.clinica-modal-close {
    background: none;
    border: none;
    font-size: 1.5em;
    cursor: pointer;
    color: #666;
}

.clinica-modal-body {
    padding: 20px;
}

.clinica-family-form {
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
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
</style>

<script>
 jQuery(document).ready(function($) {
     // Variabile pentru paginare - TREBUIE DEFINITE ÎNAINTE de loadFamilies()!
     var currentPage = 1;
     var familiesPerPage = 5; // Default la 5 familii per pagină
     var totalFamilies = 0;
     
     // Sincronizează selectorul cu variabila JavaScript la încărcarea paginii
     $('#per-page-select').val('5');
     
     // Încarcă familiile la încărcarea paginii
     loadFamilies();
    
    // Butonul de refresh pentru familii
    $('#refresh-families-btn').click(function(e) {
        e.preventDefault();
        loadFamilies(1);
    });
    
    // Event handlers pentru paginare
    $(document).on('click', '.page-number:not(.disabled)', function() {
        var page = parseInt($(this).data('page'));
        if (page && page !== currentPage) {
            loadFamilies(page);
        }
    });
    
    $('#prev-page-btn').click(function(e) {
        e.preventDefault();
        if (currentPage > 1) {
            loadFamilies(currentPage - 1);
        }
    });
    
    $('#next-page-btn').click(function(e) {
        e.preventDefault();
        var totalPages = Math.ceil(totalFamilies / familiesPerPage);
        if (currentPage < totalPages) {
            loadFamilies(currentPage + 1);
        }
    });
    
    // Modal pentru crearea unei familii
    $('#create-family-btn, #create-first-family-btn').click(function(e) {
        e.preventDefault();
        $('#create-family-modal').show();
    });
    
    // Modal pentru adăugarea unui membru
    $('.add-member-btn').click(function(e) {
        e.preventDefault();
        var familyId = $(this).data('family-id');
        $('#add_member_family_id').val(familyId);
        $('#add-member-modal').show();
    });
    
    // Modal pentru eliminarea unui membru
    $('.remove-member-btn').click(function(e) {
        e.preventDefault();
        var patientId = $(this).data('patient-id');
        $('#remove_member_patient_id').val(patientId);
        $('#remove-member-modal').show();
    });

    // Modal pentru editarea rolului unui membru
    $('.edit-member-role-btn').click(function(e) {
        e.preventDefault();
        var memberName = $(this).data('member-name');
        var patientId = $(this).data('patient-id');
        var currentRole = $(this).data('current-role');

        $('#edit-role-member-name').text(memberName);
        $('#edit_role_patient_id').val(patientId);
        $('#edit_role_current_role').val(currentRole);
        $('#edit-member-role-modal').show();
    });
    
    // Închiderea modalelor
    $('.clinica-modal-close').click(function() {
        $('.clinica-modal').hide();
    });
    
    // Închiderea modalelor la click în afara lor
    $('.clinica-modal').click(function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Actualizare pagină după acțiuni
    $('.clinica-family-form').submit(function() {
        // Aici se poate adăuga logica pentru actualizare AJAX
    });

    // Funcționalitatea pentru crearea automată a familiilor
    $('#auto-create-families-btn').click(function(e) {
        e.preventDefault();
        $('.auto-create-families-section').show();
        $(this).hide();
    });

    $('#cancel-auto-create-btn').click(function(e) {
        e.preventDefault();
        $('.auto-create-families-section').hide();
        $('#auto-create-families-btn').show();
        $('#families-preview').html('<p>Click pe "Detectează Familii" pentru a vedea previzualizarea...</p>');
        $('#create-families-auto-btn').hide();
    });

    $('#detect-families-btn').click(function(e) {
        e.preventDefault();
        
        var options = {
            create_parent_as_head: $('#create-parent-as-head').is(':checked'),
            assign_roles_automatically: $('#assign-roles-automatically').is(':checked'),
            only_unassigned_patients: $('#only-unassigned-patients').is(':checked')
        };

        $('#families-preview').html('<p>Se detectează familiile...</p>');

        $.ajax({
            url: (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.ajaxurl) ? 
                 clinica_autosuggest.ajaxurl : 
                 (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
            type: 'POST',
            data: {
                action: 'clinica_detect_families',
                options: options,
                nonce: '<?php echo wp_create_nonce("clinica_family_auto_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#families-preview').html(response.data.html);
                    $('#create-families-auto-btn').show();
                } else {
                    $('#families-preview').html('<p style="color: red;">Eroare: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#families-preview').html('<p style="color: red;">Eroare la detectarea familiilor</p>');
            }
        });
    });

    $('#create-families-auto-btn').click(function(e) {
        e.preventDefault();
        
        var options = {
            create_parent_as_head: $('#create-parent-as-head').is(':checked'),
            assign_roles_automatically: $('#assign-roles-automatically').is(':checked'),
            only_unassigned_patients: $('#only-unassigned-patients').is(':checked')
        };

        $(this).prop('disabled', true).text('Se creează familiile...');

        $.ajax({
            url: (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.ajaxurl) ? 
                 clinica_autosuggest.ajaxurl : 
                 (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
            type: 'POST',
            data: {
                action: 'clinica_create_families_auto',
                options: options,
                nonce: '<?php echo wp_create_nonce("clinica_family_auto_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Familiile au fost create cu succes! ' + response.data.created + ' familii create.');
                    location.reload();
                } else {
                    alert('Eroare: ' + response.data);
                    $('#create-families-auto-btn').prop('disabled', false).text('Creează Familiile Detectate');
                }
            },
            error: function() {
                alert('Eroare la crearea familiilor');
                $('#create-families-auto-btn').prop('disabled', false).text('Creează Familiile Detectate');
            }
        });
            });
        
        // Funcționalitatea pentru log-uri
        $('#view-family-logs-btn').click(function(e) {
            e.preventDefault();
            $('.auto-create-families-section').hide();
            $('.family-logs-section').show();
            $(this).hide();
            $('#auto-create-families-btn').show();
            loadFamilyLogs();
        });
        
        $('#refresh-logs-btn').click(function(e) {
            e.preventDefault();
            loadFamilyLogs();
        });
        
        $('#export-logs-btn').click(function(e) {
            e.preventDefault();
            exportFamilyLogs();
        });
        
        $('#clear-old-logs-btn').click(function(e) {
            e.preventDefault();
            if (confirm('Ești sigur că vrei să ștergi log-urile vechi (mai vechi de 30 de zile)?')) {
                clearOldLogs();
            }
        });

                 // Funcționalitatea pentru editarea rolului unui membru
         $('#edit-role-form').submit(function(e) {
             e.preventDefault();
             var patientId = $('#edit_role_patient_id').val();
             var newRole = $('#edit_role_new_role').val();
             var currentRole = $('#edit_role_current_role').val();

             if (newRole === currentRole) {
                 alert('Rolul nu a fost schimbat. Selectați un rol diferit.');
                 return;
             }

             $(this).prop('disabled', true).text('Se salvează rolul...');

             $.ajax({
                 url: (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.ajaxurl) ? 
                      clinica_autosuggest.ajaxurl : 
                      (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
                 type: 'POST',
                 data: {
                     action: 'clinica_edit_member_role',
                     patient_id: patientId,
                     new_role: newRole,
                     nonce: '<?php echo wp_create_nonce("clinica_family_action"); ?>'
                 },
                 success: function(response) {
                     if (response.success) {
                         alert('Rolul membrului a fost schimbat cu succes!');
                         location.reload(); // Reîncarcă pagina pentru a actualiza rolul în tabel
                     } else {
                         alert('Eroare: ' + response.data);
                         $('#edit-role-form').prop('disabled', false).text('Salvează rolul');
                     }
                 },
                 error: function() {
                     alert('Eroare la salvarea rolului');
                     $('#edit-role-form').prop('disabled', false).text('Salvează rolul');
                 }
             });
         });
         
         // Event handler pentru schimbarea numărului de familii per pagină
         $('#per-page-select').change(function() {
             var selectedValue = $(this).val();
             if (selectedValue === '0') {
                 familiesPerPage = 0; // Toți
             } else {
                 familiesPerPage = parseInt(selectedValue);
             }
             currentPage = 1; // Reset la prima pagină
             loadFamilies(1);
         });
        
                 function loadFamilies(page = 1) {
             currentPage = page;
             
             // Asigură-te că se folosește familiesPerPage corect
             if (familiesPerPage === 0) {
                 familiesPerPage = 5; // Default la 5 dacă nu e setat
             }
             
             // Ascunde mesajul de eroare și afișează loading
             $('#families-loading').show();
             $('#families-grid').hide();
             $('#no-families-message').hide();
             $('#families-pagination').hide();
            
            $.ajax({
                url: (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.ajaxurl) ? 
                     clinica_autosuggest.ajaxurl : 
                     (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
                type: 'POST',
                data: {
                    action: 'clinica_get_families',
                    page: page,
                    per_page: familiesPerPage,
                    nonce: '<?php echo wp_create_nonce("clinica_family_list_nonce"); ?>'
                },
                beforeSend: function() {
                    console.log('DEBUG: Trimit AJAX cu:', {
                        page: page,
                        per_page: familiesPerPage,
                        familiesPerPage_type: typeof familiesPerPage
                    });
                },
                success: function(response) {
                    $('#families-loading').hide();
                    
                    if (response.success) {
                        // Actualizează tbody-ul cu familiile
                        $('#families-grid tbody').html(response.data.html);
                        
                        // Actualizează statisticile
                        if (response.data.stats) {
                            totalFamilies = response.data.stats.total_families;
                            $('#total-families').text(response.data.stats.total_families);
                            $('#total-members').text(response.data.stats.total_members);
                            $('#families-with-head').text(response.data.stats.families_with_head);
                        }
                        
                        // Afișează sau ascunde mesajul pentru familii goale
                        if (response.data.stats.total_families === 0) {
                            $('#no-families-message').show();
                            $('#families-grid').hide();
                        } else {
                            $('#no-families-message').hide();
                            $('#families-grid').show();
                            
                                                         // Afișează paginarea dacă sunt mai multe familii decât per_page și nu sunt "Toți"
                            if (familiesPerPage === 0) {
                                // Pentru "Toți" - ascunde paginarea și afișează toate familiile
                                $('#families-pagination').hide();
                            } else if (familiesPerPage > 0 && response.data.stats.total_families > familiesPerPage) {
                                // Afișează paginarea pentru numărul selectat de familii per pagină
                                $('#families-pagination').show();
                                updatePagination(page, response.data.stats.total_families);
                            } else {
                                // Ascunde paginarea dacă toate familiile încap pe o pagină
                                $('#families-pagination').hide();
                            }
                        }
                    } else {
                        $('#families-grid tbody').html('<tr><td colspan="4" style="color: red;">Eroare: ' + response.data + '</td></tr>');
                    }
                },
                error: function() {
                    $('#families-loading').hide();
                    $('#families-grid tbody').html('<tr><td colspan="4" style="color: red;">Eroare la încărcarea familiilor</td></tr>');
                }
            });
        }
        
        function updatePagination(currentPage, totalFamilies) {
            // Dacă familiesPerPage = 0 (Toți), nu se aplică paginarea
            if (familiesPerPage === 0) {
                $('#pagination-info').text('Se afișează toate familiile (' + totalFamilies + ' total)');
                $('#page-numbers').html('');
                $('#prev-page-btn').prop('disabled', true);
                $('#next-page-btn').prop('disabled', true);
                return;
            }
            
            var totalPages = Math.ceil(totalFamilies / familiesPerPage);
            var startFamily = (currentPage - 1) * familiesPerPage + 1;
            var endFamily = Math.min(currentPage * familiesPerPage, totalFamilies);
            
            // Actualizează informațiile despre paginare
            $('#pagination-info').text('Se afișează familiile ' + startFamily + '-' + endFamily + ' din ' + totalFamilies + ' total');
            
            // Actualizează butoanele de navigare
            $('#prev-page-btn').prop('disabled', currentPage === 1);
            $('#next-page-btn').prop('disabled', currentPage === totalPages);
            
            // Generează numerele paginilor
            var pageNumbers = '';
            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);
            
            // Adaugă prima pagină dacă nu este vizibilă
            if (startPage > 1) {
                pageNumbers += '<span class="page-number" data-page="1">1</span>';
                if (startPage > 2) {
                    pageNumbers += '<span class="page-number disabled">...</span>';
                }
            }
            
            // Adaugă paginile din mijloc
            for (var i = startPage; i <= endPage; i++) {
                var activeClass = (i === currentPage) ? 'active' : '';
                pageNumbers += '<span class="page-number ' + activeClass + '" data-page="' + i + '">' + i + '</span>';
            }
            
            // Adaugă ultima pagină dacă nu este vizibilă
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    pageNumbers += '<span class="page-number disabled">...</span>';
                }
                pageNumbers += '<span class="page-number" data-page="' + totalPages + '">' + totalPages + '</span>';
            }
            
            $('#page-numbers').html(pageNumbers);
        }
        
        function loadFamilyLogs() {
            $('#family-logs-list').html('<p>Se încarcă log-urile...</p>');
            
                    $.ajax({
            url: (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.ajaxurl) ? 
                 clinica_autosuggest.ajaxurl : 
                 (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
            type: 'POST',
            data: {
                action: 'clinica_get_family_logs',
                nonce: '<?php echo wp_create_nonce("clinica_family_logs_nonce"); ?>'
            },
                success: function(response) {
                    if (response.success) {
                        $('#family-logs-list').html(response.data.html);
                    } else {
                        $('#family-logs-list').html('<p style="color: red;">Eroare: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    $('#family-logs-list').html('<p style="color: red;">Eroare la încărcarea log-urilor</p>');
                }
            });
        }
        
        function exportFamilyLogs() {
            var ajaxUrl = (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.ajaxurl) ? 
                      clinica_autosuggest.ajaxurl : 
                      (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
        window.open(ajaxUrl + '?action=clinica_export_family_logs&nonce=<?php echo wp_create_nonce("clinica_family_logs_nonce"); ?>', '_blank');
        }
        
        function clearOldLogs() {
                    $.ajax({
            url: (typeof clinica_autosuggest !== 'undefined' && clinica_autosuggest.ajaxurl) ? 
                 clinica_autosuggest.ajaxurl : 
                 (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
            type: 'POST',
            data: {
                action: 'clinica_clear_old_logs',
                nonce: '<?php echo wp_create_nonce("clinica_family_logs_nonce"); ?>'
            },
                success: function(response) {
                    if (response.success) {
                        alert('Log-urile vechi au fost șterse cu succes!');
                        loadFamilyLogs();
                    } else {
                        alert('Eroare: ' + response.data);
                    }
                },
                error: function() {
                    alert('Eroare la ștergerea log-urilor');
                }
            });
        }
    });
</script> 