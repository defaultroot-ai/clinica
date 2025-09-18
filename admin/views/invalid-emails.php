<?php
/**
 * Pagina pentru E-mailuri Neactualizate
 * Afișează pacienții cu e-mailuri care conțin: temp, demo, fake, .sx
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

// Obține pacienții cu e-mailuri neactualizate
global $wpdb;

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$email_type_filter = isset($_GET['email_type']) ? sanitize_text_field($_GET['email_type']) : '';

// Procesare actualizare e-mail - TREBUIE SĂ FIE ÎNAINTE DE QUERY!
if (isset($_POST['action']) && $_POST['action'] === 'update_email' && isset($_POST['patient_id']) && isset($_POST['new_email'])) {
    $patient_id = intval($_POST['patient_id']);
    $new_email = sanitize_email($_POST['new_email']);
    
    // Validare e-mail
    $validation = validate_email($new_email);
    
    if ($validation === "OK") {
        // Obține user_id pentru pacient
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}clinica_patients WHERE id = %d",
            $patient_id
        ));
        
        if ($user_id) {
            // Actualizează e-mailul în wp_users
            $result = $wpdb->update(
                $wpdb->users,
                array('user_email' => $new_email),
                array('ID' => $user_id),
                array('%s'),
                array('%d')
            );
            
            if ($result !== false) {
                $success_message = "E-mailul a fost actualizat cu succes!";
            } else {
                $error_message = "Eroare la actualizarea e-mailului.";
            }
        } else {
            $error_message = "Pacientul nu a fost găsit.";
        }
    } else {
        $error_message = $validation;
    }
}

$where_conditions = array();

// Filtru pentru e-mailuri neactualizate - SIMPLIFICAT
$where_conditions[] = "(
    LOWER(u.user_email) LIKE '%demo%' OR 
    LOWER(u.user_email) LIKE '%fake%' OR 
    LOWER(u.user_email) LIKE '%temp%' OR 
	LOWER(u.user_email) LIKE '%gamil%' OR 
    LOWER(u.user_email) LIKE '%.sx'
)";

if (!empty($search)) {
    $where_conditions[] = $wpdb->prepare(
        "(um1.meta_value LIKE %s OR um2.meta_value LIKE %s OR p.cnp LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s)",
        '%' . $search . '%',
        '%' . $search . '%',
        '%' . $search . '%',
        '%' . $search . '%',
        '%' . $search . '%'
    );
}

// Filtru pentru tipul de e-mail - SIMPLIFICAT
if (!empty($email_type_filter)) {
    switch ($email_type_filter) {
        case 'temp':
            $where_conditions[] = "LOWER(u.user_email) LIKE '%temp%'";
            break;
        case 'demo':
            $where_conditions[] = "LOWER(u.user_email) LIKE '%demo%'";
            break;
        case 'fake':
            $where_conditions[] = "LOWER(u.user_email) LIKE '%fake%'";
            break;
		 case 'gamil':
            $where_conditions[] = "LOWER(u.user_email) LIKE '%gamil%'";
            break;	
        case 'sx':
            $where_conditions[] = "LOWER(u.user_email) LIKE '%.sx'";
            break;
    }
}



$where_clause = implode(' AND ', $where_conditions);

// Paginare
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Query pentru total
$count_query = "
    SELECT COUNT(*) as total
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE {$where_clause}
";

$total_result = $wpdb->get_row($count_query);
$total_invalid_email_patients = $total_result->total;
$total_pages = ceil($total_invalid_email_patients / $per_page);

// QUERY cu JOIN-uri pentru nume și prenume + PAGINARE
$query = "
    SELECT p.id, p.cnp, u.user_email, u.user_login,
           um1.meta_value as first_name, um2.meta_value as last_name,
           '' as status
    FROM {$wpdb->prefix}clinica_patients p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
    WHERE {$where_clause}
    ORDER BY COALESCE(um2.meta_value, '') ASC, COALESCE(um1.meta_value, '') ASC
    LIMIT {$per_page} OFFSET {$offset}
";

$invalid_email_patients = $wpdb->get_results($query);

// Debug: Afișează informații de debug (doar pentru admin)
if (current_user_can('manage_options') && isset($_GET['debug'])) {
    echo '<div style="background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffeaa7;">';
    echo '<strong>Debug Info:</strong><br>';
    echo '<strong>Total rezultate:</strong> ' . $total_invalid_email_patients . '<br>';
    echo '<strong>Where clause:</strong> ' . esc_html($where_clause) . '<br>';
    echo '</div>';
}



// Funcție pentru a determina tipul de e-mail
function get_email_type($email) {
    $email = strtolower($email);
    if (strpos($email, 'temp') !== false) {
        return 'temp';
    } elseif (strpos($email, 'demo') !== false) {
        return 'demo';
    } elseif (strpos($email, 'fake') !== false) {
        return 'fake';
    } elseif (strpos($email, '.sx') !== false) {
        return 'sx';
		 } elseif (strpos($gamil, '.sx') !== false) {
        return 'gamil';
    } elseif (strpos($email, '@test') !== false || strpos($email, '@example') !== false) {
        return 'test';
    } else {
        return 'other';
    }
}

// Funcție pentru a obține culoarea pentru tipul de e-mail
function get_email_type_color($type) {
    switch ($type) {
        case 'temp':
            return '#f39c12';
        case 'demo':
            return '#e74c3c';
        case 'fake':
            return '#9b59b6';
        case 'sx':
            return '#3498db';
		 case 'gamil':
            return '#8f2182';	
        case 'test':
            return '#95a5a6';
        default:
            return '#34495e';
    }
}

// Funcție pentru validarea e-mailului
function validate_email($email) {
    // Format valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "E-mail invalid";
    }
    
    // Nu este gol
    if (empty($email)) {
        return "E-mailul nu poate fi gol";
    }
    
    // Nu conține temp/demo/fake
    if (stripos($email, 'temp') !== false || 
        stripos($email, 'demo') !== false || 
		stripos($email, 'gamil') !== false ||
        stripos($email, 'fake') !== false) {
        return "E-mailul nu poate conține temp/demo/gamil/fake";
    }
    
    // Nu este deja folosit
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_email = %s", 
        $email
    ));
    if ($exists > 0) {
        return "E-mailul este deja folosit";
    }
    
    return "OK";
}


?>

<div class="wrap clinica-invalid-emails">
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    

    
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="clinica-patients-header">
        <div class="clinica-header-main">
            <div class="clinica-header-left">
                <h1>
                    <span class="dashicons dashicons-email-alt"></span>
                    E-mailuri Neactualizate
                </h1>
                <p>Pacienți cu e-mailuri temporare sau neactualizate (temp, demo, fake, , gamil, .sx)</p>
            </div>
            <div class="clinica-header-right">
                <a href="<?php echo admin_url('admin.php?page=clinica'); ?>" class="button">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Înapoi la Dashboard
                </a>
            </div>
        </div>
        

    </div>

    <!-- Filtre -->
    <div class="clinica-filters-container">
        <form method="GET" class="clinica-filters-form">
            <input type="hidden" name="page" value="clinica-invalid-emails">
            
            <div class="clinica-filters-row">
                <div class="clinica-filter-group">
                    <label for="search">Căutare:</label>
                    <input type="text" id="search" name="search" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Nume, prenume, CNP sau e-mail...">
                </div>
                
                <div class="clinica-filter-group">
                    <label for="email_type">Tip E-mail:</label>
                    <select id="email_type" name="email_type">
                        <option value="">Toate</option>
                        <option value="temp" <?php selected($email_type_filter, 'temp'); ?>>Temp</option>
                        <option value="demo" <?php selected($email_type_filter, 'demo'); ?>>Demo</option>
                        <option value="fake" <?php selected($email_type_filter, 'fake'); ?>>Fake</option>
						 <option value="gamil" <?php selected($email_type_filter, 'fake'); ?>>Gamil</option>
                        <option value="sx" <?php selected($email_type_filter, 'sx'); ?>>.sx</option>
                    </select>
                </div>
                
                
            </div>
            
            <div class="clinica-filters-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    Caută
                </button>
                <a href="<?php echo admin_url('admin.php?page=clinica-invalid-emails'); ?>" class="button">
                    <span class="dashicons dashicons-update"></span>
                    Resetează
                </a>
            </div>
        </form>
    </div>

    <!-- Tabelul de pacienți -->
    <div class="clinica-patients-table">
        <!-- Total și informații -->
        <div class="clinica-table-info">
            <div class="clinica-total-info">
                <span class="clinica-total-count">
                    <strong><?php echo number_format($total_invalid_email_patients); ?></strong> pacienți cu e-mailuri neactualizate
                </span>
                <?php if ($total_pages > 1): ?>
                    <span class="clinica-page-info">
                        Pagina <strong><?php echo $current_page; ?></strong> din <strong><?php echo $total_pages; ?></strong>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (empty($invalid_email_patients)): ?>
            <div class="clinica-empty-state">
                <div class="clinica-empty-content">
                    <span class="dashicons dashicons-email-alt"></span>
                    <h3>Nu există e-mailuri neactualizate</h3>
                    <p>Nu au fost găsiți pacienți cu e-mailuri temporare sau neactualizate.</p>
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
                         <th>Tip E-mail</th>
                         <th>Telefon</th>
                         <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Pre-calculează toate valorile pentru performanță maximă
                    $patients_data = array();
                    if (!empty($invalid_email_patients)) {
                        foreach ($invalid_email_patients as $patient) {
                            $email_type = get_email_type($patient->user_email);
                            $birth_date = get_birth_date_from_cnp($patient->cnp);
                            $patients_data[] = array(
                                'patient' => $patient,
                                'email_type' => $email_type,
                                'email_color' => get_email_type_color($email_type),
                                'gender' => get_gender_from_cnp($patient->cnp),
                                'birth_date' => $birth_date,
                                'age' => calculate_age($birth_date),
                                'clean_name' => preg_replace('/[^\p{L}\s]/u', '', trim($patient->last_name . ' ' . $patient->first_name))
                            );
                        }
                    }
                    ?>
                    
                    <?php foreach ($patients_data as $data): ?>
                        <?php 
                        $patient = $data['patient'];
                        $email_type = $data['email_type'];
                        $email_color = $data['email_color'];
                        $calculated_gender = $data['gender'];
                        $birth_date = $data['birth_date'];
                        $age = $data['age'];
                        $clean_name = $data['clean_name'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($clean_name); ?></strong>
                            </td>
                            <td>
                                <code><?php echo esc_html(!empty($patient->cnp) ? $patient->cnp : $patient->user_login); ?></code>
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
                                <a href="mailto:<?php echo esc_attr($patient->user_email); ?>" class="clinica-email-link">
                                    <?php echo esc_html($patient->user_email); ?>
                                </a>
                            </td>
                            <td>
                                <span class="clinica-email-type-badge" style="background-color: <?php echo $email_color; ?>">
                                    <?php echo strtoupper($email_type); ?>
                                </span>
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
                                     <button type="button" class="clinica-action-btn clinica-update-email-btn" 
                                             onclick="updateEmailPrompt(<?php echo $patient->id; ?>, '<?php echo esc_js($patient->user_email); ?>')" 
                                             title="Actualizează E-mail">
                                         <span class="dashicons dashicons-email-alt"></span>
                                         Actualizează E-mail
                                     </button>
                                 </div>
                             </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginare -->
            <?php if ($total_pages > 1): ?>
                <div class="clinica-pagination">
                    <div class="clinica-pagination-info">
                        Afișez <strong><?php echo count($invalid_email_patients); ?></strong> din <strong><?php echo number_format($total_invalid_email_patients); ?></strong> pacienți
                    </div>
                    <div class="clinica-pagination-links">
                        <?php
                        // Construiește URL-ul pentru paginare păstrând filtrele
                        $pagination_url = add_query_arg(array(
                            'page' => 'clinica-invalid-emails',
                            'search' => $search,
                            'email_type' => $email_type_filter
                        ), admin_url('admin.php'));
                        
                        // Pagina anterioară
                        if ($current_page > 1): ?>
                            <a href="<?php echo add_query_arg('paged', $current_page - 1, $pagination_url); ?>" class="clinica-page-link">
                                <span class="dashicons dashicons-arrow-left-alt"></span>
                                Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        // Afișează numerele paginilor
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1): ?>
                            <a href="<?php echo add_query_arg('paged', 1, $pagination_url); ?>" class="clinica-page-link">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="clinica-page-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="clinica-page-link clinica-page-current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo add_query_arg('paged', $i, $pagination_url); ?>" class="clinica-page-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="clinica-page-dots">...</span>
                            <?php endif; ?>
                            <a href="<?php echo add_query_arg('paged', $total_pages, $pagination_url); ?>" class="clinica-page-link"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                        
                        <?php // Pagina următoare
                        if ($current_page < $total_pages): ?>
                            <a href="<?php echo add_query_arg('paged', $current_page + 1, $pagination_url); ?>" class="clinica-page-link">
                                Următor
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal pentru actualizarea e-mailului -->
<div id="updateEmailModal" class="clinica-modal" style="display: none;">
    <div class="clinica-modal-content">
        <div class="clinica-modal-header">
            <h3>Actualizează E-mailul</h3>
            <span class="clinica-modal-close" onclick="closeEmailModal()">&times;</span>
        </div>
        <div class="clinica-modal-body">
            <form id="updateEmailForm" method="POST">
                <input type="hidden" name="action" value="update_email">
                <input type="hidden" id="patientId" name="patient_id">
                <div class="clinica-form-group">
                    <label for="newEmail">E-mail nou:</label>
                    <input type="email" id="newEmail" name="new_email" required 
                           placeholder="exemplu@email.com">
                    <small class="clinica-form-help">
                        E-mailul nu poate conține temp, demo, gamil sau fake
                    </small>
                </div>
                <div class="clinica-form-actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-yes"></span>
                        Actualizează E-mail
                    </button>
                    <button type="button" class="button" onclick="closeEmailModal()">
                        <span class="dashicons dashicons-no"></span>
                        Anulează
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funcții simple pentru modal - fără event listeners duplicați
let modalElements = null;

function getModalElements() {
    if (!modalElements) {
        modalElements = {
            patientId: document.getElementById('patientId'),
            newEmail: document.getElementById('newEmail'),
            modal: document.getElementById('updateEmailModal')
        };
    }
    return modalElements;
}

function updateEmailPrompt(patientId, currentEmail) {
    const elements = getModalElements();
    if (elements.patientId && elements.newEmail && elements.modal) {
        elements.patientId.value = patientId;
        elements.newEmail.value = '';
        elements.modal.style.display = 'block';
    }
}

function closeEmailModal() {
    const elements = getModalElements();
    if (elements.modal) {
        elements.modal.style.display = 'none';
    }
}

// Event listener simplu - o singură dată
document.addEventListener('DOMContentLoaded', function() {
    // Click outside modal
    window.addEventListener('click', function(event) {
        const elements = getModalElements();
        if (elements.modal && event.target === elements.modal) {
            closeEmailModal();
        }
    });
});
</script>

<style>
.clinica-invalid-emails {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px;
    width: 100%;
    box-sizing: border-box;
}

.clinica-invalid-emails .clinica-patients-header {
    background: #2c3e50;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    position: relative;
    padding: 0;
    min-height: 120px; /* Redus pentru a fi mai compact */
    width: 100%;
    box-sizing: border-box;
}

