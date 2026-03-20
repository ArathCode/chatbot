<?php
require_once __DIR__ . '/../database/conexion.php';

class Propiedad {

    public static function buscar($tipo = null, $ubicacion = null) {
        $db = conectarDB();

        $sql = "SELECT * FROM propiedades WHERE 1=1";
        $params = [];

        // Si hay tipo específico, filtra por él
        if ($tipo && $tipo !== 'null' && $tipo !== '') {
            $sql .= " AND (tipo = :tipo OR tipo LIKE :tipo_like)";
            $params[":tipo"] = $tipo;
            $params[":tipo_like"] = "%" . $tipo . "%";
        }

        // Si hay ubicación específica, filtra por ella
        if ($ubicacion && $ubicacion !== 'null' && $ubicacion !== '') {
            $sql .= " AND ubicacion LIKE :ubicacion";
            $params[":ubicacion"] = "%" . $ubicacion . "%";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}