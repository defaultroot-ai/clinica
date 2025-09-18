<?php
/**
 * Test Import Utilizatori
 * Script pentru testarea rapidă a importului cu date de exemplu
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Test Import Utilizatori</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
.warning { color: orange; }
.section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.btn { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
.btn:hover { background: #005a87; }
.results { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; }
</style>";

// Procesare acțiuni
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_test_users':
            create_test_users();
            break;
        case 'test_family_detection':
            test_family_detection();
            break;
        case 'test_autosuggest':
            test_autosuggest();
            break;
        case 'cleanup_test_data':
            cleanup_test_data();
            break;
    }
}

// Afișează statisticile
display_stats();

// Formulare de acțiune
echo "<div class='section'>";
echo "<h2>Teste Import și Funcționalitate</h2>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='create_test_users'>";
echo "<button type='submit' class='btn'>👥 Creează Utilizatori Test</button>";
echo "</form>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='test_family_detection'>";
echo "<button type='submit' class='btn'>🏠 Testează Detectare Familii</button>";
echo "</form>";

echo "<form method='post' style='display:inline;'>";
echo "<input type='hidden' name='action' value='test_autosuggest'>";
echo "<button type='submit' class='btn'>🔍 Testează Autosuggest</button>";
echo "</form>";

echo "<form method='post' style='display:inline;' onsubmit='return confirm(\"Sigur vrei să ștergi datele de test?\")'>";
echo "<input type='hidden' name='action' value='cleanup_test_data'>";
echo "<button type='submit' class='btn' style='background:#dc3232;'>🗑️ Șterge Date Test</button>";
echo "</form>";

echo "</div>";

/**
 * Creează utilizatori de test cu emailuri de familie
 */
