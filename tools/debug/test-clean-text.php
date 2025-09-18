<?php
/**
 * Test pentru curățarea textului
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Clean Text</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Test Clean Text</h1>
    
    <div id="test-results"></div>
    
    <script>
        function cleanTextValue(text) {
            if (!text) return '';
            return text.trim().replace(/\s+/g, '');
        }
        
        // Test cases
        var testCases = [
            '08:30',
            ' 08:30 ',
            '\n                                            08:30                                        ',
            '08:30\n',
            '  08:30  ',
            '',
            null,
            undefined
        ];
        
        var results = $('#test-results');
        results.html('<h2>Test Results:</h2>');
        
        testCases.forEach(function(testCase, index) {
            var cleaned = cleanTextValue(testCase);
            var html = '<p><strong>Test ' + (index + 1) + ':</strong><br>';
            html += 'Original: "' + testCase + '"<br>';
            html += 'Cleaned: "' + cleaned + '"<br>';
            html += 'Length: ' + cleaned.length + '</p>';
            results.append(html);
        });
        
        console.log('Test completed');
    </script>
</body>
</html> 