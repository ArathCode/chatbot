<?php
function responder($mensaje, $fotos = []) {

    $mensaje_safe = htmlspecialchars($mensaje, ENT_XML1, 'UTF-8');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        header("Content-Type: text/xml; charset=utf-8");

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        echo "<Response>";

        // 🔥 Si hay fotos → manda texto + UNA imagen
        if (!empty($fotos) && is_array($fotos)) {

            $foto_safe = htmlspecialchars($fotos[0], ENT_XML1, 'UTF-8');

            echo "<Message>";
            echo "<Body>$mensaje_safe</Body>";
            echo "<Media>$foto_safe</Media>";
            echo "</Message>";

        } else {
            // Solo texto
            echo "<Message>$mensaje_safe</Message>";
        }

        echo "</Response>";

    } else {
        // Debug navegador
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