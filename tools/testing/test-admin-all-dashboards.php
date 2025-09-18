<?php
/**
 * Test Script pentru Toate Dashboard-urile (Administrator)
 * 
 * Acest script permite administratorilor să acceseze toate dashboard-urile
 * din frontend pentru testare și verificare.
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat pentru a accesa acest test.');
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

// Verifică dacă utilizatorul este administrator
if (!in_array('administrator', $user_roles)) {
    wp_die('Acest test este disponibil doar pentru administratori.');
}

// Adaugă temporar toate rolurile pentru testare completă
$all_roles = ['clinica_patient', 'clinica_doctor', 'clinica_assistant', 'clinica_receptionist', 'clinica_manager'];
foreach ($all_roles as $role) {
    if (!in_array($role, $user_roles)) {
        $current_user->add_role($role);
    }
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Toate Dashboard-urile - Clinica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .test-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .test-info {
            background: white;
            margin: 1rem;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .dashboard-selector {
            background: white;
            margin: 1rem;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .dashboard-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .dashboard-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }
        .dashboard-card.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        .dashboard-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        .dashboard-card.active .dashboard-icon {
            color: white;
        }
        .dashboard-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .dashboard-description {
            font-size: 0.9rem;
            color: #666;
        }
        .dashboard-card.active .dashboard-description {
            color: rgba(255, 255, 255, 0.8);
        }
        .test-content {
            margin: 1rem;
        }
        .back-link {
            display: inline-block;
            margin: 1rem;
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        .back-link:hover {
            background: #2980b9;
        }
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #e9ecef;
            color: #495057;
            border-radius: 20px;
            font-size: 0.8rem;
            margin: 0.25rem;
        }
        .shortcode-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .shortcode-info strong {
            color: #667eea;
        }
        .dashboard-preview {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .preview-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-title {
            font-weight: 600;
            color: #495057;
        }
        .preview-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5a6fd8;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .preview-content {
            padding: 1rem;
            min-height: 400px;
        }
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="test-header">
        <h1><i class="fas fa-tachometer-alt"></i> Test Toate Dashboard-urile</h1>
        <p>Sistem de Gestionare Medicală Clinica - Acces Administrator</p>
    </div>
    
    <div class="test-info">
        <h3><i class="fas fa-info-circle"></i> Informații Test:</h3>
        <ul>
            <li><strong>Utilizator curent:</strong> <?php echo esc_html($current_user->display_name); ?></li>
            <li><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></li>
            <li><strong>Roluri active:</strong> 
                <?php foreach ($user_roles as $role): ?>
                    <span class="role-badge"><?php echo esc_html($role); ?></span>
                <?php endforeach; ?>
            </li>
            <li><strong>Data test:</strong> <?php echo current_time('d.m.Y H:i:s'); ?></li>
        </ul>
        
        <div class="success">
            <i class="fas fa-check-circle"></i> 
            <strong>Acces complet activat!</strong> Ca administrator, aveți acces la toate dashboard-urile din sistem.
        </div>
    </div>
    
    <a href="test-admin-fix.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Înapoi la Teste
    </a>
    
    <div class="dashboard-selector">
        <h3><i class="fas fa-th-large"></i> Selectează Dashboard-ul de Testat:</h3>
        
        <div class="dashboard-grid">
            <div class="dashboard-card" data-dashboard="patient">
                <div class="dashboard-icon">
                    <i class="fas fa-user-injured"></i>
                </div>
                <div class="dashboard-title">Dashboard Pacient</div>
                <div class="dashboard-description">
                    Interfața pentru pacienți - programări, informații medicale, mesaje
                </div>
            </div>
            
            <div class="dashboard-card" data-dashboard="doctor">
                <div class="dashboard-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="dashboard-title">Dashboard Doctor</div>
                <div class="dashboard-description">
                    Interfața pentru doctori - programări, pacienți, fișe medicale
                </div>
            </div>
            
            <div class="dashboard-card" data-dashboard="assistant">
                <div class="dashboard-icon">
                    <i class="fas fa-user-nurse"></i>
                </div>
                <div class="dashboard-title">Dashboard Asistent</div>
                <div class="dashboard-description">
                    Interfața pentru asistenți - gestionare programări și pacienți
                </div>
            </div>
            
            <div class="dashboard-card" data-dashboard="manager">
                <div class="dashboard-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="dashboard-title">Dashboard Manager</div>
                <div class="dashboard-description">
                    Interfața pentru manageri - administrare completă sistem
                </div>
            </div>
        </div>
        
        <div class="shortcode-info">
            <h4><i class="fas fa-code"></i> Shortcode-uri disponibile:</h4>
            <p><strong>Pacient:</strong> <code>[clinica_patient_dashboard]</code></p>
            <p><strong>Doctor:</strong> <code>[clinica_doctor_dashboard]</code></p>
            <p><strong>Asistent:</strong> <code>[clinica_assistant_dashboard]</code></p>
            <p><strong>Manager:</strong> <code>[clinica_manager_dashboard]</code></p>
        </div>
    </div>
    
    <div class="test-content">
        <div class="dashboard-preview">
            <div class="preview-header">
                <div class="preview-title">
                    <i class="fas fa-eye"></i> 
                    <span id="preview-title">Selectează un dashboard pentru a începe testarea</span>
                </div>
                <div class="preview-actions">
                    <button class="btn btn-primary" id="refresh-btn" style="display: none;">
                        <i class="fas fa-sync-alt"></i> Reîmprospătează
                    </button>
                    <button class="btn btn-secondary" id="fullscreen-btn" style="display: none;">
                        <i class="fas fa-expand"></i> Ecran Complet
                    </button>
                </div>
            </div>
            <div class="preview-content" id="dashboard-preview">
                <div class="loading">
                    <i class="fas fa-hand-pointer"></i>
                    <p>Click pe unul din dashboard-urile de mai sus pentru a începe testarea</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Adaugă ajaxurl pentru AJAX
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        document.addEventListener('DOMContentLoaded', function() {
            const dashboardCards = document.querySelectorAll('.dashboard-card');
            const previewTitle = document.getElementById('preview-title');
            const previewContent = document.getElementById('dashboard-preview');
            const refreshBtn = document.getElementById('refresh-btn');
            const fullscreenBtn = document.getElementById('fullscreen-btn');
            
            let currentDashboard = null;
            
            // Event listeners pentru card-uri
            dashboardCards.forEach(card => {
                card.addEventListener('click', function() {
                    const dashboardType = this.dataset.dashboard;
                    
                    // Update active card
                    dashboardCards.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update preview
                    loadDashboard(dashboardType);
                });
            });
            
            // Load dashboard function
            function loadDashboard(type) {
                currentDashboard = type;
                
                // Update title
                const titles = {
                    'patient': 'Dashboard Pacient',
                    'doctor': 'Dashboard Doctor', 
                    'assistant': 'Dashboard Asistent',
                    'manager': 'Dashboard Manager'
                };
                previewTitle.textContent = titles[type];
                
                // Show buttons
                refreshBtn.style.display = 'inline-flex';
                fullscreenBtn.style.display = 'inline-flex';
                
                // Show loading
                previewContent.innerHTML = `
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Se încarcă dashboard-ul ${titles[type]}...</p>
                    </div>
                `;
                
                // Load dashboard content via AJAX
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'load_dashboard_preview',
                        dashboard_type: type,
                        nonce: '<?php echo wp_create_nonce('dashboard_preview_nonce'); ?>'
                    })
                })
                .then(response => response.text())
                .then(html => {
                    previewContent.innerHTML = html;
                    
                    // Initialize dashboard JavaScript if available
                    if (typeof ClinicaPatientDashboard !== 'undefined' && type === 'patient') {
                        ClinicaPatientDashboard.init();
                    }
                    if (typeof ClinicaDoctorDashboard !== 'undefined' && type === 'doctor') {
                        ClinicaDoctorDashboard.init();
                    }
                    if (typeof ClinicaAssistantDashboard !== 'undefined' && type === 'assistant') {
                        ClinicaAssistantDashboard.init();
                    }
                    if (typeof ClinicaManagerDashboard !== 'undefined' && type === 'manager') {
                        ClinicaManagerDashboard.init();
                    }
                })
                .catch(error => {
                    console.error('Error loading dashboard:', error);
                    previewContent.innerHTML = `
                        <div class="error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Eroare la încărcarea dashboard-ului:</strong><br>
                            ${error.message}
                        </div>
                    `;
                });
            }
            
            // Refresh button
            refreshBtn.addEventListener('click', function() {
                if (currentDashboard) {
                    loadDashboard(currentDashboard);
                }
            });
            
            // Fullscreen button
            fullscreenBtn.addEventListener('click', function() {
                const preview = document.querySelector('.dashboard-preview');
                if (preview.requestFullscreen) {
                    preview.requestFullscreen();
                } else if (preview.webkitRequestFullscreen) {
                    preview.webkitRequestFullscreen();
                } else if (preview.msRequestFullscreen) {
                    preview.msRequestFullscreen();
                }
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key) {
                        case '1':
                            e.preventDefault();
                            document.querySelector('[data-dashboard="patient"]').click();
                            break;
                        case '2':
                            e.preventDefault();
                            document.querySelector('[data-dashboard="doctor"]').click();
                            break;
                        case '3':
                            e.preventDefault();
                            document.querySelector('[data-dashboard="assistant"]').click();
                            break;
                        case '4':
                            e.preventDefault();
                            document.querySelector('[data-dashboard="manager"]').click();
                            break;
                        case 'r':
                            e.preventDefault();
                            refreshBtn.click();
                            break;
                        case 'f':
                            e.preventDefault();
                            fullscreenBtn.click();
                            break;
                    }
                }
            });
            
            // Console logging for debugging
            console.log('Dashboard Test Interface loaded');
            console.log('Available shortcuts: Ctrl+1-4 (switch dashboards), Ctrl+R (refresh), Ctrl+F (fullscreen)');
        });
    </script>
</body>
</html> 