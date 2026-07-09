<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "usreqxApp";
$password = "Fhj09y6mqDF#z7v";
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

$stmtInitCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'nivel_riesgo_inicial'");
$has_nivel_inicial = ($stmtInitCol && $stmtInitCol->fetch() !== false);
$stmtFinalCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'nivel_riesgo_final'");
$has_nivel_final = ($stmtFinalCol && $stmtFinalCol->fetch() !== false);

$action = $_GET['action'] ?? '';

// 1. Listar tareas con soporte de paginación opcional en la API
if ($action === 'listar') {
    try {
        // Paginación dinámica por parámetros GET
        $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
        $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $offset = ($pagina - 1) * $limite;

        // Conteo total para metadatos de la API
        $total_items = $pdo->query("SELECT COUNT(*) FROM tareas")->fetchColumn();
        $total_paginas = ceil($total_items / $limite);

        $cols = ['t.id', 'g.nombre AS gerencia', 'a.nombre AS area', 't.actividad', 't.ubicacion', 't.fecha', 't.permisos_requeridos'];
        if ($has_nivel_inicial && $has_nivel_final) {
            $cols[] = 't.nivel_riesgo_inicial';
            $cols[] = 't.nivel_riesgo_final';
        } else {
            $cols[] = 't.nivel_riesgo';
        }

        $sql = "SELECT " . implode(', ', $cols) . " FROM tareas t
                INNER JOIN gerencias g ON t.gerencia_id = g.id
                INNER JOIN areas a ON t.area_id = a.id
                ORDER BY t.id DESC LIMIT $limite OFFSET $offset";
                
        $stmt = $pdo->query($sql);
        
        echo json_encode([
            "status" => "success", 
            "paginacion" => [
                "total_registros" => (int)$total_items,
                "paginas_totales" => (int)$total_paginas,
                "pagina_actual" => $pagina,
                "limite_por_pagina" => $limite
            ],
            "data" => $stmt->fetchAll()
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_gerencias') {
    try {
        $stmt = $pdo->query("SELECT * FROM gerencias ORDER BY nombre ASC");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

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

if ($action === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nivel_riesgo_inicial = trim($_POST['nivel_riesgo_inicial'] ?? '');
    $nivel_riesgo_final = trim($_POST['nivel_riesgo_final'] ?? '');

    if (
        empty($_POST['gerencia_id']) || empty($_POST['area_id']) || 
        empty($_POST['ubicacion']) || empty($nivel_riesgo_inicial) || empty($nivel_riesgo_final) ||
        empty($_POST['fecha']) || empty($_POST['actividad'])
    ) {
        echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios."]);
        exit;
    }

    $permisos_requeridos = $_POST['permisos_requeridos'] ?? [];
    if (!is_array($permisos_requeridos)) {
        $permisos_requeridos = [$permisos_requeridos];
    }
    $permisos_requeridos_json = json_encode(array_values($permisos_requeridos), JSON_UNESCAPED_UNICODE);

    try {
        if ($has_nivel_inicial && $has_nivel_final) {
            $sql = "INSERT INTO tareas (gerencia_id, area_id, ubicacion, nivel_riesgo_inicial, nivel_riesgo_final, nivel_riesgo, fecha, permisos_requeridos, actividad)
                    VALUES (:gerencia_id, :area_id, :ubicacion, :nivel_riesgo_inicial, :nivel_riesgo_final, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':gerencia_id' => $_POST['gerencia_id'],
                ':area_id' => $_POST['area_id'],
                ':ubicacion' => $_POST['ubicacion'],
                ':nivel_riesgo_inicial' => $nivel_riesgo_inicial,
                ':nivel_riesgo_final' => $nivel_riesgo_final,
                ':nivel_riesgo' => '',
                ':fecha' => $_POST['fecha'],
                ':permisos_requeridos' => $permisos_requeridos_json,
                ':actividad' => $_POST['actividad']
            ]);
        } else {
            $nivel_riesgo = $nivel_riesgo_inicial . ' / ' . $nivel_riesgo_final;
            $sql = "INSERT INTO tareas (gerencia_id, area_id, ubicacion, nivel_riesgo, fecha, permisos_requeridos, actividad) 
                    VALUES (:gerencia_id, :area_id, :ubicacion, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':gerencia_id'  => $_POST['gerencia_id'],
                ':area_id'      => $_POST['area_id'],
                ':ubicacion'    => $_POST['ubicacion'],
                ':nivel_riesgo' => $nivel_riesgo,
                ':fecha'        => $_POST['fecha'],
                ':permisos_requeridos' => $permisos_requeridos_json,
                ':actividad'    => $_POST['actividad']
            ]);
        }

        echo json_encode(["status" => "success", "message" => "Registro guardado correctamente."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Error al guardar: " . $e->getMessage()]);
    }
    exit;
}