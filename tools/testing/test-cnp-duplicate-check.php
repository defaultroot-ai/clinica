<?php
/**
 * Test pentru verificarea CNP duplicat în formularul de adăugare pacient
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
    <title>Test Verificare CNP Duplicat - Clinica</title>
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .test-info h3 {
            margin-top: 0;
            color: #856404;
        }
        
        .test-info ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .test-info li {
            margin-bottom: 8px;
            color: #856404;
        }
        
        .test-steps {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .test-steps h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .test-steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .test-steps li {
            margin-bottom: 10px;
            color: #34495e;
            line-height: 1.6;
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
            border: 2px dashed #e74c3c;
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
        
        .cnp-test-cases {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .cnp-test-cases h4 {
            margin-top: 0;
            color: #155724;
        }
        
        .cnp-test-cases ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .cnp-test-cases li {
            margin-bottom: 5px;
            color: #155724;
        }
        
        .cnp-test-cases code {
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
            <h1>🔍 Test Verificare CNP Duplicat</h1>
            <p>Testează funcționalitatea de verificare CNP duplicat în formularul de adăugare pacient</p>
        </div>
        
        <div class="test-content">
            <div class="test-info">
                <h3>⚠️ Funcționalitate Testată</h3>
                <ul>
                    <li><strong>Verificare în timp real:</strong> CNP-ul este verificat automat după introducerea a 13 cifre</li>
                    <li><strong>Mesaj de eroare:</strong> Afișează "Acest CNP există deja în sistem!" pentru CNP-uri duplicate</li>
                    <li><strong>Blocare formular:</strong> Butonul de trimitere este dezactivat pentru CNP-uri duplicate</li>
                    <li><strong>Blocare navigare:</strong> Nu se poate trece la următorul tab cu un CNP duplicat</li>
                    <li><strong>Validare suplimentară:</strong> Verificare la click pe "Următor" în primul tab</li>
                    <li><strong>Feedback vizual:</strong> Mesaje colorate și iconuri pentru diferite stări</li>
                </ul>
            </div>
            
            <div class="test-steps">
                <h3>📋 Pași de Testare</h3>
                <ol>
                    <li><strong>Introduceți un CNP valid</strong> (13 cifre) care nu există în sistem</li>
                    <li><strong>Verificați mesajul:</strong> Ar trebui să apară "CNP valid și disponibil" cu icon verde</li>
                    <li><strong>Testați navigarea:</strong> Click pe "Următor" ar trebui să funcționeze</li>
                    <li><strong>Introduceți un CNP existent:</strong> Folosiți un CNP deja înregistrat în sistem</li>
                    <li><strong>Verificați blocarea:</strong> Mesaj de eroare și butoane dezactivate</li>
                    <li><strong>Testați din nou navigarea:</strong> Click pe "Următor" ar trebui să fie blocat</li>
                    <li><strong>Schimbați CNP-ul:</strong> Introduceți un CNP nou pentru a debloca formularul</li>
                </ol>
            </div>
            
            <div class="cnp-test-cases">
                <h4>🧪 Cazuri de Test CNP</h4>
                <ul>
                    <li><strong>CNP Valid Nou:</strong> <code>1234567890123</code> (ar trebui să fie acceptat)</li>
                    <li><strong>CNP Invalid:</strong> <code>123456789012</code> (prea scurt - 12 cifre)</li>
                    <li><strong>CNP Invalid:</strong> <code>12345678901234</code> (prea lung - 14 cifre)</li>
                    <li><strong>CNP Existente:</strong> Orice CNP deja înregistrat în sistem (ar trebui să fie respins)</li>
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
                <h3>👀 Test Formular cu Verificare CNP</h3>
                <?php
                // Încarcă formularul
                $patient_form = new Clinica_Patient_Creation_Form();
                echo $patient_form->render_form();
                ?>
            </div>
        </div>
    </div>
    
    <script>
    // Test suplimentar pentru verificarea CNP duplicat
    jQuery(document).ready(function($) {
        console.log('🔍 Test Verificare CNP Duplicat - Încărcat');
        
        // Verifică dacă funcțiile de verificare sunt prezente
        var cnpInput = $('#cnp');
        var validationMessage = $('.cnp-validation-message');
        var submitButton = $('button[type="submit"]');
        var nextButton = $('#next-tab');
        
        console.log('📝 Input CNP găsit:', cnpInput.length > 0);
        console.log('💬 Mesaj validare găsit:', validationMessage.length > 0);
        console.log('📤 Buton submit găsit:', submitButton.length > 0);
        console.log('➡️ Buton următor găsit:', nextButton.length > 0);
        
        // Test funcționalitate verificare CNP
        if (cnpInput.length > 0) {
            console.log('✅ Funcționalitatea de verificare CNP este disponibilă');
            
            // Monitorizează schimbările în mesajul de validare
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        var message = validationMessage.text();
                        console.log('🔄 Mesaj validare actualizat:', message);
                        
                        if (message.includes('există deja')) {
                            console.log('❌ CNP duplicat detectat - formularul ar trebui să fie blocat');
                        } else if (message.includes('valid și disponibil')) {
                            console.log('✅ CNP valid și disponibil - formularul ar trebui să fie activ');
                        }
                    }
                });
            });
            
            observer.observe(validationMessage[0], {
                childList: true,
                subtree: true
            });
            
            // Test introducere CNP
            cnpInput.on('input', function() {
                var cnp = $(this).val();
                console.log('📝 CNP introdus:', cnp);
                
                if (cnp.length === 13) {
                    console.log('🔍 Verificare CNP inițiată pentru:', cnp);
                }
            });
            
            // Test click pe buton următor
            nextButton.on('click', function() {
                var message = validationMessage.text();
                console.log('➡️ Click pe buton Următor - Mesaj validare:', message);
                
                if (message.includes('există deja')) {
                    console.log('🚫 Navigarea ar trebui să fie blocată pentru CNP duplicat');
                }
            });
            
            // Test click pe buton submit
            submitButton.on('click', function() {
                var message = validationMessage.text();
                console.log('📤 Click pe buton Submit - Mesaj validare:', message);
                
                if (message.includes('există deja')) {
                    console.log('🚫 Submit-ul ar trebui să fie blocat pentru CNP duplicat');
                }
            });
        } else {
            console.log('❌ Funcționalitatea de verificare CNP nu este disponibilă');
        }
        
        // Test AJAX handler pentru verificare CNP
        console.log('🔧 Testare AJAX handler clinica_check_cnp_exists...');
        
        // Simulează o verificare CNP
        setTimeout(function() {
            if (typeof clinica_check_cnp_exists !== 'undefined') {
                console.log('✅ AJAX handler clinica_check_cnp_exists este disponibil');
            } else {
                console.log('❌ AJAX handler clinica_check_cnp_exists nu este disponibil');
            }
        }, 1000);
    });
    </script>
</body>
</html> 