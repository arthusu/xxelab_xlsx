# Excel Parser Lab - XXE Vulnerability Demo

## 🎯 Propósito

Esta aplicación web ha sido diseñada específicamente para demostrar vulnerabilidades XXE (XML External Entity) en el contexto del procesamiento de archivos Excel (.xlsx). Es perfecta para:

- Práctica de penetration testing
- Educación en ciberseguridad
- Pruebas de herramientas de XXE injection

## ⚠️ DISCLAIMER

**ADVERTENCIA DE SEGURIDAD:** Esta aplicación contiene vulnerabilidades intencionales y NO debe usarse en entornos de producción. Está diseñada exclusivamente para fines educativos y pruebas de penetración autorizadas.

## 🏗️ Arquitectura

La aplicación simula un sistema corporativo de procesamiento de archivos Excel con las siguientes características:

- **Frontend**: Interfaz web moderna con HTML5, CSS3 y JavaScript
- **Backend**: PHP con procesamiento XML vulnerable
- **Funcionalidad**: Descompresión y análisis de archivos OOXML
- **Vulnerabilidad**: XXE injection en múltiples archivos XML dentro del Excel

## 📁 Estructura del Proyecto

```
xxe_lab_app/
├── index.html          # Página principal de upload
├── process.php         # Script vulnerable de procesamiento
├── results.php         # Página de resultados
├── css/
│   └── style.css       # Estilos de la aplicación
├── uploads/            # Directorio temporal para archivos
├── logs/               # Logs de procesamiento
└── README.md          # Esta documentación
```

## 🚀 Instalación y Setup

### Requisitos

- PHP 7.4 o superior
- Extensiones PHP necesarias:
  - `zip` (para manipular archivos .xlsx)
  - `xml` (para procesamiento XML)
  - `libxml` (para parsing XML)
  - `simplexml` (para análisis XML)

### Instalación Local

1. **Clonar/Copiar los archivos:**
   ```bash
   
   cd xxe_lab_app
   ```

2. **Configurar servidor web local:**
   ```bash
   # Opción 1: Servidor PHP integrado (recomendado para testing)
   php -S localhost:8080
   
   # Opción 2: Apache/Nginx
   # Copiar la carpeta al directorio web del servidor
   ```

3. **Verificar permisos:**
   ```bash
   chmod 755 uploads/ logs/
   chmod 644 *.php *.html css/*.css
   ```

4. **Acceder a la aplicación:**
   - Abrir navegador en: `http://localhost:8080`

### Instalación en Servidor Web

```bash
# Para Apache/Nginx
sudo cp -r xxe_lab_app/ /var/www/html/xxe-lab/
sudo chown -R www-data:www-data /var/www/html/xxe-lab/
sudo chmod 755 /var/www/html/xxe-lab/uploads /var/www/html/xxe-lab/logs
```

### Paso 1: Probar la Vulnerabilidad
1. Abrir la aplicación web
2. Subir el archivo `malicious.xlsx`
3. Observar los resultados y indicadores XXE
4. Verificar logs/requests en tu servidor colaborator

## 🔍 Análisis de Resultados

La aplicación web mostrará:

### Indicadores XXE Detectados
- `DOCTYPE declaration found`
- `Entity declaration found`
- `SYSTEM entity with URI: [URL]`
- `XXE entity reference found`
- `Suspicious entity reference pattern detected`

### Información Mostrada
- Contenido XML procesado
- Metadatos del archivo
- Estructura de las hojas
- Estadísticas de procesamiento
- Errores y warnings

## 🛡️ Vulnerabilidades Implementadas

### XXE en process.php

```php
// VULNERABLE: Sin protección contra XXE
$dom = new DOMDocument();
$dom->resolveExternals = true;      // ⚠️ PELIGROSO
$dom->substituteEntities = true;    // ⚠️ PELIGROSO
$dom->loadXML($xmlContent, LIBXML_DTDLOAD | LIBXML_DTDATTR);
```

### Archivos XML Objetivo
- `xl/workbook.xml` - Estructura principal (más común)
- `xl/worksheets/sheet1.xml` - Datos de la hoja
- `xl/sharedStrings.xml` - Cadenas compartidas
- `docProps/core.xml` - Propiedades del documento
- `docProps/app.xml` - Propiedades de la aplicación

## 🔧 Personalización

### Modificar Targets XML
Editar en `process.php` la variable `$xmlFiles`:
```php
$xmlFiles = [
    'xl/workbook.xml' => 'Workbook Structure',
    'custom/file.xml' => 'Custom Target',
    // Agregar más targets...
];
```

### Ajustar Logging
```php
// En process.php
define('LOG_FILE', __DIR__ . '/logs/custom.log');
```

### Modificar Límites
```php
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB
```

## 🐛 Troubleshooting

### Error: "No se pudo abrir el archivo Excel como ZIP"
- Verificar que el archivo sea un .xlsx válido
- Asegurar que la extensión ZIP esté instalada en PHP

### Error de permisos
```bash
sudo chown -R www-data:www-data uploads/ logs/
sudo chmod 755 uploads/ logs/
```

### PHP no encuentra libxml
```bash
# Ubuntu/Debian
sudo apt-get install php-xml php-zip

# CentOS/RHEL
sudo yum install php-xml php-zip
```

### XXE no funciona
1. Verificar que el payload se inyectó correctamente
2. Verificar logs del servidor colaborator
3. Probar con diferentes archivos XML target
4. Verificar que PHP no tenga protecciones adicionales

## 📚 Recursos Adicionales

### Referencias Técnicas
- [OWASP XXE Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/XML_External_Entity_Prevention_Cheat_Sheet.html)
- [4ARMED Blog - Exploiting XXE with Excel](https://www.4armed.com/blog/exploiting-xxe-with-excel/)
- [PortSwigger XXE Lab](https://portswigger.net/web-security/xxe)

### Herramientas Relacionadas
- Burp Suite (Collaborator)
- Wireshark (para análisis de red)
- tcpdump (para captura de tráfico)

## 🎓 Objetivos de Aprendizaje

Después de completar este laboratorio, deberías poder:

1. **Identificar** vulnerabilidades XXE en aplicaciones web
2. **Explotar** XXE para:
   - Leer archivos locales
   - Realizar requests HTTP externos
   - Ejecutar ataques SSRF
3. **Crear** payloads XXE personalizados
4. **Mitigar** vulnerabilidades XXE implementando:
   - `libxml_disable_entity_loader(true)`
   - Validación de entrada
   - Sanitización de XML

## 🔐 Remediación (Para Referencia)

### Código Seguro (NO implementado en este lab)
```php
// SEGURO: Deshabilitar entidades externas
libxml_disable_entity_loader(true);

$dom = new DOMDocument();
$dom->resolveExternals = false;
$dom->substituteEntities = false;

// Usar LIBXML_NONET para deshabilitar acceso a red
$dom->loadXML($xmlContent, LIBXML_NONET);
```

## 📞 Soporte

Este laboratorio ha sido diseñado para ser autoexplicativo. Para debugging:

1. Verificar logs en `logs/processing.log`
2. Usar herramientas de desarrollador del navegador
3. Revisar error logs de PHP
4. Verificar la configuración del servidor colaborator

---

**Happy Hacking! 🔥** 

*Recuerda: Solo utiliza estas técnicas en entornos autorizados como tu propio laboratorio.*
