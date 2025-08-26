<?php
session_start();

// Check if we have results to display
if (!isset($_SESSION['processing_result'])) {
    header('Location: index.html');
    exit;
}

$result = $_SESSION['processing_result'];

// Function to format XML for display
function formatXMLForDisplay($xml, $maxLength = 2000) {
    if (strlen($xml) > $maxLength) {
        return htmlspecialchars(substr($xml, 0, $maxLength)) . "\n\n[... contenido truncado por seguridad ...]";
    }
    return htmlspecialchars($xml);
}

// Function to detect potential XXE indicators in XML
function detectXXEIndicators($xml) {
    $indicators = [];
    
    if (strpos($xml, '<!DOCTYPE') !== false) {
        $indicators[] = 'DOCTYPE declaration found';
    }
    if (strpos($xml, '<!ENTITY') !== false) {
        $indicators[] = 'Entity declaration found';
    }
    if (preg_match('/SYSTEM\s+["\']([^"\']+)["\']/', $xml, $matches)) {
        $indicators[] = 'SYSTEM entity with URI: ' . htmlspecialchars($matches[1]);
    }
    if (strpos($xml, '&xxe;') !== false) {
        $indicators[] = 'XXE entity reference found';
    }
    if (preg_match('/<x>&[^;]+;<\/x>/', $xml)) {
        $indicators[] = 'Suspicious entity reference pattern detected';
    }
    
    return $indicators;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados del Procesamiento - Excel Parser Lab</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="results-container">
            <!-- Header -->
            <div class="results-header">
                <h1>📊 Resultados del Procesamiento</h1>
                <a href="index.html" class="back-button">← Procesar Otro Archivo</a>
            </div>

            <!-- Success Message -->
            <div class="result-section">
                <div class="success-message">
                    ✅ <strong>Archivo procesado exitosamente:</strong> <?php echo htmlspecialchars($result['filename']); ?>
                </div>
            </div>

            <!-- File Information -->
            <div class="result-section">
                <h3>📋 Información del Archivo</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Nombre:</strong> <?php echo htmlspecialchars($result['filename']); ?><br>
                        <strong>Hojas encontradas:</strong> <?php echo count($result['sheets']); ?><br>
                        <strong>Archivos XML procesados:</strong> <?php echo count($result['xml_content']); ?>
                    </div>
                </div>
            </div>

            <!-- Sheets Information -->
            <?php if (!empty($result['sheets'])): ?>
            <div class="result-section">
                <h3>📄 Hojas de Cálculo</h3>
                <?php foreach ($result['sheets'] as $sheet): ?>
                <div class="info-item" style="margin-bottom: 10px; padding: 10px; background: #f8f9ff; border-radius: 6px;">
                    <strong>Nombre:</strong> <?php echo htmlspecialchars($sheet['name']); ?><br>
                    <strong>ID:</strong> <?php echo htmlspecialchars($sheet['sheetId']); ?><br>
                    <strong>Relación:</strong> <?php echo htmlspecialchars($sheet['id']); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Metadata -->
            <?php if (!empty($result['metadata'])): ?>
            <div class="result-section">
                <h3>ℹ️ Metadatos del Documento</h3>
                <div class="info-grid">
                    <?php foreach ($result['metadata'] as $key => $value): ?>
                        <?php if (!empty($value)): ?>
                        <div class="info-item">
                            <strong><?php echo ucfirst($key); ?>:</strong> <?php echo htmlspecialchars($value); ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- XML Content Analysis -->
            <div class="result-section">
                <h3>🔍 Análisis de Contenido XML</h3>
                <div class="warning-banner" style="margin-bottom: 20px;">
                    <span class="warning-icon">⚠️</span>
                    <div class="warning-content">
                        <strong>Análisis de Vulnerabilidades:</strong> Esta sección muestra el contenido XML procesado y cualquier indicador de XXE detectado.
                    </div>
                </div>
                
                <?php foreach ($result['xml_content'] as $index => $xmlData): ?>
                <div style="margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fafafa;">
                    <h4 style="color: #333; margin-bottom: 15px;">
                        📄 <?php echo htmlspecialchars($xmlData['file']); ?> 
                        <small style="color: #666;">(<?php echo htmlspecialchars($xmlData['description']); ?>)</small>
                    </h4>
                    
                    <?php
                    $indicators = detectXXEIndicators($xmlData['content']);
                    if (!empty($indicators)):
                    ?>
                    <div class="error-message" style="margin-bottom: 15px;">
                        <strong>⚠️ Indicadores XXE Detectados:</strong>
                        <ul style="margin-top: 10px; margin-left: 20px;">
                            <?php foreach ($indicators as $indicator): ?>
                            <li><?php echo htmlspecialchars($indicator); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <div class="xml-content">
                        <strong>Contenido XML Procesado:</strong><br><br>
                        <pre style="white-space: pre-wrap; word-wrap: break-word;"><?php echo formatXMLForDisplay($xmlData['content']); ?></pre>
                    </div>
                    
                    <?php if (isset($xmlData['original_size']) && isset($xmlData['processed_size'])): ?>
                    <div style="margin-top: 10px; font-size: 0.9em; color: #666;">
                        <strong>Estadísticas:</strong> 
                        Tamaño original: <?php echo number_format($xmlData['original_size']); ?> bytes | 
                        Tamaño procesado: <?php echo number_format($xmlData['processed_size']); ?> bytes
                        <?php if ($xmlData['processed_size'] != $xmlData['original_size']): ?>
                        | <span style="color: #e74c3c;">⚠️ Tamaño modificado durante el procesamiento</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($xmlData['errors']) && !empty($xmlData['errors'])): ?>
                    <div class="error-message" style="margin-top: 15px;">
                        <strong>Errores de Parsing:</strong>
                        <ul style="margin-top: 10px; margin-left: 20px;">
                            <?php foreach ($xmlData['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Security Notice -->
            <div class="result-section">
                <div class="warning-banner">
                    <span class="warning-icon">🔒</span>
                    <div class="warning-content">
                        <strong>Nota de Seguridad:</strong> 
                        Este procesador XML está configurado de forma vulnerable para fines educativos. 
                        En un entorno de producción, se deberían implementar protecciones contra XXE como 
                        <code>libxml_disable_entity_loader(true)</code> y validación estricta de entrada.
                    </div>
                </div>
            </div>

            <!-- Lab Information -->
            <div class="result-section">
                <h3>🧪 Información del Laboratorio</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <h4>Vulnerabilidades Implementadas:</h4>
                        <ul style="margin-top: 10px;">
                            <li>XXE (XML External Entity) Injection</li>
                            <li>Procesamiento XML sin sanitización</li>
                            <li>Resolución de entidades externas habilitada</li>
                            <li>Substitución de entidades habilitada</li>
                        </ul>
                    </div>
                    <div class="info-item">
                        <h4>Posibles Vectores de Ataque:</h4>
                        <ul style="margin-top: 10px;">
                            <li>Lectura de archivos locales (file://)</li>
                            <li>Requests HTTP externos</li>
                            <li>Ataques SSRF (Server-Side Request Forgery)</li>
                            <li>Denegación de servicio (DoS)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to first XXE indicator if found
        window.addEventListener('load', function() {
            const errorElements = document.querySelectorAll('.error-message');
            if (errorElements.length > 0) {
                errorElements[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html>

<?php
// Clear the result from session after displaying
unset($_SESSION['processing_result']);
?>
