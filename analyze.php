<?php
/**
 * ANALYZE.PHP
 * Phân tích file Word để tìm lỗi page numbering
 */

header('Content-Type: application/json; charset=utf-8');

// Configuration
define('UPLOAD_DIR', 'uploads/');
define('TEMP_DIR', 'temp/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Create directories if not exist
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(TEMP_DIR)) mkdir(TEMP_DIR, 0755, true);

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_FILES['file'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['file'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }

    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File quá lớn. Kích thước tối đa: 50MB');
    }

    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['doc', 'docx'])) {
        throw new Exception('Chỉ hỗ trợ file .doc và .docx');
    }

    // Generate unique file ID
    $fileId = uniqid('doc_', true);
    $uploadPath = UPLOAD_DIR . $fileId . '.' . $extension;
    $unpackDir = TEMP_DIR . $fileId;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Không thể lưu file');
    }

    // Unpack document
    $scriptDir = __DIR__ . '/scripts';
    $cmd = "python3 " . escapeshellarg($scriptDir . '/unpack.py') . " " . 
           escapeshellarg($uploadPath) . " " . 
           escapeshellarg($unpackDir) . " 2>&1";
    
    exec($cmd, $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception('Không thể unpack file Word: ' . implode("\n", $output));
    }

    // Analyze document.xml for page numbering issues
    $documentXml = $unpackDir . '/word/document.xml';
    
    if (!file_exists($documentXml)) {
        throw new Exception('File document.xml không tồn tại');
    }

    $xml = file_get_contents($documentXml);
    
    // Parse XML
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    // Find all sections
    $sectPrNodes = $xpath->query('//w:sectPr');
    $sections = [];
    $issues = [];
    $issueCount = 0;

    foreach ($sectPrNodes as $index => $sectPr) {
        $sectionNum = $index + 1;
        $section = [
            'number' => $sectionNum,
            'has_issue' => false,
            'numbering_type' => 'Continue',
            'start_at' => null
        ];

        // Check for pgNumType
        $pgNumType = $xpath->query('.//w:pgNumType', $sectPr);
        
        if ($pgNumType->length > 0) {
            $pgNode = $pgNumType->item(0);
            
            if ($pgNode->hasAttribute('w:start')) {
                $startValue = $pgNode->getAttribute('w:start');
                $section['start_at'] = $startValue;
                $section['numbering_type'] = 'Start at ' . $startValue;
                
                // Check if it's problematic (starting at 0 or 1 in non-first section)
                if ($sectionNum > 1 && ($startValue == '0' || $startValue == '1')) {
                    $section['has_issue'] = true;
                    $issueCount++;
                    
                    $issues[] = [
                        'title' => "Section $sectionNum có vấn đề về page numbering",
                        'description' => "Section này được cấu hình để bắt đầu đánh số từ $startValue thay vì tiếp tục từ section trước. Điều này khiến số trang bị đánh sai.",
                        'section' => $sectionNum,
                        'start_value' => $startValue
                    ];
                }
            }
        }

        $sections[] = $section;
    }

    // Đếm số trang chính xác hơn
    $totalPages = countPages($uploadPath);

    // Save analysis to file
    $analysisData = [
        'file_id' => $fileId,
        'original_name' => $file['name'],
        'upload_path' => $uploadPath,
        'unpack_dir' => $unpackDir,
        'extension' => $extension,
        'sections' => $sections,
        'issues' => $issues,
        'total_pages' => $totalPages
    ];

    file_put_contents(
        TEMP_DIR . $fileId . '_analysis.json',
        json_encode($analysisData, JSON_PRETTY_PRINT)
    );

    // Response
    echo json_encode([
        'success' => true,
        'file_id' => $fileId,
        'filename' => $file['name'],
        'total_pages' => $totalPages,
        'sections' => $sections,
        'issues' => $issues,
        'has_issues' => count($issues) > 0
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Clean up on error
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    if (isset($unpackDir) && is_dir($unpackDir)) {
        deleteDirectory($unpackDir);
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Đếm số trang chính xác bằng cách dùng Python với python-docx
 */
function countPages($filepath) {
    $scriptDir = __DIR__ . '/scripts';
    $cmd = "python3 " . escapeshellarg($scriptDir . '/count_pages.py') . " " . 
           escapeshellarg($filepath) . " 2>&1";
    
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && !empty($output)) {
        $result = json_decode(implode('', $output), true);
        if (isset($result['pages'])) {
            return (int)$result['pages'];
        }
    }
    
    // Fallback: ước tính từ XML
    return estimatePagesFromXml($filepath);
}

/**
 * Ước tính số trang từ XML (fallback method)
 */
function estimatePagesFromXml($filepath) {
    try {
        $zip = new ZipArchive();
        if ($zip->open($filepath) === TRUE) {
            $docPropsApp = $zip->getFromName('docProps/app.xml');
            $zip->close();
            
            if ($docPropsApp !== false) {
                $dom = new DOMDocument();
                $dom->loadXML($docPropsApp);
                $xpath = new DOMXPath($dom);
                $xpath->registerNamespace('ep', 'http://schemas.openxmlformats.org/officeDocument/2006/extended-properties');
                
                $pages = $xpath->query('//ep:Pages');
                if ($pages->length > 0) {
                    return (int)$pages->item(0)->nodeValue;
                }
            }
        }
    } catch (Exception $e) {
        // Ignore errors, return default
    }
    
    return null; // Không xác định được
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}
?>