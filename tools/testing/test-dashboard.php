<?php
/**
 * Test pentru Dashboard-ul Pacient
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este logat
if (!is_user_logged_in()) {
    echo '<h2>Test Dashboard Pacient</h2>';
    echo '<p>Trebuie să fiți autentificat pentru a testa dashboard-ul.</p>';
    echo '<p><a href="' . wp_login_url() . '">Autentificare</a></p>';
    exit;
}

$current_user = wp_get_current_user();

// Verifică dacă utilizatorul este pacient
if (!Clinica_Roles::has_clinica_role($current_user->ID) || 
    Clinica_Roles::get_user_role($current_user->ID) !== 'clinica_patient') {
    echo '<h2>Test Dashboard Pacient</h2>';
    echo '<p>Acest test este destinat doar pacienților.</p>';
    echo '<p>Utilizatorul curent: ' . esc_html($current_user->user_login) . '</p>';
    echo '<p>Rol: ' . esc_html(Clinica_Roles::get_user_role($current_user->ID)) . '</p>';
    exit;
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Dashboard Pacient - Clinica</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
        }
        .test-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .test-info h3 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        .test-info p {
            margin: 5px 0;
            color: #424242;
        }
        .dashboard-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        .back-link:hover {
            background: #005a87;
        }
    </style>
</head>
<body>
    <a href="../" class="back-link">← Înapoi la plugin</a>
    
    <div class="test-header">
        <h1>Test Dashboard Pacient</h1>
        <p>Testarea funcționalității dashboard-ului pentru pacienți</p>
    </div>
    
    <div class="test-info">
        <h3>Informații test:</h3>
        <p><strong>Utilizator:</strong> <?php echo esc_html($current_user->display_name); ?></p>
        <p><strong>CNP:</strong> <?php echo esc_html($current_user->user_login); ?></p>
        <p><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
        <p><strong>Rol:</strong> <?php echo esc_html(Clinica_Roles::get_user_role($current_user->ID)); ?></p>
        <p><strong>Data test:</strong> <?php echo date('d.m.Y H:i:s'); ?></p>
    </div>
    
    <div class="dashboard-container">
        <?php
        // Testează dashboard-ul
        $dashboard = new Clinica_Patient_Dashboard();
        echo $dashboard->render_dashboard_shortcode(array());
        ?>
    </div>
    
    <script>
        // Test JavaScript
        console.log('Dashboard test loaded');
        
        // Verifică dacă jQuery este încărcat
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery is loaded');
            
            // Verifică dacă scriptul dashboard-ului este încărcat
            if (typeof ClinicaDashboard !== 'undefined') {
                console.log('ClinicaDashboard is loaded');
            } else {
                console.log('ClinicaDashboard is NOT loaded');
            }
        } else {
            console.log('jQuery is NOT loaded');
        }
        
        // Test AJAX
        jQuery(document).ready(function($) {
            console.log('Document ready');
            
            // Test tab navigation
            $('.tab-button').on('click', function() {
                console.log('Tab clicked:', $(this).data('tab'));
            });
            
            // Test message system
            setTimeout(function() {
                if (typeof ClinicaDashboard !== 'undefined') {
                    ClinicaDashboard.showMessage('Test message - Dashboard funcționează!', 'success');
                }
            }, 2000);
        });
    </script>
</body>
</html> 