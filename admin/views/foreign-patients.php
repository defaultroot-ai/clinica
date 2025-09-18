<?php
/**
 * Pagina pentru Pacienții Străini
 * Afișează pacienții cu CNP-uri de rezidenți străini
 */

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die(__('Nu aveți permisiunea de a accesa această pagină.', 'clinica'));
}

// Funcție pentru a calcula sexul din CNP
function get_gender_from_cnp($cnp) {
    if (empty($cnp) || strlen($cnp) !== 13) {
        return '-';
    }
    
    $gender_digit = intval($cnp[0]);
    if ($gender_digit === 1 || $gender_digit === 3 || $gender_digit === 5 || $gender_digit === 7 || $gender_digit === 9) {
        return 'M';
    } elseif ($gender_digit === 2 || $gender_digit === 4 || $gender_digit === 6 || $gender_digit === 8) {
        return 'F';
    }
    
    return '-';
}

// Funcție pentru a calcula data nașterii din CNP
function get_birth_date_from_cnp($cnp) {
    if (empty($cnp) || strlen($cnp) !== 13) {
        return null;
    }
    
    $year_digit = intval($cnp[0]);
    $year = intval(substr($cnp, 1, 2));
    $month = intval(substr($cnp, 3, 2));
    $day = intval(substr($cnp, 5, 2));
    
    // Determină secolul în funcție de prima cifră
    if ($year_digit === 1 || $year_digit === 2) {
        $full_year = 1900 + $year;
    } elseif ($year_digit === 3 || $year_digit === 4) {
        $full_year = 1800 + $year;
    } elseif ($year_digit === 5 || $year_digit === 6) {
        $full_year = 2000 + $year;
    } elseif ($year_digit === 7 || $year_digit === 8) {
        $full_year = 2000 + $year;
    } elseif ($year_digit === 9) {
        $full_year = 1800 + $year;
    } else {
        return null;
    }
    
    if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
        return null;
    }
    
    return sprintf('%04d-%02d-%02d', $full_year, $month, $day);
}

// Funcție pentru a calcula vârsta
function calculate_age($birth_date) {
    if (empty($birth_date)) {
        return '-';
    }
    
    $birth = new DateTime($birth_date);
    $now = new DateTime();
    $age = $now->diff($birth);
    
    if ($age->y > 0) {
        if ($age->y == 1) {
            return '1 an';
        } else {
            return $age->y . ' ani';
        }
    } else {
        return $age->m . ' luni';
    }
}

// Obține pacienții străini
global $wpdb;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$gender_filter = isset($_GET['gender']) ? sanitize_text_field($_GET['gender']) : '';
$email_status_filter = isset($_GET['email_status']) ? sanitize_text_field($_GET['email_status']) : '';

$where_conditions = array();

// Filtru pentru CNP-uri de rezidenți străini (încep cu 7, 8 sau 9)
$where_conditions[] = "(p.cnp LIKE '7%' OR p.cnp LIKE '8%' OR p.cnp LIKE '9%' OR u.user_login LIKE '7%' OR u.user_login LIKE '8%' OR u.user_login LIKE '9%')";

if (!empty($search)) {
    $where_conditions[] = $wpdb->prepare(
        "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.cnp LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s)",
        '%' . $search . '%',
        '%' . $search . '%',
        '%' . $search . '%',
        '%' . $search . '%',
        '%' . $search . '%'
    );
}

// Filtru pentru gen (dacă este selectat)
if (!empty($gender_filter)) {
    $where_conditions[] = $wpdb->prepare("p.gender = %s", $gender_filter);
}

// Filtru pentru status email (dacă este selectat)
if (!empty($email_status_filter)) {
    if ($email_status_filter === 'with_email') {
        $where_conditions[] = "u.user_email IS NOT NULL AND u.user_email != ''";
    } elseif ($email_status_filter === 'without_email') {
        $where_conditions[] = "(u.user_email IS NULL OR u.user_email = '')";
    }
}

$where_clause = implode(' AND ', $where_conditions);

$query = "
    SELECT p.*, u.user_email, u.user_registered, u.user_login,
           um1.meta_value as first_name, um2.meta_value as last_name
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE {$where_clause}
    ORDER BY um2.meta_value ASC, um1.meta_value ASC
";

$foreign_patients = $wpdb->get_results($query);
$total_foreign_patients = count($foreign_patients);

// Statistici
$stats = array(
    'total' => $total_foreign_patients,
    'male' => 0,
    'female' => 0,
    'with_email' => 0,
    'without_email' => 0
);

foreach ($foreign_patients as $patient) {
    // Calculează sexul din CNP
    $calculated_gender = get_gender_from_cnp($patient->cnp);
    if ($calculated_gender === 'M') {
        $stats['male']++;
    } elseif ($calculated_gender === 'F') {
        $stats['female']++;
    }
    
    if (!empty($patient->user_email)) {
        $stats['with_email']++;
    } else {
        $stats['without_email']++;
    }
}
?>