function create_test_users() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>Creează Utilizatori Test</h3>";
    
    // Date de test cu emailuri de familie
    $test_users = array(
        array(
            'first_name' => 'Ion',
            'last_name' => 'Popescu',
            'email' => 'ion+maria@gmail.com',
            'cnp' => '1800404080170',
            'phone' => '0722123456',
            'address' => 'Strada Exemplu 1, București',
            'birth_date' => '1980-01-15',
            'gender' => 'male'
        ),
        array(
            'first_name' => 'Maria',
            'last_name' => 'Popescu',
            'email' => 'maria+ana@gmail.com',
            'cnp' => '2800404080171',
            'phone' => '0722123457',
            'address' => 'Strada Exemplu 1, București',
            'birth_date' => '1982-03-20',
            'gender' => 'female'
        ),
        array(
            'first_name' => 'Ana',
            'last_name' => 'Popescu',
            'email' => 'ana@gmail.com',
            'cnp' => '3800404080172',
            'phone' => '0722123458',
            'address' => 'Strada Exemplu 1, București',
            'birth_date' => '2010-07-10',
            'gender' => 'female'
        ),
        array(
            'first_name' => 'Vasile',
            'last_name' => 'Ionescu',
            'email' => 'vasile+elena@yahoo.com',
            'cnp' => '4800404080173',
            'phone' => '0722123459',
            'address' => 'Strada Exemplu 2, Cluj',
            'birth_date' => '1975-11-05',
            'gender' => 'male'
        ),
        array(
            'first_name' => 'Elena',
            'last_name' => 'Ionescu',
            'email' => 'elena@yahoo.com',
            'cnp' => '5800404080174',
            'phone' => '0722123460',
            'address' => 'Strada Exemplu 2, Cluj',
            'birth_date' => '1978-09-12',
            'gender' => 'female'
        ),
        array(
            'first_name' => 'Gheorghe',
            'last_name' => 'Dumitrescu',
            'email' => 'gheorghe+ioana@hotmail.com',
            'cnp' => '6800404080175',
            'phone' => '0722123461',
            'address' => 'Strada Exemplu 3, Timișoara',
            'birth_date' => '1970-04-25',
            'gender' => 'male'
        ),
        array(
            'first_name' => 'Ioana',
            'last_name' => 'Dumitrescu',
            'email' => 'ioana+mihai@hotmail.com',
            'cnp' => '7800404080176',
            'phone' => '0722123462',
            'address' => 'Strada Exemplu 3, Timișoara',
            'birth_date' => '1972-08-30',
            'gender' => 'female'
        ),
        array(
            'first_name' => 'Mihai',
            'last_name' => 'Dumitrescu',
            'email' => 'mihai@hotmail.com',
            'cnp' => '8800404080177',
            'phone' => '0722123463',
            'address' => 'Strada Exemplu 3, Timișoara',
            'birth_date' => '2005-12-15',
            'gender' => 'male'
        ),
        array(
            'first_name' => 'Andreea',
            'last_name' => 'Dumitrescu',
            'email' => 'andreea@hotmail.com',
            'cnp' => '9800404080178',
            'phone' => '0722123464',
            'address' => 'Strada Exemplu 3, Timișoara',
            'birth_date' => '2008-06-22',
            'gender' => 'female'
        )
    );
    
    $created = 0;
    $errors = array();
    
    foreach ($test_users as $user_data) {
        // Verifică dacă utilizatorul există deja
        $existing_user = get_user_by('email', $user_data['email']);
        if ($existing_user) {
            $errors[] = "Utilizatorul cu email {$user_data['email']} există deja";
            continue;
        }
        
        // Verifică dacă CNP-ul există deja
        $existing_cnp = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
            $user_data['cnp']
        ));
        
        if ($existing_cnp) {
            $errors[] = "CNP-ul {$user_data['cnp']} există deja";
            continue;
        }
        
        // Creează utilizatorul WordPress
        $username = generate_unique_username($user_data['first_name'], $user_data['last_name']);
        $password = generate_password_from_cnp($user_data['cnp']);
        
        $user_data_wp = array(
            'user_login' => $username,
            'user_email' => $user_data['email'],
            'user_pass' => $password,
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'display_name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
            'role' => 'subscriber'
        );
        
        $user_id = wp_insert_user($user_data_wp);
        
        if (is_wp_error($user_id)) {
            $errors[] = "Eroare la crearea utilizatorului {$user_data['email']}: " . $user_id->get_error_message();
            continue;
        }
        
        // Creează înregistrarea în tabela pacienți
        $patient_data = array(
            'user_id' => $user_id,
            'cnp' => $user_data['cnp'],
            'phone_primary' => $user_data['phone'],
            'address' => $user_data['address'],
            'birth_date' => $user_data['birth_date'],
            'gender' => $user_data['gender'],
            'created_by' => get_current_user_id(),
            'import_source' => 'test_import'
        );
        
        $insert_result = $wpdb->insert(
            $wpdb->prefix . 'clinica_patients',
            $patient_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($insert_result === false) {
            $errors[] = "Eroare la crearea înregistrării pacient pentru {$user_data['email']}";
        } else {
            $created++;
            echo "<p class='success'>✅ Utilizator creat: <strong>{$user_data['first_name']} {$user_data['last_name']}</strong></p>";
            echo "<p class='info'>📧 Email: {$user_data['email']} | 🆔 CNP: {$user_data['cnp']} | 👤 Username: $username</p>";
        }
    }
    
    echo "<h4>Rezumat:</h4>";
    echo "<p class='success'>✅ Utilizatori creați: $created</p>";
    
    if (!empty($errors)) {
        echo "<h4>Erori:</h4>";
        foreach ($errors as $error) {
            echo "<p class='error'>❌ $error</p>";
        }
    }
    
    if ($created > 0) {
        echo "<h4>🎯 Următorii Pași:</h4>";
        echo "<ol>";
        echo "<li><strong>Testează detectarea familiilor:</strong> Click pe 'Testează Detectare Familii'</li>";
        echo "<li><strong>Testează autosuggest:</strong> Click pe 'Testează Autosuggest'</li>";
        echo "<li><strong>Verifică pagina de pacienți:</strong> <a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank'>Click aici</a></li>";
        echo "</ol>";
    }
    
    echo "</div>";
}

/**
 * Testează detectarea familiilor din emailuri
 */
function test_family_detection() {
    echo "<div class='section'>";
    echo "<h3>Testează Detectare Familii</h3>";
    
    // Redirecționează către scriptul de detectare familii
    echo "<p class='info'>🔄 Redirecționare către scriptul de detectare familii...</p>";
    echo "<p><a href='import-families-from-emails.php' class='btn'>🔍 Deschide Detectare Familii</a></p>";
    
    echo "<h4>Ce va detecta scriptul:</h4>";
    echo "<ul>";
    echo "<li><strong>Familia Popescu</strong> (gmail.com): Ion, Maria, Ana</li>";
    echo "<li><strong>Familia Ionescu</strong> (yahoo.com): Vasile, Elena</li>";
    echo "<li><strong>Familia Dumitrescu</strong> (hotmail.com): Gheorghe, Ioana, Mihai, Andreea</li>";
    echo "</ul>";
    
    echo "</div>";
}

/**
 * Testează funcționalitatea de autosuggest
 */
