<?php
/**
 * Excel Parser Lab - Vulnerable XXE Processing Script
 * 
 * DISCLAIMER: This script contains intentional vulnerabilities for educational 
 * purposes and authorized penetration testing only. DO NOT use in production.
 * 
 * Vulnerability: XXE (XML External Entity) Injection
 * The script is intentionally vulnerable to XXE attacks for learning purposes.
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('LOG_FILE', __DIR__ . '/logs/processing.log');

/**
 * Log function for debugging and monitoring
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Security headers (minimal for demo purposes)
 */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

/**
 * Handle file upload and processing
 */
function processUpload() {
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al subir el archivo'];
    }
    
    $file = $_FILES['excel_file'];
    $fileName = $file['name'];
    $tmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    
    // Basic validation
    if ($fileSize > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande (máximo 10MB)'];
    }
    
    $allowedExtensions = ['xlsx', 'xlsm'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Formato de archivo no válido. Solo se permiten .xlsx y .xlsm'];
    }
    
    // Generate unique filename
    $uploadFileName = uniqid('excel_', true) . '.' . $fileExtension;
    $uploadPath = UPLOAD_DIR . $uploadFileName;
    
    // Move uploaded file
    if (!move_uploaded_file($tmpPath, $uploadPath)) {
        return ['success' => false, 'message' => 'Error al guardar el archivo'];
    }
    
    logMessage("File uploaded: {$fileName} -> {$uploadFileName}");
    
    // Process the Excel file
    $result = parseExcelFile($uploadPath, $fileName);
    
    // Clean up uploaded file
    unlink($uploadPath);
    
    return $result;
}

/**
 * Parse Excel file (VULNERABLE TO XXE)
 * This function intentionally contains XXE vulnerabilities for educational purposes
 */
function parseExcelFile($filePath, $originalName) {
    $result = [
        'success' => true,
        'filename' => $originalName,
        'sheets' => [],
        'metadata' => [],
        'xml_content' => [],
        'vulnerabilities_detected' => []
    ];
    
    try {
        // Create temporary directory for extraction
        $tempDir = sys_get_temp_dir() . '/excel_' . uniqid();
        if (!mkdir($tempDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio temporal');
        }
        
        // Extract ZIP file (Excel is actually a ZIP file)
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== TRUE) {
            throw new Exception('No se pudo abrir el archivo Excel como ZIP');
        }
        
        $zip->extractTo($tempDir);
        $zip->close();
        
        logMessage("Excel file extracted to: {$tempDir}");
        
        // Process different XML files within the Excel
        $xmlFiles = [
            'xl/workbook.xml' => 'Workbook Structure',
            'xl/worksheets/sheet1.xml' => 'Sheet 1 Data',
            'xl/sharedStrings.xml' => 'Shared Strings',
            'docProps/core.xml' => 'Core Properties',
            'docProps/app.xml' => 'Application Properties'
        ];
        
        foreach ($xmlFiles as $xmlFile => $description) {
            $xmlPath = $tempDir . '/' . $xmlFile;
            if (file_exists($xmlPath)) {
                logMessage("Processing XML file: {$xmlFile}");
                
                // VULNERABLE XML PARSING - INTENTIONALLY ALLOWS XXE
                $xmlResult = parseXMLVulnerable($xmlPath, $description);
                if ($xmlResult) {
                    $result['xml_content'][] = $xmlResult;
                }
            }
        }
        
        // Extract sheet information (if workbook.xml was processed successfully)
        if (file_exists($tempDir . '/xl/workbook.xml')) {
            $result['sheets'] = extractSheetInfo($tempDir . '/xl/workbook.xml');
        }
        
        // Extract metadata
        if (file_exists($tempDir . '/docProps/core.xml')) {
            $result['metadata'] = extractMetadata($tempDir . '/docProps/core.xml');
        }
        
        // Clean up temp directory
        deleteDirectory($tempDir);
        
        logMessage("Excel processing completed successfully");
        
    } catch (Exception $e) {
        logMessage("Error processing Excel: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al procesar el archivo Excel: ' . $e->getMessage()
        ];
    }
    
    return $result;
}

/**
 * VULNERABLE XML Parser - INTENTIONALLY ALLOWS XXE ATTACKS
 * 
 * This function is deliberately vulnerable to XXE for educational purposes.
 * It does NOT use libxml_disable_entity_loader() or other protections.
 */
