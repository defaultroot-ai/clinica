<?php
/**
 * Test pentru input-urile de tip time
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Time Input</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .time-cell {
            border: 1px solid #ccc;
            padding: 10px;
            margin: 5px;
            cursor: pointer;
        }
        .time-cell.editing {
            background-color: #f0f0f0;
        }
        .cell-display {
            font-weight: bold;
        }
        .cell-edit {
            display: none;
        }
        .cell-edit input {
            width: 100px;
        }
    </style>
</head>
<body>
    <h1>Test Time Input</h1>
    
    <div class="time-cell" data-day="monday" data-type="start">
        <div class="cell-display">--:--</div>
        <div class="cell-edit">
            <input type="time" name="working_hours[monday][start]" value="">
        </div>
    </div>
    
    <div class="time-cell" data-day="monday" data-type="end">
        <div class="cell-display">--:--</div>
        <div class="cell-edit">
            <input type="time" name="working_hours[monday][end]" value="">
        </div>
    </div>
    
    <div style="margin-top: 20px;">
        <button onclick="testInput()">Test Input</button>
        <button onclick="showValues()">Show Values</button>
    </div>
    
    <div id="debug" style="margin-top: 20px; padding: 10px; background: #f0f0f0;"></div>

    <script>
        $(document).ready(function() {
            // Click to edit
            $('.time-cell').on('click', function() {
                var cell = $(this);
                
                // Close other cells
                $('.time-cell').removeClass('editing').find('.cell-edit').hide();
                
                // Open this cell
                cell.addClass('editing');
                cell.find('.cell-edit').show();
                
                // Focus input
                var input = cell.find('input[type="time"]');
                input.focus();
                input.select();
            });
            
            // Input change
            $('.time-cell input[type="time"]').on('change blur', function() {
                var input = $(this);
                var cell = input.closest('.time-cell');
                var inputValue = input.val();
                
                console.log('Input value:', inputValue);
                
                // Update display
                if (inputValue && inputValue.trim() !== '') {
                    cell.find('.cell-display').text(inputValue);
                    console.log('Updated display to:', inputValue);
                } else {
                    cell.find('.cell-display').text('--:--');
                    console.log('Updated display to: --:--');
                }
                
                // Close editing
                cell.removeClass('editing');
                cell.find('.cell-edit').hide();
            });
            
            // Click outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.time-cell').length) {
                    var editingCell = $('.time-cell.editing');
                    if (editingCell.length) {
                        var input = editingCell.find('input[type="time"]');
                        var inputValue = input.val();
                        
                        console.log('Click outside - input value:', inputValue);
                        
                        if (inputValue && inputValue.trim() !== '') {
                            editingCell.find('.cell-display').text(inputValue);
                        } else {
                            editingCell.find('.cell-display').text('--:--');
                        }
                    }
                    
                    $('.time-cell').removeClass('editing').find('.cell-edit').hide();
                }
            });
        });
        
        function testInput() {
            var cell = $('.time-cell[data-day="monday"][data-type="start"]');
            var input = cell.find('input[type="time"]');
            
            console.log('Testing input...');
            console.log('Input element:', input[0]);
            console.log('Input value:', input.val());
            console.log('Display text:', cell.find('.cell-display').text());
            
            // Set a test value
            input.val('09:30');
            console.log('Set value to 09:30');
            console.log('Input value after set:', input.val());
            
            // Trigger change
            input.trigger('change');
        }
        
        function showValues() {
            var debug = $('#debug');
            var html = '<h3>Current Values:</h3>';
            
            $('.time-cell').each(function() {
                var cell = $(this);
                var day = cell.data('day');
                var type = cell.data('type');
                var input = cell.find('input[type="time"]');
                var display = cell.find('.cell-display');
                
                html += '<p>';
                html += '<strong>' + day + ' ' + type + ':</strong><br>';
                html += 'Input value: "' + input.val() + '"<br>';
                html += 'Display text: "' + display.text() + '"<br>';
                html += 'Input disabled: ' + input.prop('disabled') + '<br>';
                html += 'Cell editing: ' + cell.hasClass('editing');
                html += '</p>';
            });
            
            debug.html(html);
        }
    </script>
</body>
</html> 