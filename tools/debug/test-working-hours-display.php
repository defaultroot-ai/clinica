<?php
/**
 * Test pentru verificarea afișării programului de lucru
 * 
 * Acest test verifică dacă datele se încarcă și se afișează corect
 */

// Simulează setările din WordPress
$schedule_settings = array(
    'working_hours' => array(
        'value' => array(
            'monday' => array('start' => '09:00', 'end' => '17:00', 'active' => true),
            'tuesday' => array('start' => '08:30', 'end' => '16:30', 'active' => true),
            'wednesday' => array('start' => '10:00', 'end' => '18:00', 'active' => true),
            'thursday' => array('start' => '09:00', 'end' => '15:00', 'active' => true),
            'friday' => array('start' => '08:00', 'end' => '14:00', 'active' => true),
            'saturday' => array('start' => '', 'end' => '', 'active' => false),
            'sunday' => array('start' => '', 'end' => '', 'active' => false)
        )
    )
);

$working_hours = isset($schedule_settings['working_hours']['value']) ? $schedule_settings['working_hours']['value'] : array();
$days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');

// Debug info
echo "<h2>Debug Info:</h2>";
echo "<pre>";
echo "working_hours array:\n";
print_r($working_hours);
echo "\n\n";
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Working Hours Display</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-container { max-width: 800px; margin: 0 auto; }
        .schedule-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .schedule-table th, .schedule-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .time-cell { position: relative; cursor: pointer; }
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
        .cell-edit {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 20;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .cell-edit input[type="time"] {
            width: 100%;
            height: 100%;
            border: none;
            background: transparent;
            font-size: 14px;
            text-align: center;
            padding: 8px;
            outline: none;
            border-radius: 4px;
        }
        .cell-edit input[type="time"]:focus {
            background: #f8f9fa;
            box-shadow: inset 0 0 0 2px #007cba;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Test Working Hours Display</h1>
        <p>Acest test verifică dacă programul de lucru se afișează și se editează corect.</p>
        
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
            <button onclick="testClickCell()">Test Click Cell</button>
            <button onclick="showAllData()">Show All Data</button>
            <button onclick="testEditMode()">Test Edit Mode</button>
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
        
        // Excel-style cell editing
        $(document).on('click', '.time-cell', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var cell = $(this);
            var day = cell.data('day');
            var type = cell.data('type');
            
            log('Clicked cell for day: ' + day + ' type: ' + type);
            log('Cell classes: ' + cell.attr('class'));
            
            // Only allow editing if the day is active
            if (cell.hasClass('inactive')) {
                log('Cell is inactive, cannot edit');
                return;
            }
            
            // Close all other editing cells
            $('.time-cell').removeClass('editing').find('.cell-edit').hide();
            
            // Open this cell for editing
            cell.addClass('editing');
            cell.find('.cell-edit').show();
            
            // Focus on the input and select all text
            var input = cell.find('input[type="time"]');
            input.focus();
            input.select();
            
            log('Editing cell for day: ' + day + ' type: ' + type);
            log('Input element: ' + input[0]);
            log('Input value: ' + input.val());
        });
        
        // Status cell toggle
        $(document).on('click', '.status-cell', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var cell = $(this);
            var day = cell.data('day');
            var checkbox = cell.find('input[type="checkbox"]');
            var indicator = cell.find('.status-indicator');
            var text = cell.find('.status-text');
            
            log('Clicked status cell for day: ' + day);
            
            // Toggle status
            checkbox.prop('checked', !checkbox.is(':checked'));
            
            if (checkbox.is(':checked')) {
                cell.removeClass('inactive').addClass('active');
                indicator.removeClass('inactive').addClass('active');
                text.text('Activ');
                
                // Enable time cells for this day
                $('.time-cell[data-day="' + day + '"]').removeClass('inactive').addClass('active').find('input').prop('disabled', false);
                log('Enabled time cells for day: ' + day);
            } else {
                cell.removeClass('active').addClass('inactive');
                indicator.removeClass('active').addClass('inactive');
                text.text('Inactiv');
                
                // Disable time cells for this day and clear their values
                $('.time-cell[data-day="' + day + '"]').removeClass('active').addClass('inactive').find('input').prop('disabled', true).val('');
                $('.time-cell[data-day="' + day + '"] .cell-display').text('--:--');
                log('Disabled time cells for day: ' + day);
            }
        });
        
        // Time input change
        $(document).on('change blur', '.time-cell input[type="time"]', function() {
            var input = $(this);
            var cell = input.closest('.time-cell');
            var day = cell.data('day');
            var type = input.attr('name').includes('start') ? 'start' : 'end';
            var inputValue = input.val();
            
            log('Time changed for day: ' + day + ' type: ' + type + ' value: ' + inputValue);
            
            // Update display
            cell.find('.cell-display').text(inputValue || '--:--');
            
            // Close editing mode
            cell.removeClass('editing');
            cell.find('.cell-edit').hide();
            
            log('Updated display and closed editing mode');
        });
        
        function testClickCell() {
            log('=== TEST CLICK CELL ===');
            var firstCell = $('.time-cell.active').first();
            if (firstCell.length > 0) {
                log('Clicking first active cell: ' + firstCell.data('day') + ' ' + firstCell.data('type'));
                firstCell.click();
            } else {
                log('No active cells found');
            }
        }
        
        function showAllData() {
            log('=== ALL DATA ===');
            $('.time-cell').each(function() {
                var cell = $(this);
                var day = cell.data('day');
                var type = cell.data('type');
                var display = cell.find('.cell-display').text();
                var input = cell.find('input[type="time"]');
                var inputValue = input.val();
                var isActive = cell.hasClass('active');
                
                log('Cell: ' + day + ' ' + type + ' - Display: "' + display + '" - Input: "' + inputValue + '" - Active: ' + isActive);
            });
        }
        
        function testEditMode() {
            log('=== TEST EDIT MODE ===');
            var editingCells = $('.time-cell.editing');
            log('Editing cells count: ' + editingCells.length);
            
            editingCells.each(function() {
                var cell = $(this);
                var day = cell.data('day');
                var type = cell.data('type');
                var editDiv = cell.find('.cell-edit');
                var input = cell.find('input[type="time"]');
                
                log('Editing cell: ' + day + ' ' + type);
                log('Edit div visible: ' + editDiv.is(':visible'));
                log('Input value: ' + input.val());
            });
        }
        
        // La încărcarea paginii
        $(document).ready(function() {
            log('=== PAGE LOAD ===');
            showAllData();
        });
    </script>
</body>
</html> 