<div class="wrap clinica-foreign-patients">
    <div class="clinica-patients-header">
        <div class="clinica-header-main">
            <div class="clinica-header-left">
                <h1>
                    <span class="dashicons dashicons-admin-users"></span>
                    Pacienți Străini
                </h1>
                <p>Pacienți cu CNP-uri de rezidenți străini (încep cu 7, 8 sau 9)</p>
            </div>
            <div class="clinica-header-right">
                <a href="<?php echo admin_url('admin.php?page=clinica'); ?>" class="button">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Înapoi la Dashboard
                </a>
            </div>
        </div>
        
        <div class="clinica-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['total']; ?></span>
                <span class="stat-label">Total Pacienți Străini</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['male']; ?></span>
                <span class="stat-label">Bărbați</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['female']; ?></span>
                <span class="stat-label">Femei</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['with_email']; ?></span>
                <span class="stat-label">Cu E-mail</span>
            </div>
        </div>
    </div>

    <!-- Filtre -->
    <div class="clinica-filters-container">
        <form method="GET" class="clinica-filters-form">
            <input type="hidden" name="page" value="clinica-foreign-patients">
            
            <div class="clinica-filters-row">
                <div class="clinica-filter-group">
                    <label for="search">Căutare:</label>
                    <input type="text" id="search" name="search" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Nume, prenume, CNP sau e-mail...">
                </div>
                
                <div class="clinica-filter-group">
                    <label for="gender">Sex:</label>
                    <select id="gender" name="gender">
                        <option value="">Toate</option>
                        <option value="M" <?php selected($gender_filter, 'M'); ?>>Bărbați</option>
                        <option value="F" <?php selected($gender_filter, 'F'); ?>>Femei</option>
                    </select>
                </div>
                
                <div class="clinica-filter-group">
                    <label for="email_status">Status E-mail:</label>
                    <select id="email_status" name="email_status">
                        <option value="">Toate</option>
                        <option value="with_email" <?php selected($email_status_filter, 'with_email'); ?>>Cu E-mail</option>
                        <option value="without_email" <?php selected($email_status_filter, 'without_email'); ?>>Fără E-mail</option>
                    </select>
                </div>
            </div>
            
            <div class="clinica-filters-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    Caută
                </button>
                <a href="<?php echo admin_url('admin.php?page=clinica-foreign-patients'); ?>" class="button">
                    <span class="dashicons dashicons-update"></span>
                    Resetează
                </a>
            </div>
        </form>
    </div>

    <!-- Tabelul de pacienți -->
    <div class="clinica-patients-table">
        <?php if (empty($foreign_patients)): ?>
            <div class="clinica-empty-state">
                <div class="clinica-empty-content">
                    <span class="dashicons dashicons-admin-users"></span>
                    <h3>Nu există pacienți străini</h3>
                    <p>Nu au fost găsiți pacienți cu CNP-uri de rezidenți străini.</p>
                </div>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nume</th>
                        <th>CNP</th>
                        <th>Sex</th>
                        <th>Data Nașterii</th>
                        <th>Vârsta</th>
                        <th>E-mail</th>
                        <th>Telefon</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($foreign_patients as $patient): ?>
                        <?php 
                        $calculated_gender = get_gender_from_cnp($patient->cnp);
                        $birth_date = get_birth_date_from_cnp($patient->cnp);
                        $age = calculate_age($birth_date);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($patient->last_name . ' ' . $patient->first_name); ?></strong>
                            </td>
                            <td>
                                <code><?php echo esc_html(!empty($patient->cnp) ? $patient->cnp : $patient->user_login); ?></code>
                                <br>
                                <small class="clinica-cnp-type">Rezident Străin</small>
                            </td>
                            <td>
                                <?php if ($calculated_gender === 'M'): ?>
                                    <span class="clinica-gender-simple clinica-gender-m">M</span>
                                <?php elseif ($calculated_gender === 'F'): ?>
                                    <span class="clinica-gender-simple clinica-gender-f">F</span>
                                <?php else: ?>
                                    <span class="clinica-no-gender">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($birth_date) {
                                    echo esc_html(date('d.m.Y', strtotime($birth_date)));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo $age !== '-' ? $age : '-'; ?>
                            </td>
                            <td>
                                <?php if (!empty($patient->user_email)): ?>
                                    <a href="mailto:<?php echo esc_attr($patient->user_email); ?>">
                                        <?php echo esc_html($patient->user_email); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="clinica-no-email">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($patient->phone)): ?>
                                    <a href="tel:<?php echo esc_attr($patient->phone); ?>">
                                        <?php echo esc_html($patient->phone); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="clinica-no-phone">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="clinica-action-buttons">
                                    <a href="<?php echo admin_url('admin.php?page=clinica-patients&action=edit&id=' . $patient->id); ?>" 
                                       class="clinica-action-btn" title="Editează">
                                        <span class="dashicons dashicons-edit"></span>
                                        Editează
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=clinica-patients&action=view&id=' . $patient->id); ?>" 
                                       class="clinica-action-btn" title="Vezi Detalii">
                                        <span class="dashicons dashicons-visibility"></span>
                                        Vezi
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.clinica-foreign-patients {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px;
}

