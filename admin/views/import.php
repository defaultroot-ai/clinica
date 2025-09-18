<?php
/**
 * Pagina pentru importul pacienților
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!Clinica_Patient_Permissions::can_import_patients()) {
    wp_die(__('Nu aveți permisiunea de a importa pacienți.', 'clinica'));
}

$importers = new Clinica_Importers();
?>

<div class="wrap">
    <h1><?php _e('Import Pacienți', 'clinica'); ?></h1>
    
    <div class="clinica-import-info">
        <h2><?php _e('Informații Import', 'clinica'); ?></h2>
        <p><?php _e('Această funcționalitate vă permite să importați pacienți din diferite surse:', 'clinica'); ?></p>
        
        <ul>
            <li><strong>ICMED:</strong> <?php _e('Format specific pentru sistemul ICMED', 'clinica'); ?></li>
            <li><strong>Joomla Community Builder:</strong> <?php _e('Export din Joomla cu extensia Community Builder', 'clinica'); ?></li>
            <li><strong>CSV Generic:</strong> <?php _e('Fișier CSV cu coloane personalizate', 'clinica'); ?></li>
            <li><strong>Excel:</strong> <?php _e('Fișiere Excel (.xlsx, .xls)', 'clinica'); ?></li>
        </ul>
        
        <div class="clinica-import-requirements">
            <h3><?php _e('Cerințe pentru fișierul de import:', 'clinica'); ?></h3>
            <ul>
                <li><?php _e('CNP-ul este obligatoriu pentru fiecare pacient', 'clinica'); ?></li>
                <li><?php _e('Numele și prenumele sunt obligatorii', 'clinica'); ?></li>
                <li><?php _e('Telefonul principal este obligatoriu', 'clinica'); ?></li>
                <li><?php _e('Email-ul este opțional dar recomandat', 'clinica'); ?></li>
                <li><?php _e('Dimensiunea maximă a fișierului: 10MB', 'clinica'); ?></li>
            </ul>
        </div>
    </div>
    
    <?php echo $importers->render_import_form(); ?>
    
    <div class="clinica-import-history">
        <h2><?php _e('Istoric Import-uri', 'clinica'); ?></h2>
        <?php 
        $importers = new Clinica_Importers();
        echo $importers->get_import_history_html(); 
        ?>
    </div>
</div>

<style>
.clinica-import-info {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    margin: 20px 0;
}

.clinica-import-info ul {
    margin: 10px 0;
    padding-left: 20px;
}

.clinica-import-info li {
    margin-bottom: 5px;
}

.clinica-import-requirements {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
}

.clinica-import-requirements h3 {
    margin-top: 0;
    color: #856404;
}

.clinica-import-history {
    margin-top: 30px;
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style> 