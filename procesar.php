<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";       
$password = "";       
$database = "equinox_gold";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de conexión: " . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

// 1. Listar todas las tareas (con INNER JOIN para traer los nombres de gerencia y área)
if ($action === 'listar') {
    try {
        $sql = "SELECT t.id, g.nombre AS gerencia, a.nombre AS area, t.actividad, t.nivel_riesgo, t.ubicacion, t.fecha, t.tipo_ats 
                FROM tareas t
                INNER JOIN gerencias g ON t.gerencia_id = g.id
                INNER JOIN areas a ON t.area_id = a.id
                ORDER BY t.id DESC";
        $stmt = $pdo->query($sql);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// 2. Obtener todas las gerencias (para cargar el select principal)
if ($action === 'get_gerencias') {
    try {
        $stmt = $pdo->query("SELECT * FROM gerencias ORDER BY nombre ASC");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// 3. Obtener áreas filtradas por el ID de la gerencia seleccionada
if ($action === 'get_areas_por_gerencia') {
    $gerencia_id = $_GET['gerencia_id'] ?? 0;
    try {
        $stmt = $pdo->prepare("SELECT * FROM areas WHERE gerencia_id = ? ORDER BY nombre ASC");
        $stmt->execute([$gerencia_id]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// 4. Guardar una nueva tarea vinculando los IDs correspondientes
if ($action === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['gerencia_id']) || empty($_POST['area_id']) || 
        empty($_POST['ubicacion']) || empty($_POST['nivel_riesgo']) || 
        empty($_POST['fecha']) || empty($_POST['tipo_ats']) || empty($_POST['actividad'])
    ) {
        echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios."]);
        exit;
    }

    try {
        $sql = "INSERT INTO tareas (gerencia_id, area_id, ubicacion, nivel_riesgo, fecha, tipo_ats, actividad) 
                VALUES (:gerencia_id, :area_id, :ubicacion, :nivel_riesgo, :fecha, :tipo_ats, :actividad)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':gerencia_id'  => $_POST['gerencia_id'],
            ':area_id'      => $_POST['area_id'],
            ':ubicacion'    => $_POST['ubicacion'],
            ':nivel_riesgo' => $_POST['nivel_riesgo'],
            ':fecha'        => $_POST['fecha'],
            ':tipo_ats'     => $_POST['tipo_ats'],
            ':actividad'    => $_POST['actividad']
        ]);

        echo json_encode(["status" => "success", "message" => "Registro guardado correctamente."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Error al guardar: " . $e->getMessage()]);
    }
    exit;
}