.clinica-foreign-patients .clinica-patients-header {
    background: #2c3e50;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    position: relative;
    padding: 0;
}

.clinica-foreign-patients .clinica-patients-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #e67e22;
}

.clinica-foreign-patients .clinica-patients-header h1 {
    color: white;
    font-size: 24px;
    font-weight: 600;
    margin: 0;
    padding: 25px 25px 15px;
    position: relative;
    z-index: 2;
}

.clinica-foreign-patients .clinica-header-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0;
}

.clinica-foreign-patients .clinica-header-left {
    flex: 1;
}

.clinica-foreign-patients .clinica-header-right {
    padding: 30px 25px 20px;
    display: flex;
    align-items: center;
}

.clinica-foreign-patients .clinica-header-right .button {
    background: #e67e22;
    border: 1px solid #d35400;
    color: white;
    padding: 10px 16px;
    border-radius: 4px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.clinica-foreign-patients .clinica-header-right .button:hover {
    background: #d35400;
    border-color: #a04000;
    color: white;
    text-decoration: none;
}

.clinica-foreign-patients .clinica-stats {
    display: flex;
    gap: 20px;
    padding: 0 25px 30px;
    position: relative;
    z-index: 2;
}

.clinica-foreign-patients .stat-item {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    padding: 15px;
    flex: 1;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.2s ease;
    position: relative;
}

.clinica-foreign-patients .stat-item:hover {
    background: rgba(255, 255, 255, 0.15);
}

.clinica-foreign-patients .stat-number {
    color: white;
    font-size: 28px;
    font-weight: 600;
    display: block;
    margin-bottom: 5px;
}

.clinica-foreign-patients .stat-label {
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.clinica-foreign-patients .clinica-patients-table {
    background: white;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    border: 1px solid #e1e5e9;
}

.clinica-foreign-patients .clinica-patients-table thead th {
    background: #34495e;
    color: white;
    font-weight: 600;
    border-bottom: 2px solid #2c3e50;
    padding: 15px 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 12px;
}

.clinica-foreign-patients .clinica-patients-table tbody tr:hover {
    background: #f8f9fa;
}

.clinica-foreign-patients .clinica-patients-table tbody td {
    padding: 18px 15px;
    vertical-align: middle;
}

.clinica-foreign-patients .clinica-action-btn {
    background: #3498db;
    border: 1px solid #2980b9;
    color: white;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.2s ease;
    text-transform: none;
    letter-spacing: normal;
    position: relative;
    margin-right: 5px;
    text-decoration: none;
}

.clinica-foreign-patients .clinica-action-btn:hover {
    background: #2980b9;
    border-color: #1f5f8b;
    color: white;
    text-decoration: none;
}

.clinica-foreign-patients .clinica-filters-container {
    background: white;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
    position: relative;
}

.clinica-foreign-patients .clinica-filters-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #e67e22;
}

.clinica-foreign-patients .clinica-filters-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.clinica-foreign-patients .clinica-filter-group {
    display: flex;
    flex-direction: column;
}

.clinica-foreign-patients .clinica-filters-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.clinica-foreign-patients .clinica-filters-form input,
.clinica-foreign-patients .clinica-filters-form select {
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 8px 12px;
    transition: all 0.2s ease;
    font-size: 14px;
    background: white;
}

.clinica-foreign-patients .clinica-filters-form input:focus,
.clinica-foreign-patients .clinica-filters-form select:focus {
    border-color: #e67e22;
    box-shadow: 0 0 0 2px rgba(230, 126, 34, 0.2);
    background: white;
}

.clinica-foreign-patients .clinica-filters-form label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    display: block;
    text-transform: none;
    letter-spacing: normal;
    font-size: 13px;
}

.clinica-cnp-type {
    color: #e67e22;
    font-weight: 600;
}

.clinica-no-email,
.clinica-no-phone {
    color: #999;
    font-style: italic;
}

.clinica-action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .clinica-foreign-patients .clinica-patients-header {
        padding: 15px;
    }
    
    .clinica-foreign-patients .clinica-patients-header h1 {
        font-size: 20px;
    }
    
    .clinica-foreign-patients .clinica-stats {
        flex-direction: column;
    }
    
    .clinica-foreign-patients .stat-item {
        margin: 5px 0;
        padding: 10px;
    }
    
    .clinica-foreign-patients .clinica-patients-table {
        font-size: 12px;
    }
    
    .clinica-foreign-patients .clinica-patients-table th,
    .clinica-foreign-patients .clinica-patients-table td {
        padding: 8px 6px;
    }
    
    .clinica-foreign-patients .clinica-filters-container {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .clinica-foreign-patients .clinica-patients-header h1 {
        font-size: 18px;
    }
    
    .clinica-foreign-patients .stat-number {
        font-size: 20px;
    }
    
    .clinica-foreign-patients .clinica-patients-table {
        font-size: 11px;
    }
    
    .clinica-foreign-patients .clinica-patients-table th,
    .clinica-foreign-patients .clinica-patients-table td {
        padding: 6px 4px;
    }
}
</style> 