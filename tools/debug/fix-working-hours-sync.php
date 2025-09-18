<?php
/**
 * Script pentru repararea sincronizării working_hours
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică permisiunile
if (!current_user_can('manage_options')) {
    wp_die('Nu aveți permisiunile necesare.');
}

$settings = Clinica_Settings::get_instance();

echo "<h1>🔧 Repararea Sincronizării Working Hours</h1>";

// Testează salvarea directă
echo "<h2>Test 1: Salvarea directă working_hours</h2>";

$test_working_hours = array(
    'monday' => array('start' => '08:00', 'end' => '17:00', 'active' => true),
    'tuesday' => array('start' => '08:30', 'end' => '16:30', 'active' => true),
    'wednesday' => array('start' => '09:00', 'end' => '18:00', 'active' => true),
    'thursday' => array('start' => '08:00', 'end' => '15:00', 'active' => true),
    'friday' => array('start' => '08:00', 'end' => '14:00', 'active' => true),
    'saturday' => array('start' => '', 'end' => '', 'active' => false),
    'sunday' => array('start' => '', 'end' => '', 'active' => false)
);

echo "<p>Salvând working_hours de test...</p>";
$result = $settings->set('working_hours', $test_working_hours);

if ($result) {
    echo "<p style='color: green;'>✅ Salvarea directă a funcționat!</p>";
} else {
    echo "<p style='color: red;'>❌ Salvarea directă a eșuat!</p>";
}

// Testează citirea
echo "<h2>Test 2: Citirea working_hours</h2>";
$saved_working_hours = $settings->get('working_hours');

if (is_array($saved_working_hours)) {
    echo "<p style='color: green;'>✅ working_hours citit cu succes!</p>";
    echo "<pre>" . print_r($saved_working_hours, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Eroare la citirea working_hours!</p>";
    echo "<p>Tip: " . gettype($saved_working_hours) . "</p>";
}

// Testează procesarea POST
echo "<h2>Test 3: Procesarea datelor POST</h2>";

// Simulează datele POST
$_POST['working_hours'] = array(
    'monday' => array('start' => '09:00', 'end' => '18:00', 'active' => '1'),
    'tuesday' => array('start' => '08:00', 'end' => '17:00', 'active' => '1'),
    'wednesday' => array('start' => '10:00', 'end' => '19:00', 'active' => '1'),
    'thursday' => array('start' => '08:30', 'end' => '16:30', 'active' => '1'),
    'friday' => array('start' => '08:00', 'end' => '15:00', 'active' => '1'),
    'saturday' => array('start' => '', 'end' => '', 'active' => '0'),
    'sunday' => array('start' => '', 'end' => '', 'active' => '0')
);

echo "<p>Datele POST simulate:</p>";
echo "<pre>" . print_r($_POST['working_hours'], true) . "</pre>";

// Procesează datele POST
$working_hours = array();
$days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

foreach ($days as $day) {
    $start_time = isset($_POST['working_hours'][$day]['start']) ? sanitize_text_field($_POST['working_hours'][$day]['start']) : '';
    $end_time = isset($_POST['working_hours'][$day]['end']) ? sanitize_text_field($_POST['working_hours'][$day]['end']) : '';
    $is_active = isset($_POST['working_hours'][$day]['active']) && $_POST['working_hours'][$day]['active'] === '1';
    
    $working_hours[$day] = array(
        'start' => $start_time,
        'end' => $end_time,
        'active' => $is_active
    );
}

echo "<p>Datele procesate:</p>";
echo "<pre>" . print_r($working_hours, true) . "</pre>";

// Salvează datele procesate
$result = $settings->set('working_hours', $working_hours);

if ($result) {
    echo "<p style='color: green;'>✅ Salvarea datelor procesate a funcționat!</p>";
} else {
    echo "<p style='color: red;'>❌ Salvarea datelor procesate a eșuat!</p>";
}

// Verifică din nou
$final_working_hours = $settings->get('working_hours');
echo "<p>Datele finale salvate:</p>";
echo "<pre>" . print_r($final_working_hours, true) . "</pre>";

// Testează structura HTML
echo "<h2>Test 4: Structura HTML pentru formular</h2>";
?>

<form method="post" action="">
    <h3>Hidden Inputs:</h3>
    <?php foreach ($days as $day_key): 
        $day_hours = isset($final_working_hours[$day_key]) ? $final_working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
    ?>
    <div>
        <label>Hidden inputs pentru <?php echo $day_key; ?>:</label><br>
        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][start]" value="<?php echo esc_attr(!empty($day_hours['start']) ? $day_hours['start'] : ''); ?>">
        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][end]" value="<?php echo esc_attr(!empty($day_hours['end']) ? $day_hours['end'] : ''); ?>">
        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][active]" value="<?php echo (!empty($day_hours['active']) && $day_hours['active']) ? '1' : '0'; ?>">
        <span>Start: <?php echo esc_html(!empty($day_hours['start']) ? $day_hours['start'] : 'gol'); ?></span> |
        <span>End: <?php echo esc_html(!empty($day_hours['end']) ? $day_hours['end'] : 'gol'); ?></span> |
        <span>Active: <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? 'Da' : 'Nu'; ?></span>
    </div>
    <?php endforeach; ?>
    
    <h3>Visible Inputs:</h3>
    <?php foreach ($days as $day_key): 
        $day_hours = isset($final_working_hours[$day_key]) ? $final_working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
    ?>
    <div style="margin: 10px 0; padding: 10px; border: 1px solid #ccc;">
        <strong><?php echo ucfirst($day_key); ?>:</strong><br>
        <label>
            <input type="checkbox" name="working_hours[<?php echo $day_key; ?>][active]" value="1" <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? 'checked' : ''; ?>>
            Activ
        </label><br>
        <label>
            Start: <input type="time" name="working_hours[<?php echo $day_key; ?>][start]" value="<?php echo esc_attr(!empty($day_hours['start']) ? $day_hours['start'] : ''); ?>" <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? '' : 'disabled'; ?>>
        </label><br>
        <label>
            End: <input type="time" name="working_hours[<?php echo $day_key; ?>][end]" value="<?php echo esc_attr(!empty($day_hours['end']) ? $day_hours['end'] : ''); ?>" <?php echo (!empty($day_hours['active']) && $day_hours['active']) ? '' : 'disabled'; ?>>
        </label>
    </div>
    <?php endforeach; ?>
    
    <button type="submit" name="test_submit" value="1">Testează Submit</button>
</form>

<?php
// Procesează submit-ul de test
if (isset($_POST['test_submit'])) {
    echo "<h2>Test 5: Procesarea submit-ului</h2>";
    
    if (isset($_POST['working_hours'])) {
        echo "<p style='color: green;'>✅ Datele POST au fost primite!</p>";
        echo "<pre>" . print_r($_POST['working_hours'], true) . "</pre>";
        
        // Procesează din nou
        $processed_hours = array();
        foreach ($days as $day) {
            $start_time = isset($_POST['working_hours'][$day]['start']) ? sanitize_text_field($_POST['working_hours'][$day]['start']) : '';
            $end_time = isset($_POST['working_hours'][$day]['end']) ? sanitize_text_field($_POST['working_hours'][$day]['end']) : '';
            $is_active = isset($_POST['working_hours'][$day]['active']) && $_POST['working_hours'][$day]['active'] === '1';
            
            $processed_hours[$day] = array(
                'start' => $start_time,
                'end' => $end_time,
                'active' => $is_active
            );
        }
        
        echo "<p>Datele procesate din submit:</p>";
        echo "<pre>" . print_r($processed_hours, true) . "</pre>";
        
        // Salvează
        $result = $settings->set('working_hours', $processed_hours);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Salvarea din submit a funcționat!</p>";
        } else {
            echo "<p style='color: red;'>❌ Salvarea din submit a eșuat!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Nu s-au primit datele POST!</p>";
    }
}

echo "<h2>🎯 Recomandări</h2>";
echo "<p>1. Verifică dacă JavaScript-ul se încarcă corect în pagina de setări</p>";
echo "<p>2. Verifică dacă hidden inputs sunt sincronizate cu visible inputs</p>";
echo "<p>3. Verifică dacă formularul se trimite cu toate datele necesare</p>";
echo "<p>4. Verifică dacă procesarea PHP funcționează corect</p>";
?> 