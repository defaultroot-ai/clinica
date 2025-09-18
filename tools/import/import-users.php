<?php
/**
 * Import Utilizatori în WordPress
 * Script pentru importul utilizatorilor cu emailuri de familie
 */

// Încarcă WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunea de a rula acest script');
}

echo "<h1>Import Utilizatori în WordPress</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
.warning { color: orange; }
.section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.form-group { margin: 10px 0; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
.btn { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; }
.btn:hover { background: #005a87; }
.results { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; }
.csv-preview { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; }
.user-preview { margin: 5px 0; padding: 5px; border: 1px solid #eee; border-radius: 3px; background: #fafafa; }
</style>";

// Procesare formular
if ($_POST['action'] === 'import_users') {
    echo "<div class='section'>";
    echo "<h2>Rezultate Import Utilizatori</h2>";
    
    $csv_data = $_POST['csv_data'];
    $delimiter = $_POST['delimiter'];
    $has_header = isset($_POST['has_header']);
    $create_patients = isset($_POST['create_patients']);
    
    $results = process_user_import($csv_data, $delimiter, $has_header, $create_patients);
    display_import_results($results);
    echo "</div>";
}

// Formular principal
echo "<div class='section'>";
echo "<h2>Import Utilizatori din CSV</h2>";

echo "<form method='post'>";
echo "<input type='hidden' name='action' value='import_users'>";

echo "<div class='form-group'>";
echo "<label>Delimitator:</label>";
echo "<select name='delimiter'>";
echo "<option value=','>Virgulă (,)</option>";
echo "<option value=';'>Punct și virgulă (;)</option>";
echo "<option value='\t'>Tab</option>";
echo "<option value='|'>Pipe (|)</option>";
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label><input type='checkbox' name='has_header' checked> Primul rând conține header-ul</label>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label><input type='checkbox' name='create_patients' checked> Creează și înregistrări în tabela pacienți</label>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>Date CSV:</label>";
echo "<textarea name='csv_data' rows='15' placeholder='first_name,last_name,email,cnp,phone,address&#10;Ion,Popescu,ion+maria@gmail.com,1800404080170,0722123456,Strada Exemplu 1&#10;Maria,Popescu,maria+ana@gmail.com,2800404080171,0722123457,Strada Exemplu 1&#10;Ana,Popescu,ana@gmail.com,3800404080172,0722123458,Strada Exemplu 1'></textarea>";
echo "</div>";

echo "<div class='form-group'>";
echo "<h3>Format CSV Așteptat:</h3>";
echo "<p class='info'>Coloanele necesare:</p>";
echo "<ul>";
echo "<li><strong>first_name</strong> - Prenumele (obligatoriu)</li>";
echo "<li><strong>last_name</strong> - Numele de familie (obligatoriu)</li>";
echo "<li><strong>email</strong> - Adresa de email (obligatoriu, unică)</li>";
echo "<li><strong>cnp</strong> - CNP-ul (obligatoriu, unic)</li>";
echo "<li><strong>phone</strong> - Numărul de telefon (opțional)</li>";
echo "<li><strong>address</strong> - Adresa (opțional)</li>";
echo "<li><strong>birth_date</strong> - Data nașterii (opțional, format: YYYY-MM-DD)</li>";
echo "<li><strong>gender</strong> - Genul (opțional: male/female)</li>";
echo "</ul>";
echo "</div>";

echo "<button type='submit' class='btn'>👥 Import Utilizatori</button>";
echo "</form>";
echo "</div>";

// Exemplu CSV
echo "<div class='section'>";
echo "<h2>Exemplu CSV cu Emailuri de Familie</h2>";
echo "<div class='csv-preview'>";
echo "<pre>first_name,last_name,email,cnp,phone,address,birth_date,gender
Ion,Popescu,ion+maria@gmail.com,1800404080170,0722123456,Strada Exemplu 1,1980-01-15,male
Maria,Popescu,maria+ana@gmail.com,2800404080171,0722123457,Strada Exemplu 1,1982-03-20,female
Ana,Popescu,ana@gmail.com,3800404080172,0722123458,Strada Exemplu 1,2010-07-10,female
Vasile,Ionescu,vasile+elena@yahoo.com,4800404080173,0722123459,Strada Exemplu 2,1975-11-05,male
Elena,Ionescu,elena@yahoo.com,5800404080174,0722123460,Strada Exemplu 2,1978-09-12,female
Gheorghe,Dumitrescu,gheorghe+ioana@hotmail.com,6800404080175,0722123461,Strada Exemplu 3,1970-04-25,male
Ioana,Dumitrescu,ioana+mihai@hotmail.com,7800404080176,0722123462,Strada Exemplu 3,1972-08-30,female
Mihai,Dumitrescu,mihai@hotmail.com,8800404080177,0722123463,Strada Exemplu 3,2005-12-15,male
Andreea,Dumitrescu,andreea@hotmail.com,9800404080178,0722123464,Strada Exemplu 3,2008-06-22,female</pre>";
echo "</div>";
echo "<p class='info'>💡 Observă că emailurile conțin informații despre relațiile familiale (parinte+copil@domain.com)</p>";
echo "</div>";

// Statistici curente
echo "<div class='section'>";
echo "<h2>Statistici Curente</h2>";
display_current_stats();
echo "</div>";

/**
 * Procesează importul utilizatorilor
 */
function process_user_import($csv_data, $delimiter = ',', $has_header = true, $create_patients = true) {
    global $wpdb;
    
    $results = array(
        'success' => 0,
        'errors' => array(),
        'warnings' => array(),
        'users_created' => 0,
        'patients_created' => 0,
        'rows_processed' => 0
    );
    
    $lines = explode("\n", trim($csv_data));
    $start_line = $has_header ? 1 : 0;
    
    // Parsează CSV-ul
    for ($i = $start_line; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;
        
        $parts = str_getcsv($line, $delimiter);
        
        if (count($parts) < 4) {
            $results['errors'][] = "Linia " . ($i + 1) . ": Format invalid (necesită minim 4 coloane: first_name, last_name, email, cnp)";
            continue;
        }
        
        $first_name = trim($parts[0]);
        $last_name = trim($parts[1]);
        $email = trim($parts[2]);
        $cnp = trim($parts[3]);
        $phone = isset($parts[4]) ? trim($parts[4]) : '';
        $address = isset($parts[5]) ? trim($parts[5]) : '';
        $birth_date = isset($parts[6]) ? trim($parts[6]) : '';
        $gender = isset($parts[7]) ? trim($parts[7]) : '';
        
        // Validări
        if (empty($first_name)) {
            $results['errors'][] = "Linia " . ($i + 1) . ": Prenumele este gol";
            continue;
        }
        
        if (empty($last_name)) {
            $results['errors'][] = "Linia " . ($i + 1) . ": Numele de familie este gol";
            continue;
        }
        
        if (empty($email) || !is_email($email)) {
            $results['errors'][] = "Linia " . ($i + 1) . ": Email invalid '$email'";
            continue;
        }
        
        if (empty($cnp) || strlen($cnp) !== 13) {
            $results['errors'][] = "Linia " . ($i + 1) . ": CNP invalid '$cnp' (trebuie 13 caractere)";
            continue;
        }
        
        // Verifică dacă emailul există deja
        $existing_user = get_user_by('email', $email);
        if ($existing_user) {
            $results['warnings'][] = "Linia " . ($i + 1) . ": Email '$email' există deja (utilizator ID: {$existing_user->ID})";
            continue;
        }
        
        // Verifică dacă CNP-ul există deja
        $existing_cnp = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE cnp = %s",
            $cnp
        ));
        
        if ($existing_cnp) {
            $results['warnings'][] = "Linia " . ($i + 1) . ": CNP '$cnp' există deja (utilizator ID: $existing_cnp)";
            continue;
        }
        
        // Creează utilizatorul WordPress
        $username = generate_unique_username($first_name, $last_name);
        $password = generate_password_from_cnp($cnp);
        
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => 'subscriber'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            $results['errors'][] = "Linia " . ($i + 1) . ": Eroare la crearea utilizatorului: " . $user_id->get_error_message();
            continue;
        }
        
        $results['users_created']++;
        
        // Creează înregistrarea în tabela pacienți
        if ($create_patients) {
            $patient_data = array(
                'user_id' => $user_id,
                'cnp' => $cnp,
                'phone_primary' => $phone,
                'address' => $address,
                'birth_date' => !empty($birth_date) ? $birth_date : null,
                'gender' => !empty($gender) ? $gender : null,
                'created_by' => get_current_user_id(),
                'import_source' => 'csv_import'
            );
            
            $insert_result = $wpdb->insert(
                $wpdb->prefix . 'clinica_patients',
                $patient_data,
                array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
            );
            
            if ($insert_result === false) {
                $results['errors'][] = "Linia " . ($i + 1) . ": Eroare la crearea înregistrării pacient: " . $wpdb->last_error;
            } else {
                $results['patients_created']++;
            }
        }
        
        $results['rows_processed']++;
        
        echo "<div class='user-preview'>";
        echo "<p class='success'>✅ Utilizator creat: <strong>$first_name $last_name</strong> (ID: $user_id)</p>";
        echo "<p class='info'>📧 Email: $email | 🆔 CNP: $cnp | 👤 Username: $username</p>";
        if ($create_patients) {
            echo "<p class='info'>🏥 Înregistrare pacient creată</p>";
        }
        echo "</div>";
    }
    
    $results['success'] = 1;
    return $results;
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
    // Folosește ultimele 6 cifre din CNP ca parolă
    $password = substr($cnp, -6);
    
    // Adaugă caractere speciale pentru a îndeplini cerințele de securitate
    $password = 'Clinica' . $password . '!';
    
    return $password;
}

