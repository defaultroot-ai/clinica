<?php
require_once('../../../wp-load.php');

// Configurare baza de date Joomla
$joomla_db_host = 'localhost';
$joomla_db_name = 'cmmf';
$joomla_db_user = 'root';
$joomla_db_pass = '';

// Conectare la baza de date Joomla
$joomla_db = new mysqli($joomla_db_host, $joomla_db_user, $joomla_db_pass, $joomla_db_name);

if ($joomla_db->connect_error) {
    die("Eroare conectare la baza de date Joomla: " . $joomla_db->connect_error);
}

// Funcție pentru validarea telefonului (cu toate formatele românești)
function validatePhoneWithAllFormats($phone) {
    if (empty($phone)) return true;
    
    // Verifică lungimea (inclusiv caracterele speciale)
    if (strlen($phone) > 20) {
        return false;
    }
    
    // Verifică formatele valide:
    // 1. România: +40, +4, 0, 4, 40, 0040 urmat de 9 cifre (cu sau fără separatori)
    // 2. Ucraina: +380 urmat de 9 cifre
    // 3. Internațional: + urmat de 10-15 cifre
    
    // Format românesc fără separatori: 07xxxxxxxx, +407xxxxxxxx, +47xxxxxxxx, 47xxxxxxxx, 407xxxxxxxx, 00407xxxxxxxx
    if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{9}$/', $phone)) {
        return true; // Format românesc fără separatori
    }
    
    // Format românesc cu puncte: 07xx.xxx.xxx, +407xx.xxx.xxx, +47xx.xxx.xxx, 47xx.xxx.xxx, 407xx.xxx.xxx, 00407xx.xxx.xxx
    if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}\.[0-9]{3}\.[0-9]{3}$/', $phone)) {
        return true; // Format românesc cu puncte
    }
    
    // Format românesc cu liniuțe: 07xx-xxx-xxx, +407xx-xxx-xxx, +47xx-xxx-xxx, 47xx-xxx-xxx, 407xx-xxx-xxx, 00407xx-xxx-xxx
    if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}-[0-9]{3}-[0-9]{3}$/', $phone)) {
        return true; // Format românesc cu liniuțe
    }
    
    // Format ucrainean
    if (preg_match('/^\+380[0-9]{9}$/', $phone)) {
        return true; // Format ucrainean
    }
    
    // Verifică dacă este un telefon internațional valid (alte țări)
    if (preg_match('/^\+[0-9]{10,15}$/', $phone)) {
        return true;
    }
    
    return false;
}

// Funcție pentru a determina tipul de eroare
function getPhoneErrorTypeWithAllFormats($phone) {
    if (empty($phone)) return 'GOL';
    
    if (strlen($phone) > 20) {
        return 'PREA LUNG';
    }
    
    // Verifică dacă începe cu formatele valide
    if (preg_match('/^(\+40|\+4|0|4|40|0040)/', $phone)) {
        // Verifică dacă are formatul corect cu puncte
        if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}\.[0-9]{3}\.[0-9]{3}$/', $phone)) {
            return 'VALID ROMÂNIA CU PUNCTE';
        }
        // Verifică dacă are formatul corect cu liniuțe
        if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}-[0-9]{3}-[0-9]{3}$/', $phone)) {
            return 'VALID ROMÂNIA CU LINIUȚE';
        }
        // Verifică dacă are formatul corect fără separatori
        if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{9}$/', $phone)) {
            return 'VALID ROMÂNIA FĂRĂ SEPARATORI';
        }
        return 'FORMAT INVALID ROMÂNIA';
    }
    
    if (preg_match('/^\+380/', $phone)) {
        if (strlen(preg_replace('/[^0-9+]/', '', $phone)) !== 13) {
            return 'LUNGIME INVALIDĂ UCRAINA';
        }
        return 'VALID UCRAINA';
    }
    
    if (preg_match('/^\+/', $phone)) {
        $clean_length = strlen(preg_replace('/[^0-9+]/', '', $phone));
        if ($clean_length < 10 || $clean_length > 15) {
            return 'LUNGIME INVALIDĂ INTERNAȚIONAL';
        }
        return 'VALID INTERNAȚIONAL';
    }
    
    return 'FORMAT INVALID';
}

