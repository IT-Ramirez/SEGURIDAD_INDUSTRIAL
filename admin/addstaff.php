<?php 
include("../functions.php");
include_once("../session_check.php");

if (isset($_POST['addstaff'])) {
    if (!empty($_POST['staffname']) && !empty($_POST['staffrole']) && !empty($_POST['staffpwd'])) {
        $id_empleado = $sqlconnection->real_escape_string($_POST['id_empleado']);
        $id_area = $sqlconnection->real_escape_string($_POST['area']);
        $staffname = $sqlconnection->real_escape_string($_POST['staffname']);
         $staffname = $sqlconnection->real_escape_string($_POST['staffname']);
        $staffpwd = $sqlconnection->real_escape_string($_POST['staffpwd']);
        $staffRoleID = (int)$_POST['staffrole']; // Convertir a entero

        // Opcional: hashear la contraseña
        $hashedPwd = password_hash($staffpwd, PASSWORD_DEFAULT);

        // Insert usando roleID correcto
        $addStaffQuery = "INSERT INTO tbl_users (userID,nombre_empleado, id_area,username, password, status, roleID) 
                          VALUES ('$id_empleado','{$staffname}','$id_area','{$staffname}', '{$hashedPwd}', 'Offline', {$staffRoleID})";

        if ($sqlconnection->query($addStaffQuery) === TRUE) {
            // Redirigir a staff.php
            header("Location: staff.php"); 
            exit();
        } else {
            echo "Ha ocurrido un error: " . $sqlconnection->error;
        }
    } else {
        echo "Todos los campos son obligatorios.";
    }
}
?>
