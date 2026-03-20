<?php
require_once __DIR__ . '/../database/conexion.php';

class UsuarioSession {

    const COOLDOWN_MINUTOS = 30; // Tiempo de desactivación: 30 minutos

    /**
     * Registra que un usuario se contactó con un asesor
     */
    public static function registrarContactoAsesor($telefonoUsuario) {
        $db = conectarDB();
        
        // Crear tabla si no existe
        self::crearTablaIfNotExists($db);
        
        $sql = "INSERT INTO usuarios_contactados (telefono, fecha_contacto) 
                VALUES (:telefono, NOW())
                ON DUPLICATE KEY UPDATE fecha_contacto = NOW()";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':telefono' => $telefonoUsuario]);
    }

    /**
     * Verifica si un usuario está en cooldown (no debe hablar con el bot)
     */
    public static function estaEnCooldown($telefonoUsuario) {
        $db = conectarDB();
        
        // Crear tabla si no existe
        self::crearTablaIfNotExists($db);
        
        $sql = "SELECT fecha_contacto FROM usuarios_contactados 
                WHERE telefono = :telefono 
                ORDER BY fecha_contacto DESC 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':telefono' => $telefonoUsuario]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            return false; // Usuario nuevo, sin cooldown
        }
        
        $fechaContacto = strtotime($resultado['fecha_contacto']);
        $ahora = time();
        $minutosTranscurridos = ($ahora - $fechaContacto) / 60;
        
        // Está en cooldown si pasaron menos de COOLDOWN_MINUTOS
        return $minutosTranscurridos < self::COOLDOWN_MINUTOS;
    }

    /**
     * Obtiene cuántos minutos faltan para que termine el cooldown
     */
    public static function minutosFaltanteCooldown($telefonoUsuario) {
        $db = conectarDB();
        
        $sql = "SELECT fecha_contacto FROM usuarios_contactados 
                WHERE telefono = :telefono 
                ORDER BY fecha_contacto DESC 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':telefono' => $telefonoUsuario]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            return 0;
        }
        
        $fechaContacto = strtotime($resultado['fecha_contacto']);
        $ahora = time();
        $minutosTranscurridos = ($ahora - $fechaContacto) / 60;
        $minutosFaltantes = ceil(self::COOLDOWN_MINUTOS - $minutosTranscurridos);
        
        return max(0, $minutosFaltantes);
    }

    /**
     * Crea la tabla si no existe
     */
    private static function crearTablaIfNotExists($db) {
        $sql = "CREATE TABLE IF NOT EXISTS usuarios_contactados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            telefono VARCHAR(20) UNIQUE NOT NULL,
            fecha_contacto DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_telefono (telefono)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
    }
}