// Funcție pentru a determina țara telefonului
function getPhoneCountry($phone) {
    if (empty($phone)) return 'UNKNOWN';
    
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    if (preg_match('/^(\+40|\+4|0|4|40|0040)/', $clean_phone)) {
        return 'ROMANIA';
    }
    
    if (preg_match('/^\+380/', $clean_phone)) {
        return 'UKRAINE';
    }
    
    if (preg_match('/^\+/', $clean_phone)) {
        return 'INTERNATIONAL';
    }
    
    return 'UNKNOWN';
}

// Obține structura tabelului Community Builder
$structure_query = "DESCRIBE bqzce_comprofiler";
$structure_result = $joomla_db->query($structure_query);

$cb_fields = array();
while ($field = $structure_result->fetch_assoc()) {
    $cb_fields[] = $field['Field'];
}

// Obține toate telefoanele din baza de date Joomla cu toate câmpurile CB
$fields_list = implode(', ', array_map(function($field) {
    return "cb.`$field`";
}, $cb_fields));

$query = "
    SELECT
        u.id as joomla_id,
        u.username,
        u.name,
        u.email,
        u.registerDate,
        u.lastvisitDate,
        $fields_list
    FROM bqzce_users u
    LEFT JOIN bqzce_comprofiler cb ON u.id = cb.user_id
    WHERE cb.cb_telefon IS NOT NULL OR cb.cb_telefon2 IS NOT NULL
    ORDER BY u.id
";

$result = $joomla_db->query($query);
$invalid_phones = array();
$total_checked = 0;
$valid_phones = 0;
$romania_phones = 0;
$ukraine_phones = 0;
$international_phones = 0;

while ($row = $result->fetch_assoc()) {
    $total_checked++;
    $has_invalid = false;
    $invalid_data = array(
        'joomla_id' => $row['joomla_id'],
        'username' => $row['username'],
        'name' => $row['name'],
        'email' => $row['email'],
        'registerDate' => $row['registerDate'],
        'lastvisitDate' => $row['lastvisitDate'],
        'telefon_principal' => null,
        'telefon_secundar' => null,
        'erori' => array(),
        'countries' => array(),
        'all_cb_fields' => array()
    );

    // Adaugă toate câmpurile CB
    foreach ($cb_fields as $field) {
        $invalid_data['all_cb_fields'][$field] = $row[$field];
    }

                    // Verifică telefonul principal
                if (!empty($row['cb_telefon'])) {
                    $phone = $row['cb_telefon'];
                    $is_valid = validatePhoneWithAllFormats($phone);
                    $error_type = getPhoneErrorTypeWithAllFormats($phone);
                    $country = getPhoneCountry($phone);

                    if ($is_valid) {
                        $valid_phones++;
                        if ($country === 'ROMANIA') $romania_phones++;
                        elseif ($country === 'UKRAINE') $ukraine_phones++;
                        elseif ($country === 'INTERNATIONAL') $international_phones++;
                    } else {
                        $invalid_data['telefon_principal'] = $phone;
                        $invalid_data['erori'][] = 'Principal: ' . $error_type;
                        $has_invalid = true;
                    }

                    $invalid_data['countries'][] = $country;
                }

                // Verifică telefonul secundar
                if (!empty($row['cb_telefon2'])) {
                    $phone2 = $row['cb_telefon2'];
                    $is_valid2 = validatePhoneWithAllFormats($phone2);
                    $error_type2 = getPhoneErrorTypeWithAllFormats($phone2);
                    $country2 = getPhoneCountry($phone2);

                    if ($is_valid2) {
                        $valid_phones++;
                        if ($country2 === 'ROMANIA') $romania_phones++;
                        elseif ($country2 === 'UKRAINE') $ukraine_phones++;
                        elseif ($country2 === 'INTERNATIONAL') $international_phones++;
                    } else {
                        $invalid_data['telefon_secundar'] = $phone2;
                        $invalid_data['erori'][] = 'Secundar: ' . $error_type2;
                        $has_invalid = true;
                    }

                    $invalid_data['countries'][] = $country2;
                }

    if ($has_invalid) {
        $invalid_phones[] = $invalid_data;
    }
}

// Analiză tipuri de erori
$error_types = array();
foreach ($invalid_phones as $data) {
    foreach ($data['erori'] as $error) {
        $error_type = str_replace(['Principal: ', 'Secundar: '], '', $error);
        if (!isset($error_types[$error_type])) {
            $error_types[$error_type] = 0;
        }
        $error_types[$error_type]++;
    }
}

