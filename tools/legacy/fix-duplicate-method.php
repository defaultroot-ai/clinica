<?php
/**
 * Script pentru eliminarea metodei duplicată render_manager_dashboard
 */

$file_path = 'clinica.php';
$content = file_get_contents($file_path);

// Găsește toate aparițiile metodei render_manager_dashboard
$pattern = '/\/\*\*\s*\n\s*\*\s*Render manager dashboard\s*\n\s*\*\/\s*\n\s*public function render_manager_dashboard\($atts\)\s*\{[^}]*\}/s';

preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

if (count($matches[0]) > 1) {
    echo "Găsite " . count($matches[0]) . " apariții ale metodei render_manager_dashboard\n";
    
    // Păstrează doar prima apariție și elimină restul
    $first_match = $matches[0][0];
    $first_content = $first_match[0];
    $first_offset = $first_match[1];
    
    // Elimină prima apariție din conținut
    $content_before = substr($content, 0, $first_offset);
    $content_after = substr($content, $first_offset + strlen($first_content));
    
    // Elimină toate celelalte apariții
    $remaining_content = $content_before . $content_after;
    $remaining_content = preg_replace($pattern, '', $remaining_content, -1, $count);
    
    // Adaugă înapoi prima apariție
    $final_content = $content_before . $first_content . $remaining_content;
    
    // Salvează fișierul
    if (file_put_contents($file_path, $final_content)) {
        echo "✅ Metoda duplicată a fost eliminată cu succes!\n";
        echo "Eliminate " . ($count + 1) . " apariții duplicate\n";
    } else {
        echo "❌ Eroare la salvarea fișierului\n";
    }
} else {
    echo "Nu s-au găsit apariții duplicate ale metodei render_manager_dashboard\n";
}

// Verifică dacă mai există duplicate
$remaining_matches = preg_match_all($pattern, file_get_contents($file_path), $matches);
echo "Rămase " . $remaining_matches . " apariții ale metodei render_manager_dashboard\n";
?> 