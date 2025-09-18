<?php
/**
 * Test pentru verificarea fix-ului hidden input-urilor
 * 
 * Acest test simulează comportamentul din settings.php pentru a verifica
 * dacă hidden input-urile nu mai sunt șterse la încărcarea paginii
 */

// Simulează datele salvate în baza de date
$working_hours = array(
    'monday' => array('start' => '09:00', 'end' => '17:00', 'active' => true),
    'tuesday' => array('start' => '08:30', 'end' => '16:30', 'active' => true),
    'wednesday' => array('start' => '', 'end' => '', 'active' => false),
    'thursday' => array('start' => '10:00', 'end' => '18:00', 'active' => true),
    'friday' => array('start' => '09:00', 'end' => '15:00', 'active' => true),
    'saturday' => array('start' => '', 'end' => '', 'active' => false),
    'sunday' => array('start' => '', 'end' => '', 'active' => false)
);

$days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Hidden Inputs Fix</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-container { max-width: 800px; margin: 0 auto; }
        .schedule-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .schedule-table th, .schedule-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .time-cell { position: relative; }
        .cell-display { cursor: pointer; }
        .cell-edit { display: none; }
        .cell-edit input { width: 80px; }
        .status-cell { cursor: pointer; }
        .status-indicator { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
        .status-indicator.active { background-color: #4CAF50; }
        .status-indicator.inactive { background-color: #f44336; }
        .active { background-color: #e8f5e8; }
        .inactive { background-color: #ffe8e8; }
        .debug-panel { background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .debug-panel h3 { margin-top: 0; }
        .debug-log { background: #000; color: #0f0; padding: 10px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Test Hidden Inputs Fix</h1>
        <p>Acest test simulează comportamentul din settings.php pentru a verifica dacă hidden input-urile nu mai sunt șterse la încărcarea paginii.</p>
        
        <form id="test-form">
            <!-- Hidden inputs pentru working_hours -->
            <div style="display: none;">
                <?php foreach ($days as $day_key): 
                    $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
                ?>
                <input type="hidden" name="working_hours[<?php echo $day_key; ?>][start]" value="<?php echo htmlspecialchars(!empty($day_hours['start']) ? $day_hours['start'] : ''); ?>">
                <input type="hidden" name="working_hours[<?php echo $day_key; ?>][end]" value="<?php echo htmlspecialchars(!empty($day_hours['end']) ? $day_hours['end'] : ''); ?>">
                <input type="hidden" name="working_hours[<?php echo $day_key; ?>][active]" value="<?php echo $day_hours['active'] ? '1' : '0'; ?>">
                <?php endforeach; ?>
            </div>
            
            <div class="schedule-table-container">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Setare</th>
                            <th data-day="monday">Luni</th>
                            <th data-day="tuesday">Marți</th>
                            <th data-day="wednesday">Miercuri</th>
                            <th data-day="thursday">Joi</th>
                            <th data-day="friday">Vineri</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rând Status -->
                        <tr class="status-row">
                            <td>Status</td>
                            <?php foreach ($days as $day_key): 
                                $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
                            ?>
                            <td class="status-cell <?php echo $day_hours['active'] ? 'active' : 'inactive'; ?>" data-day="<?php echo $day_key; ?>" data-type="status">
                                <div class="cell-content">
                                    <span class="status-indicator <?php echo $day_hours['active'] ? 'active' : 'inactive'; ?>"></span>
                                    <span class="status-text"><?php echo $day_hours['active'] ? 'Activ' : 'Inactiv'; ?></span>
                                </div>
                                <input type="checkbox" name="working_hours[<?php echo $day_key; ?>][active]" value="1" <?php echo $day_hours['active'] ? 'checked' : ''; ?> style="display: none;">
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Rând Început -->
                        <tr class="start-row">
                            <td>Început</td>
                            <?php foreach ($days as $day_key): 
                                $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
                            ?>
                            <td class="time-cell <?php echo $day_hours['active'] ? 'active' : 'inactive'; ?>" data-day="<?php echo $day_key; ?>" data-type="start">
                                <div class="cell-display">
                                    <?php echo htmlspecialchars($day_hours['start'] ?: '--:--'); ?>
                                </div>
                                <div class="cell-edit" style="display: none;">
                                    <input type="time" name="working_hours[<?php echo $day_key; ?>][start]" value="<?php echo htmlspecialchars(!empty($day_hours['start']) ? $day_hours['start'] : ''); ?>" <?php echo !$day_hours['active'] ? 'disabled' : ''; ?>>
                                </div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Rând Sfârșit -->
                        <tr class="end-row">
                            <td>Sfârșit</td>
                            <?php foreach ($days as $day_key): 
                                $day_hours = isset($working_hours[$day_key]) ? $working_hours[$day_key] : array("start" => "", "end" => "", "active" => false);
                            ?>
                            <td class="time-cell <?php echo $day_hours['active'] ? 'active' : 'inactive'; ?>" data-day="<?php echo $day_key; ?>" data-type="end">
                                <div class="cell-display">
                                    <?php echo htmlspecialchars($day_hours['end'] ?: '--:--'); ?>
                                </div>
                                <div class="cell-edit" style="display: none;">
                                    <input type="time" name="working_hours[<?php echo $day_key; ?>][end]" value="<?php echo htmlspecialchars(!empty($day_hours['end']) ? $day_hours['end'] : ''); ?>" <?php echo !$day_hours['active'] ? 'disabled' : ''; ?>>
                                </div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <button type="submit">Test Submit</button>
        </form>
        
        <div class="debug-panel">
            <h3>Debug Panel</h3>
            <button onclick="testSyncHiddenInputs()">Test syncHiddenInputs()</button>
            <button onclick="testSyncHiddenInputsOnLoad()">Test syncHiddenInputsOnLoad()</button>
            <button onclick="showAllHiddenInputs()">Show All Hidden Inputs</button>
            <div class="debug-log" id="debug-log"></div>
        </div>
    </div>

    <script>
        function log(message) {
            var debugLog = $('#debug-log');
            debugLog.append('<div>' + new Date().toLocaleTimeString() + ': ' + message + '</div>');
            debugLog.scrollTop(debugLog[0].scrollHeight);
            console.log(message);
        }
        
        // Sincronizează valorile cu hidden inputs (versiunea originală)
        function syncHiddenInputs() {
            log('=== syncHiddenInputs() START ===');
            $('.time-cell input[type="time"]').each(function() {
                var input = $(this);
                var day = input.closest('.time-cell').data('day');
                var type = input.attr('name').includes('start') ? 'start' : 'end';
                var value = input.val();
                
                log('Syncing input for day: ' + day + ' type: ' + type + ' value: ' + value);
                
                if (value && value.trim() !== '') {
                    var hiddenInput = $('input[name="working_hours[' + day + '][' + type + ']"]');
                    hiddenInput.val(value);
                    log('Updated hidden input: ' + hiddenInput[0].outerHTML + ' with value: ' + value);
                } else {
                    var hiddenInput = $('input[name="working_hours[' + day + '][' + type + ']"]');
                    hiddenInput.val('');
                    log('Cleared hidden input for day: ' + day + ' type: ' + type);
                }
            });
            
            $('.status-cell input[type="checkbox"]').each(function() {
                var checkbox = $(this);
                var day = checkbox.closest('.status-cell').data('day');
                var isActive = checkbox.is(':checked');
                
                var hiddenInput = $('input[name="working_hours[' + day + '][active]"]');
                hiddenInput.val(isActive ? '1' : '0');
                log('Updated status hidden input for day: ' + day + ' active: ' + isActive);
            });
            log('=== syncHiddenInputs() END ===');
        }
        
        // Sincronizează valorile cu hidden inputs la încărcare (fără să ștergi valorile existente)
        function syncHiddenInputsOnLoad() {
            log('=== syncHiddenInputsOnLoad() START ===');
            $('.time-cell input[type="time"]').each(function() {
                var input = $(this);
                var day = input.closest('.time-cell').data('day');
                var type = input.attr('name').includes('start') ? 'start' : 'end';
                var value = input.val();
                
                log('Initial sync for day: ' + day + ' type: ' + type + ' value: ' + value);
                
                if (value && value.trim() !== '') {
                    var hiddenInput = $('input[name="working_hours[' + day + '][' + type + ']"]');
                    hiddenInput.val(value);
                    log('Updated hidden input on load: ' + hiddenInput[0].outerHTML + ' with value: ' + value);
                }
                // Nu șterge hidden input-ul dacă input-ul vizibil este gol la încărcare
            });
            
            $('.status-cell input[type="checkbox"]').each(function() {
                var checkbox = $(this);
                var day = checkbox.closest('.status-cell').data('day');
                var isActive = checkbox.is(':checked');
                
                var hiddenInput = $('input[name="working_hours[' + day + '][active]"]');
                hiddenInput.val(isActive ? '1' : '0');
                log('Updated status hidden input on load for day: ' + day + ' active: ' + isActive);
            });
            log('=== syncHiddenInputsOnLoad() END ===');
        }
        
        function showAllHiddenInputs() {
            log('=== ALL HIDDEN INPUTS ===');
            $('input[name^="working_hours"]').each(function() {
                var input = $(this);
                var name = input.attr('name');
                var value = input.val();
                log('Hidden input: ' + name + ' = ' + value);
            });
        }
        
        function testSyncHiddenInputs() {
            log('Testing syncHiddenInputs()...');
            syncHiddenInputs();
        }
        
        function testSyncHiddenInputsOnLoad() {
            log('Testing syncHiddenInputsOnLoad()...');
            syncHiddenInputsOnLoad();
        }
        
        // La încărcarea paginii
        $(document).ready(function() {
            log('=== PAGE LOAD START ===');
            
            // Debug: Log hidden inputs la încărcare
            log('Hidden inputs at page load:');
            $('input[name^="working_hours"]').each(function() {
                var input = $(this);
                var name = input.attr('name');
                var value = input.val();
                log('Hidden input: ' + name + ' = ' + value);
            });
            
            // Debug: Log visible inputs la încărcare
            log('Visible time inputs at page load:');
            $('.time-cell input[type="time"]').each(function() {
                var input = $(this);
                var day = input.closest('.time-cell').data('day');
                var type = input.attr('name').includes('start') ? 'start' : 'end';
                var value = input.val();
                log('Visible input: ' + day + ' ' + type + ' = ' + value);
            });
            
            // Inițializează sincronizarea (folosește versiunea care nu șterge valorile existente)
            syncHiddenInputsOnLoad();
            
            log('=== PAGE LOAD END ===');
        });
        
        // Test form submit
        $('#test-form').on('submit', function(e) {
            e.preventDefault();
            log('=== FORM SUBMIT ===');
            
            // Sincronizează hidden inputs înainte de submit
            syncHiddenInputs();
            
            // Debug: Log toate hidden inputs pentru working_hours
            log('All working_hours hidden inputs:');
            $('input[name^="working_hours"]').each(function() {
                var input = $(this);
                var name = input.attr('name');
                var value = input.val();
                log('Hidden input: ' + name + ' = ' + value);
            });
        });
    </script>
</body>
</html> 