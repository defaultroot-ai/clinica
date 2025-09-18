<?php
if (!defined('ABSPATH')) { exit; }

if (!Clinica_Patient_Permissions::can_view_doctors()) {
    wp_die(__('Nu aveți permisiunea de a vedea medicii.', 'clinica'));
}

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$args = array(
    'role__in' => array('clinica_doctor', 'clinica_manager'),
    'orderby' => 'display_name',
    'order' => 'ASC',
    'number' => 200,
);
if (!empty($search)) {
    $args['search'] = '*' . esc_attr($search) . '*';
}
$users = get_users($args);
$nonce = wp_create_nonce('clinica_doctors_nonce');

?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Medici', 'clinica'); ?></h1>
    <hr class="wp-header-end">

    <form method="get" action="" style="margin-bottom:12px;">
        <input type="hidden" name="page" value="clinica-doctors" />
        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Caută doctor...', 'clinica'); ?>" />
        <button class="button"><?php _e('Caută', 'clinica'); ?></button>
        <?php if (!empty($search)): ?><a class="button" href="<?php echo admin_url('admin.php?page=clinica-doctors'); ?>"><?php _e('Reset', 'clinica'); ?></a><?php endif; ?>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'clinica'); ?></th>
                <th><?php _e('Nume', 'clinica'); ?></th>
                <th><?php _e('Email', 'clinica'); ?></th>
                <th><?php _e('Roluri', 'clinica'); ?></th>
                <th><?php _e('Program', 'clinica'); ?></th>
                <th><?php _e('Acțiuni', 'clinica'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="6" style="text-align:center;padding:20px;"><?php _e('Nu s-au găsit medici.', 'clinica'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?php echo (int)$u->ID; ?></strong></td>
                    <td><strong><?php echo esc_html($u->display_name); ?></strong></td>
                    <td><?php echo esc_html($u->user_email); ?></td>
                    <td><?php echo esc_html(implode(', ', $u->roles)); ?></td>
                    <td>
                        <?php 
                        $schedule = get_user_meta($u->ID, 'clinica_working_hours', true);
                        if (is_string($schedule)) { $schedule = json_decode($schedule, true); }
                        if (empty($schedule)) {
                            echo '<span class="description">' . esc_html__('Folosește programul global', 'clinica') . '</span>';
                        } else {
                            echo '<span class="description">' . esc_html__('Program personalizat', 'clinica') . '</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url(get_edit_user_link($u->ID)); ?>" class="button button-small"><?php _e('Editează profil', 'clinica'); ?></a>
                        <button type="button" class="button button-small edit-schedule" data-id="<?php echo (int)$u->ID; ?>" data-name="<?php echo esc_attr($u->display_name); ?>"><?php _e('Editează program', 'clinica'); ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Secțiunea de program doctor - noua implementare -->
<div class="clinica-doctor-schedule-section" id="doctor-schedule-section" style="display:none;">
    <div class="clinica-schedule-excel">
        <div class="schedule-header">
            <h3 id="schedule-doctor-name">Program Doctor - [Nume Doctor]</h3>
            <p><?php _e('Click pe celule pentru editare rapidă', 'clinica'); ?></p>
        </div>
        
        <div class="schedule-table-container">
            <table class="schedule-excel-table">
                <thead>
                    <tr>
                        <th class="row-header"><?php _e('Setare', 'clinica'); ?></th>
                        <th class="day-header" data-day="monday"><?php _e('Luni', 'clinica'); ?></th>
                        <th class="day-header" data-day="tuesday"><?php _e('Marți', 'clinica'); ?></th>
                        <th class="day-header" data-day="wednesday"><?php _e('Miercuri', 'clinica'); ?></th>
                        <th class="day-header" data-day="thursday"><?php _e('Joi', 'clinica'); ?></th>
                        <th class="day-header" data-day="friday"><?php _e('Vineri', 'clinica'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Rând Status -->
                    <tr class="status-row">
                        <td class="row-label"><?php _e('Status', 'clinica'); ?></td>
                        <?php 
                        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
                        foreach ($days as $day_key): 
                        ?>
                        <td class="status-cell inactive" data-day="<?php echo $day_key; ?>" data-type="status">
                            <div class="cell-content">
                                <span class="status-indicator inactive"></span>
                                <span class="status-text"><?php _e('Inactiv', 'clinica'); ?></span>
                            </div>
                            <input type="checkbox" name="working_hours[<?php echo $day_key; ?>][active]" value="1" style="display: none;">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Rând Început -->
                    <tr class="start-row">
                        <td class="row-label"><?php _e('Început', 'clinica'); ?></td>
                        <?php foreach ($days as $day_key): ?>
                        <td class="time-cell inactive" data-day="<?php echo $day_key; ?>" data-type="start">
                            <div class="cell-display">--:--</div>
                            <div class="cell-edit" style="display: none;">
                                <input type="time" name="working_hours[<?php echo $day_key; ?>][start]" value="" disabled>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Rând Sfârșit -->
                    <tr class="end-row">
                        <td class="row-label"><?php _e('Sfârșit', 'clinica'); ?></td>
                        <?php foreach ($days as $day_key): ?>
                        <td class="time-cell inactive" data-day="<?php echo $day_key; ?>" data-type="end">
                            <div class="cell-display">--:--</div>
                            <div class="cell-edit" style="display: none;">
                                <input type="time" name="working_hours[<?php echo $day_key; ?>][end]" value="" disabled>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Rând Pauză Început -->
                    <tr class="break-start-row">
                        <td class="row-label"><?php _e('Pauză Început', 'clinica'); ?></td>
                        <?php foreach ($days as $day_key): ?>
                        <td class="time-cell inactive" data-day="<?php echo $day_key; ?>" data-type="break_start">
                            <div class="cell-display">--:--</div>
                            <div class="cell-edit" style="display: none;">
                                <input type="time" name="working_hours[<?php echo $day_key; ?>][break_start]" value="" disabled>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Rând Pauză Sfârșit -->
                    <tr class="break-end-row">
                        <td class="row-label"><?php _e('Pauză Sfârșit', 'clinica'); ?></td>
                        <?php foreach ($days as $day_key): ?>
                        <td class="time-cell inactive" data-day="<?php echo $day_key; ?>" data-type="break_end">
                            <div class="cell-display">--:--</div>
                            <div class="cell-edit" style="display: none;">
                                <input type="time" name="working_hours[<?php echo $day_key; ?>][break_end]" value="" disabled>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="holidays-section">
            <h3><?php _e('Concedii (zile indisponibile)', 'clinica'); ?></h3>
            <div id="schedule-holidays" class="holidays-list"></div>
            <div class="holiday-controls">
                <input type="date" id="schedule-holiday-date" class="holiday-date-input" />
                <button type="button" class="button button-secondary" id="schedule-add-holiday"><?php _e('Adaugă', 'clinica'); ?></button>
                <button type="button" class="button button-link-delete" id="schedule-clear-holidays"><?php _e('Golește', 'clinica'); ?></button>
            </div>
        </div>
        
        <div class="schedule-actions">
            <button type="button" class="button" id="schedule-cancel"><?php _e('Renunță', 'clinica'); ?></button>
            <button type="button" class="button button-primary" id="schedule-save"><?php _e('Salvează', 'clinica'); ?></button>
        </div>
    </div>
</div>


<style>
/* Secțiunea de program doctor */
.clinica-doctor-schedule-section {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

/* Schedule Excel Style - adaptat din settings.php */
.clinica-schedule-excel {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.schedule-header {
    text-align: center;
    margin-bottom: 25px;
}

.schedule-header h3 {
    margin: 0 0 10px 0;
    color: #1d2327;
    font-size: 24px;
    font-weight: 600;
}

.schedule-header p {
    margin: 0;
    color: #646970;
    font-size: 14px;
    line-height: 1.4;
}

.schedule-table-container {
    margin-bottom: 25px;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.schedule-excel-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    background: #fff;
}

.schedule-excel-table th {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    padding: 12px 8px;
    text-align: center;
    font-weight: 600;
    color: #1d2327;
    position: sticky;
    top: 0;
    z-index: 10;
}

.schedule-excel-table td {
    border: 1px solid #e1e5e9;
    padding: 10px 8px;
    text-align: center;
    vertical-align: middle;
    transition: all 0.2s ease;
    position: relative;
}

.schedule-excel-table td:hover {
    background: #f8f9fa;
    cursor: pointer;
}

.schedule-excel-table td.editing {
    background: #e3f2fd;
    border-color: #007cba;
    box-shadow: inset 0 0 0 2px #007cba;
}

.row-header {
    background: #f1f3f4 !important;
    font-weight: 600;
    color: #1d2327;
    text-align: left !important;
    min-width: 100px;
}

.day-header {
    background: #e8f4fd !important;
    color: #007cba;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    min-width: 100px;
}

.row-label {
    font-weight: 600;
    color: #1d2327;
    text-align: left;
    padding-left: 12px;
}

/* Status Cells */
.status-cell {
    cursor: pointer;
    user-select: none;
}

.status-cell.active {
    background: #e8f5e8;
    border-color: #4caf50;
}

.status-cell.inactive {
    background: #f5f5f5;
    border-color: #ddd;
}

.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
    vertical-align: middle;
}

.status-indicator.active {
    background: #4caf50;
}

.status-indicator.inactive {
    background: #ccc;
}

.status-text {
    font-weight: 500;
    font-size: 12px;
}

/* Time Cells */
.time-cell {
    cursor: pointer;
    position: relative;
}

.time-cell.active {
    background: #fff;
}

.time-cell.inactive {
    background: #f5f5f5;
    cursor: not-allowed;
}

.cell-display {
    font-weight: 500;
    color: #1d2327;
}

.cell-edit {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #fff;
    z-index: 10;
}

.cell-edit input[type="time"] {
    width: 100%;
    height: 100%;
    border: none;
    padding: 8px;
    font-size: 13px;
    text-align: center;
    background: transparent;
}

.cell-edit input[type="time"]:focus {
    outline: none;
    background: #f0f8ff;
}

/* Holidays Section */
.holidays-section {
    margin-bottom: 25px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

.holidays-section h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
    font-size: 16px;
    font-weight: 600;
}

.holidays-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
    min-height: 30px;
}

.holiday-tag {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    background: #007cba;
    color: white;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}

.holiday-tag .remove-holiday {
    margin-left: 8px;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    padding: 0;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.holiday-tag .remove-holiday:hover {
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
}

.holiday-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.holiday-date-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

/* Schedule Actions */
.schedule-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

.schedule-actions .button {
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.schedule-actions .button-primary {
    background: #007cba;
    border-color: #007cba;
}

.schedule-actions .button-primary:hover {
    background: #005a87;
    border-color: #005a87;
}

/* Responsive */
@media (max-width: 768px) {
    .clinica-doctor-schedule-section {
        padding: 15px;
    }
    
    .clinica-schedule-excel {
        padding: 15px;
    }
    
    .schedule-excel-table {
        font-size: 11px;
    }
    
    .schedule-excel-table th,
    .schedule-excel-table td {
        padding: 6px 4px;
    }
    
    .row-header {
        min-width: 80px;
    }
    
    .day-header {
        min-width: 90px;
        font-size: 10px;
    }
    
    .status-text {
        font-size: 10px;
    }
    
    .cell-display {
        font-size: 12px;
    }
    
    .holiday-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .holiday-controls input,
    .holiday-controls button {
        width: 100%;
    }
    
    .schedule-actions {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<script>
jQuery(function($){
    var currentDoctorId = null, schedule = {}, holidays = [];
    var days = [
        {key:'monday', label:'Luni'},
        {key:'tuesday', label:'Marți'},
        {key:'wednesday', label:'Miercuri'},
        {key:'thursday', label:'Joi'},
        {key:'friday', label:'Vineri'},
        {key:'saturday', label:'Sâmbătă'},
        {key:'sunday', label:'Duminică'}
    ];

    // Funcții helper pentru sincronizare
    function syncInputWithDisplay(cell) {
        var input = cell.find('input[type="time"]');
        var display = cell.find('.cell-display');
        var inputValue = input.val();
        
        if (inputValue && inputValue.trim() !== '') {
            display.text(inputValue);
        } else {
            display.text('--:--');
        }
    }

    function syncDisplayWithInput(cell) {
        var input = cell.find('input[type="time"]');
        var display = cell.find('.cell-display');
        var inputValue = input.val();
        
        if (inputValue && inputValue.trim() !== '') {
            display.text(inputValue);
        } else {
            display.text('--:--');
        }
    }

    function preserveInputValue(input) {
        var cell = input.closest('.time-cell');
        var inputValue = input.val();
        
        // Validate and clean the time value
        if (inputValue && inputValue.trim() !== '') {
            var timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
            if (timeRegex.test(inputValue)) {
                cell.find('.cell-display').text(inputValue);
            } else {
                input.val('');
                cell.find('.cell-display').text('--:--');
            }
        } else {
            cell.find('.cell-display').text('--:--');
        }
    }

    function syncHiddenInputs() {
        days.forEach(function(d) {
            var dayData = schedule[d.key] || {};
            $('input[name="working_hours[' + d.key + '][active]"]').val(dayData.active ? '1' : '0');
            $('input[name="working_hours[' + d.key + '][start]"]').val(dayData.start || '');
            $('input[name="working_hours[' + d.key + '][end]"]').val(dayData.end || '');
            $('input[name="working_hours[' + d.key + '][break_start]"]').val(dayData.break_start || '');
            $('input[name="working_hours[' + d.key + '][break_end]"]').val(dayData.break_end || '');
        });
    }

    function renderScheduleTable() {
        days.forEach(function(d) {
            var dayData = schedule[d.key] || {active: false, start: '', end: '', break_start: '', break_end: ''};
            
            // Update status cell
            var statusCell = $('.status-cell[data-day="' + d.key + '"]');
            var checkbox = statusCell.find('input[type="checkbox"]');
            var indicator = statusCell.find('.status-indicator');
            var text = statusCell.find('.status-text');
            
            if (dayData.active) {
                statusCell.removeClass('inactive').addClass('active');
                indicator.removeClass('inactive').addClass('active');
                text.text('Activ');
                checkbox.prop('checked', true);
                
                // Enable time cells
                $('.time-cell[data-day="' + d.key + '"]').removeClass('inactive').addClass('active').find('input').prop('disabled', false);
            } else {
                statusCell.removeClass('active').addClass('inactive');
                indicator.removeClass('active').addClass('inactive');
                text.text('Inactiv');
                checkbox.prop('checked', false);
                
                // Disable time cells
                $('.time-cell[data-day="' + d.key + '"]').removeClass('active').addClass('inactive').find('input').prop('disabled', true);
            }
            
            // Update time cells
            $('.time-cell[data-day="' + d.key + '"]').each(function() {
                var cell = $(this);
                var type = cell.data('type');
                var value = dayData[type] || '';
                var display = cell.find('.cell-display');
                var input = cell.find('input[type="time"]');
                
                input.val(value);
                if (value) {
                    display.text(value);
                } else {
                    display.text('--:--');
                }
            });
        });
        
        renderHolidays();
    }

    function renderHolidays() {
        var html = '';
        holidays.forEach(function(d) {
            html += '<span class="holiday-tag" data-date="' + d + '">' +
                '<span>' + d + '</span>' +
                '<a href="#" class="remove-holiday">×</a>' +
                '</span>';
        });
        $('#schedule-holidays').html(html);
    }

    function openScheduleSection(doctorId, doctorName) {
        currentDoctorId = doctorId;
        $('#schedule-doctor-name').text('Program Doctor - ' + doctorName);
        
        // Load schedule
        $.post(ajaxurl, {
            action: 'clinica_get_doctor_schedule',
            nonce: '<?php echo esc_js($nonce); ?>',
            doctor_id: doctorId
        }, function(resp) {
            if (resp && resp.success) {
                schedule = resp.data.schedule || {};
                holidays = resp.data.holidays || [];
                renderScheduleTable();
                $('#doctor-schedule-section').show();
                $('html, body').animate({
                    scrollTop: $('#doctor-schedule-section').offset().top - 100
                }, 500);
            } else {
                alert(resp && resp.data ? resp.data : 'Eroare la încărcare');
            }
        });
    }

    function collectScheduleData() {
        days.forEach(function(d) {
            var dayData = schedule[d.key] || {};
            dayData.active = $('.status-cell[data-day="' + d.key + '"] input[type="checkbox"]').is(':checked');
            dayData.start = $('input[name="working_hours[' + d.key + '][start]"]').val() || '';
            dayData.end = $('input[name="working_hours[' + d.key + '][end]"]').val() || '';
            dayData.break_start = $('input[name="working_hours[' + d.key + '][break_start]"]').val() || '';
            dayData.break_end = $('input[name="working_hours[' + d.key + '][break_end]"]').val() || '';
            schedule[d.key] = dayData;
        });
    }

    // Event handlers
    $(document).on('click', '.edit-schedule', function() {
        openScheduleSection($(this).data('id'), $(this).data('name'));
    });

    // Excel-style cell editing
    $(document).on('click', '.time-cell', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var cell = $(this);
        var day = cell.data('day');
        var type = cell.data('type');
        
        // Only allow editing if the day is active
        if (cell.hasClass('inactive')) {
            return;
        }
        
        // Close all other editing cells
        $('.time-cell').removeClass('editing').find('.cell-edit').hide();
        
        // Open this cell for editing
        cell.addClass('editing');
        cell.find('.cell-edit').show();
        
        // Sync input with display before editing
        syncInputWithDisplay(cell);
        
        // Focus on the input and select all text
        var input = cell.find('input[type="time"]');
        input.focus();
        input.select();
    });

    // Prevent invalid input in time fields
    $(document).on('input', '.time-cell input[type="time"]', function() {
        var input = $(this);
        var value = input.val();
        
        if (value && value.trim() !== '') {
            var cleanedValue = value.replace(/[^0-9:]/g, '');
            
            if (cleanedValue.length > 0) {
                if (cleanedValue.length >= 2 && !cleanedValue.includes(':')) {
                    cleanedValue = cleanedValue.substring(0, 2) + ':' + cleanedValue.substring(2);
                }
                
                if (cleanedValue.length > 5) {
                    cleanedValue = cleanedValue.substring(0, 5);
                }
                
                if (cleanedValue !== value) {
                    input.val(cleanedValue);
                }
            }
        }
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
        
        // Toggle status
        checkbox.prop('checked', !checkbox.is(':checked'));
        
        if (checkbox.is(':checked')) {
            cell.removeClass('inactive').addClass('active');
            indicator.removeClass('inactive').addClass('active');
            text.text('Activ');
            
            // Enable time cells for this day
            $('.time-cell[data-day="' + day + '"]').removeClass('inactive').addClass('active').find('input').prop('disabled', false);
        } else {
            cell.removeClass('active').addClass('inactive');
            indicator.removeClass('active').addClass('inactive');
            text.text('Inactiv');
            
            // Disable time cells for this day and clear their values
            $('.time-cell[data-day="' + day + '"]').removeClass('active').addClass('inactive').find('input').prop('disabled', true).val('');
            $('.time-cell[data-day="' + day + '"] .cell-display').text('--:--');
        }
        
        syncHiddenInputs();
    });

    // Time input change
    $(document).on('change blur', '.time-cell input[type="time"]', function() {
        var input = $(this);
        var cell = input.closest('.time-cell');
        var day = cell.data('day');
        var type = input.attr('name').includes('start') ? 'start' : (input.attr('name').includes('end') ? 'end' : (input.attr('name').includes('break_start') ? 'break_start' : 'break_end'));
        var inputValue = input.val();
        
        // Check if the value is valid before preserving
        if (inputValue && inputValue.trim() !== '') {
            var timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
            if (!timeRegex.test(inputValue)) {
                input.val('');
                inputValue = '';
            }
        }
        
        // Sync display with input
        syncDisplayWithInput(cell);
        
        // Preserve the input value
        preserveInputValue(input);
        
        // Only close editing mode on blur, not on change
        if (event.type === 'blur') {
            cell.removeClass('editing');
            cell.find('.cell-edit').hide();
        }
        
        // Update schedule data
        if (!schedule[day]) schedule[day] = {};
        schedule[day][type] = inputValue;
        syncHiddenInputs();
    });

    // Enter key to save and close
    $(document).on('keydown', '.time-cell input[type="time"]', function(e) {
        if (e.keyCode === 13) { // Enter key
            $(this).blur();
        }
    });

    // Escape key to cancel editing
    $(document).on('keydown', '.time-cell input[type="time"]', function(e) {
        if (e.keyCode === 27) { // Escape key
            var cell = $(this).closest('.time-cell');
            var input = $(this);
            
            preserveInputValue(input);
            
            cell.removeClass('editing');
            cell.find('.cell-edit').hide();
            $(this).blur();
        }
    });

    // Click outside to close editing
    $(document).on('click', function(e) {
        if ($(e.target).closest('.time-cell, .status-cell, .cell-edit').length) {
            return;
        }
        
        var editingCell = $('.time-cell.editing');
        if (editingCell.length) {
            var input = editingCell.find('input[type="time"]');
            preserveInputValue(input);
        }
        
        $('.time-cell').removeClass('editing').find('.cell-edit').hide();
    });

    // Holiday management
    $(document).on('click', '#schedule-add-holiday', function() {
        var date = $('#schedule-holiday-date').val();
        if (!date) return;
        if (holidays.indexOf(date) === -1) {
            holidays.push(date);
            renderHolidays();
        }
    });

    $(document).on('click', '.remove-holiday', function(e) {
        e.preventDefault();
        var date = $(this).parent().data('date');
        holidays = holidays.filter(function(x) { return x !== date; });
        renderHolidays();
    });

    $(document).on('click', '#schedule-clear-holidays', function() {
        holidays = [];
        renderHolidays();
    });

    // Schedule actions
    $(document).on('click', '#schedule-cancel', function() {
        $('#doctor-schedule-section').hide();
        schedule = {};
        holidays = [];
    });

    $(document).on('click', '#schedule-save', function() {
        collectScheduleData();
        
        // Validations
        for (var i = 0; i < days.length; i++) {
            var k = days[i].key;
            var r = schedule[k] || {};
            if (r.active) {
                if (!r.start || !r.end) {
                    alert('Completați start/sfârșit pentru ' + days[i].label);
                    return;
                }
                if (r.end <= r.start) {
                    alert('Pentru ' + days[i].label + ': sfârșitul trebuie să fie după start.');
                    return;
                }
                if (r.break_start || r.break_end) {
                    if (!r.break_start || !r.break_end) {
                        alert('Pentru ' + days[i].label + ': completați ambele ore ale pauzei.');
                        return;
                    }
                    if (r.break_start >= r.break_end) {
                        alert('Pentru ' + days[i].label + ': pauza început < pauza sfârșit.');
                        return;
                    }
                    if (!(r.break_start >= r.start && r.break_end <= r.end)) {
                        alert('Pentru ' + days[i].label + ': pauza trebuie să fie în interiorul intervalului de lucru.');
                        return;
                    }
                }
            }
        }
        
        $.post(ajaxurl, {
            action: 'clinica_save_doctor_schedule',
            nonce: '<?php echo esc_js($nonce); ?>',
            doctor_id: currentDoctorId,
            schedule: JSON.stringify(schedule),
            holidays: JSON.stringify(holidays)
        }, function(resp) {
            if (resp && resp.success) {
                alert('Salvat cu succes!');
                location.reload();
            } else {
                alert(resp && resp.data ? resp.data : 'Eroare la salvare');
            }
        });
    });
});
</script>
