<?php
require_once __DIR__ . '/../services/OpenAIService.php';
require_once __DIR__ . '/../models/Propiedad.php';
require_once __DIR__ . '/../services/WhatsAppService.php';

class ChatController {

    public static function manejarMensaje($mensaje) {
        
        if (empty($mensaje)) {
            responder("❌ Error: no recibí ningún mensaje");
            return;
        }

        $datos = interpretarMensaje($mensaje);
        
        // Validar respuesta de OpenAI
        if (!is_array($datos) || isset($datos['error'])) {
            responder("❌ Error procesando tu solicitud: " . ($datos['error'] ?? 'Desconocido'));
            return;
        }

        // Verificar si el usuario quiere contactar con un ejecutivo
        if (isset($datos['tipo_accion']) && $datos['tipo_accion'] === 'contacto_ejecutivo') {
            $respuesta = "👔 *¡Excelente!*\n\n";
            $respuesta .= "Te conectaremos con uno de nuestros ejecutivos de ventas que te brindará la mejor atención personalizada.\n\n";
            $respuesta .= "🏢 *Promotoria Serrano* - Siempre la mejor opción\n\n";
            $respuesta .= "Pronto uno de nuestros asesores se pondrá en contacto contigo. ¡Gracias por tu confianza!";
            responder($respuesta);
            return;
        }

        // Búsqueda de propiedades
        $tipo = trim($datos['tipo_propiedad'] ?? '') ?: null;
        $ubicacion = trim($datos['ubicacion'] ?? '') ?: null;

        // Validar que al menos tipo y ubicación estén presentes
        if (!$tipo || !$ubicacion) {
            responder("⚠️ No pude entender bien tu búsqueda. Intenta así:\n'Busco una casa en [lugar]'");
            return;
        }

        // Buscar propiedades (sin filtro de precio)
        $resultados = Propiedad::buscar($tipo, $ubicacion);

        if (count($resultados) > 0) {
            $respuesta = "🏡 *Opciones encontradas:* (" . count($resultados) . ")\n\n";
            $fotos = [];

            foreach ($resultados as $prop) {
                $respuesta .= "📍 *{$prop['tipo']}* en {$prop['ubicacion']}\n";
                $respuesta .= "💵 \${$prop['precio']}\n";
                $respuesta .= "📝 {$prop['descripcion']}\n";
                $respuesta .= "---\n";
                
                // Recolectar fotos si existen
                if (!empty($prop['foto'])) {
                    $fotos[] = $prop['foto'];
                }
            }
            
            responder($respuesta, $fotos);

        } else {
            $respuesta = "😕 No encontré propiedades que coincidan con:\n";
            $respuesta .= "• Tipo: $tipo\n";
            $respuesta .= "• Ubicación: $ubicacion\n\n";
            $respuesta .= "¿Quieres buscar en otro lugar?";
            
            responder($respuesta);
        }
    }
}