.clinica-invalid-emails .clinica-patients-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #e74c3c;
}

.clinica-invalid-emails .clinica-patients-header h1 {
    color: white;
    font-size: 22px;
    font-weight: 600;
    margin: 0;
    padding: 20px 25px 10px;
    position: relative;
    z-index: 2;
    min-height: 50px; /* Redus pentru a fi mai compact */
    box-sizing: border-box;
}

.clinica-invalid-emails .clinica-header-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0;
}

.clinica-invalid-emails .clinica-header-left {
    flex: 1;
    min-height: 60px; /* Redus pentru a fi mai compact */
    box-sizing: border-box;
}

.clinica-invalid-emails .clinica-header-right {
    padding: 20px 25px 15px;
    display: flex;
    align-items: center;
    min-height: 60px; /* Redus pentru a fi mai compact */
    box-sizing: border-box;
}

.clinica-invalid-emails .clinica-header-right .button {
    background: #e74c3c;
    border: 1px solid #c0392b;
    color: white;
    padding: 10px 16px;
    border-radius: 4px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.clinica-invalid-emails .clinica-header-right .button:hover {
    background: #c0392b;
    border-color: #a93226;
    color: white;
    text-decoration: none;
}



.clinica-invalid-emails .clinica-patients-table {
    background: white;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    border: 1px solid #e1e5e9;
    min-height: 600px; /* Previne CLS - mărit dramatic */
    width: 100%;
    box-sizing: border-box;
}

/* Total și informații */
.clinica-table-info {
    background: #f8f9fa;
    border-bottom: 1px solid #e1e5e9;
    padding: 15px 20px;
}

.clinica-total-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.clinica-total-count {
    color: #2c3e50;
    font-size: 14px;
}

.clinica-page-info {
    color: #7f8c8d;
    font-size: 13px;
}

/* Paginare */
.clinica-pagination {
    background: #f8f9fa;
    border-top: 1px solid #e1e5e9;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.clinica-pagination-info {
    color: #7f8c8d;
    font-size: 13px;
}

.clinica-pagination-links {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.clinica-page-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    color: #374151;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s ease;
    background: white;
}

.clinica-page-link:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
    color: #1f2937;
    text-decoration: none;
}

