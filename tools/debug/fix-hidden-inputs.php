<?php
/**
 * Script pentru repararea hidden inputs pentru working_hours
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🔧 Repararea hidden inputs pentru working_hours...\n\n";

// Calea către fișierul settings.php
$settings_file = __DIR__ . '/../../admin/views/settings.php';

if (!file_exists($settings_file)) {
    echo "❌ Fișierul settings.php nu a fost găsit!\n";
    exit(1);
}

// Citește conținutul fișierului
$content = file_get_contents($settings_file);

// Adaugă hidden inputs pentru working_hours înainte de tabel
echo "📝 Adăugarea hidden inputs pentru working_hours...\n";

$hidden_inputs = '
                    <!-- Hidden inputs pentru working_hours -->
                    <div style="display: none;">
                        <?php foreach ($days as $day_key): 
                            $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
                        ?>
                        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][start]" value="<?php echo esc_attr(!empty($day_hours[\'start\']) ? $day_hours[\'start\'] : \'\'); ?>">
                        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][end]" value="<?php echo esc_attr(!empty($day_hours[\'end\']) ? $day_hours[\'end\'] : \'\'); ?>">
                        <input type="hidden" name="working_hours[<?php echo $day_key; ?>][active]" value="<?php echo $day_hours[\'active\'] ? \'1\' : \'0\'; ?>">
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="schedule-table-container">';

// Găsește locul unde începe tabelul și adaugă hidden inputs
$table_start = '<div class="schedule-table-container">';
if (strpos($content, $table_start) !== false) {
    $content = str_replace($table_start, $hidden_inputs, $content);
    echo "✅ Hidden inputs adăugate cu succes!\n";
} else {
    echo "❌ Nu s-a găsit locul pentru adăugarea hidden inputs!\n";
}

// Adaugă JavaScript pentru a sincroniza valorile cu hidden inputs
echo "📝 Adăugarea JavaScript pentru sincronizare...\n";

$sync_js = '
    // Sincronizează valorile cu hidden inputs
    function syncHiddenInputs() {
        $(\'.time-cell input[type="time"]\').each(function() {
            var input = $(this);
            var day = input.closest(\'.time-cell\').data(\'day\');
            var type = input.attr(\'name\').includes(\'start\') ? \'start\' : \'end\';
            var value = input.val();
            
            // Actualizează hidden input
            $(\'input[name="working_hours[\' + day + \'][\' + type + \']"]\').val(value);
        });
        
        // Sincronizează status-ul
        $(\'.status-cell input[type="checkbox"]\').each(function() {
            var checkbox = $(this);
            var day = checkbox.closest(\'.status-cell\').data(\'day\');
            var isActive = checkbox.is(\':checked\');
            
            $(\'input[name="working_hours[\' + day + \'][active]"]\').val(isActive ? \'1\' : \'0\');
        });
    }
    
    // Sincronizează la fiecare schimbare
    $(document).on(\'change blur\', \'.time-cell input[type="time"], .status-cell input[type="checkbox"]\', function() {
        syncHiddenInputs();
    });
    
    // Sincronizează la trimiterea formularului
    $(\'.clinica-settings-form\').on(\'submit\', function() {
        syncHiddenInputs();
    });
    
    // Sincronizează la încărcarea paginii
    $(document).ready(function() {
        syncHiddenInputs();
    });
';

// Găsește locul unde se termină JavaScript-ul și adaugă codul de sincronizare
$js_end = '    });
});';
if (strpos($content, $js_end) !== false) {
    $content = str_replace($js_end, $sync_js . "\n" . $js_end, $content);
    echo "✅ JavaScript pentru sincronizare adăugat cu succes!\n";
} else {
    echo "❌ Nu s-a găsit locul pentru adăugarea JavaScript!\n";
}

// Scrie conținutul înapoi
if (file_put_contents($settings_file, $content)) {
    echo "✅ Toate reparațiile au fost aplicate cu succes!\n";
} else {
    echo "❌ Eroare la scrierea fișierului!\n";
    exit(1);
}

echo "\n🎯 Repararea completă! Verifică pagina de setări.\n";
?> 