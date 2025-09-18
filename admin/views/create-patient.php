<?php
/**
 * Pagina pentru crearea pacienților
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifică permisiunile
if (!Clinica_Patient_Permissions::can_create_patient()) {
    wp_die(__('Nu aveți permisiunea de a crea pacienți.', 'clinica'));
}

$form = new Clinica_Patient_Creation_Form();
?>

<div class="wrap">
    <h1><?php _e('Creare Pacient Nou', 'clinica'); ?></h1>
    
    <div class="clinica-admin-notice">
        <p><strong><?php _e('Instrucțiuni:', 'clinica'); ?></strong></p>
        <ul>
            <li><?php _e('CNP-ul este obligatoriu și va fi folosit ca username pentru pacient', 'clinica'); ?></li>
            <li><?php _e('Parola va fi generată automat din primele 6 cifre CNP sau data nașterii', 'clinica'); ?></li>
            <li><?php _e('Pacientul va primi un email cu credențialele de autentificare', 'clinica'); ?></li>
            <li><?php _e('Pacienții nu se pot înregistra singuri - doar personalul medical poate crea conturi', 'clinica'); ?></li>
        </ul>
    </div>
    
    <?php echo $form->render_form(); ?>
</div>

<style>
.clinica-admin-notice {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
}

.clinica-admin-notice ul {
    margin: 10px 0 0 20px;
}

.clinica-admin-notice li {
    margin-bottom: 5px;
}
</style> 