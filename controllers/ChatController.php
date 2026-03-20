<?php
require_once __DIR__ . '/../services/OpenAIService.php';
require_once __DIR__ . '/../models/Propiedad.php';
require_once __DIR__ . '/../services/WhatsAppService.php';

class ChatController
{

    public static function manejarMensaje($mensaje)
    {

        if (empty($mensaje)) {
            responder("❌ Error: no recibí ningún mensaje");
            return;
        }
        $mensajeLower = strtolower($mensaje);

        // Detectar si parece búsqueda (palabras clave)
        $esBusqueda = (
            strpos($mensajeLower, "casa") !== false ||
            strpos($mensajeLower, "terreno") !== false ||
            strpos($mensajeLower, "departamento") !== false ||
            strpos($mensajeLower, "en ") !== false
        );

        // Detectar saludo
        $esSaludo = (
            strpos($mensajeLower, "hola") !== false ||
            strpos($mensajeLower, "informacion") !== false ||
            strpos($mensajeLower, "info") !== false
        );

        // 👉 Si es saludo PERO no es búsqueda → mostrar bienvenida
        if ($esSaludo && !$esBusqueda) {

            $respuesta = "👋 *¡Bienvenido a Inmobiliaria Serrano!*\n\n";
            $respuesta .= "🏡 Te ayudamos a encontrar la propiedad ideal.\n\n";
            $respuesta .= "🔎 *Puedes buscar así:*\n";
            $respuesta .= "• 'Casa en Tlaxcala'\n";
            $respuesta .= "• 'Terreno en Puebla'\n";
            $respuesta .= "• 'Departamento en Querétaro'\n\n";
            $respuesta .= "💬 O escribe:\n👉 'Hablar con asesor'\n\n";
            $respuesta .= "✨ *Inmobiliaria Serrano - Siempre la mejor opción*";

            responder($respuesta);
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
            $respuesta = "🏡 *Inmobiliaria Serrano*\n";
            $respuesta .= "✨ *Tenemos estas opciones ideales para ti* (" . count($resultados) . ")\n\n";
            $respuesta .= "📢 Propiedades seleccionadas según tu búsqueda:\n\n";
            $fotos = [];

            foreach ($resultados as $prop) {
                $respuesta .= "📍 *{$prop['tipo']}* en {$prop['ubicacion']}\n";
                $respuesta .= "💵 \${$prop['precio']}\n";
                $respuesta .= "📝 {$prop['descripcion']}\n";
                $respuesta .= "👉 *¡Agenda tu visita hoy mismo!*\n";

                // Recolectar fotos si existen
                if (!empty($prop['foto'])) {
                    $fotos[] = $prop['foto'];
                }
            }
            $respuesta .= "\n📲 ¿Te interesa alguna propiedad?\n";
            $respuesta .= "Responde con el nombre o escribe:\n";
            $respuesta .= "👉 'Hablar con asesor'\n\n";
            $respuesta .= "🏢 *Inmobiliaria Serrano*\n";
            $respuesta .= "Tu mejor opción en bienes raíces ✅";

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
