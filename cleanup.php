<?php
/**
 * CLEANUP.PHP
 * Script để xóa các file cũ (chạy bằng cron job)
 * 
 */

define('TEMP_DIR', 'temp/');
define('UPLOAD_DIR', 'uploads/');
define('MAX_AGE', 3600); // 1 hour in seconds

$deletedCount = 0;
$totalSize = 0;

// Clean uploads directory
if (is_dir(UPLOAD_DIR)) {
    $files = glob(UPLOAD_DIR . '*');
    foreach ($files as $file) {
        if (is_file($file) && (time() - filemtime($file)) > MAX_AGE) {
            $totalSize += filesize($file);
            unlink($file);
            $deletedCount++;
        }
    }
}

// Clean temp directory
if (is_dir(TEMP_DIR)) {
    // Delete old analysis files
    $analysisFiles = glob(TEMP_DIR . '*_analysis.json');
    foreach ($analysisFiles as $file) {
        if ((time() - filemtime($file)) > MAX_AGE) {
            $data = json_decode(file_get_contents($file), true);
            
            // Delete associated files
            if (isset($data['upload_path']) && file_exists($data['upload_path'])) {
                $totalSize += filesize($data['upload_path']);
                unlink($data['upload_path']);
            }
            
            if (isset($data['fixed_path']) && file_exists($data['fixed_path'])) {
                $totalSize += filesize($data['fixed_path']);
                unlink($data['fixed_path']);
            }
            
            if (isset($data['unpack_dir']) && is_dir($data['unpack_dir'])) {
                deleteDirectory($data['unpack_dir']);
            }
            
            unlink($file);
            $deletedCount++;
        }
    }
    
    // Delete old directories
    $dirs = glob(TEMP_DIR . 'doc_*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        if ((time() - filemtime($dir)) > MAX_AGE) {
            deleteDirectory($dir);
        }
    }
}

// Log cleanup
$logMessage = sprintf(
    "[%s] Cleanup completed: %d files deleted, %s freed\n",
    date('Y-m-d H:i:s'),
    $deletedCount,
    formatBytes($totalSize)
);

file_put_contents('cleanup.log', $logMessage, FILE_APPEND);

echo $logMessage;

function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>