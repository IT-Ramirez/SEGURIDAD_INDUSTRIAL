<?php
include("functions.php");

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔥 MARCAR USUARIO COMO OFFLINE (ANTES de destruir sesión)
if (isset($_SESSION['uid'])) {
    $userID = (int) $_SESSION['uid'];

    $stmt = $sqlconnection->prepare(
        "UPDATE tbl_users SET status = 'Offline' WHERE userID = ?"
    );
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->close();
}

// Limpiar todas las variables de sesión
$_SESSION = [];

// Si existe una cookie de sesión, eliminarla del cliente
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Evitar caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirigir al login
header("Location: ./login.php");
exit;
?>
