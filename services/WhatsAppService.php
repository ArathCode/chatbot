<?php
function responder($mensaje, $fotos = []) {

    // Escaper XML para caracteres especiales
    $mensaje_safe = htmlspecialchars($mensaje, ENT_XML1, 'UTF-8');

    // Detecta si viene de Twilio (POST real)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        header("Content-Type: text/xml; charset=utf-8");

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        echo "<Response>";
        
        // Enviar mensaje de texto
        echo "<Message>$mensaje_safe</Message>";
        
        // Enviar fotos si existen
        if (!empty($fotos) && is_array($fotos)) {
            foreach ($fotos as $foto_url) {
                if (!empty($foto_url)) {
                    $foto_safe = htmlspecialchars($foto_url, ENT_XML1, 'UTF-8');
                    echo "<Message><Media>$foto_safe</Media></Message>";
                }
            }
        }
        
        echo "</Response>";

    } else {
        // Para navegador o pruebas
        header("Content-Type: text/plain; charset=utf-8");

        echo $mensaje;
        
        if (!empty($fotos)) {
            echo "\n\nFotos:\n";
            foreach ($fotos as $foto) {
                echo "- $foto\n";
            }
        }
    }
}