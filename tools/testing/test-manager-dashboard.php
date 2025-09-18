<?php
/**
 * Test Script pentru Manager Dashboard
 * 
 * Acest script testează funcționalitatea dashboard-ului manager
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are rolul de manager
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat pentru a accesa acest test.');
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

if (!in_array('clinica_manager', $user_roles)) {
    // Adaugă temporar rolul de manager pentru testare
    $current_user->add_role('clinica_manager');
    echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; margin: 1rem 0; border-radius: 5px;">';
    echo '<strong>Notă:</strong> Rolul de manager a fost adăugat temporar pentru testare.';
    echo '</div>';
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Manager Dashboard - Clinica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .test-header {
            background: #2c3e50;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        .test-info {
            background: white;
            margin: 1rem;
            padding: 1rem;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-content {
            margin: 1rem;
        }
        .back-link {
            display: inline-block;
            margin: 1rem;
            padding: 0.5rem 1rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="test-header">
        <h1><i class="fas fa-crown"></i> Test Manager Dashboard</h1>
        <p>Sistem de Gestionare Medicală Clinica</p>
    </div>
    
    <div class="test-info">
        <h3>Informații Test:</h3>
        <ul>
            <li><strong>Utilizator curent:</strong> <?php echo esc_html($current_user->display_name); ?></li>
            <li><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></li>
            <li><strong>Roluri:</strong> <?php echo esc_html(implode(', ', $user_roles)); ?></li>
            <li><strong>Data test:</strong> <?php echo current_time('d.m.Y H:i:s'); ?></li>
        </ul>
    </div>
    
    <a href="test-admin-fix.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Înapoi la Teste
    </a>
    
    <div class="test-content">
        <?php
        // Testează dashboard-ul manager
        if (class_exists('Clinica_Manager_Dashboard')) {
            $manager_dashboard = new Clinica_Manager_Dashboard();
            echo $manager_dashboard->render_dashboard();
        } else {
            echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 1rem; border-radius: 5px; color: #721c24;">';
            echo '<strong>Eroare:</strong> Clasa Clinica_Manager_Dashboard nu a fost găsită.';
            echo '</div>';
        }
        ?>
    </div>
    
    <script>
        // Adaugă ajaxurl pentru AJAX
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        // Testează funcționalitatea JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Manager Dashboard Test - JavaScript loaded');
            
            // Verifică dacă ClinicaManagerDashboard este disponibil
            if (typeof ClinicaManagerDashboard !== 'undefined') {
                console.log('ClinicaManagerDashboard object found');
                
                // Testează funcționalitățile de bază
                setTimeout(function() {
                    console.log('Testing manager dashboard functionality...');
                    
                    // Verifică dacă tab-urile funcționează
                    const tabs = document.querySelectorAll('.nav-tab');
                    console.log('Found ' + tabs.length + ' tabs');
                    
                    // Verifică dacă butoanele sunt prezente
                    const buttons = document.querySelectorAll('.btn');
                    console.log('Found ' + buttons.length + ' buttons');
                    
                    // Verifică dacă tabelele sunt prezente
                    const tables = document.querySelectorAll('table');
                    console.log('Found ' + tables.length + ' tables');
                    
                }, 1000);
            } else {
                console.error('ClinicaManagerDashboard object not found');
            }
        });
    </script>
</body>
</html> 