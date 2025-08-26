#!/bin/bash

# XXE Excel Lab - Script de Inicio
# Este script inicia el servidor web local para el laboratorio XXE

echo "======================================================"
echo "🧪 XXE Excel Parser Lab - Iniciando Servidor"
echo "======================================================"

# Verificar que PHP está instalado
if ! command -v php &> /dev/null; then
    echo "❌ Error: PHP no está instalado"
    echo "Por favor instala PHP primero"
    exit 1
fi

echo "✅ PHP encontrado: $(php -v | head -n1)"

# Verificar extensiones necesarias
echo "🔍 Verificando extensiones PHP..."

php -m | grep -q "zip" || { echo "❌ Extensión 'zip' no encontrada"; exit 1; }
php -m | grep -q "xml" || { echo "❌ Extensión 'xml' no encontrada"; exit 1; }
php -m | grep -q "SimpleXML" || { echo "❌ Extensión 'SimpleXML' no encontrada"; exit 1; }

echo "✅ Todas las extensiones necesarias están disponibles"

# Crear directorios si no existen
mkdir -p uploads logs

# Configurar permisos
chmod 755 uploads logs
chmod 644 *.php *.html css/*.css

echo "✅ Permisos configurados"

# Obtener dirección IP local
LOCAL_IP=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -n1)

echo "======================================================"
echo "🚀 Iniciando servidor web PHP..."
echo "======================================================"
echo "📍 URL Local: http://localhost:8080"
echo "🌐 URL Red Local: http://$LOCAL_IP:8080"
echo "======================================================"
echo "⚠️  DISCLAIMER: Solo para uso educativo y laboratorios"
echo "======================================================"
echo ""
echo "Presiona Ctrl+C para detener el servidor"
echo ""

# Iniciar servidor PHP
php -S 0.0.0.0:8080
