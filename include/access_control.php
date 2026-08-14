<?php
// Verificar que haya sesión activa
if (!isset($_SESSION['user_role'])) {
    header("Location: ../login.php");
    exit();
}

// Roles permitidos para la página actual
// Cambiar según la página
$allowed_roles = ['admin', 'chef', 'empleado']; 

if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: ../login.php");
    exit();
}
?>
