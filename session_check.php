<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

/* =========================================================
   CONTROL DE INACTIVIDAD
   ========================================================= */

$tiempoMaximo = 10800;

if (isset($_SESSION['last_activity'])) {

    if (time() - $_SESSION['last_activity'] > $tiempoMaximo) {

        if (isset($_SESSION['uid'])) {
            require_once(__DIR__ . "/functions.php");

            $userID = (int) $_SESSION['uid'];

            if (isset($sqlconnection)) {
                $stmt = $sqlconnection->prepare(
                    "UPDATE tbl_users SET status = 'Offline' WHERE userID = ?"
                );

                if ($stmt) {
                    $stmt->bind_param("i", $userID);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        $_SESSION = [];

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

        session_destroy();

        header("Location: /login.php");
        exit();
    }
}

$_SESSION['last_activity'] = time();

/* =========================================================
   SEGURIDAD DE SESIÓN
   ========================================================= */

if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

$required_keys = ['uid', 'username', 'email', 'user_role', 'user_level'];

foreach ($required_keys as $key) {
    if (empty($_SESSION[$key])) {
        header("Location: /login.php");
        exit();
    }
}

function checkRole(array $rolesPermitidos): void {
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $rolesPermitidos, true)) {

        $_SESSION = [];

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

        session_destroy();

        header("Location: /login.php");
        exit();
    }
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");