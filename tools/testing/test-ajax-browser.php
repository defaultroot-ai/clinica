<?php
// Simulează WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test AJAX Browser</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Test AJAX Browser</h1>
    
    <div>
        <label for="cnp">CNP:</label>
        <input type="text" id="cnp" value="1800404080170" />
        <button onclick="testAjax()">Test AJAX</button>
    </div>
    
    <div id="result"></div>
    
    <script>
    function testAjax() {
        var cnp = $('#cnp').val();
        
        $('#result').html('Se testează...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'clinica_validate_cnp',
                cnp: cnp,
                nonce: '<?php echo wp_create_nonce('clinica_nonce'); ?>'
            },
            success: function(response) {
                $('#result').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
                console.log('Success:', response);
            },
            error: function(xhr, status, error) {
                $('#result').html('<div style="color: red;">Eroare: ' + status + ' - ' + error + '</div>');
                console.log('Error:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
            }
        });
    }
    
    // Test automat la încărcare
    $(document).ready(function() {
        setTimeout(testAjax, 1000);
    });
    </script>
</body>
</html> 