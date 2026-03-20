<?php
require_once __DIR__ . '/../services/OpenAIService.php';
require_once __DIR__ . '/../models/Propiedad.php';
require_once __DIR__ . '/../models/UsuarioSession.php';
require_once __DIR__ . '/../services/WhatsAppService.php';

class ChatController
{

    public static function manejarMensaje($mensaje, $telefonoUsuario = 'desconocido')
    {

        if (empty($mensaje)) {
            responder("❌ Error: no recibí ningún mensaje");
            return;
        }

        // ⏰ Verificar si el usuario está en cooldown (fue contactado por asesor recientemente)
        if (UsuarioSession::estaEnCooldown($telefonoUsuario)) {
            $minutosFaltantes = UsuarioSession::minutosFaltanteCooldown($telefonoUsuario);
            $s = $minutosFaltantes == 1 ? '' : 's';
            responder(
                "⏳ Un asesor de *Inmobiliaria Serrano* se pondrá en contacto contigo en unos momentos.\n\n" .
                "⏰ Vuelve a escribir en aproximadamente *$minutosFaltantes minuto$s*.\n\n" .
                "Gracias por tu paciencia ✅"
            );
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
            // 📞 Registrar que se contactó con asesor para activar cooldown
            UsuarioSession::registrarContactoAsesor($telefonoUsuario);
            
            $respuesta = "👔 *¡Excelente!*\n\n";
            $respuesta .= "Te conectaremos con uno de nuestros ejecutivos de ventas que te brindará la mejor atención personalizada.\n\n";
            $respuesta .= "🏢 *Promotoria Serrano* - Siempre la mejor opción\n\n";
            $respuesta .= "Pronto uno de nuestros asesores se pondrá en contacto contigo. ¡Gracias por tu confianza!";
            responder($respuesta);
            return;
        }

        // Búsqueda de propiedades (flexible - puede ser solo tipo, solo ubicación, o ambas)
        $tipo = trim($datos['tipo_propiedad'] ?? '');
        $tipo = ($tipo && $tipo !== 'null' && $tipo !== '') ? $tipo : null;
        
        $ubicacion = trim($datos['ubicacion'] ?? '');
        $ubicacion = ($ubicacion && $ubicacion !== 'null' && $ubicacion !== '') ? $ubicacion : null;

        // Validar que al menos haya algo para buscar
        if (!$tipo && !$ubicacion) {
            responder("⚠️ No pude entender bien tu búsqueda.\n\n🔎 Intenta así:\n• 'Casas en Tlaxcala'\n• 'Terrenos'\n• 'Departamentos en Puebla'\n• 'Propiedades en Querétaro'");
            return;
        }

        // Buscar propiedades (sin filtro de precio)
        $resultados = Propiedad::buscar($tipo, $ubicacion);

        $resultados = Propiedad::buscar($tipo, $ubicacion);

        if (count($resultados) > 0) {
            $respuesta = "🏡 *Inmobiliaria Serrano*\n";
            $respuesta .= "✨ *Tenemos estas opciones ideales para ti* (" . count($resultados) . ")\n\n";
            
            // Mensaje personalizado según lo que se buscó
            if ($tipo && $ubicacion) {
                $respuesta .= "📢 $tipo" . "s en " . ucfirst($ubicacion) . ":\n\n";
            } elseif ($tipo) {
                $respuesta .= "📢 Todos nuestros " . $tipo . "s:\n\n";
            } elseif ($ubicacion) {
                $respuesta .= "📢 Propiedades en " . ucfirst($ubicacion) . ":\n\n";
            } else {
                $respuesta .= "📢 Nuestras mejores opciones:\n\n";
            }
            
            $fotos = [];

            foreach ($resultados as $prop) {
                $respuesta .= "📍 *{$prop['tipo']}* en {$prop['ubicacion']}\n";
                $respuesta .= "💵 \${$prop['precio']}\n";
                $respuesta .= "📝 {$prop['descripcion']}\n";
                $respuesta .= "👉 *¡Agenda tu visita hoy mismo!*\n\n";

                // Recolectar fotos si existen
                if (!empty($prop['foto'])) {
                    $fotos[] = $prop['foto'];
                }
            }
            $respuesta .= "📲 ¿Te interesa alguna propiedad?\n";
            $respuesta .= "Responde con el nombre o escribe:\n";
            $respuesta .= "👉 'Hablar con asesor'\n\n";
            $respuesta .= "🏢 *Inmobiliaria Serrano*\n";
            $respuesta .= "Tu mejor opción en bienes raíces ✅";

            responder($respuesta, $fotos);
        } else {
            $respuesta = "😕 No encontré propiedades que coincidan con tu búsqueda.\n\n";
            if ($tipo) {
                $respuesta .= "• Buscaste: *" . ucfirst($tipo) . "s*";
            }
            if ($ubicacion) {
                $respuesta .= ($tipo ? "" : "• Buscaste: ") . ($tipo ? " en " : "") . "*" . ucfirst($ubicacion) . "*\n";
            }
            $respuesta .= "\n🔍 Intenta con:\n";
            $respuesta .= "• Otra ubicación\n";
            $respuesta .= "• Otro tipo de propiedad\n";
            $respuesta .= "• O habla con un asesor";

            responder($respuesta);
        }
    }
}
