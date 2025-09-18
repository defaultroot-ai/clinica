<?php
/**
 * Test pentru formularul de adăugare pacient cu tab-uri
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este autentificat și are permisiuni
if (!current_user_can('manage_options')) {
    wp_die('Acces restricționat');
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Formular Pacient cu Tab-uri - Clinica</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .test-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        
        .test-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .test-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .test-content {
            padding: 40px;
        }
        
        .test-info {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .test-info h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .test-info ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .test-info li {
            margin-bottom: 5px;
            color: #34495e;
        }
        
        .test-actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .test-btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .test-btn:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        .test-btn.success {
            background: #27ae60;
        }
        
        .test-btn.success:hover {
            background: #229954;
        }
        
        .test-btn.warning {
            background: #f39c12;
        }
        
        .test-btn.warning:hover {
            background: #e67e22;
        }
        
        .form-preview {
            border: 2px dashed #3498db;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            background: #fafbfc;
        }
        
        .form-preview h3 {
            margin-top: 0;
            color: #2c3e50;
            text-align: center;
        }
        
        .keyboard-shortcuts {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .keyboard-shortcuts h4 {
            margin-top: 0;
            color: #856404;
        }
        
        .keyboard-shortcuts ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .keyboard-shortcuts li {
            margin-bottom: 5px;
            color: #856404;
        }
        
        .keyboard-shortcuts kbd {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            padding: 2px 6px;
            font-size: 12px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1>🧪 Test Formular Pacient cu Tab-uri</h1>
            <p>Testează funcționalitatea formularului de adăugare pacient împărțit în tab-uri</p>
        </div>
        
        <div class="test-content">
            <div class="test-info">
                <h3>📋 Caracteristici Testate</h3>
                <ul>
                    <li><strong>4 Tab-uri:</strong> CNP & Identitate, Informații Personale, Informații Medicale, Setări Cont</li>
                    <li><strong>Navigare:</strong> Butoane Anterior/Următor cu validare</li>
                    <li><strong>Progress Bar:</strong> Indică progresul prin tab-uri</li>
                    <li><strong>Validare:</strong> Verifică câmpurile obligatorii înainte de trecerea la următorul tab</li>
                    <li><strong>Keyboard Shortcuts:</strong> Ctrl+Arrow pentru navigare</li>
                    <li><strong>Responsive:</strong> Adaptare pentru dispozitive mobile</li>
                    <li><strong>Design Modern:</strong> UI/UX îmbunătățit cu animații</li>
                </ul>
            </div>
            
            <div class="keyboard-shortcuts">
                <h4>⌨️ Scurtături Tastatură</h4>
                <ul>
                    <li><kbd>Ctrl</kbd> + <kbd>←</kbd> - Tab anterior</li>
                    <li><kbd>Ctrl</kbd> + <kbd>→</kbd> - Tab următor</li>
                    <li><kbd>Tab</kbd> - Navigare între câmpuri</li>
                    <li><kbd>Enter</kbd> - Trimite formularul (pe ultimul tab)</li>
                </ul>
            </div>
            
            <div class="test-actions">
                <a href="<?php echo admin_url('admin.php?page=clinica-patients'); ?>" class="test-btn">
                    📊 Pagina Pacienți (Admin)
                </a>
                
                <a href="<?php echo home_url('/receptionist-dashboard/'); ?>" class="test-btn success">
                    👩‍💼 Dashboard Receptionist
                </a>
                
                <a href="<?php echo home_url('/assistant-dashboard/'); ?>" class="test-btn warning">
                    👨‍💼 Dashboard Asistent
                </a>
            </div>
            
            <div class="form-preview">
                <h3>👀 Previzualizare Formular</h3>
                <?php
                // Încarcă formularul
                $patient_form = new Clinica_Patient_Creation_Form();
                echo $patient_form->render_form();
                ?>
            </div>
        </div>
    </div>
    
    <script>
    // Test suplimentar pentru funcționalitatea tab-urilor
    jQuery(document).ready(function($) {
        console.log('🧪 Test Formular Pacient cu Tab-uri - Încărcat');
        
        // Verifică dacă tab-urile sunt prezente
        var tabs = $('.clinica-tab-button');
        var tabPanes = $('.clinica-tab-pane');
        var progressBar = $('.clinica-tab-progress-fill');
        var progressText = $('.clinica-tab-progress-text');
        
        console.log('📊 Tab-uri găsite:', tabs.length);
        console.log('📄 Pane-uri tab găsite:', tabPanes.length);
        console.log('📈 Progress bar găsit:', progressBar.length > 0);
        console.log('📝 Progress text găsit:', progressText.length > 0);
        
        // Test funcționalitate tab-uri
        if (tabs.length > 0) {
            console.log('✅ Tab-urile sunt funcționale');
            
            // Test click pe tab-uri
            tabs.on('click', function() {
                var tabName = $(this).data('tab');
                console.log('🖱️ Click pe tab:', tabName);
            });
            
            // Test navigare cu butoane
            $('#next-tab').on('click', function() {
                console.log('➡️ Click pe buton Următor');
            });
            
            $('#prev-tab').on('click', function() {
                console.log('⬅️ Click pe buton Anterior');
            });
        } else {
            console.log('❌ Tab-urile nu sunt încărcate corect');
        }
        
        // Test validare CNP
        $('#cnp').on('input', function() {
            var cnp = $(this).val();
            if (cnp.length === 13) {
                console.log('🔍 CNP completat:', cnp);
            }
        });
        
        // Test generare parolă
        $('#generate_password_btn').on('click', function() {
            console.log('🔐 Generare parolă solicitată');
        });
    });
    </script>
</body>
</html> 