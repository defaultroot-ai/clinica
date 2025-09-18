<?php
/**
 * Importatori pentru Clinica
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clinica_Importers {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_clinica_import_patients', array($this, 'ajax_import_patients'));
        add_action('wp_ajax_clinica_import_progress', array($this, 'ajax_import_progress'));
    }
    
    /**
     * AJAX pentru importul pacienților
     */
    public function ajax_import_patients() {
        check_ajax_referer('clinica_import_patients', 'nonce');
        
        // Verifică permisiunile
        if (!Clinica_Patient_Permissions::can_import_patients()) {
            wp_send_json_error('Nu aveți permisiunea de a importa pacienți');
        }
        
        $import_type = sanitize_text_field($_POST['import_type']);
        $file_data = $_FILES['import_file'] ?? null;
        
        if (!$file_data) {
            wp_send_json_error('Nu a fost selectat niciun fișier');
        }
        
        // Verifică tipul fișierului
        $allowed_types = array('csv', 'xlsx', 'xls');
        $file_extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            wp_send_json_error('Tipul de fișier nu este suportat. Folosiți CSV sau Excel.');
        }
        
        // Procesează importul în background
        $import_id = $this->start_import($import_type, $file_data);
        
        if ($import_id) {
            wp_send_json_success(array(
                'import_id' => $import_id,
                'message' => 'Importul a început. Veți fi notificat când se termină.'
            ));
        } else {
            wp_send_json_error('Eroare la pornirea importului');
        }
    }
    
    /**
     * AJAX pentru progresul importului
     */
    public function ajax_import_progress() {
        check_ajax_referer('clinica_import_progress', 'nonce');
        
        $import_id = intval($_POST['import_id']);
        
        $progress = $this->get_import_progress($import_id);
        
        wp_send_json_success($progress);
    }
    
    /**
     * Începe un import
     */
    private function start_import($import_type, $file_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_imports';
        
        // Creează înregistrarea de import
        $import_data = array(
            'import_type' => $import_type,
            'filename' => $file_data['name'],
            'status' => 'pending',
            'created_by' => get_current_user_id()
        );
        
        $result = $wpdb->insert($table_name, $import_data);
        
        if (!$result) {
            return false;
        }
        
        $import_id = $wpdb->insert_id;
        
        // Salvează fișierul
        $upload_dir = wp_upload_dir();
        $import_dir = $upload_dir['basedir'] . '/clinica-imports/';
        
        if (!is_dir($import_dir)) {
            wp_mkdir_p($import_dir);
        }
        
        $file_path = $import_dir . 'import_' . $import_id . '_' . time() . '.' . pathinfo($file_data['name'], PATHINFO_EXTENSION);
        
        if (move_uploaded_file($file_data['tmp_name'], $file_path)) {
            // Actualizează calea fișierului
            $wpdb->update($table_name, array('filename' => $file_path), array('id' => $import_id));
            
            // Pornește procesarea în background
            $this->process_import_background($import_id, $file_path, $import_type);
            
            return $import_id;
        }
        
        return false;
    }
    
    /**
     * Procesează importul în background
     */
    private function process_import_background($import_id, $file_path, $import_type) {
        // În practică, aici ar trebui să folosești un sistem de job-uri în background
        // Pentru moment, vom procesa direct
        
        $this->process_import($import_id, $file_path, $import_type);
    }
    
    /**
     * Procesează importul
     */
    private function process_import($import_id, $file_path, $import_type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_imports';
        
        // Actualizează statusul la processing
        $wpdb->update($table_name, array('status' => 'processing'), array('id' => $import_id));
        
        $imported_count = 0;
        $failed_count = 0;
        $error_log = array();
        
        try {
            // Citește fișierul
            $data = $this->read_import_file($file_path);
            
            if (!$data) {
                throw new Exception('Nu s-a putut citi fișierul');
            }
            
            $total_records = count($data);
            
            // Actualizează numărul total de înregistrări
            $wpdb->update($table_name, array('total_records' => $total_records), array('id' => $import_id));
            
            // Procesează fiecare înregistrare
            foreach ($data as $index => $row) {
                try {
                    $result = $this->import_patient_record($row, $import_type);
                    
                    if ($result['success']) {
                        $imported_count++;
                    } else {
                        $failed_count++;
                        $error_log[] = "Rândul " . ($index + 2) . ": " . $result['message'];
                    }
                    
                    // Actualizează progresul la fiecare 10 înregistrări
                    if (($index + 1) % 10 === 0) {
                        $wpdb->update($table_name, array(
                            'imported_records' => $imported_count,
                            'failed_records' => $failed_count
                        ), array('id' => $import_id));
                    }
                    
                } catch (Exception $e) {
                    $failed_count++;
                    $error_log[] = "Rândul " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            
            // Finalizează importul
            $wpdb->update($table_name, array(
                'status' => 'completed',
                'imported_records' => $imported_count,
                'failed_records' => $failed_count,
                'error_log' => json_encode($error_log),
                'completed_at' => current_time('mysql')
            ), array('id' => $import_id));
            
        } catch (Exception $e) {
            // Marchează importul ca eșuat
            $wpdb->update($table_name, array(
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'completed_at' => current_time('mysql')
            ), array('id' => $import_id));
        }
    }
    
    /**
     * Citește fișierul de import
     */
    private function read_import_file($file_path) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'csv':
                return $this->read_csv_file($file_path);
            case 'xlsx':
            case 'xls':
                return $this->read_excel_file($file_path);
            default:
                return false;
        }
    }
    
    /**
     * Citește fișierul CSV
     */
    private function read_csv_file($file_path) {
        $data = array();
        
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            // Citește header-ul
            $headers = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== FALSE) {
                $data[] = array_combine($headers, $row);
            }
            
            fclose($handle);
        }
        
        return $data;
    }
    
    /**
     * Citește fișierul Excel
     */
    private function read_excel_file($file_path) {
        // Pentru Excel, ar trebui să folosești o librărie precum PhpSpreadsheet
        // Pentru moment, vom returna false
        return false;
    }
    
    /**
     * Importă o înregistrare de pacient
     */
    private function import_patient_record($row, $import_type) {
        // Mapează coloanele în funcție de tipul de import
        $mapped_data = $this->map_import_data($row, $import_type);
        
        // Validează datele
        $validation = $this->validate_import_data($mapped_data);
        if (!$validation['valid']) {
            return array('success' => false, 'message' => $validation['message']);
        }
        
        // Verifică dacă pacientul există deja
        if ($this->patient_exists($mapped_data['cnp'])) {
            return array('success' => false, 'message' => 'Pacientul cu acest CNP există deja');
        }
        
        // Creează pacientul
        $form = new Clinica_Patient_Creation_Form();
        $result = $form->create_patient($mapped_data);
        
        return $result;
    }
    
    /**
     * Mapează datele de import
     */
    private function map_import_data($row, $import_type) {
        $mapped_data = array();
        
        switch ($import_type) {
            case 'icmed':
                $mapped_data = $this->map_icmed_data($row);
                break;
            case 'joomla':
                $mapped_data = $this->map_joomla_data($row);
                break;
            case 'csv':
                $mapped_data = $this->map_csv_data($row);
                break;
            default:
                $mapped_data = $row;
        }
        
        return $mapped_data;
    }
    
    /**
     * Mapează datele ICMED
     */
    private function map_icmed_data($row) {
        return array(
            'cnp' => $row['CNP'] ?? $row['cnp'] ?? '',
            'cnp_type' => 'romanian',
            'first_name' => $row['Prenume'] ?? $row['prenume'] ?? '',
            'last_name' => $row['Nume'] ?? $row['nume'] ?? '',
            'email' => $row['Email'] ?? $row['email'] ?? '',
            'phone_primary' => $row['Telefon'] ?? $row['telefon'] ?? '',
            'phone_secondary' => $row['Telefon2'] ?? $row['telefon2'] ?? '',
            'birth_date' => $this->format_date($row['Data_nasterii'] ?? $row['data_nasterii'] ?? ''),
            'gender' => $this->map_gender($row['Sex'] ?? $row['sex'] ?? ''),
            'address' => $row['Adresa'] ?? $row['adresa'] ?? '',
            'emergency_contact' => $row['Contact_urgenta'] ?? $row['contact_urgenta'] ?? '',
            'blood_type' => $row['Grupa_sanguina'] ?? $row['grupa_sanguina'] ?? '',
            'allergies' => $row['Alergii'] ?? $row['alergii'] ?? '',
            'medical_history' => $row['Istoric_medical'] ?? $row['istoric_medical'] ?? '',
            'password_method' => 'cnp',
            'import_source' => 'icmed'
        );
    }
    
    /**
     * Mapează datele Joomla
     */
    private function map_joomla_data($row) {
        return array(
            'cnp' => $row['cnp'] ?? $row['CNP'] ?? '',
            'cnp_type' => 'romanian',
            'first_name' => $row['first_name'] ?? $row['prenume'] ?? '',
            'last_name' => $row['last_name'] ?? $row['nume'] ?? '',
            'email' => $row['email'] ?? '',
            'phone_primary' => $row['phone'] ?? $row['telefon'] ?? '',
            'phone_secondary' => $row['phone2'] ?? $row['telefon2'] ?? '',
            'birth_date' => $this->format_date($row['birth_date'] ?? $row['data_nasterii'] ?? ''),
            'gender' => $this->map_gender($row['gender'] ?? $row['sex'] ?? ''),
            'address' => $row['address'] ?? $row['adresa'] ?? '',
            'emergency_contact' => $row['emergency_contact'] ?? $row['contact_urgenta'] ?? '',
            'blood_type' => $row['blood_type'] ?? $row['grupa_sanguina'] ?? '',
            'allergies' => $row['allergies'] ?? $row['alergii'] ?? '',
            'medical_history' => $row['medical_history'] ?? $row['istoric_medical'] ?? '',
            'password_method' => 'cnp',
            'import_source' => 'joomla'
        );
    }
    
    /**
     * Mapează datele CSV generice
     */
    private function map_csv_data($row) {
        return array(
            'cnp' => $row['cnp'] ?? $row['CNP'] ?? '',
            'cnp_type' => $row['cnp_type'] ?? 'romanian',
            'first_name' => $row['first_name'] ?? $row['prenume'] ?? '',
            'last_name' => $row['last_name'] ?? $row['nume'] ?? '',
            'email' => $row['email'] ?? '',
            'phone_primary' => $row['phone_primary'] ?? $row['telefon'] ?? '',
            'phone_secondary' => $row['phone_secondary'] ?? $row['telefon2'] ?? '',
            'birth_date' => $this->format_date($row['birth_date'] ?? $row['data_nasterii'] ?? ''),
            'gender' => $this->map_gender($row['gender'] ?? $row['sex'] ?? ''),
            'address' => $row['address'] ?? $row['adresa'] ?? '',
            'emergency_contact' => $row['emergency_contact'] ?? $row['contact_urgenta'] ?? '',
            'blood_type' => $row['blood_type'] ?? $row['grupa_sanguina'] ?? '',
            'allergies' => $row['allergies'] ?? $row['alergii'] ?? '',
            'medical_history' => $row['medical_history'] ?? $row['istoric_medical'] ?? '',
            'password_method' => $row['password_method'] ?? 'cnp',
            'import_source' => 'csv'
        );
    }
    
    /**
     * Formatează data
     */
    private function format_date($date_string) {
        if (empty($date_string)) {
            return '';
        }
        
        // Încearcă diferite formate
        $formats = array('Y-m-d', 'd.m.Y', 'd/m/Y', 'm/d/Y', 'Y/m/d');
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $date_string);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        return '';
    }
    
    /**
     * Mapează genul
     */
    private function map_gender($gender) {
        $gender = strtolower(trim($gender));
        
        switch ($gender) {
            case 'm':
            case 'male':
            case 'masculin':
            case 'bărbat':
                return 'male';
            case 'f':
            case 'female':
            case 'feminin':
            case 'femeie':
                return 'female';
            default:
                return '';
        }
    }
    
    /**
     * Validează datele de import
     */
    private function validate_import_data($data) {
        if (empty($data['cnp'])) {
            return array('valid' => false, 'message' => 'CNP-ul este obligatoriu');
        }
        
        if (empty($data['first_name'])) {
            return array('valid' => false, 'message' => 'Prenumele este obligatoriu');
        }
        
        if (empty($data['last_name'])) {
            return array('valid' => false, 'message' => 'Numele este obligatoriu');
        }
        
        if (empty($data['phone_primary'])) {
            return array('valid' => false, 'message' => 'Numărul de telefon principal este obligatoriu');
        }
        
        return array('valid' => true);
    }
    
    /**
     * Verifică dacă pacientul există
     */
    private function patient_exists($cnp) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_patients';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE cnp = %s",
            $cnp
        ));
        
        return $exists > 0;
    }
    
    /**
     * Obține progresul importului
     */
    private function get_import_progress($import_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_imports';
        
        $import = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $import_id
        ));
        
        if (!$import) {
            return array('error' => 'Importul nu a fost găsit');
        }
        
        $progress = 0;
        if ($import->total_records > 0) {
            $progress = round((($import->imported_records + $import->failed_records) / $import->total_records) * 100, 2);
        }
        
        return array(
            'status' => $import->status,
            'total_records' => $import->total_records,
            'imported_records' => $import->imported_records,
            'failed_records' => $import->failed_records,
            'progress' => $progress,
            'error_log' => json_decode($import->error_log, true),
            'started_at' => $import->started_at,
            'completed_at' => $import->completed_at
        );
    }
    
    /**
     * Generează formularul de import
     */
    public function render_import_form() {
        ob_start();
        ?>
        <div class="clinica-import-container">
            <h2><?php _e('Import Pacienți', 'clinica'); ?></h2>
            
            <form id="clinica-import-form" enctype="multipart/form-data">
                <?php wp_nonce_field('clinica_import_patients', 'clinica_import_nonce'); ?>
                
                <div class="form-group">
                    <label for="import_type"><?php _e('Tip de import', 'clinica'); ?></label>
                    <select id="import_type" name="import_type" required>
                        <option value=""><?php _e('Selectează tipul de import', 'clinica'); ?></option>
                        <option value="icmed"><?php _e('ICMED', 'clinica'); ?></option>
                        <option value="joomla"><?php _e('Joomla Community Builder', 'clinica'); ?></option>
                        <option value="csv"><?php _e('CSV Generic', 'clinica'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="import_file"><?php _e('Fișier de import', 'clinica'); ?></label>
                    <input type="file" id="import_file" name="import_file" accept=".csv,.xlsx,.xls" required>
                    <p class="description"><?php _e('Suportă fișiere CSV și Excel (.xlsx, .xls)', 'clinica'); ?></p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Începe importul', 'clinica'); ?></button>
                </div>
            </form>
            
            <div id="import-progress" style="display: none;">
                <h3><?php _e('Progres Import', 'clinica'); ?></h3>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <div class="progress-stats">
                    <span class="imported"><?php _e('Importați: 0', 'clinica'); ?></span>
                    <span class="failed"><?php _e('Eșuați: 0', 'clinica'); ?></span>
                    <span class="total"><?php _e('Total: 0', 'clinica'); ?></span>
                </div>
                <div class="progress-status"></div>
            </div>
            
            <div id="import-results" style="display: none;">
                <h3><?php _e('Rezultate Import', 'clinica'); ?></h3>
                <div class="results-content"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var importForm = $('#clinica-import-form');
            var importProgress = $('#import-progress');
            var importResults = $('#import-results');
            var currentImportId = null;
            var progressInterval = null;
            
            importForm.on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'clinica_import_patients');
                formData.append('nonce', '<?php echo wp_create_nonce('clinica_import_patients'); ?>');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            currentImportId = response.data.import_id;
                            importProgress.show();
                            importForm.hide();
                            
                            // Începe monitorizarea progresului
                            startProgressMonitoring();
                        } else {
                            alert('Eroare: ' + response.data);
                        }
                    }
                });
            });
            
            function startProgressMonitoring() {
                progressInterval = setInterval(function() {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'clinica_import_progress',
                            import_id: currentImportId,
                            nonce: '<?php echo wp_create_nonce('clinica_import_progress'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                updateProgress(response.data);
                                
                                if (response.data.status === 'completed' || response.data.status === 'failed') {
                                    clearInterval(progressInterval);
                                    showResults(response.data);
                                }
                            }
                        }
                    });
                }, 2000); // Verifică la fiecare 2 secunde
            }
            
            function updateProgress(data) {
                $('.progress-fill').css('width', data.progress + '%');
                $('.imported').text('Importați: ' + data.imported_records);
                $('.failed').text('Eșuați: ' + data.failed_records);
                $('.total').text('Total: ' + data.total_records);
                $('.progress-status').text('Status: ' + data.status);
            }
            
            function showResults(data) {
                var resultsHtml = '<div class="import-summary">';
                resultsHtml += '<p><strong>Status:</strong> ' + data.status + '</p>';
                resultsHtml += '<p><strong>Total înregistrări:</strong> ' + data.total_records + '</p>';
                resultsHtml += '<p><strong>Importați cu succes:</strong> ' + data.imported_records + '</p>';
                resultsHtml += '<p><strong>Eșuați:</strong> ' + data.failed_records + '</p>';
                
                if (data.error_log && data.error_log.length > 0) {
                    resultsHtml += '<h4>Erori:</h4><ul>';
                    data.error_log.forEach(function(error) {
                        resultsHtml += '<li>' + error + '</li>';
                    });
                    resultsHtml += '</ul>';
                }
                
                resultsHtml += '</div>';
                
                $('.results-content').html(resultsHtml);
                importResults.show();
            }
        });
        </script>
        
        <style>
        .clinica-import-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .clinica-import-container .form-group {
            margin-bottom: 20px;
        }
        
        .clinica-import-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .clinica-import-container input,
        .clinica-import-container select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #0073aa;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-status {
            font-weight: bold;
            color: #0073aa;
        }
        
        .import-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        
        .import-summary ul {
            margin-left: 20px;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generează HTML-ul pentru istoricul importurilor
     */
    public function get_import_history_html() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clinica_imports';
        
        // Obține ultimele 10 importuri
        $imports = $wpdb->get_results("
            SELECT i.*, u.display_name as created_by_name
            FROM $table_name i
            LEFT JOIN {$wpdb->users} u ON i.created_by = u.ID
            ORDER BY i.started_at DESC
            LIMIT 10
        ");
        
        if (empty($imports)) {
            return '<p>' . __('Nu există importuri în istoric.', 'clinica') . '</p>';
        }
        
        ob_start();
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Data', 'clinica'); ?></th>
                    <th><?php _e('Tip', 'clinica'); ?></th>
                    <th><?php _e('Fișier', 'clinica'); ?></th>
                    <th><?php _e('Status', 'clinica'); ?></th>
                    <th><?php _e('Rezultate', 'clinica'); ?></th>
                    <th><?php _e('Creat de', 'clinica'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($imports as $import): ?>
                <tr>
                    <td><?php echo esc_html(date('d.m.Y H:i', strtotime($import->created_at))); ?></td>
                    <td><?php echo esc_html(ucfirst($import->import_type)); ?></td>
                    <td><?php echo esc_html(basename($import->filename)); ?></td>
                    <td>
                        <span class="import-status status-<?php echo esc_attr($import->status); ?>">
                            <?php echo esc_html($import->status); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($import->status === 'completed'): ?>
                            <?php printf(__('%d importați, %d eșuați', 'clinica'), $import->imported_records, $import->failed_records); ?>
                        <?php elseif ($import->status === 'processing'): ?>
                            <?php printf(__('%d din %d', 'clinica'), $import->imported_records, $import->total_records); ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($import->created_by_name); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <style>
        .import-status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #e7f3ff;
            color: #0073aa;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}

// Inițializează importatorii
new Clinica_Importers(); 