.clinica-page-current {
    background: #e74c3c !important;
    border-color: #c0392b !important;
    color: white !important;
}

.clinica-page-dots {
    color: #9ca3af;
    padding: 8px 4px;
    font-size: 13px;
}

.clinica-invalid-emails .clinica-patients-table {
    position: relative;
    max-height: 70vh;
    overflow-y: auto;
}

.clinica-invalid-emails .clinica-patients-table table {
    width: 100%;
    border-collapse: collapse;
}

.clinica-invalid-emails .clinica-patients-table thead {
    position: sticky;
    top: 0;
    z-index: 1000;
}

.clinica-invalid-emails .clinica-patients-table thead th {
    background: #34495e;
    color: white;
    font-weight: 600;
    border-bottom: 2px solid #2c3e50;
    padding: 15px 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 12px;
    min-height: 50px; /* Previne CLS */
    box-sizing: border-box;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.clinica-invalid-emails .clinica-patients-table tbody tr {
    min-height: 60px; /* Previne CLS */
    box-sizing: border-box;
}

.clinica-invalid-emails .clinica-patients-table tbody tr:hover {
    background: #f8f9fa;
}

.clinica-invalid-emails .clinica-patients-table tbody td {
    padding: 18px 15px;
    vertical-align: middle;
    min-height: 60px; /* Previne CLS */
    box-sizing: border-box;
}

.clinica-invalid-emails .clinica-action-btn {
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

.clinica-invalid-emails .clinica-action-btn:hover {
    background: #2980b9;
    border-color: #1f5f8b;
    color: white;
    text-decoration: none;
}

.clinica-invalid-emails .clinica-update-email-btn {
    background: #27ae60;
    border-color: #229954;
}

.clinica-invalid-emails .clinica-update-email-btn:hover {
    background: #229954;
    border-color: #1e8449;
}

.clinica-invalid-emails .clinica-filters-container {
    background: white;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
    position: relative;
    min-height: 150px; /* Previne CLS */
    width: 100%;
    box-sizing: border-box;
}

.clinica-invalid-emails .clinica-filters-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #e74c3c;
}

.clinica-invalid-emails .clinica-filters-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.clinica-invalid-emails .clinica-filter-group {
    display: flex;
    flex-direction: column;
}

.clinica-invalid-emails .clinica-filters-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.clinica-invalid-emails .clinica-filters-form input,
.clinica-invalid-emails .clinica-filters-form select {
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 8px 12px;
    transition: all 0.2s ease;
    font-size: 14px;
    background: white;
}

.clinica-invalid-emails .clinica-filters-form input:focus,
.clinica-invalid-emails .clinica-filters-form select:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
    background: white;
}

