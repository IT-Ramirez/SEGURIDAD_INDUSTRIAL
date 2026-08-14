<?php
// Conexión a la base de datos
include("../dbconnection.php");

// Obtener el número de notificaciones no leídas
$sql = "SELECT COUNT(*) AS total_vencidos
FROM tbl_menuitem
WHERE fecha_vencimiento < CURDATE();
";
$resultado = $sqlconnection->query($sql);
$total_no_leidas = $resultado->fetch_assoc()['total_no_leidas'];

echo json_encode(['total_no_leidas' => $total_no_leidas]);
?>
