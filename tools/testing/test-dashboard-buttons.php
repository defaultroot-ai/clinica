<?php
/**
 * Test pentru butoanele dashboard-ului
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este logat
if (!is_user_logged_in()) {
    echo '<h2>Test Butoane Dashboard</h2>';
    echo '<p>Trebuie să fiți autentificat pentru a testa dashboard-ul.</p>';
    echo '<p><a href="' . wp_login_url() . '">Autentificare</a></p>';
    exit;
}

$current_user = wp_get_current_user();

// Verifică dacă utilizatorul este pacient
if (!Clinica_Roles::has_clinica_role($current_user->ID) || 
    Clinica_Roles::get_user_role($current_user->ID) !== 'clinica_patient') {
    echo '<h2>Test Butoane Dashboard</h2>';
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
    <title>Test Butoane Dashboard - Clinica</title>
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
        .test-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section h3 {
            margin-top: 0;
            color: #333;
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
        .debug-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .debug-info h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        .debug-info p {
            margin: 5px 0;
            color: #424242;
        }
    </style>
</head>
<body>
    <a href="../" class="back-link">← Înapoi la plugin</a>
    
    <div class="test-header">
        <h1>Test Butoane Dashboard</h1>
        <p>Testarea vizibilității și stilizării butoanelor din dashboard</p>
    </div>
    
    <div class="test-section">
        <h3>1. Informații despre utilizator</h3>
        <div class="debug-info">
            <h4>Detalii utilizator:</h4>
            <p><strong>ID:</strong> <?php echo esc_html($current_user->ID); ?></p>
            <p><strong>Username:</strong> <?php echo esc_html($current_user->user_login); ?></p>
            <p><strong>Display Name:</strong> <?php echo esc_html($current_user->display_name); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
            <p><strong>Rol:</strong> <?php echo esc_html(Clinica_Roles::get_user_role($current_user->ID)); ?></p>
        </div>
    </div>
    
    <div class="test-section">
        <h3>2. Test butoane simple (fără CSS)</h3>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 10px 0;">
            <p><strong>Butoane HTML simple:</strong></p>
            <button type="button" class="button" id="test-btn-1">Test Button 1</button>
            <a href="#" class="button button-secondary">Test Button 2</a>
            <button type="button" class="button" id="test-btn-3">Test Button 3</button>
        </div>
    </div>
    
    <div class="test-section">
        <h3>3. Test butoane cu stiluri dashboard</h3>
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin: 10px 0;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="margin: 0; color: white;">Test Header</h2>
                    <p style="margin: 5px 0; color: rgba(255,255,255,0.9);">Test subtitle</p>
                </div>
                <div class="dashboard-actions" style="display: flex; gap: 12px; position: relative; z-index: 1;">
                    <button type="button" class="button" id="edit-profile-btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; transition: all 0.3s ease; backdrop-filter: blur(10px);">Editează Profilul</button>
                    <a href="<?php echo wp_logout_url(); ?>" class="button button-secondary" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; transition: all 0.3s ease; backdrop-filter: blur(10px);">Deconectare</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="test-section">
        <h3>4. Test dashboard complet (cu CSS WordPress)</h3>
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 10px 0;">
            <?php
            // Testează dashboard-ul
            $dashboard = new Clinica_Patient_Dashboard();
            echo $dashboard->render_dashboard_shortcode(array());
            ?>
        </div>
    </div>
    
    <div class="test-section">
        <h3>5. Verificare CSS încărcat</h3>
        <div class="debug-info">
            <h4>Fișiere CSS încărcate:</h4>
            <?php
            global $wp_styles;
            if (isset($wp_styles) && isset($wp_styles->queue)) {
                echo '<ul>';
                foreach ($wp_styles->queue as $handle) {
                    if (strpos($handle, 'clinica') !== false || strpos($handle, 'patient') !== false) {
                        echo '<li><strong>' . esc_html($handle) . '</strong> - ' . esc_html($wp_styles->registered[$handle]->src) . '</li>';
                    }
                }
                echo '</ul>';
            } else {
                echo '<p>Nu s-au găsit fișiere CSS Clinica încărcate.</p>';
            }
            ?>
        </div>
    </div>
    
    <div class="test-section">
        <h3>6. Test JavaScript</h3>
        <div class="debug-info">
            <h4>Verificare JavaScript:</h4>
            <p>Deschideți console-ul browser-ului (F12) pentru a vedea mesajele de debug.</p>
        </div>
    </div>
    
    <script>
        console.log('Test Dashboard Buttons loaded');
        
        // Verifică dacă jQuery este încărcat
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery is loaded');
            
            jQuery(document).ready(function($) {
                console.log('Document ready');
                
                // Test butoane simple
                $('#test-btn-1').on('click', function() {
                    alert('Test Button 1 clicked!');
                });
                
                $('#test-btn-3').on('click', function() {
                    alert('Test Button 3 clicked!');
                });
                
                // Test butoane dashboard
                $('#edit-profile-btn').on('click', function() {
                    alert('Edit Profile button clicked!');
                });
                
                // Verifică dacă butoanele există în DOM
                console.log('Test buttons found:', $('#test-btn-1').length);
                console.log('Dashboard edit button found:', $('#edit-profile-btn').length);
                console.log('Dashboard logout button found:', $('.button-secondary').length);
                
                // Verifică stilurile butoanelor
                setTimeout(function() {
                    const editBtn = $('#edit-profile-btn');
                    const logoutBtn = $('.button-secondary');
                    
                    if (editBtn.length) {
                        console.log('Edit button styles:', {
                            'display': editBtn.css('display'),
                            'visibility': editBtn.css('visibility'),
                            'opacity': editBtn.css('opacity'),
                            'background': editBtn.css('background'),
                            'color': editBtn.css('color')
                        });
                    }
                    
                    if (logoutBtn.length) {
                        console.log('Logout button styles:', {
                            'display': logoutBtn.css('display'),
                            'visibility': logoutBtn.css('visibility'),
                            'opacity': logoutBtn.css('opacity'),
                            'background': logoutBtn.css('background'),
                            'color': logoutBtn.css('color')
                        });
                    }
                }, 1000);
            });
        } else {
            console.log('jQuery is NOT loaded');
        }
    </script>
</body>
</html> 