.clinica-invalid-emails .clinica-filters-form label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    display: block;
    text-transform: none;
    letter-spacing: normal;
    font-size: 13px;
}

.clinica-email-type-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    color: white;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.clinica-status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    color: white;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.clinica-status-active {
    background-color: #27ae60;
}

.clinica-status-deceased {
    background-color: #e74c3c;
}

.clinica-gender-simple {
    display: inline-block;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    text-align: center;
    line-height: 24px;
    font-weight: bold;
    font-size: 12px;
    color: white;
}

.clinica-gender-m {
    background-color: #3498db;
}

.clinica-gender-f {
    background-color: #e91e63;
}

.clinica-no-gender {
    color: #999;
    font-style: italic;
}

.clinica-email-link {
    color: #3498db;
    text-decoration: none;
}

.clinica-email-link:hover {
    text-decoration: underline;
}

.clinica-no-phone {
    color: #999;
    font-style: italic;
}

.clinica-action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

/* Modal styles */
.clinica-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.clinica-modal-content {
    background-color: white;
    margin: 15% auto;
    padding: 0;
    border-radius: 8px;
    width: 400px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.clinica-modal-header {
    padding: 20px;
    border-bottom: 1px solid #e1e5e9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.clinica-modal-header h3 {
    margin: 0;
    color: #333;
}

.clinica-modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.clinica-modal-close:hover {
    color: #000;
}

.clinica-modal-body {
    padding: 20px;
}

.clinica-form-group {
    margin-bottom: 20px;
}

.clinica-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.clinica-form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 14px;
}

