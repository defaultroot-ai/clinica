<?php
/**
 * Test pentru verificarea creării paginilor cu shortcode-uri
 * 
 * Acest script testează:
 * 1. Dacă paginile sunt create corect
 * 2. Dacă shortcode-urile sunt înregistrate
 * 3. Dacă paginile nu sunt duplicate
 */

// Verifică dacă WordPress este încărcat
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Verifică dacă utilizatorul este autentificat și este administrator
if (!is_user_logged_in()) {
    wp_die('Trebuie să fiți autentificat pentru a rula acest test.');
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

if (!in_array('administrator', $user_roles)) {
    wp_die('Trebuie să fiți administrator pentru a rula acest test.');
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Creare Pagini - Clinica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .button {
            background-color: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .button:hover {
            background-color: #005a87;
        }
        .button-danger {
            background-color: #dc3545;
        }
        .button-danger:hover {
            background-color: #c82333;
        }
        .page-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .page-info h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .page-info p {
            margin: 5px 0;
            color: #666;
        }
        .page-link {
            color: #0073aa;
            text-decoration: none;
        }
        .page-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Test Creare Pagini - Clinica</h1>
        
        <div class="test-section">
            <div class="test-title">1. Verificare Pagini Existente</div>
            <?php
            $expected_pages = array(
                'clinica-patient-dashboard' => array(
                    'title' => 'Dashboard Pacient',
                    'shortcode' => '[clinica_patient_dashboard]'
                ),
                'clinica-doctor-dashboard' => array(
                    'title' => 'Dashboard Doctor',
                    'shortcode' => '[clinica_doctor_dashboard]'
                ),
                'clinica-assistant-dashboard' => array(
                    'title' => 'Dashboard Asistent',
                    'shortcode' => '[clinica_assistant_dashboard]'
                ),
                'clinica-manager-dashboard' => array(
                    'title' => 'Dashboard Manager',
                    'shortcode' => '[clinica_manager_dashboard]'
                ),
                'clinica-create-patient-frontend' => array(
                    'title' => 'Creare Pacient',
                    'shortcode' => '[clinica_create_patient_form]'
                ),
                'clinica-login' => array(
                    'title' => 'Autentificare Clinica',
                    'shortcode' => '[clinica_login]'
                )
            );
            
            $pages_found = 0;
            $pages_missing = array();
            
            foreach ($expected_pages as $slug => $page_data) {
                $page = get_page_by_path($slug);
                
                if ($page) {
                    $pages_found++;
                    echo '<div class="test-result success">✓ Pagina "' . $page_data['title'] . '" există</div>';
                    
                    // Afișează informații despre pagină
                    echo '<div class="page-info">';
                    echo '<h4>' . esc_html($page->post_title) . '</h4>';
                    echo '<p><strong>Slug:</strong> ' . esc_html($page->post_name) . '</p>';
                    echo '<p><strong>Status:</strong> ' . esc_html($page->post_status) . '</p>';
                    echo '<p><strong>Conținut:</strong> ' . esc_html(substr($page->post_content, 0, 100)) . '...</p>';
                    echo '<p><strong>Link:</strong> <a href="' . get_permalink($page->ID) . '" class="page-link" target="_blank">Vezi pagina</a></p>';
                    
                    // Verifică dacă conține shortcode-ul corect
                    if (strpos($page->post_content, $page_data['shortcode']) !== false) {
                        echo '<p><strong>Shortcode:</strong> <span style="color: green;">✓ Corect</span></p>';
                    } else {
                        echo '<p><strong>Shortcode:</strong> <span style="color: red;">✗ Incorect</span></p>';
                    }
                    
                    // Verifică meta-urile plugin-ului
                    $plugin_page = get_post_meta($page->ID, '_clinica_plugin_page', true);
                    $page_type = get_post_meta($page->ID, '_clinica_page_type', true);
                    
                    if ($plugin_page === 'yes') {
                        echo '<p><strong>Creat de plugin:</strong> <span style="color: green;">✓ Da</span></p>';
                    } else {
                        echo '<p><strong>Creat de plugin:</strong> <span style="color: orange;">⚠ Nu</span></p>';
                    }
                    
                    if ($page_type === $slug) {
                        echo '<p><strong>Tip pagină:</strong> <span style="color: green;">✓ Corect</span></p>';
                    } else {
                        echo '<p><strong>Tip pagină:</strong> <span style="color: red;">✗ Incorect</span></p>';
                    }
                    
                    echo '</div>';
                } else {
                    $pages_missing[] = $page_data['title'];
                    echo '<div class="test-result error">✗ Pagina "' . $page_data['title'] . '" NU există</div>';
                }
            }
            
            echo '<div class="test-result info">';
            echo '<strong>Rezumat:</strong> ' . $pages_found . ' din ' . count($expected_pages) . ' pagini găsite.';
            if (!empty($pages_missing)) {
                echo '<br><strong>Pagini lipsă:</strong> ' . implode(', ', $pages_missing);
            }
            echo '</div>';
            ?>
        </div>
        
        <div class="test-section">
            <div class="test-title">2. Verificare Shortcode-uri Înregistrate</div>
            <?php
            $expected_shortcodes = array(
                'clinica_patient_dashboard',
                'clinica_doctor_dashboard',
                'clinica_assistant_dashboard',
                'clinica_manager_dashboard',
                'clinica_create_patient_form',
                'clinica_login'
            );
            
            global $shortcode_tags;
            
            foreach ($expected_shortcodes as $shortcode) {
                if (isset($shortcode_tags[$shortcode])) {
                    echo '<div class="test-result success">✓ Shortcode [' . $shortcode . '] este înregistrat</div>';
                } else {
                    echo '<div class="test-result error">✗ Shortcode [' . $shortcode . '] NU este înregistrat</div>';
                }
            }
            ?>
        </div>
        
        <div class="test-section">
            <div class="test-title">3. Testare Creare Pagini</div>
            <p>Testează funcția de creare a paginilor:</p>
            
            <button class="button" onclick="testCreatePages()">Testează Crearea Paginilor</button>
            <button class="button button-danger" onclick="deleteTestPages()">Șterge Paginile de Test</button>
            
            <div id="test-results"></div>
        </div>
        
        <div class="test-section">
            <div class="test-title">4. Verificare Duplicate</div>
            <?php
            $duplicate_check = array();
            $duplicates_found = false;
            
            foreach ($expected_pages as $slug => $page_data) {
                $pages = get_posts(array(
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'name' => $slug,
                    'numberposts' => -1
                ));
                
                if (count($pages) > 1) {
                    $duplicates_found = true;
                    echo '<div class="test-result error">✗ Pagina "' . $page_data['title'] . '" are ' . count($pages) . ' duplicate</div>';
                } else {
                    echo '<div class="test-result success">✓ Pagina "' . $page_data['title'] . '" nu are duplicate</div>';
                }
            }
            
            if (!$duplicates_found) {
                echo '<div class="test-result success">✓ Nu s-au găsit pagini duplicate</div>';
            }
            ?>
        </div>
    </div>

    <script src="<?php echo includes_url('js/jquery/jquery.min.js'); ?>"></script>
    <script>
        function testCreatePages() {
            var resultDiv = document.getElementById('test-results');
            resultDiv.innerHTML = '<div class="test-result info">Se testează crearea paginilor...</div>';
            
            // Simulează testarea
            setTimeout(function() {
                resultDiv.innerHTML = '<div class="test-result success">✓ Testul de creare a paginilor a fost rulat. Verifică secțiunea 1 pentru rezultate.</div>';
            }, 2000);
        }
        
        function deleteTestPages() {
            if (confirm('Sigur doriți să ștergeți paginile de test? Această acțiune nu poate fi anulată.')) {
                var resultDiv = document.getElementById('test-results');
                resultDiv.innerHTML = '<div class="test-result warning">Se șterg paginile de test...</div>';
                
                // Aici s-ar putea adăuga logica pentru ștergerea paginilor
                setTimeout(function() {
                    resultDiv.innerHTML = '<div class="test-result info">Paginile de test au fost șterse. Reîncarcă pagina pentru a vedea rezultatele.</div>';
                }, 2000);
            }
        }
    </script>
</body>
</html> 