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

// Funcție pentru validarea telefonului (cu suport Ucraina)
function validatePhoneWithUkraine($phone) {
    if (empty($phone)) return true;
    
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Verifică lungimea
    if (strlen($clean_phone) > 20) {
        return false;
    }
    
    // Verifică formatele valide:
    // 1. România: +40 sau 0 urmat de 9 cifre
    // 2. Ucraina: +380 urmat de 9 cifre
    if (preg_match('/^(\+40|0)[0-9]{9}$/', $clean_phone)) {
        return true; // Format românesc
    }
    
    if (preg_match('/^\+380[0-9]{9}$/', $clean_phone)) {
        return true; // Format ucrainean
    }
    
    // Verifică dacă este un telefon internațional valid (alte țări)
    if (preg_match('/^\+[0-9]{10,15}$/', $clean_phone)) {
        return true;
    }
    
    return false;
}

// Funcție pentru a determina tipul de eroare
function getPhoneErrorTypeWithUkraine($phone) {
    if (empty($phone)) return 'GOL';
    
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    if (strlen($clean_phone) > 20) {
        return 'PREA LUNG';
    }
    
    // Verifică dacă începe cu formatele valide
    if (preg_match('/^(\+40|0)/', $clean_phone)) {
        if (strlen($clean_phone) !== 10 && strlen($clean_phone) !== 12) {
            return 'LUNGIME INVALIDĂ ROMÂNIA';
        }
        return 'VALID ROMÂNIA';
    }
    
    if (preg_match('/^\+380/', $clean_phone)) {
        if (strlen($clean_phone) !== 13) {
            return 'LUNGIME INVALIDĂ UCRAINA';
        }
        return 'VALID UCRAINA';
    }
    
    if (preg_match('/^\+/', $clean_phone)) {
        if (strlen($clean_phone) < 10 || strlen($clean_phone) > 15) {
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
    
    if (preg_match('/^(\+40|0)/', $clean_phone)) {
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

// Obține toate telefoanele din baza de date Joomla
$query = "
    SELECT
        u.id as joomla_id,
        u.username,
        u.email,
        cb.cb_telefon,
        cb.cb_telefon2
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
        'email' => $row['email'],
        'telefon_principal' => null,
        'telefon_secundar' => null,
        'erori' => array(),
        'countries' => array()
    );

    // Verifică telefonul principal
    if (!empty($row['cb_telefon'])) {
        $phone = $row['cb_telefon'];
        $is_valid = validatePhoneWithUkraine($phone);
        $error_type = getPhoneErrorTypeWithUkraine($phone);
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
        $is_valid2 = validatePhoneWithUkraine($phone2);
        $error_type2 = getPhoneErrorTypeWithUkraine($phone2);
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
    <title>Lista Telefoane Neconforme - Suport Ucraina</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1400px;
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
        .table-container {
            padding: 30px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background-color: #f8f9fa;
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
        .formats-info {
            padding: 30px;
            background: #d4edda;
            border-left: 5px solid #28a745;
        }
        .formats-info h3 {
            margin-top: 0;
            color: #155724;
        }
        .format-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border-left: 3px solid #28a745;
        }
        .format-item strong {
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 Lista Telefoane Neconforme</h1>
            <p>Analiza telefoanelor din baza de date Joomla cu suport pentru România, Ucraina și internațional</p>
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
                <div class="stat-number"><?php echo round((count($invalid_phones) / $total_checked) * 100, 1); ?>%</div>
                <div class="stat-label">Procent Neconform</div>
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

        <div class="formats-info">
            <h3>✅ Formate Valide Acceptate</h3>
            <div class="format-item">
                <strong>🇷🇴 România:</strong>
                <ul>
                    <li>07XXXXXXXX (10 cifre)</li>
                    <li>+407XXXXXXXX (12 caractere)</li>
                </ul>
            </div>
            <div class="format-item">
                <strong>🇺🇦 Ucraina:</strong>
                <ul>
                    <li>+380XXXXXXXXX (13 caractere)</li>
                </ul>
            </div>
            <div class="format-item">
                <strong>🌍 Internațional:</strong>
                <ul>
                    <li>+XXXXXXXXXXX (10-15 caractere)</li>
                </ul>
            </div>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Caută după username, email sau telefon..." onkeyup="filterTable()">
        </div>

        <button class="export-btn" onclick="exportToCSV()">📊 Exportă la CSV</button>

        <div class="table-container">
            <?php if (empty($invalid_phones)): ?>
                <div class="no-data">
                    <h2>✅ Excelent!</h2>
                    <p>Nu s-au găsit telefoane neconforme în baza de date.</p>
                </div>
            <?php else: ?>
                <table id="phonesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Joomla ID</th>
                            <th>Email</th>
                            <th>Telefon Principal</th>
                            <th>Telefon Secundar</th>
                            <th>Erori</th>
                            <th>Țări</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invalid_phones as $index => $data): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($data['username']); ?></strong></td>
                                <td><?php echo $data['joomla_id']; ?></td>
                                <td><?php echo htmlspecialchars($data['email']); ?></td>
                                <td>
                                    <?php if ($data['telefon_principal']): ?>
                                        <span class="invalid-phone"><?php echo htmlspecialchars($data['telefon_principal']); ?></span>
                                    <?php else: ?>
                                        <em>-</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($data['telefon_secundar']): ?>
                                        <span class="invalid-phone"><?php echo htmlspecialchars($data['telefon_secundar']); ?></span>
                                    <?php else: ?>
                                        <em>-</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php foreach ($data['erori'] as $error): ?>
                                        <span class="error-badge"><?php echo htmlspecialchars($error); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php 
                                    $unique_countries = array_unique($data['countries']);
                                    foreach ($unique_countries as $country): 
                                        $country_lower = strtolower($country);
                                    ?>
                                        <span class="country-badge <?php echo $country_lower; ?>"><?php echo $country; ?></span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('phonesTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }

                row.style.display = found ? '' : 'none';
            }
        }

        function exportToCSV() {
            const table = document.getElementById('phonesTable');
            const rows = table.getElementsByTagName('tr');
            let csv = 'Username,Joomla ID,Email,Telefon Principal,Telefon Secundar,Erori,Țări\n';

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let rowData = [];

                for (let j = 1; j < cells.length; j++) {
                    const cell = cells[j];
                    let cellText = cell.textContent.trim();
                    cellText = cellText.replace(/"/g, '""');
                    rowData.push('"' + cellText + '"');
                }

                csv += rowData.join(',') + '\n';
            }

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'telefoane_neconforme_ukraine.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html> 