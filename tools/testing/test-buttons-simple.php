<?php
/**
 * Test simplu pentru butoane
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este logat
if (!is_user_logged_in()) {
    echo '<h2>Test Butoane Simplu</h2>';
    echo '<p>Trebuie să fiți autentificat.</p>';
    exit;
}

$current_user = wp_get_current_user();

// Verifică dacă utilizatorul este pacient
if (!Clinica_Roles::has_clinica_role($current_user->ID) || 
    Clinica_Roles::get_user_role($current_user->ID) !== 'clinica_patient') {
    echo '<h2>Test Butoane Simplu</h2>';
    echo '<p>Acest test este destinat doar pacienților.</p>';
    exit;
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Butoane Simplu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f0f0f0;
        }
        .test-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            position: relative;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        .patient-info h2 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 600;
        }
        .patient-info p {
            margin: 4px 0;
            opacity: 0.9;
        }
        .dashboard-actions {
            display: flex;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        .dashboard-actions .button {
            background: rgba(255,255,255,0.2) !important;
            color: white !important;
            border: 1px solid rgba(255,255,255,0.3) !important;
            padding: 10px 20px !important;
            border-radius: 6px !important;
            text-decoration: none !important;
            font-weight: 500 !important;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(10px) !important;
            display: inline-block !important;
            cursor: pointer !important;
            font-size: 14px !important;
            line-height: 1.4 !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
        .dashboard-actions .button:hover {
            background: rgba(255,255,255,0.3) !important;
            border-color: rgba(255,255,255,0.5) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            color: white !important;
            text-decoration: none !important;
        }
        .dashboard-actions .button-secondary {
            background: rgba(255,255,255,0.1) !important;
            color: white !important;
            border: 1px solid rgba(255,255,255,0.3) !important;
        }
        .debug-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .debug-info h3 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        .debug-info p {
            margin: 5px 0;
            color: #424242;
        }
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            .dashboard-actions {
                flex-direction: column;
                width: 100%;
            }
            .dashboard-actions .button {
                width: 100% !important;
                text-align: center !important;
                margin-bottom: 10px !important;
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Test Butoane Simplu - Dashboard Pacient</h1>
        
        <div class="debug-info">
            <h3>Informații utilizator:</h3>
            <p><strong>ID:</strong> <?php echo esc_html($current_user->ID); ?></p>
            <p><strong>Username:</strong> <?php echo esc_html($current_user->user_login); ?></p>
            <p><strong>Display Name:</strong> <?php echo esc_html($current_user->display_name); ?></p>
            <p><strong>Rol:</strong> <?php echo esc_html(Clinica_Roles::get_user_role($current_user->ID)); ?></p>
        </div>
        
        <div class="header">
            <div class="header-content">
                <div class="patient-info">
                    <h2><?php echo esc_html($current_user->display_name); ?></h2>
                    <p>CNP: <?php echo esc_html($current_user->user_login); ?></p>
                    <p><?php echo esc_html($current_user->user_email); ?></p>
                </div>
                <div class="dashboard-actions">
                    <button type="button" class="button" id="edit-profile-btn">Editează Profilul</button>
                    <a href="<?php echo wp_logout_url(); ?>" class="button button-secondary">Deconectare</a>
                </div>
            </div>
        </div>
        
        <div class="debug-info">
            <h3>Instrucțiuni test:</h3>
            <p>1. Verifică dacă butoanele sunt vizibile în header</p>
            <p>2. Testează hover-ul pe butoane</p>
            <p>3. Testează click-ul pe butoane</p>
            <p>4. Testează pe mobile (responsive)</p>
        </div>
        
        <p><a href="../" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">← Înapoi la plugin</a></p>
    </div>
    
    <script>
        console.log('Test Buttons Simple loaded');
        
        // Test butoane
        document.getElementById('edit-profile-btn').addEventListener('click', function() {
            alert('Butonul "Editează Profilul" funcționează!');
        });
        
        // Verifică dacă butoanele există
        console.log('Edit button found:', document.getElementById('edit-profile-btn') !== null);
        console.log('Logout button found:', document.querySelector('.button-secondary') !== null);
        
        // Verifică stilurile
        setTimeout(function() {
            const editBtn = document.getElementById('edit-profile-btn');
            const logoutBtn = document.querySelector('.button-secondary');
            
            if (editBtn) {
                const styles = window.getComputedStyle(editBtn);
                console.log('Edit button computed styles:', {
                    'display': styles.display,
                    'visibility': styles.visibility,
                    'opacity': styles.opacity,
                    'background': styles.background,
                    'color': styles.color
                });
            }
            
            if (logoutBtn) {
                const styles = window.getComputedStyle(logoutBtn);
                console.log('Logout button computed styles:', {
                    'display': styles.display,
                    'visibility': styles.visibility,
                    'opacity': styles.opacity,
                    'background': styles.background,
                    'color': styles.color
                });
            }
        }, 1000);
    </script>
</body>
</html> 