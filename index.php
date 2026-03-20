<?php
header("Content-Type: text/xml; charset=utf-8");

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/ChatController.php';

// Validar que es POST de Twilio
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método no permitido";
    exit;
}

// Obtener el mensaje y número de teléfono de WhatsApp (vía Twilio)
$mensaje = $_POST['Body'] ?? '';
$telefonoUsuario = $_POST['From'] ?? 'desconocido'; // Número de Twilio format: whatsapp:+XXXXXXXXXXX

// Trim y validar
$mensaje = trim($mensaje);

if (empty($mensaje)) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><Response><Message>Envía un mensaje</Message></Response>";
    exit;
}

// Procesar el mensaje con el número de teléfono
ChatController::manejarMensaje($mensaje, $telefonoUsuario);