$joomla_db->close();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telefoane Neconforme - Toate Câmpurile CB</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .country-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #e8f4fd;
        }
        .country-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        .country-card.romania {
            border-left-color: #ff6b6b;
        }
        .country-card.ukraine {
            border-left-color: #4ecdc4;
        }
        .country-card.international {
            border-left-color: #45b7d1;
        }
        .error-types {
            padding: 30px;
            background: #fff3cd;
            border-left: 5px solid #ffc107;
        }
        .error-types h3 {
            margin-top: 0;
            color: #856404;
        }
        .error-type {
            display: inline-block;
            background: #fff;
            padding: 8px 16px;
            margin: 5px;
            border-radius: 20px;
            border: 1px solid #ffc107;
            color: #856404;
        }
        .search-box {
            margin: 20px 30px 0;
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s;
        }
        .search-box input:focus {
            border-color: #667eea;
        }
        .export-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px 30px;
            font-size: 14px;
        }
        .export-btn:hover {
            background: #218838;
        }
        .user-details {
            margin: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }
        .user-header {
            background: #667eea;
            color: white;
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-header:hover {
            background: #5a6fd8;
        }
        .user-content {
            padding: 20px;
            display: none;
        }
        .user-content.active {
            display: block;
        }
        .user-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-section {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 3px solid #667eea;
        }
        .info-section h4 {
            margin: 0 0 10px 0;
            color: #667eea;
        }
        .info-item {
            margin: 5px 0;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .invalid-phone {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .error-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin: 2px;
            display: inline-block;
        }
        .country-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin: 2px;
            display: inline-block;
        }
        .country-badge.romania {
            background: #ff6b6b;
            color: white;
        }
        .country-badge.ukraine {
            background: #4ecdc4;
            color: white;
        }
        .country-badge.international {
            background: #45b7d1;
            color: white;
        }
        .country-badge.unknown {
            background: #6c757d;
            color: white;
        }
        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-style: italic;
        }
        .toggle-icon {
            font-size: 1.2em;
            transition: transform 0.3s;
        }
        .toggle-icon.rotated {
            transform: rotate(180deg);
        }
        .cb-fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .cb-field {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            border-left: 2px solid #28a745;
        }
        .cb-field-label {
            font-weight: bold;
            color: #28a745;
            font-size: 0.9em;
        }
        .cb-field-value {
            color: #333;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 Telefoane Neconforme - Toate Câmpurile CB</h1>
            <p>Analiza completă a utilizatorilor cu telefoane neconforme și toate câmpurile Community Builder</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_checked; ?></div>
                <div class="stat-label">Total Utilizatori Verificați</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($invalid_phones); ?></div>
                <div class="stat-label">Utilizatori cu Telefoane Neconforme</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $valid_phones; ?></div>
                <div class="stat-label">Total Telefoane Valide</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($cb_fields); ?></div>
                <div class="stat-label">Câmpuri CB Disponibile</div>
            </div>
        </div>

        <div class="country-stats">
            <div class="country-card romania">
                <h3>🇷🇴 România</h3>
                <div class="stat-number"><?php echo $romania_phones; ?></div>
                <div class="stat-label">Telefoane Valide</div>
            </div>
            <div class="country-card ukraine">
                <h3>🇺🇦 Ucraina</h3>
                <div class="stat-number"><?php echo $ukraine_phones; ?></div>
                <div class="stat-label">Telefoane Valide</div>
            </div>
            <div class="country-card international">
                <h3>🌍 Internațional</h3>
                <div class="stat-number"><?php echo $international_phones; ?></div>
                <div class="stat-label">Telefoane Valide</div>
            </div>
        </div>

        <div class="error-types">
            <h3>🔍 Tipuri de Erori Identificate</h3>
            <?php foreach ($error_types as $type => $count): ?>
                <span class="error-type"><?php echo $type; ?>: <?php echo $count; ?></span>
            <?php endforeach; ?>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Caută după username, email sau câmpuri CB..." onkeyup="filterUsers()">
        </div>

        <button class="export-btn" onclick="exportToCSV()">📊 Exportă la CSV</button>

        <div class="user-details">
            <?php if (empty($invalid_phones)): ?>
                <div class="no-data">
                    <h2>✅ Excelent!</h2>
                    <p>Nu s-au găsit telefoane neconforme în baza de date.</p>
                </div>
            <?php else: ?>
                <?php foreach ($invalid_phones as $index => $data): ?>
                    <div class="user-header" onclick="toggleUser(<?php echo $index; ?>)">
                        <div>
                            <strong><?php echo htmlspecialchars($data['username']); ?></strong>
                            <span style="margin-left: 10px; opacity: 0.8;"><?php echo htmlspecialchars($data['name']); ?></span>
                            <?php if ($data['telefon_principal']): ?>
                                <span class="invalid-phone" style="margin-left: 10px;"><?php echo htmlspecialchars($data['telefon_principal']); ?></span>
                            <?php endif; ?>
                            <?php if ($data['telefon_secundar']): ?>
                                <span class="invalid-phone" style="margin-left: 5px;"><?php echo htmlspecialchars($data['telefon_secundar']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php foreach ($data['erori'] as $error): ?>
                                <span class="error-badge"><?php echo htmlspecialchars($error); ?></span>
                            <?php endforeach; ?>
                            <span class="toggle-icon" id="toggle-<?php echo $index; ?>">▼</span>
                        </div>
                    </div>
                    <div class="user-content" id="content-<?php echo $index; ?>">
                        <div class="user-info">
                            <div class="info-section">
                                <h4>📋 Informații de Bază</h4>
                                <div class="info-item">
                                    <span class="info-label">Joomla ID:</span>
                                    <span class="info-value"><?php echo $data['joomla_id']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Username:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($data['username']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Nume:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($data['name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($data['email']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Data înregistrării:</span>
                                    <span class="info-value"><?php echo $data['registerDate']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Ultima vizită:</span>
                                    <span class="info-value"><?php echo $data['lastvisitDate']; ?></span>
                                </div>
                            </div>

                            <div class="info-section">
                                <h4>📞 Telefoane</h4>
                                <?php if ($data['telefon_principal']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Telefon principal:</span>
                                        <span class="invalid-phone"><?php echo htmlspecialchars($data['telefon_principal']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($data['telefon_secundar']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Telefon secundar:</span>
                                        <span class="invalid-phone"><?php echo htmlspecialchars($data['telefon_secundar']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <span class="info-label">Țări detectate:</span>
                                    <?php 
                                    $unique_countries = array_unique($data['countries']);
                                    foreach ($unique_countries as $country): 
                                        $country_lower = strtolower($country);
                                    ?>
                                        <span class="country-badge <?php echo $country_lower; ?>"><?php echo $country; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="info-section">
                            <h4>🏥 Toate Câmpurile Community Builder</h4>
                            <div class="cb-fields-grid">
                                <?php foreach ($data['all_cb_fields'] as $field => $value): ?>
                                    <?php if (!empty($value) && $value !== 'NULL' && $value !== '0000-00-00 00:00:00' && $value !== '0000-00-00'): ?>
                                        <div class="cb-field">
                                            <div class="cb-field-label"><?php echo htmlspecialchars($field); ?></div>
                                            <div class="cb-field-value"><?php echo htmlspecialchars($value); ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleUser(index) {
            const content = document.getElementById('content-' + index);
            const toggle = document.getElementById('toggle-' + index);
            
            if (content.classList.contains('active')) {
                content.classList.remove('active');
                toggle.classList.remove('rotated');
            } else {
                content.classList.add('active');
                toggle.classList.add('rotated');
            }
        }

        function filterUsers() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const userHeaders = document.querySelectorAll('.user-header');

            userHeaders.forEach((header, index) => {
                const content = document.getElementById('content-' + index);
                const text = header.textContent.toLowerCase() + ' ' + content.textContent.toLowerCase();
                
                if (text.indexOf(filter) > -1) {
                    header.style.display = '';
                    content.style.display = content.classList.contains('active') ? 'block' : 'none';
                } else {
                    header.style.display = 'none';
                    content.style.display = 'none';
                }
            });
        }

        function exportToCSV() {
            const users = <?php echo json_encode($invalid_phones); ?>;
            let csv = 'Username,Nume,Email,Joomla ID,Telefon Principal,Telefon Secundar,Erori,Țări\n';

            users.forEach(user => {
                const row = [
                    user.username,
                    user.name,
                    user.email,
                    user.joomla_id,
                    user.telefon_principal || '',
                    user.telefon_secundar || '',
                    user.erori.join('; '),
                    user.countries.join('; ')
                ].map(field => '"' + field.replace(/"/g, '""') + '"').join(',');
                
                csv += row + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'telefoane_neconforme_complete.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html> 