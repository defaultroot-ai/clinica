<?php
/**
 * Test pentru sincronizarea input-urilor working_hours
 * 
 * Acest fișier testează dacă input-urile hidden sunt sincronizate corect
 * cu input-urile visible pentru orele de lucru.
 */

// Include WordPress
require_once('../../../wp-load.php');

// Verifică dacă utilizatorul este admin
if (!current_user_can('manage_options')) {
    wp_die('Acces interzis');
}

// Procesează formularul dacă este trimis
if ($_POST) {
    echo '<h2>Date POST primite:</h2>';
    echo '<pre>' . print_r($_POST, true) . '</pre>';
    
    if (isset($_POST['working_hours'])) {
        echo '<h3>Working Hours Data:</h3>';
        echo '<pre>' . print_r($_POST['working_hours'], true) . '</pre>';
        
        // Testează procesarea datelor
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
        $processed_data = array();
        
        foreach ($days as $day) {
            $start_time = isset($_POST['working_hours'][$day]['start']) ? sanitize_text_field($_POST['working_hours'][$day]['start']) : '';
            $end_time = isset($_POST['working_hours'][$day]['end']) ? sanitize_text_field($_POST['working_hours'][$day]['end']) : '';
            $is_active = isset($_POST['working_hours'][$day]['active']) && $_POST['working_hours'][$day]['active'] === '1';
            
            $processed_data[$day] = array(
                'start' => $start_time,
                'end' => $end_time,
                'active' => $is_active
            );
            
            echo "<p><strong>$day:</strong> Start: '$start_time', End: '$end_time', Active: " . ($is_active ? 'true' : 'false') . "</p>";
        }
        
        echo '<h3>Date procesate:</h3>';
        echo '<pre>' . print_r($processed_data, true) . '</pre>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Sincronizare Working Hours</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-form { max-width: 800px; margin: 0 auto; }
        .schedule-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .schedule-table th, .schedule-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .schedule-table th { background: #f5f5f5; }
        .time-input { width: 80px; text-align: center; }
        .status-cell { cursor: pointer; }
        .status-active { background: #d4edda; }
        .status-inactive { background: #f8d7da; }
        .debug-info { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .hidden-inputs { background: #e9ecef; padding: 10px; margin: 10px 0; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="test-form">
        <h1>Test Sincronizare Working Hours</h1>
        
        <div class="debug-info">
            <h3>Instrucțiuni:</h3>
            <ol>
                <li>Modifică orele în tabelul de mai jos</li>
                <li>Verifică că input-urile hidden sunt actualizate corect</li>
                <li>Trimite formularul pentru a vedea datele procesate</li>
            </ol>
        </div>
        
        <form method="post">
            <?php
            $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
            $day_names = array(
                'monday' => 'Luni',
                'tuesday' => 'Marți', 
                'wednesday' => 'Miercuri',
                'thursday' => 'Joi',
                'friday' => 'Vineri'
            );
            ?>
            
            <!-- Hidden inputs pentru working_hours -->
            <div class="hidden-inputs">
                <h4>Hidden Inputs:</h4>
                <?php foreach ($days as $day_key): ?>
                <div>
                    <input type="hidden" name="working_hours[<?php echo $day_key; ?>][start]" value="" id="hidden_<?php echo $day_key; ?>_start">
                    <input type="hidden" name="working_hours[<?php echo $day_key; ?>][end]" value="" id="hidden_<?php echo $day_key; ?>_end">
                    <input type="hidden" name="working_hours[<?php echo $day_key; ?>][active]" value="0" id="hidden_<?php echo $day_key; ?>_active">
                    <?php echo $day_names[$day_key]; ?>: Start=<span id="debug_<?php echo $day_key; ?>_start">-</span>, 
                    End=<span id="debug_<?php echo $day_key; ?>_end">-</span>, 
                    Active=<span id="debug_<?php echo $day_key; ?>_active">-</span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Ziua</th>
                        <th>Status</th>
                        <th>Început</th>
                        <th>Sfârșit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day_key): ?>
                    <tr>
                        <td><?php echo $day_names[$day_key]; ?></td>
                        <td class="status-cell" data-day="<?php echo $day_key; ?>">
                            <div class="status-indicator status-inactive" id="status_<?php echo $day_key; ?>">
                                Inactiv
                            </div>
                            <input type="checkbox" name="working_hours[<?php echo $day_key; ?>][active]" value="1" style="display: none;">
                        </td>
                        <td>
                            <input type="time" class="time-input" name="working_hours[<?php echo $day_key; ?>][start]" 
                                   data-day="<?php echo $day_key; ?>" data-type="start" disabled>
                        </td>
                        <td>
                            <input type="time" class="time-input" name="working_hours[<?php echo $day_key; ?>][end]" 
                                   data-day="<?php echo $day_key; ?>" data-type="end" disabled>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <button type="submit">Trimite Datele</button>
        </form>
        
        <div class="debug-info">
            <h3>Debug Console:</h3>
            <div id="debug-console" style="background: #000; color: #0f0; padding: 10px; height: 200px; overflow-y: scroll; font-family: monospace;"></div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        jQuery(document).ready(function($) {
            var debugConsole = $('#debug-console');
            
            function log(message) {
                var timestamp = new Date().toLocaleTimeString();
                debugConsole.append('[' + timestamp + '] ' + message + '\n');
                debugConsole.scrollTop(debugConsole[0].scrollHeight);
                console.log(message);
            }
            
            log('Test page loaded');
            
            // Funcție de sincronizare
            function syncHiddenInputs() {
                $('.time-input').each(function() {
                    var input = $(this);
                    var day = input.data('day');
                    var type = input.data('type');
                    var value = input.val();
                    
                    log('Syncing input for day: ' + day + ' type: ' + type + ' value: ' + value);
                    
                    var hiddenInput = $('#hidden_' + day + '_' + type);
                    if (value && value.trim() !== '') {
                        hiddenInput.val(value);
                        log('Updated hidden input: ' + hiddenInput[0] + ' with value: ' + value);
                    } else {
                        hiddenInput.val('');
                        log('Cleared hidden input for day: ' + day + ' type: ' + type);
                    }
                    
                    // Update debug display
                    $('#debug_' + day + '_' + type).text(value || '-');
                });
                
                // Sync status
                $('.status-cell input[type="checkbox"]').each(function() {
                    var checkbox = $(this);
                    var day = checkbox.closest('.status-cell').data('day');
                    var isActive = checkbox.is(':checked');
                    
                    var hiddenInput = $('#hidden_' + day + '_active');
                    hiddenInput.val(isActive ? '1' : '0');
                    log('Updated status hidden input for day: ' + day + ' active: ' + isActive);
                    
                    // Update debug display
                    $('#debug_' + day + '_active').text(isActive ? 'true' : 'false');
                });
            }
            
            // Status toggle
            $('.status-cell').on('click', function() {
                var cell = $(this);
                var day = cell.data('day');
                var checkbox = cell.find('input[type="checkbox"]');
                var indicator = cell.find('.status-indicator');
                
                checkbox.prop('checked', !checkbox.is(':checked'));
                
                if (checkbox.is(':checked')) {
                    indicator.removeClass('status-inactive').addClass('status-active').text('Activ');
                    $('.time-input[data-day="' + day + '"]').prop('disabled', false);
                } else {
                    indicator.removeClass('status-active').addClass('status-inactive').text('Inactiv');
                    $('.time-input[data-day="' + day + '"]').prop('disabled', true).val('');
                }
                
                syncHiddenInputs();
                log('Status toggled for day: ' + day + ' active: ' + checkbox.is(':checked'));
            });
            
            // Time input change
            $('.time-input').on('change blur', function() {
                var input = $(this);
                var day = input.data('day');
                var type = input.data('type');
                var value = input.val();
                
                log('Time changed for day: ' + day + ' type: ' + type + ' value: ' + value);
                
                // Update hidden input
                var hiddenInput = $('#hidden_' + day + '_' + type);
                if (value && value.trim() !== '') {
                    hiddenInput.val(value);
                    log('Updated hidden input: ' + hiddenInput[0] + ' with value: ' + value);
                } else {
                    hiddenInput.val('');
                    log('Cleared hidden input for day: ' + day + ' type: ' + type);
                }
                
                // Update debug display
                $('#debug_' + day + '_' + type).text(value || '-');
            });
            
            // Sync on page load
            syncHiddenInputs();
            log('Initial sync completed');
            
            // Form submit debug
            $('form').on('submit', function() {
                log('=== FORM SUBMIT DEBUG ===');
                
                // Sync before submit
                syncHiddenInputs();
                
                // Log all hidden inputs
                $('input[name^="working_hours"]').each(function() {
                    var input = $(this);
                    var name = input.attr('name');
                    var value = input.val();
                    log('Hidden input: ' + name + ' = ' + value);
                });
                
                log('=== END FORM SUBMIT DEBUG ===');
            });
        });
    </script>
</body>
</html> 