.clinica-form-group input:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    outline: none;
}

.clinica-form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.clinica-form-help {
    color: #6b7280;
    font-size: 12px;
    margin-top: 4px;
    display: block;
}

@media (max-width: 768px) {
    .clinica-invalid-emails .clinica-patients-header {
        padding: 15px;
    }
    
    .clinica-invalid-emails .clinica-patients-header h1 {
        font-size: 20px;
    }
    
    .clinica-total-info {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .clinica-pagination {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .clinica-pagination-links {
        justify-content: center;
        width: 100%;
    }
    
    .clinica-invalid-emails .clinica-patients-table {
        font-size: 12px;
    }
    
    .clinica-invalid-emails .clinica-patients-table th,
    .clinica-invalid-emails .clinica-patients-table td {
        padding: 8px 6px;
    }
    
    .clinica-invalid-emails .clinica-filters-container {
        padding: 15px;
    }
    
    .clinica-modal-content {
        width: 90%;
        margin: 10% auto;
    }
}

@media (max-width: 480px) {
    .clinica-invalid-emails .clinica-patients-header h1 {
        font-size: 18px;
    }
    

    
    .clinica-invalid-emails .clinica-patients-table {
        font-size: 11px;
    }
    
    .clinica-invalid-emails .clinica-patients-table th,
    .clinica-invalid-emails .clinica-patients-table td {
        padding: 6px 4px;
    }
}
</style> 