function test_autosuggest() {
    echo "<div class='section'>";
    echo "<h3>Testează Autosuggest</h3>";
    
    echo "<p class='info'>🔄 Redirecționare către pagina de pacienți pentru testarea autosuggest...</p>";
    echo "<p><a href='" . admin_url('admin.php?page=clinica-patients') . "' target='_blank' class='btn'>🔍 Deschide Pagina Pacienți</a></p>";
    
    echo "<h4>Ce să testezi:</h4>";
    echo "<ol>";
    echo "<li><strong>Câmpul de căutare generală:</strong> Scrie 'Ion', 'Maria', 'Popescu'</li>";
    echo "<li><strong>Câmpul CNP:</strong> Scrie '1800', '2800', '3800'</li>";
    echo "<li><strong>Câmpul familie:</strong> Scrie 'Popescu', 'Ionescu', 'Dumitrescu'</li>";
    echo "</ol>";
    
    echo "<h4>Ce ar trebui să vezi:</h4>";
    echo "<ul>";
    echo "<li>✅ <strong>Sugestii în timp real</strong> când scrieți</li>";
    echo "<li>✅ <strong>Highlight text</strong> pentru termenul căutat</li>";
    echo "<li>✅ <strong>Navigare cu taste</strong> (săgeți, Enter, Escape)</li>";
    echo "<li>✅ <strong>Click selectare</strong> pe sugestii</li>";
    echo "<li>✅ <strong>Fără erori</strong> în console</li>";
    echo "</ul>";
    
    echo "</div>";
}

/**
 * Șterge datele de test
 */
function cleanup_test_data() {
    global $wpdb;
    
    echo "<div class='section'>";
    echo "<h3>Ștergere Date Test</h3>";
    
    // Șterge utilizatorii de test
    $test_users = $wpdb->get_results("
        SELECT user_id FROM {$wpdb->prefix}clinica_patients 
        WHERE import_source = 'test_import'
    ");
    
    $deleted_users = 0;
    $deleted_patients = 0;
    
    foreach ($test_users as $user) {
        // Șterge utilizatorul WordPress
        $user_deleted = wp_delete_user($user->user_id);
        if ($user_deleted) {
            $deleted_users++;
        }
    }
    
    // Șterge înregistrările din tabela pacienți
    $deleted_patients = $wpdb->delete(
        $wpdb->prefix . 'clinica_patients',
        array('import_source' => 'test_import'),
        array('%s')
    );
    
    echo "<p class='success'>✅ Utilizatori șterși: $deleted_users</p>";
    echo "<p class='success'>✅ Înregistrări pacienți șterse: $deleted_patients</p>";
    
    echo "</div>";
}

/**
 * Generează un username unic
 */
function generate_unique_username($first_name, $last_name) {
    $base_username = sanitize_user(strtolower($first_name . '.' . $last_name));
    $username = $base_username;
    $counter = 1;
    
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }
    
    return $username;
}

/**
 * Generează o parolă din CNP
 */
function generate_password_from_cnp($cnp) {
    $password = substr($cnp, -6);
    $password = 'Clinica' . $password . '!';
    return $password;
}

/**
 * Afișează statisticile curente
 */
function display_stats() {
    global $wpdb;
    
    $table_patients = $wpdb->prefix . 'clinica_patients';
    
    // Statistici generale
    $total_users = count_users()['total_users'];
    $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients");
    $test_patients = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_patients WHERE import_source = %s",
        'test_import'
    ));
    
    echo "<div class='section'>";
    echo "<h2>Statistici Curente</h2>";
    echo "<p class='info'>👥 Total utilizatori WordPress: <strong>$total_users</strong></p>";
    echo "<p class='info'>🏥 Total pacienți: <strong>$total_patients</strong></p>";
    echo "<p class='info'>🧪 Pacienți de test: <strong>$test_patients</strong></p>";
    
    if ($test_patients > 0) {
        echo "<h4>Pacienți de Test Disponibili:</h4>";
        $test_users = $wpdb->get_results("
            SELECT p.user_id, p.cnp, um1.meta_value as first_name, um2.meta_value as last_name, um3.meta_value as email
            FROM $table_patients p
            LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
            LEFT JOIN {$wpdb->usermeta} um3 ON p.user_id = um3.user_id AND um3.meta_key = 'email'
            WHERE p.import_source = 'test_import'
            ORDER BY um1.meta_value
        ");
        
        echo "<ul>";
        foreach ($test_users as $user) {
            $name = trim($user->first_name . ' ' . $user->last_name);
            echo "<li><strong>$name</strong> - {$user->email} (CNP: {$user->cnp})</li>";
        }
        echo "</ul>";
    }
    
    echo "</div>";
}
?> 