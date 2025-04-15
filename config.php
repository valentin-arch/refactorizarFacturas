<?php
$host = '192.168.10.204';
$usuario = 'desarrollo';
$contrasena = 'desarrollosoporte975';
$base_datos = 'clubtop';
$base_datos2 = 'facturacion';

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);
$conexion2 = new mysqli($host, $usuario, $contrasena, $base_datos2);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Opcional: configurar charset
$conexion->set_charset("utf8");
?>