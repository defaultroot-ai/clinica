<?php
/**
 * Pagina pentru rapoarte
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!Clinica_Patient_Permissions::can_view_reports()) {
    wp_die(__('Nu aveți permisiunea de a vedea rapoartele.', 'clinica'));
}

// Obține statisticile
$stats = Clinica_Database::get_database_stats();
?>

<div class="wrap">
    <h1><?php _e('Rapoarte Clinica', 'clinica'); ?></h1>
    
    <div class="clinica-reports-container">
        <!-- Statistici generale -->
        <div class="report-section">
            <h2><?php _e('Statistici Generale', 'clinica'); ?></h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?php _e('Total Pacienți', 'clinica'); ?></h3>
                    <div class="stat-value"><?php echo number_format($stats['total_patients'] ?? 0); ?></div>
                </div>
                
                <div class="stat-item">
                    <h3><?php _e('Total Programări', 'clinica'); ?></h3>
                    <div class="stat-value"><?php echo number_format($stats['total_appointments'] ?? 0); ?></div>
                </div>
                
                <div class="stat-item">
                    <h3><?php _e('Programări Astăzi', 'clinica'); ?></h3>
                    <div class="stat-value"><?php echo number_format($stats['appointments_today'] ?? 0); ?></div>
                </div>
                
                <div class="stat-item">
                    <h3><?php _e('Dosare Medicale', 'clinica'); ?></h3>
                    <div class="stat-value"><?php echo number_format($stats['total_medical_records'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Statistici programări -->
        <div class="report-section">
            <h2><?php _e('Statistici Programări', 'clinica'); ?></h2>
            <?php if (!empty($stats['appointments_by_status'])): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Status', 'clinica'); ?></th>
                        <th><?php _e('Număr', 'clinica'); ?></th>
                        <th><?php _e('Procent', 'clinica'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_appointments = $stats['total_appointments'] ?? 0;
                    foreach ($stats['appointments_by_status'] as $status): 
                        $percentage = $total_appointments > 0 ? round(($status->count / $total_appointments) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?php echo esc_html($status->status); ?></td>
                        <td><?php echo number_format($status->count); ?></td>
                        <td><?php echo $percentage; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p><?php _e('Nu există programări în sistem.', 'clinica'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Statistici pacienți -->
        <div class="report-section">
            <h2><?php _e('Statistici Pacienți', 'clinica'); ?></h2>
            <?php if (!empty($stats['patients_by_cnp_type'])): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Tip CNP', 'clinica'); ?></th>
                        <th><?php _e('Număr', 'clinica'); ?></th>
                        <th><?php _e('Procent', 'clinica'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_patients = $stats['total_patients'] ?? 0;
                    foreach ($stats['patients_by_cnp_type'] as $cnp_type): 
                        $percentage = $total_patients > 0 ? round(($cnp_type->count / $total_patients) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?php echo esc_html($cnp_type->cnp_type); ?></td>
                        <td><?php echo number_format($cnp_type->count); ?></td>
                        <td><?php echo $percentage; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p><?php _e('Nu există pacienți în sistem.', 'clinica'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Statistici autentificare -->
        <div class="report-section">
            <h2><?php _e('Statistici Autentificare', 'clinica'); ?></h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?php _e('Total Încercări', 'clinica'); ?></h3>
                    <div class="stat-value"><?php echo number_format($stats['total_login_attempts'] ?? 0); ?></div>
                </div>
                
                <div class="stat-item">
                    <h3><?php _e('Autentificări Reușite', 'clinica'); ?></h3>
                    <div class="stat-value"><?php echo number_format($stats['successful_logins'] ?? 0); ?></div>
                </div>
                
                <div class="stat-item">
                    <h3><?php _e('Autentificări Eșuate', 'clinica'); ?></h3>
                    <div class="stat-value"><?php echo number_format($stats['failed_logins'] ?? 0); ?></div>
                </div>
                
                <div class="stat-item">
                    <h3><?php _e('Rata de Succes', 'clinica'); ?></h3>
                    <div class="stat-value"><?php echo $stats['success_rate'] ?? 0; ?>%</div>
                </div>
            </div>
        </div>
        
        <!-- Acțiuni rapide -->
        <div class="report-section">
            <h2><?php _e('Acțiuni Rapide', 'clinica'); ?></h2>
            <div class="quick-actions">
                <?php if (Clinica_Patient_Permissions::can_export_reports()): ?>
                <button type="button" class="button button-primary" onclick="exportReport('patients')">
                    <?php _e('Export Pacienți (CSV)', 'clinica'); ?>
                </button>
                
                <button type="button" class="button button-primary" onclick="exportReport('appointments')">
                    <?php _e('Export Programări (CSV)', 'clinica'); ?>
                </button>
                
                <button type="button" class="button button-primary" onclick="exportReport('stats')">
                    <?php _e('Export Statistici (PDF)', 'clinica'); ?>
                </button>
                <?php endif; ?>
                
                <button type="button" class="button button-secondary" onclick="printReport()">
                    <?php _e('Printează Raport', 'clinica'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.clinica-reports-container {
    margin-top: 20px;
}

.report-section {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.report-section h2 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
}

.stat-item h3 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.quick-actions .button {
    margin: 0;
}

@media print {
    .quick-actions {
        display: none;
    }
    
    .report-section {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>

<script>
function exportReport(type) {
    // Implementare export
    alert('Funcționalitatea de export va fi implementată în versiunea următoare.');
}

function printReport() {
    window.print();
}
</script> 