function parseXMLVulnerable($xmlPath, $description) {
    try {
        $xmlContent = file_get_contents($xmlPath);
        
        // VULNERABILITY: Create DOMDocument without disabling external entities
        $dom = new DOMDocument();
        
        // VULNERABILITY: Allow external entities (this is the dangerous part)
        $dom->resolveExternals = true;
        $dom->substituteEntities = true;
        
        // VULNERABILITY: Load XML without protection against XXE
        $oldValue = libxml_use_internal_errors(true);
        
        if (!$dom->loadXML($xmlContent, LIBXML_DTDLOAD | LIBXML_DTDATTR)) {
            $errors = libxml_get_errors();
            $errorMessages = array_map(function($error) {
                return $error->message;
            }, $errors);
            
            logMessage("XML parsing errors in {$xmlPath}: " . implode(', ', $errorMessages));
            libxml_clear_errors();
            libxml_use_internal_errors($oldValue);
            
            return [
                'file' => basename($xmlPath),
                'description' => $description,
                'content' => 'Error: No se pudo parsear el XML',
                'errors' => $errorMessages,
                'xxe_vulnerable' => true
            ];
        }
        
        libxml_use_internal_errors($oldValue);
        
        // Get the processed XML content (this is where XXE payloads would be executed)
        $processedContent = $dom->saveXML();
        
        logMessage("Successfully parsed XML: {$xmlPath}");
        
        return [
            'file' => basename($xmlPath),
            'description' => $description,
            'content' => $processedContent,
            'original_size' => strlen($xmlContent),
            'processed_size' => strlen($processedContent),
            'xxe_vulnerable' => true
        ];
        
    } catch (Exception $e) {
        logMessage("Exception in parseXMLVulnerable: " . $e->getMessage());
        return [
            'file' => basename($xmlPath),
            'description' => $description,
            'content' => 'Error: ' . $e->getMessage(),
            'xxe_vulnerable' => true
        ];
    }
}

/**
 * Extract sheet information from workbook.xml
 */
function extractSheetInfo($workbookPath) {
    $sheets = [];
    
    try {
        // Use simplexml for basic parsing (still vulnerable but different approach)
        $xml = simplexml_load_file($workbookPath);
        
        if ($xml && isset($xml->sheets)) {
            foreach ($xml->sheets->sheet as $sheet) {
                $sheets[] = [
                    'name' => (string)$sheet['name'],
                    'sheetId' => (string)$sheet['sheetId'],
                    'id' => (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id']
                ];
            }
        }
    } catch (Exception $e) {
        logMessage("Error extracting sheet info: " . $e->getMessage());
    }
    
    return $sheets;
}

/**
 * Extract metadata from core.xml
 */
function extractMetadata($corePath) {
    $metadata = [];
    
    try {
        $xml = simplexml_load_file($corePath);
        
        if ($xml) {
            $namespaces = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/dcmitype/');
            $xml->registerXPathNamespace('cp', 'http://schemas.openxmlformats.org/package/2006/metadata/core-properties');
            
            $metadata = [
                'creator' => (string)$xml->xpath('//dc:creator')[0] ?? '',
                'title' => (string)$xml->xpath('//dc:title')[0] ?? '',
                'description' => (string)$xml->xpath('//dc:description')[0] ?? '',
                'created' => (string)$xml->xpath('//dcterms:created')[0] ?? '',
                'modified' => (string)$xml->xpath('//dcterms:modified')[0] ?? '',
                'lastModifiedBy' => (string)$xml->xpath('//cp:lastModifiedBy')[0] ?? ''
            ];
        }
    } catch (Exception $e) {
        logMessage("Error extracting metadata: " . $e->getMessage());
    }
    
    return $metadata;
}

/**
 * Recursively delete directory
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) return false;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    
    return rmdir($dir);
}

/**
 * Create upload directory if it doesn't exist
 */
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Create logs directory if it doesn't exist
if (!is_dir(dirname(LOG_FILE))) {
    mkdir(dirname(LOG_FILE), 0755, true);
}

// Main processing logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logMessage("Processing request from IP: " . $_SERVER['REMOTE_ADDR']);
    
    $result = processUpload();
    
    if ($result['success']) {
        // Store result in session for display
        $_SESSION['processing_result'] = $result;
        header('Location: results.php');
        exit;
    } else {
        $error = $result['message'];
    }
} else {
    // Redirect to main page if accessed directly
    header('Location: index.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Excel Parser Lab</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="results-container">
            <div class="results-header">
                <h1>Error en el Procesamiento</h1>
                <a href="index.html" class="back-button">← Volver al Inicio</a>
            </div>
            
            <div class="result-section">
                <div class="error-message">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error ?? 'Error desconocido'); ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
