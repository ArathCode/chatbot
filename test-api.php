<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/services/OpenAIService.php';
require_once __DIR__ . '/models/Propiedad.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Solo POST']);
    exit;
}

// Leer JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['mensaje']) || empty($input['mensaje'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta "mensaje" en el JSON']);
    exit;
}

$mensaje = trim($input['mensaje']);

// Paso 1: Extraer datos con OpenAI
$datos = interpretarMensaje($mensaje);

// Paso 2: Buscar propiedades
$resultados = [];
if (!isset($datos['error'])) {
    $tipo = trim($datos['tipo_propiedad'] ?? '') ?: null;
    $ubicacion = trim($datos['ubicacion'] ?? '') ?: null;
    
    if ($tipo && $ubicacion) {
        $resultados = Propiedad::buscar($tipo, $ubicacion);
    }
}

// Retornar resultado completo
echo json_encode([
    'mensaje_original' => $mensaje,
    'datos_extraidos' => $datos,
    'propiedades_encontradas' => count($resultados),
    'propiedades' => $resultados
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
