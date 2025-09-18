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

// Funcție pentru validarea telefonului
function validatePhone($phone) {
    if (empty($phone)) return true;
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    if (strlen($clean_phone) > 20) return false;
    if (!preg_match('/^(\+40|0)/', $clean_phone)) return false;
    return true;
}

// Funcție pentru a determina tipul de eroare
function getPhoneErrorType($phone) {
    if (empty($phone)) return 'GOL';
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    if (strlen($clean_phone) > 20) return 'PREA LUNG';
    if (!preg_match('/^(\+40|0)/', $clean_phone)) return 'FORMAT INVALID';
    return 'ALT TIP';
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

while ($row = $result->fetch_assoc()) {
    $total_checked++;
    $has_invalid = false;
    $invalid_data = array(
        'joomla_id' => $row['joomla_id'],
        'username' => $row['username'],
        'email' => $row['email'],
        'telefon_principal' => null,
        'telefon_secundar' => null,
        'erori' => array()
    );
    
    // Verifică telefonul principal
    if (!empty($row['cb_telefon'])) {
        $phone = $row['cb_telefon'];
        $is_valid = validatePhone($phone);
        
        if (!$is_valid) {
            $invalid_data['telefon_principal'] = $phone;
            $invalid_data['erori'][] = 'Principal: ' . getPhoneErrorType($phone);
            $has_invalid = true;
        }
    }
    
    // Verifică telefonul secundar
    if (!empty($row['cb_telefon2'])) {
        $phone2 = $row['cb_telefon2'];
        $is_valid2 = validatePhone($phone2);
        
        if (!$is_valid2) {
            $invalid_data['telefon_secundar'] = $phone2;
            $invalid_data['erori'][] = 'Secundar: ' . getPhoneErrorType($phone2);
            $has_invalid = true;
        }
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
    <title>Lista Telefoane Neconforme - Joomla Migration</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 Lista Telefoane Neconforme</h1>
            <p>Analiza telefoanelor din baza de date Joomla care nu respectă formatul valid</p>
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
                <div class="stat-number"><?php echo array_sum($error_types); ?></div>
                <div class="stat-label">Total Erori Găsite</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo round((count($invalid_phones) / $total_checked) * 100, 1); ?>%</div>
                <div class="stat-label">Procent Neconform</div>
            </div>
        </div>

        <div class="error-types">
            <h3>🔍 Tipuri de Erori Identificate</h3>
            <?php foreach ($error_types as $type => $count): ?>
                <span class="error-type"><?php echo $type; ?>: <?php echo $count; ?></span>
            <?php endforeach; ?>
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
            let csv = 'Username,Joomla ID,Email,Telefon Principal,Telefon Secundar,Erori\n';

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
            link.setAttribute('download', 'telefoane_neconforme.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html> 