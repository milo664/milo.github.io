<?php
/**
 * FIX.PHP
 * Sửa lỗi page numbering trong file Word
 */

header('Content-Type: application/json; charset=utf-8');

define('TEMP_DIR', 'temp/');
define('UPLOAD_DIR', 'uploads/');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['file_id'])) {
        throw new Exception('Missing file_id');
    }

    $fileId = $input['file_id'];
    
    // Load analysis data
    $analysisFile = TEMP_DIR . $fileId . '_analysis.json';
    
    if (!file_exists($analysisFile)) {
        throw new Exception('Analysis data not found. Please upload the file again.');
    }

    $analysisData = json_decode(file_get_contents($analysisFile), true);
    
    $unpackDir = $analysisData['unpack_dir'];
    $documentXml = $unpackDir . '/word/document.xml';

    if (!file_exists($documentXml)) {
        throw new Exception('Document XML not found');
    }

    // Load and parse XML
    $xml = file_get_contents($documentXml);
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->formatOutput = false;
    $dom->loadXML($xml);
    
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    // Find all pgNumType elements with start attribute
    $pgNumTypes = $xpath->query('//w:pgNumType[@w:start]');
    $fixedCount = 0;

    foreach ($pgNumTypes as $pgNumType) {
        // Remove the w:start attribute to make it continue from previous section
        $pgNumType->removeAttribute('w:start');
        $fixedCount++;
    }

    // If no pgNumType found but there are issues, remove the entire pgNumType element
    if ($fixedCount === 0 && count($analysisData['issues']) > 0) {
        $pgNumTypes = $xpath->query('//w:pgNumType');
        foreach ($pgNumTypes as $pgNumType) {
            $pgNumType->parentNode->removeChild($pgNumType);
            $fixedCount++;
        }
    }

    // Save the modified XML
    $dom->save($documentXml);

    // Pack the document back to .docx
    $fixedFileName = 'fixed_' . $analysisData['original_name'];
    $fixedPath = UPLOAD_DIR . $fileId . '_fixed.' . $analysisData['extension'];

    $scriptDir = __DIR__ . '/scripts';
    $cmd = "python3 " . escapeshellarg($scriptDir . '/pack.py') . " " .
           escapeshellarg($unpackDir) . " " .
           escapeshellarg($fixedPath) . " 2>&1";
    
    exec($cmd, $output, $returnCode);

    if ($returnCode !== 0) {
        throw new Exception('Không thể pack file: ' . implode("\n", $output));
    }

    if (!file_exists($fixedPath)) {
        throw new Exception('Fixed file was not created');
    }

    // Update analysis data
    $analysisData['fixed_path'] = $fixedPath;
    $analysisData['fixed_name'] = $fixedFileName;
    $analysisData['fixed_count'] = $fixedCount;
    
    file_put_contents($analysisFile, json_encode($analysisData, JSON_PRETTY_PRINT));

    // Response
    echo json_encode([
        'success' => true,
        'message' => "Đã sửa thành công $fixedCount section(s)",
        'fixed_count' => $fixedCount,
        'file_id' => $fileId
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>