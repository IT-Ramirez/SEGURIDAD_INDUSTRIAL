<?php
include("../dbconnection.php");
// Marcar todas las notificaciones como leídas
$consulta_notificaciones= "UPDATE tbl_notificaciones SET leido = 1 WHERE leido = 0";
$conexion->query($consulta_notificaciones);

$nombre_usuario= $_SESSION["username"];


echo json_encode(['success' => true]);
?>
