<?php
/**
 * Dashboard Admin pentru Clinica
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!Clinica_Patient_Permissions::can_access_dashboard()) {
    wp_die(__('Nu aveți permisiunea de a accesa dashboard-ul.', 'clinica'));
}

// Obține statisticile
$stats = Clinica_Database::get_database_stats();
?>

<div class="wrap">
    <h1><?php _e('Dashboard Clinica', 'clinica'); ?></h1>
    
    <div class="clinica-dashboard-stats">
        <div class="stat-card">
            <h3><?php _e('Pacienți', 'clinica'); ?></h3>
            <div class="stat-number"><?php echo esc_html($stats['total_patients'] ?? 0); ?></div>
            <div class="stat-description"><?php _e('Total pacienți înregistrați', 'clinica'); ?></div>
        </div>
        
        <div class="stat-card">
            <h3><?php _e('Programări', 'clinica'); ?></h3>
            <div class="stat-number"><?php echo esc_html($stats['total_appointments'] ?? 0); ?></div>
            <div class="stat-description"><?php _e('Total programări', 'clinica'); ?></div>
        </div>
        
        <div class="stat-card">
            <h3><?php _e('Programări Astăzi', 'clinica'); ?></h3>
            <div class="stat-number"><?php echo esc_html($stats['appointments_today'] ?? 0); ?></div>
            <div class="stat-description"><?php _e('Programări pentru astăzi', 'clinica'); ?></div>
        </div>
        
        <div class="stat-card">
            <h3><?php _e('Dosare Medicale', 'clinica'); ?></h3>
            <div class="stat-number"><?php echo esc_html($stats['total_medical_records'] ?? 0); ?></div>
            <div class="stat-description"><?php _e('Total dosare medicale', 'clinica'); ?></div>
        </div>
    </div>
    
    <div class="clinica-dashboard-sections">
        <div class="section">
            <h2><?php _e('Acțiuni Rapide', 'clinica'); ?></h2>
            <div class="quick-actions">
                <?php if (Clinica_Patient_Permissions::can_create_patient()): ?>
                <a href="<?php echo admin_url('admin.php?page=clinica-create-patient'); ?>" class="button button-primary">
                    <?php _e('Adaugă Pacient Nou', 'clinica'); ?>
                </a>
                <?php endif; ?>
                
                <?php if (Clinica_Patient_Permissions::can_create_appointments()): ?>
                <a href="<?php echo admin_url('admin.php?page=clinica-appointments'); ?>" class="button button-secondary">
                    <?php _e('Programare Nouă', 'clinica'); ?>
                </a>
                <?php endif; ?>
                
                <?php if (Clinica_Patient_Permissions::can_import_patients()): ?>
                <a href="<?php echo admin_url('admin.php?page=clinica-import'); ?>" class="button button-secondary">
                    <?php _e('Import Pacienți', 'clinica'); ?>
                </a>
                <?php endif; ?>
                
                <?php if (Clinica_Patient_Permissions::can_view_reports()): ?>
                <a href="<?php echo admin_url('admin.php?page=clinica-reports'); ?>" class="button button-secondary">
                    <?php _e('Rapoarte', 'clinica'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2><?php _e('Programări Recente', 'clinica'); ?></h2>
            <?php echo $this->get_recent_appointments_html(); ?>
        </div>
        
        <div class="section">
            <h2><?php _e('Pacienți Recenți', 'clinica'); ?></h2>
            <?php echo $this->get_recent_patients_html(); ?>
        </div>
    </div>
</div> 