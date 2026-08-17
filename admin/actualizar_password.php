<?php
// Conexión a la base de datos
include("../dbconnection.php"); 
include_once("../session_check.php");
checkRole(['admin']);




// Obtener todos los usuarios
$query = $sqlconnection->query("SELECT UserID FROM tbl_users");

if (!$query) {
    die("Error al consultar usuarios: " . $sqlconnection->error);
}

$contador = 0;

while ($row = $query->fetch_assoc()) {

    $userID = $row['UserID'];

    // Generar hash BCRYPT basado en el UserID
    $hash = password_hash($userID, PASSWORD_BCRYPT);

    // Actualizar registro
    $update = $sqlconnection->query("
        UPDATE tbl_users 
        SET password = '$hash' 
        WHERE UserID = '$userID'
    ");

    if ($update) {
        $contador++;
    } else {
        echo "Error con $userID: " . $sqlconnection->error . "<br>";
    }
}

echo "Listo. Contraseñas actualizadas para $contador usuarios.";
?>
