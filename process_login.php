<?php
include("dbconnection.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "error";
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo "incorrecto";
    exit;
}

$stmt = $sqlconnection->prepare(
    "SELECT userID, nombre_empleado, username, email, password, roleID, id_area 
     FROM tbl_users 
     WHERE email = ? 
     LIMIT 1"
);

if (!$stmt) {
    echo "error";
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    if (password_verify($password, $row['password'])) {

        session_start();
        session_regenerate_id(true);

        $_SESSION['uid'] = $row['userID'];
        $_SESSION['nombre_empleado'] = $row['nombre_empleado'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['roleID'] = (int)$row['roleID'];
        $_SESSION['admin_area_id'] = (int)$row['id_area'];
        $_SESSION['last_activity'] = time();

        switch ((int)$row['roleID']) {
            case 1:
                $_SESSION['user_role']  = "admin";
                $_SESSION['user_level'] = "Administrador";
                $respuesta = "admin";
                break;

            case 2:
                $_SESSION['user_role']  = "cocinero";
                $_SESSION['user_level'] = "Cocinero";
                $respuesta = "cocinero";
                break;

            case 3:
                $_SESSION['user_role']  = "empleado";
                $_SESSION['user_level'] = "Empleado";
                $respuesta = "empleado";
                break;

            default:
                session_unset();
                session_destroy();
                echo "rol_invalido";
                exit;
        }

        $up = $sqlconnection->prepare(
            "UPDATE tbl_users SET status = 'Online' WHERE userID = ?"
        );

        if ($up) {
            $up->bind_param("i", $row['userID']);
            $up->execute();
            $up->close();
        }

        echo $respuesta;
        exit;
    }
}

echo "incorrecto";
exit;