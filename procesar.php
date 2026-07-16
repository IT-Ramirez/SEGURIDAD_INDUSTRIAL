<?php
header('Content-Type: application/json');
include 'config.php';

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
$stmtEstadoCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'estado'");
$has_estado = ($stmtEstadoCol && $stmtEstadoCol->fetch() !== false);

$action = $_GET['action'] ?? '';

// ACTUALIZACIÓN ÚNICAMENTE DEL ESTADO DESDE EL PANEL
if ($action === 'actualizar_estado' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $nuevo_estado = trim($_POST['estado'] ?? '');
    $token = trim($_POST['edit_token'] ?? '');

    if (!$has_estado) {
        echo json_encode(["status" => "error", "message" => "La columna de estado no está disponible en la base de datos."]);
        exit;
    }

    $stmtEditTokenCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'edit_token'");
    $has_edit_token = ($stmtEditTokenCol && $stmtEditTokenCol->fetch() !== false);

    if (!$has_edit_token) {
        echo json_encode(["status" => "error", "message" => "No existe el token de autorización para esta tarea."]);
        exit;
    }

    if ($id <= 0 || $token === '' || !in_array($nuevo_estado, ['En desarrollo', 'Finalizado', 'finalizado'], true)) {
        echo json_encode(["status" => "error", "message" => "Se requiere el token de autorización para modificar el estado."]);
        exit;
    }

    $estado_guardado = ($nuevo_estado === 'Finalizado' || $nuevo_estado === 'finalizado') ? 'Finalizado' : 'En desarrollo';

    try {
        $stmtOwner = $pdo->prepare("SELECT id FROM tareas WHERE id = ? AND edit_token = ? LIMIT 1");
        $stmtOwner->execute([$id, $token]);

        if ($stmtOwner->fetch() === false) {
            echo json_encode(["status" => "error", "message" => "Token de autorización inválido."]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tareas SET estado = ? WHERE id = ? AND edit_token = ?");
        $stmt->execute([$estado_guardado, $id, $token]);
        echo json_encode(["status" => "success", "message" => "Estado actualizado correctamente."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Error al actualizar: " . $e->getMessage()]);
    }
    exit;
}

// 1. Listar tareas con soporte de paginación opcional en la API
if ($action === 'listar') {
    try {
        $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
        $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $offset = ($pagina - 1) * $limite;

        $total_items = $pdo->query("SELECT COUNT(*) FROM tareas")->fetchColumn();
        $total_paginas = ceil($total_items / $limite);

        $cols = ['t.id', 'g.nombre AS gerencia', 'a.nombre AS area', 't.actividad', 't.ubicacion', 't.responsable', 't.fecha', 't.permisos_requeridos', 't.nivel_riesgo'];
        if ($has_estado) {
            $cols[] = 't.estado';
        }
        if ($has_nivel_inicial && $has_nivel_final) {
            $cols[] = 't.nivel_riesgo_inicial';
            $cols[] = 't.nivel_riesgo_final';
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

if ($action === 'estados') {
    if (!$has_estado) {
        echo json_encode(["status" => "error", "message" => "La columna de estado no está disponible en la base de datos."]);
        exit;
    }

    try {
        $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
        $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $offset = ($pagina - 1) * $limite;

        $conditions = [];
        $params = [];
        if (isset($_GET['filtro_gerencia']) && intval($_GET['filtro_gerencia']) > 0) {
            $conditions[] = 't.gerencia_id = ?';
            $params[] = intval($_GET['filtro_gerencia']);
        }
        if (isset($_GET['filtro_area']) && intval($_GET['filtro_area']) > 0) {
            $conditions[] = 't.area_id = ?';
            $params[] = intval($_GET['filtro_area']);
        }

        $whereSQL = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT t.id, t.estado FROM tareas t
                INNER JOIN gerencias g ON t.gerencia_id = g.id
                INNER JOIN areas a ON t.area_id = a.id
                $whereSQL
                ORDER BY t.id DESC LIMIT $limite OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
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

    $estado_inicial = "En desarrollo";

    try {
        if ($has_nivel_inicial && $has_nivel_final) {
            $sql = "INSERT INTO tareas (gerencia_id, area_id, ubicacion, nivel_riesgo_inicial, nivel_riesgo_final, nivel_riesgo, fecha, permisos_requeridos, actividad";
            $params = [
                ':gerencia_id' => $_POST['gerencia_id'],
                ':area_id' => $_POST['area_id'],
                ':ubicacion' => $_POST['ubicacion'],
                ':nivel_riesgo_inicial' => $nivel_riesgo_inicial,
                ':nivel_riesgo_final' => $nivel_riesgo_final,
                ':nivel_riesgo' => '',
                ':fecha' => $_POST['fecha'],
                ':permisos_requeridos' => $permisos_requeridos_json,
                ':actividad' => $_POST['actividad']
            ];

            if ($has_estado) {
                $sql .= ", estado) VALUES (:gerencia_id, :area_id, :ubicacion, :nivel_riesgo_inicial, :nivel_riesgo_final, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad, :estado)";
                $params[':estado'] = $estado_inicial;
            } else {
                $sql .= ") VALUES (:gerencia_id, :area_id, :ubicacion, :nivel_riesgo_inicial, :nivel_riesgo_final, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad)";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $nivel_riesgo = $nivel_riesgo_inicial . ' / ' . $nivel_riesgo_final;
            $sql = "INSERT INTO tareas (gerencia_id, area_id, ubicacion, nivel_riesgo, fecha, permisos_requeridos, actividad";
            $params = [
                ':gerencia_id'  => $_POST['gerencia_id'],
                ':area_id'      => $_POST['area_id'],
                ':ubicacion'    => $_POST['ubicacion'],
                ':nivel_riesgo' => $nivel_riesgo,
                ':fecha'        => $_POST['fecha'],
                ':permisos_requeridos' => $permisos_requeridos_json,
                ':actividad'    => $_POST['actividad']
            ];

            if ($has_estado) {
                $sql .= ", estado) VALUES (:gerencia_id, :area_id, :ubicacion, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad, :estado)";
                $params[':estado'] = $estado_inicial;
            } else {
                $sql .= ") VALUES (:gerencia_id, :area_id, :ubicacion, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad)";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        echo json_encode(["status" => "success", "message" => "Registro guardado correctamente."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Error al guardar: " . $e->getMessage()]);
    }
    exit;
}