<?php
/**
 * Simula un mensaje de Twilio/WhatsApp
 * Útil para testing sin números reales
 */

// Simular que viene POST de Twilio
$_SERVER['REQUEST_METHOD'] = 'POST';
//$_POST['Body'] = 'Busco una Casa en Tlaxcala de hasta 950000 pesos';
$_POST['Body'] = 'Hola informacion ';
$_POST['From'] = 'whatsapp:+5219999999999';
$_POST['To'] = 'whatsapp:+525512345678';

// Incluir el index
require_once __DIR__ . '/index.php';