/**
 * Afișează rezultatele importului
 */
function display_import_results($results) {
    if ($results['success']) {
        echo "<div class='results'>";
        echo "<h3>Import Finalizat!</h3>";
        echo "<p class='success'>✅ Rânduri procesate: {$results['rows_processed']}</p>";
        echo "<p class='success'>✅ Utilizatori creați: {$results['users_created']}</p>";
        echo "<p class='success'>✅ Pacienți creați: {$results['patients_created']}</p>";
        
        if (!empty($results['warnings'])) {
            echo "<h4>Avertismente:</h4>";
            echo "<ul>";
            foreach ($results['warnings'] as $warning) {
                echo "<li class='warning'>⚠️ $warning</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($results['errors'])) {
            echo "<h4>Erori:</h4>";
            echo "<ul>";
            foreach ($results['errors'] as $error) {
                echo "<li class='error'>❌ $error</li>";
            }
            echo "</ul>";
        }
        
        if ($results['users_created'] > 0) {
            echo "<h4>🎯 Următorii Pași:</h4>";
            echo "<ol>";
            echo "<li><strong>Testează autentificarea:</strong> Utilizatorii pot să se autentifice cu emailul și parola generată din CNP</li>";
            echo "<li><strong>Import familii:</strong> Folosește <a href='import-families-from-emails.php'>scriptul de import familii</a> pentru a detecta familiile din emailuri</li>";
            echo "<li><strong>Verifică autosuggest:</strong> Testează funcționalitatea de autosuggest pe pagina de pacienți</li>";
            echo "</ol>";
        }
        
        echo "</div>";
    } else {
        echo "<div class='results'>";
        echo "<h3>Eroare la Import</h3>";
        echo "<ul>";
        foreach ($results['errors'] as $error) {
            echo "<li class='error'>❌ $error</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

/**
 * Afișează statisticile curente
 */
function display_current_stats() {
    global $wpdb;
    
    $table_patients = $wpdb->prefix . 'clinica_patients';
    
    // Statistici generale
    $total_users = count_users()['total_users'];
    $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $table_patients");
    $patients_with_email = $wpdb->get_var("
        SELECT COUNT(*) FROM $table_patients p
        JOIN {$wpdb->usermeta} um ON p.user_id = um.user_id 
        WHERE um.meta_key = 'email' AND um.meta_value IS NOT NULL AND um.meta_value != ''
    ");
    
    echo "<p class='info'>👥 Total utilizatori WordPress: <strong>$total_users</strong></p>";
    echo "<p class='info'>🏥 Total pacienți în baza de date: <strong>$total_patients</strong></p>";
    echo "<p class='info'>📧 Pacienți cu email: <strong>$patients_with_email</strong></p>";
    
    // Exemple de utilizatori recenti
    $recent_users = $wpdb->get_results("
        SELECT p.user_id, p.cnp, um1.meta_value as first_name, um2.meta_value as last_name, um3.meta_value as email
        FROM $table_patients p
        LEFT JOIN {$wpdb->usermeta} um1 ON p.user_id = um1.user_id AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} um2 ON p.user_id = um2.user_id AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->usermeta} um3 ON p.user_id = um3.user_id AND um3.meta_key = 'email'
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    
    if ($recent_users) {
        echo "<h4>Utilizatori Recenti:</h4>";
        echo "<ul>";
        foreach ($recent_users as $user) {
            $name = trim($user->first_name . ' ' . $user->last_name);
            $name = !empty($name) ? $name : 'Necunoscut';
            echo "<li><strong>$name</strong> - {$user->email} (CNP: {$user->cnp})</li>";
        }
        echo "</ul>";
    }
    
    // Link către import familii
    echo "<h4>🔗 Link-uri Utile:</h4>";
    echo "<ul>";
    echo "<li><a href='import-families-from-emails.php'>Import Familii din Emailuri</a></li>";
    echo "<li><a href='import-families-simple.php'>Import Familii Simplu</a></li>";
    echo "<li><a href='import-families-csv.php'>Import Familii din CSV</a></li>";
    echo "</ul>";
}
?> 