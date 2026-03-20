<?php
require_once __DIR__ . '/../database/conexion.php';

class Propiedad {

    public static function buscar($tipo, $ubicacion) {
        $db = conectarDB();

        $sql = "SELECT * FROM propiedades 
                WHERE tipo = :tipo
                AND ubicacion LIKE :ubicacion";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ":tipo" => $tipo,
            ":ubicacion" => "%$ubicacion%"
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}