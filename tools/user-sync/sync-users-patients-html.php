<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronizare Utilizatori cu Pacienți</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: #0073aa;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #0073aa;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        .user-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            background: #fafafa;
        }
        .user-card.existing {
            border-left: 4px solid #28a745;
        }
        .user-card.to-sync {
            border-left: 4px solid #ffc107;
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .user-name {
            font-weight: bold;
            font-size: 16px;
        }
        .user-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .user-type.existing {
            background: #d4edda;
            color: #155724;
        }
        .user-type.to-sync {
            background: #fff3cd;
            color: #856404;
        }
        .form-group {
            margin: 10px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #0073aa;
            box-shadow: 0 0 5px rgba(0,115,170,0.3);
        }
        .btn {
            background: #0073aa;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #005a87;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .no-users {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .sync-section {
            background: #e9ecef;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Sincronizare Utilizatori cu Lista de Pacienți</h1>
            <p>Gestionare utilizatori WordPress și sincronizare cu tabelul clinica_patients</p>
        </div>

        <?php
        require_once('../../../wp-load.php');
        
        global $wpdb;
        
        // Funcție pentru validarea telefonului
        function validatePhoneWithAllFormats($phone) {
            if (empty($phone)) return true;
            if (strlen($phone) > 20) return false;
            
            if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{9}$/', $phone)) return true;
            if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}\.[0-9]{3}\.[0-9]{3}$/', $phone)) return true;
            if (preg_match('/^(\+40|\+4|0|4|40|0040)[0-9]{3}-[0-9]{3}-[0-9]{3}$/', $phone)) return true;
            if (preg_match('/^\+380[0-9]{9}$/', $phone)) return true;
            if (preg_match('/^\+[0-9]{10,15}$/', $phone)) return true;
            
            return false;
        }
        
        // Procesează actualizările
        if ($_POST['action'] === 'edit_phones') {
            $updated_count = 0;
            $errors = array();
            
            foreach ($_POST['phone_primary'] as $user_id => $phone) {
                $phone = trim($phone);
                $phone2 = trim($_POST['phone_secondary'][$user_id]);
                
                $valid_primary = empty($phone) || validatePhoneWithAllFormats($phone);
                $valid_secondary = empty($phone2) || validatePhoneWithAllFormats($phone2);
                
                if ($valid_primary && $valid_secondary) {
                    update_user_meta($user_id, 'telefon_principal', $phone);
                    update_user_meta($user_id, 'telefon_secundar', $phone2);
                    
                    $existing_patient = $wpdb->get_row($wpdb->prepare("
                        SELECT id FROM {$wpdb->prefix}clinica_patients WHERE user_id = %d
                    ", $user_id));
                    
                    if ($existing_patient) {
                        $wpdb->update(
                            $wpdb->prefix . 'clinica_patients',
                            array(
                                'phone_primary' => $phone,
                                'phone_secondary' => $phone2
                            ),
                            array('user_id' => $user_id),
                            array('%s', '%s'),
                            array('%d')
                        );
                    }
                    
                    $updated_count++;
                } else {
                    $errors[] = "Utilizatorul ID: {$user_id} - Telefoane invalide";
                }
            }
            
            if ($updated_count > 0) {
                echo "<div class='alert alert-success'>✅ Actualizați cu succes {$updated_count} utilizatori!</div>";
            }
            
            if (!empty($errors)) {
                echo "<div class='alert alert-danger'>❌ Erori: " . implode(', ', $errors) . "</div>";
            }
        }
        
        // Obține datele
        $users = $wpdb->get_results("
            SELECT 
                u.ID,
                u.user_login,
                u.user_email,
                u.display_name,
                u.user_registered,
                um_phone.meta_value as phone_primary,
                um_phone2.meta_value as phone_secondary
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um_phone ON u.ID = um_phone.user_id AND um_phone.meta_key = 'telefon_principal'
            LEFT JOIN {$wpdb->usermeta} um_phone2 ON u.ID = um_phone2.user_id AND um_phone2.meta_key = 'telefon_secundar'
            ORDER BY u.ID
        ");
        
        $patients = $wpdb->get_results("SELECT user_id FROM {$wpdb->prefix}clinica_patients");
        $patient_user_ids = array_column($patients, 'user_id');
        
        // Analizează utilizatorii
        $users_to_sync = array();
        $users_with_invalid_phones = array();
        $users_already_patients = array();
        
        foreach ($users as $user) {
            $has_invalid_phone = false;
            $phone_issues = array();
            
            if (!empty($user->phone_primary) && !validatePhoneWithAllFormats($user->phone_primary)) {
                $has_invalid_phone = true;
                $phone_issues[] = 'Principal: ' . $user->phone_primary;
            }
            
            if (!empty($user->phone_secondary) && !validatePhoneWithAllFormats($user->phone_secondary)) {
                $has_invalid_phone = true;
                $phone_issues[] = 'Secundar: ' . $user->phone_secondary;
            }
            
            if (in_array($user->ID, $patient_user_ids)) {
                $users_already_patients[] = $user;
                if ($has_invalid_phone) {
                    $users_with_invalid_phones[] = array(
                        'user' => $user,
                        'issues' => $phone_issues,
                        'type' => 'existing_patient'
                    );
                }
            } else {
                $users_to_sync[] = $user;
                if ($has_invalid_phone) {
                    $users_with_invalid_phones[] = array(
                        'user' => $user,
                        'issues' => $phone_issues,
                        'type' => 'to_sync'
                    );
                }
            }
        }
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($users); ?></div>
                <div>Total Utilizatori WordPress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($patients); ?></div>
                <div>Pacienți în clinica_patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($users_to_sync); ?></div>
                <div>Utilizatori de Sincronizat</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($users_with_invalid_phones); ?></div>
                <div>Cu Telefoane Invalide</div>
            </div>
        </div>
        
        <?php if (!empty($users_with_invalid_phones)): ?>
            <div class="sync-section">
                <h2>📞 Utilizatori cu Telefoane Invalide</h2>
                <p>Editează telefoanele pentru a le face valide:</p>
                
                <form method="post" action="">
                    <input type="hidden" name="action" value="edit_phones">
                    
                    <?php foreach ($users_with_invalid_phones as $index => $data): ?>
                        <?php $user = $data['user']; ?>
                        <?php $type_class = $data['type'] === 'existing_patient' ? 'existing' : 'to-sync'; ?>
                        <?php $type_text = $data['type'] === 'existing_patient' ? 'PACIENT EXISTENT' : 'DE SINCRONIZAT'; ?>
                        
                        <div class="user-card <?php echo $type_class; ?>">
                            <div class="user-header">
                                <div class="user-name"><?php echo htmlspecialchars($user->display_name); ?> (<?php echo htmlspecialchars($user->user_login); ?>)</div>
                                <span class="user-type <?php echo $type_class; ?>"><?php echo $type_text; ?></span>
                            </div>
                            
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user->user_email); ?></p>
                            <p><strong>Probleme:</strong> <?php echo implode(', ', $data['issues']); ?></p>
                            
                            <div class="form-group">
                                <label>Telefon Principal:</label>
                                <input type="text" name="phone_primary[<?php echo $user->ID; ?>]" 
                                       value="<?php echo htmlspecialchars($user->phone_primary); ?>" 
                                       placeholder="ex: 0756248957 sau 0756.248.957">
                            </div>
                            
                            <div class="form-group">
                                <label>Telefon Secundar:</label>
                                <input type="text" name="phone_secondary[<?php echo $user->ID; ?>]" 
                                       value="<?php echo htmlspecialchars($user->phone_secondary); ?>" 
                                       placeholder="ex: 0756248957 sau 0756.248.957">
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" class="btn btn-success">💾 Actualizează Telefoanele</button>
                </form>
            </div>
        <?php else: ?>
            <div class="no-users">
                <h2>🎉 Excelent!</h2>
                <p>Toți utilizatorii au telefoane valide!</p>
            </div>
        <?php endif; ?>
        
        <div class="sync-section">
            <h2>📋 Formate Acceptate pentru Telefoane</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                <div>
                    <h3>🇷🇴 România</h3>
                    <ul>
                        <li>0756248957 (fără separatori)</li>
                        <li>0756.248.957 (cu puncte)</li>
                        <li>0756-248-957 (cu liniuțe)</li>
                        <li>+40756248957 (internațional)</li>
                        <li>+4756248957 (scurt)</li>
                        <li>4756248957 (fără +)</li>
                        <li>40756248957 (cu 40)</li>
                        <li>0040756248957 (cu 0040)</li>
                    </ul>
                </div>
                <div>
                    <h3>🇺🇦 Ucraina</h3>
                    <ul>
                        <li>+380501234567</li>
                    </ul>
                    <h3>🌍 Internațional</h3>
                    <ul>
                        <li>+1234567890</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 